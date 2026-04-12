<?php

declare(strict_types=1);

namespace App\Services;

final class PacklinkService
{
    private const ORIGIN_COUNTRY = 'ES';
    private const ORIGIN_ZIP     = '08773';

    private const ORIGIN_CITY    = 'Barcelona';
    private const ORIGIN_STREET  = 'Calle Tennis 1';
    private const ORIGIN_COMPANY = 'Tarumba\'s Farm';

    // Sobre acolchado estándar (cm) + 150g
    private const PKG_WEIGHT_KG = 0.15;
    private const PKG_W_CM      = 15;
    private const PKG_H_CM      = 5;
    private const PKG_L_CM      = 10;

    private string $apiKey;
    private bool $sandbox;

    /** @param array{api_key?: string, sandbox?: bool} $config */
    public function __construct(private array $config)
    {
        $this->apiKey  = (string) ($config['api_key'] ?? '');
        $this->sandbox = (bool) ($config['sandbox'] ?? false);
    }

    /**
     * @return list<array{carrier: string, service_name: string, price_cents: int, days: int|null, id: string, badge_key: string}>
     */
    public function getShippingRates(string $countryCode, int $postalCode): array
    {
        $countryCode = strtoupper(substr(trim($countryCode), 0, 2));
        $postalCode  = (int) $postalCode;

        if ($countryCode === '' || $postalCode <= 0) {
            return [];
        }

        if ($this->apiKey === '') {
            throw new \RuntimeException('Packlink: falta api_key en configuración.');
        }

        $fromCountry = self::ORIGIN_COUNTRY;
        $fromZip     = self::ORIGIN_ZIP;
        $toCountry   = $countryCode;
        $toZip       = (string) $postalCode;

        if ($fromCountry === 'ES') {
            $fromZip = str_pad($fromZip, 5, '0', STR_PAD_LEFT);
        }
        if ($toCountry === 'ES') {
            $toZip = str_pad($toZip, 5, '0', STR_PAD_LEFT);
        }

        // Packlink Pro requiere el formato con corchetes sin URL-encoding en las keys.
        // Escapamos valores, no las keys (para evitar inyección en query string).
        $params = [
            'from[country]' => $fromCountry,
            'from[zip]' => $fromZip,
            'to[country]' => $toCountry,
            'to[zip]' => $toZip,
            'packages[0][weight]' => (string) self::PKG_WEIGHT_KG,
            'packages[0][width]' => (string) self::PKG_W_CM,
            'packages[0][height]' => (string) self::PKG_H_CM,
            'packages[0][length]' => (string) self::PKG_L_CM,
        ];

        $base = 'https://api.packlink.com';
        // Nota: Packlink Pro no documenta públicamente un endpoint alternativo; mantenemos base única.
        // Si sandbox requiere otra base en tu cuenta, se puede extender vía config sin romper la API.
        if ($this->sandbox) {
            $base = 'https://api.packlink.com';
        }

        $query =
            'from[country]=' . rawurlencode($fromCountry)
            . '&from[zip]=' . rawurlencode($fromZip)
            . '&to[country]=' . rawurlencode($toCountry)
            . '&to[zip]=' . rawurlencode($toZip)
            . '&packages[0][weight]=' . rawurlencode((string) self::PKG_WEIGHT_KG)
            . '&packages[0][width]=' . rawurlencode((string) self::PKG_W_CM)
            . '&packages[0][height]=' . rawurlencode((string) self::PKG_H_CM)
            . '&packages[0][length]=' . rawurlencode((string) self::PKG_L_CM);

        $url = $base . '/v1/services?' . $query;

        error_log('[Packlink] URL: ' . $url);
        error_log('[Packlink] Params: ' . $query);

        $apiKey = $this->apiKey;
        $headers = [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        error_log('[Packlink] Authorization header: Authorization: ' . substr($apiKey, 0, 8) . '...');

        $raw = $this->httpGetJson($url, [
            ...$headers,
        ]);

        $items = $this->coerceServicesList($raw);

        /** @var list<array{id: string, carrier: string, service_name: string, price_cents: int, days: int|null, category: string, parcelshop: bool}> $internal */
        $internal = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            // Formato Packlink Pro v1 (lista de servicios)
            $carrier = $this->pickString($item, ['carrier_name']);
            if ($carrier === '') {
                $carrier = $this->pickString($item, ['carrier', 'carrierName', 'carrier_name_text']);
            }
            if ($carrier === '' && isset($item['carrier']) && is_array($item['carrier'])) {
                $carrier = $this->pickString($item['carrier'], ['name', 'title']);
            }

            $serviceName = $this->pickString($item, ['name']);
            if ($serviceName === '') {
                $serviceName = $this->pickString($item, ['service_name', 'service', 'label', 'title', 'description']);
            }

            $basePrice = $this->pickNumberNullable($item, ['base_price', 'price', 'total_price', 'totalPrice', 'amount']);
            if ($basePrice === null && isset($item['price']) && is_array($item['price'])) {
                $basePrice = $this->pickNumberNullable($item['price'], ['amount', 'value', 'base_price']);
            }

            $days = $this->parseTransitDays($item['transit_time'] ?? null);

            if ($carrier === '' && $serviceName === '') {
                continue;
            }
            if ($basePrice === null) {
                continue;
            }

            $priceCents = (int) round((float) $basePrice * 100);
            if ($priceCents < 0) {
                continue;
            }

            $id = $this->pickServiceId($item);
            if ($id === '') {
                $id = substr(hash('sha256', $carrier . '|' . $serviceName . '|' . (string) $priceCents . '|' . (string) ($days ?? '')), 0, 16);
            }

            $categoryRaw = $this->pickString($item, ['category', 'service_category']);
            if ($categoryRaw === '' && isset($item['service']) && is_array($item['service'])) {
                $categoryRaw = $this->pickString($item['service'], ['category', 'service_category']);
            }
            $category = strtolower(trim($categoryRaw));

            $parcelshop = $this->pickBool($item, [
                'delivery_to_parcelshop',
                'deliveryToParcelshop',
                'parcel_shop',
                'ship_to_parcel_shop',
                'to_parcelshop',
            ], false);
            if (!$parcelshop && isset($item['destination']) && is_array($item['destination'])) {
                $parcelshop = $this->pickBool($item['destination'], ['parcel_shop', 'parcelShop', 'parcelshop'], false);
            }

            $internal[] = [
                'id'           => $id,
                'carrier'      => $carrier !== '' ? $carrier : '—',
                'service_name' => $serviceName !== '' ? $serviceName : '—',
                'price_cents'  => $priceCents,
                'days'         => $days,
                'category'     => $category,
                'parcelshop'   => $parcelshop,
            ];
        }

        usort(
            $internal,
            static fn (array $a, array $b): int => ($a['price_cents'] <=> $b['price_cents']) ?: strcmp($a['carrier'] . $a['service_name'], $b['carrier'] . $b['service_name']),
        );

        $internal = array_values($internal);
        error_log('[Packlink] Mapped options (full): ' . json_encode($internal, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));

        $curated = $this->pickCuratedDisplayRates($internal);
        error_log('[Packlink] Curated options: ' . json_encode($curated, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));

        return $curated;
    }

    /**
     * Hasta 4 opciones para el checkout: mejor precio, estándar, punto de recogida, express (más rápido).
     * Sin repetir el mismo `id` entre categorías. Orden fijo para el UI.
     *
     * @param list<array{id: string, carrier: string, service_name: string, price_cents: int, days: int|null, category: string, parcelshop: bool}> $rows
     *
     * @return list<array{carrier: string, service_name: string, price_cents: int, days: int|null, id: string, badge_key: string}>
     */
    private function pickCuratedDisplayRates(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $sortPrice = static function (array $a, array $b): int {
            $c = $a['price_cents'] <=> $b['price_cents'];
            if ($c !== 0) {
                return $c;
            }

            return strcmp($a['carrier'] . $a['service_name'], $b['carrier'] . $b['service_name']);
        };

        $byPrice = $rows;
        usort($byPrice, $sortPrice);

        $takeFirstNotChosen = static function (array $pool, array $chosen): ?array {
            foreach ($pool as $r) {
                if (!isset($chosen[$r['id']])) {
                    return $r;
                }
            }

            return null;
        };

        $chosen = [];
        $out    = [];

        $r = $takeFirstNotChosen($byPrice, $chosen);
        if ($r === null) {
            return [];
        }
        $out[] = $this->publicRateRow($r, 'best_price');
        $chosen[$r['id']] = true;

        $hasStandard = false;
        foreach ($rows as $row) {
            if (($row['category'] ?? '') === 'standard') {
                $hasStandard = true;
                break;
            }
        }

        if ($hasStandard) {
            $pool = array_values(array_filter($rows, static fn (array $x): bool => ($x['category'] ?? '') === 'standard'));
            usort($pool, $sortPrice);
        } else {
            $pool = $byPrice;
        }
        $r = $takeFirstNotChosen($pool, $chosen);
        if ($r !== null) {
            $out[] = $this->publicRateRow($r, 'standard');
            $chosen[$r['id']] = true;
        }

        $pool = array_values(array_filter($rows, static fn (array $x): bool => !empty($x['parcelshop'])));
        usort($pool, $sortPrice);
        $r = $takeFirstNotChosen($pool, $chosen);
        if ($r !== null) {
            $out[] = $this->publicRateRow($r, 'pickup_point');
            $chosen[$r['id']] = true;
        }

        $pool = array_values(array_filter($rows, static fn (array $x): bool => !isset($chosen[$x['id']])));
        usort(
            $pool,
            static function (array $a, array $b) use ($sortPrice): int {
                $da = $a['days'] ?? PHP_INT_MAX;
                $db = $b['days'] ?? PHP_INT_MAX;
                $c  = $da <=> $db;
                if ($c !== 0) {
                    return $c;
                }

                return $sortPrice($a, $b);
            },
        );
        $r = $takeFirstNotChosen($pool, $chosen);
        if ($r !== null) {
            $out[] = $this->publicRateRow($r, 'express');
            $chosen[$r['id']] = true;
        }

        return $out;
    }

    /**
     * @param array{id: string, carrier: string, service_name: string, price_cents: int, days: int|null, category: string, parcelshop: bool} $row
     *
     * @return array{carrier: string, service_name: string, price_cents: int, days: int|null, id: string, badge_key: string}
     */
    private function publicRateRow(array $row, string $badgeKey): array
    {
        return [
            'carrier'      => $row['carrier'],
            'service_name' => $row['service_name'],
            'price_cents'  => $row['price_cents'],
            'days'         => $row['days'],
            'id'           => $row['id'],
            'badge_key'    => $badgeKey,
        ];
    }

    /** @return mixed */
    private function httpGetJson(string $url, array $headers)
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('Packlink: curl_init falló.');
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log('[Packlink] Response code: ' . (string) $httpCode);
        error_log('[Packlink] Response body: ' . (string) ($body === false ? '' : $body));

        if ($body === false) {
            throw new \RuntimeException('Packlink: error HTTP: ' . ($err !== '' ? $err : 'desconocido'));
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Packlink: HTTP ' . (string) $httpCode);
        }

        $decoded = json_decode((string) $body, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Packlink: JSON inválido.');
        }

        return $decoded;
    }

    /** @return list<mixed> */
    private function coerceServicesList($raw): array
    {
        if (is_array($raw)) {
            // Si viene como {services: [...]}
            if (isset($raw['services']) && is_array($raw['services'])) {
                return array_values($raw['services']);
            }
            // Si viene como lista directa [...]
            if (array_is_list($raw)) {
                return $raw;
            }
        }

        return [];
    }

    private function pickBool(array $a, array $keys, bool $default = false): bool
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $a)) {
                continue;
            }
            $v = $a[$k];
            if (is_bool($v)) {
                return $v;
            }
            if (is_int($v)) {
                return $v !== 0;
            }
            if (is_string($v)) {
                $t = strtolower(trim($v));
                if ($t === '1' || $t === 'true' || $t === 'yes') {
                    return true;
                }
                if ($t === '0' || $t === 'false' || $t === 'no') {
                    return false;
                }
            }
        }

        return $default;
    }

    private function pickString(array $a, array $keys): string
    {
        foreach ($keys as $k) {
            if (isset($a[$k]) && is_string($a[$k])) {
                $v = trim($a[$k]);
                if ($v !== '') {
                    return $v;
                }
            }
        }
        return '';
    }

    private function pickIntNullable(array $a, array $keys): ?int
    {
        foreach ($keys as $k) {
            if (!isset($a[$k])) {
                continue;
            }
            $v = $a[$k];
            if (is_int($v)) {
                return $v;
            }
            if (is_string($v) && preg_match('/^\d+$/', $v)) {
                return (int) $v;
            }
        }
        return null;
    }

    private function pickNumberNullable(array $a, array $keys): ?float
    {
        foreach ($keys as $k) {
            if (!isset($a[$k])) {
                continue;
            }
            $v = $a[$k];
            if (is_int($v) || is_float($v)) {
                return (float) $v;
            }
            if (is_string($v)) {
                $s = trim($v);
                if ($s === '') {
                    continue;
                }
                // Normalizar coma decimal
                $s = str_replace(',', '.', $s);
                if (is_numeric($s)) {
                    return (float) $s;
                }
            }
        }
        return null;
    }

    private function parseTransitDays(mixed $transitTime): ?int
    {
        if (is_int($transitTime)) {
            return $transitTime;
        }
        if (!is_string($transitTime)) {
            return null;
        }
        $s = trim($transitTime);
        if ($s === '') {
            return null;
        }
        if (preg_match('/(\d+)/', $s, $m) === 1) {
            return (int) $m[1];
        }

        return null;
    }

    /** @return string id como string (API puede devolver int) */
    private function pickServiceId(array $item): string
    {
        foreach (['id', 'service_id', 'serviceId', 'carrier_service_id', 'code'] as $k) {
            if (!isset($item[$k])) {
                continue;
            }
            $v = $item[$k];
            if (is_int($v) || is_float($v)) {
                return (string) (int) $v;
            }
            if (is_string($v)) {
                $t = trim($v);
                if ($t !== '') {
                    return $t;
                }
            }
        }

        return '';
    }

    /** @return array<string, mixed> */
    public static function loadConfig(): array
    {
        $root     = dirname(__DIR__, 2);
        $primary  = $root . '/config/packlink.php';
        $fallback = $root . '/config/packlink.default.php';

        $cfg = is_readable($primary) ? require $primary : require $fallback;
        if (!is_array($cfg)) {
            $cfg = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require $fallback;
        $merged   = array_replace($defaults, $cfg);

        return $merged;
    }

    /**
     * Crea un envío en Packlink Pro (POST /v1/shipments).
     *
     * @param array<string, mixed> $order Estructura como Order::getForEmail()
     * @return array{ok: bool, tracking_number: ?string, label_url: ?string, error: ?string}
     */
    public function createShipment(array $order): array
    {
        $fail = static fn (string $msg): array => ['ok' => false, 'tracking_number' => null, 'label_url' => null, 'error' => $msg];

        if ($this->apiKey === '') {
            return $fail('Falta api_key en configuración Packlink.');
        }

        $ship = $order['shipping'] ?? [];
        if (!is_array($ship)) {
            return $fail('Datos de envío inválidos.');
        }

        $mode = isset($ship['shipping']) ? (string) $ship['shipping'] : '';
        if ($mode === 'pickup') {
            return $fail('Recogida en tienda: no aplica envío Packlink.');
        }

        $country = strtoupper(substr(trim((string) ($ship['country'] ?? '')), 0, 2));
        $postal  = preg_replace('/\D/', '', (string) ($ship['postal'] ?? '')) ?? '';
        if ($country === '' || $postal === '') {
            return $fail('Falta país o código postal del destino.');
        }

        $serviceId = trim((string) ($ship['packlink_service_id'] ?? ''));
        $serviceId = $this->resolveServiceIdForShipment($serviceId, $country, (int) $postal);
        if ($serviceId === '') {
            return $fail('No hay service_id Packlink válido para este pedido.');
        }

        $toZip = $country === 'ES' ? str_pad($postal, 5, '0', STR_PAD_LEFT) : $postal;

        $payload = [
            'from' => [
                'country' => self::ORIGIN_COUNTRY,
                'zip'     => str_pad(self::ORIGIN_ZIP, 5, '0', STR_PAD_LEFT),
                'city'    => self::ORIGIN_CITY,
                'street'  => self::ORIGIN_STREET,
                'company' => self::ORIGIN_COMPANY,
            ],
            'to' => [
                'country' => $country,
                'zip'     => $toZip,
                'city'    => trim((string) ($ship['city'] ?? '')),
                'street'  => trim((string) ($ship['address_line'] ?? '')),
                'name'    => trim((string) ($order['customer_name'] ?? '')) !== '' ? trim((string) $order['customer_name']) : 'Cliente',
                'email'   => trim((string) ($order['customer_email'] ?? '')),
                'phone'   => trim((string) ($ship['phone'] ?? '')),
            ],
            'packages' => [
                [
                    'weight' => self::PKG_WEIGHT_KG,
                    'width'  => self::PKG_W_CM,
                    'height' => self::PKG_H_CM,
                    'length' => self::PKG_L_CM,
                ],
            ],
            'service_id'                => is_numeric($serviceId) ? (int) $serviceId : $serviceId,
            'content'                   => 'Pedido ' . (string) ($order['order_ref'] ?? ''),
            'shipment_custom_reference' => (string) ($order['order_ref'] ?? ''),
        ];

        $base = 'https://api.packlink.com';
        $url  = $base . '/v1/shipments';

        $body = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE,
        );

        error_log('[Packlink createShipment] URL: ' . $url);
        error_log('[Packlink createShipment] Body: ' . $body);

        try {
            [$httpCode, $response] = $this->httpPostShipmentRaw($url, $body);
        } catch (\Throwable $e) {
            error_log('[Packlink createShipment] Request error: ' . $e->getMessage());

            return $fail($e->getMessage());
        }

        error_log('[Packlink createShipment] Response code: ' . (string) $httpCode);
        error_log('[Packlink createShipment] Response body: ' . $response);

        if ($httpCode < 200 || $httpCode >= 300) {
            return $fail('Packlink: HTTP ' . (string) $httpCode . ' — ' . mb_substr($response, 0, 500));
        }

        $raw = json_decode($response, true);
        if ($raw === null && json_last_error() !== JSON_ERROR_NONE) {
            return $fail('Packlink: JSON inválido en respuesta de envío.');
        }

        if (!is_array($raw)) {
            return $fail('Respuesta Packlink no reconocida.');
        }

        $tracking = $this->pickTrackingNumber($raw);
        $labelUrl = $this->pickLabelUrl($raw);

        if ($tracking === '' && $labelUrl === '') {
            $err = $this->pickApiError($raw);

            return $fail($err !== '' ? $err : 'Packlink no devolvió tracking ni etiqueta.');
        }

        return [
            'ok'              => true,
            'tracking_number' => $tracking !== '' ? $tracking : null,
            'label_url'       => $labelUrl !== '' ? $labelUrl : null,
            'error'           => null,
        ];
    }

    private function resolveServiceIdForShipment(string $stored, string $country, int $postal): string
    {
        $stored = trim($stored);
        if ($stored !== '' && ctype_digit($stored)) {
            return $stored;
        }

        try {
            $rates = $this->getShippingRates($country, $postal);
        } catch (\Throwable) {
            return '';
        }
        foreach ($rates as $r) {
            if (!is_array($r)) {
                continue;
            }
            $id = isset($r['id']) ? trim((string) $r['id']) : '';
            if ($id !== '' && ctype_digit($id)) {
                return $id;
            }
        }

        return '';
    }

    /** @param array<string, mixed> $raw */
    private function pickTrackingNumber(array $raw): string
    {
        // Packlink Pro suele devolver el identificador de envío en `reference` (p. ej. 201 {"reference":"ES2026PRO..."}).
        $candidates = [
            $raw['reference'] ?? null,
            $raw['tracking_number'] ?? null,
            $raw['trackingNumber'] ?? null,
            $raw['carrier_tracking_number'] ?? null,
            $raw['carrier_tracking'] ?? null,
            $raw['tracking'] ?? null,
        ];
        if (isset($raw['shipment']) && is_array($raw['shipment'])) {
            $s = $raw['shipment'];
            $candidates[] = $s['reference'] ?? null;
            $candidates[] = $s['tracking_number'] ?? null;
            $candidates[] = $s['tracking'] ?? null;
        }

        foreach ($candidates as $c) {
            if (is_string($c) && trim($c) !== '') {
                return trim($c);
            }
            if (is_int($c) || is_float($c)) {
                return (string) $c;
            }
        }

        return '';
    }

    /** @param array<string, mixed> $raw */
    private function pickLabelUrl(array $raw): string
    {
        $candidates = [
            $raw['label_url'] ?? null,
            $raw['labelUrl'] ?? null,
            $raw['label_pdf'] ?? null,
            $raw['pdf_url'] ?? null,
        ];
        if (isset($raw['label']) && is_array($raw['label'])) {
            $candidates[] = $raw['label']['url'] ?? null;
            $candidates[] = $raw['label']['pdf'] ?? null;
        }

        foreach ($candidates as $c) {
            if (is_string($c) && filter_var($c, FILTER_VALIDATE_URL)) {
                return $c;
            }
        }

        return '';
    }

    /** @param array<string, mixed> $raw */
    private function pickApiError(array $raw): string
    {
        $m = $raw['message'] ?? $raw['error'] ?? $raw['detail'] ?? null;
        if (is_string($m) && trim($m) !== '') {
            return trim($m);
        }
        if (isset($raw['errors']) && is_array($raw['errors'])) {
            $parts = [];
            foreach ($raw['errors'] as $e) {
                if (is_string($e)) {
                    $parts[] = $e;
                } elseif (is_array($e) && isset($e['message']) && is_string($e['message'])) {
                    $parts[] = $e['message'];
                }
            }
            if ($parts !== []) {
                return implode('; ', $parts);
            }
        }

        return '';
    }

    /**
     * POST JSON ya serializado; devuelve [httpCode, bodyString].
     *
     * @return array{0: int, 1: string}
     */
    private function httpPostShipmentRaw(string $url, string $jsonBody): array
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('Packlink: curl_init falló.');
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false) {
            throw new \RuntimeException('Packlink: error cURL: ' . ($err !== '' ? $err : 'desconocido'));
        }

        return [$httpCode, (string) $resp];
    }
}

