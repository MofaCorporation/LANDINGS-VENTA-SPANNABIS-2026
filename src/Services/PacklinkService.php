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
     * @return list<array{carrier: string, service_name: string, price_cents: int, days: int|null, id: string}>
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

        $out = [];
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

            $out[] = [
                'carrier'      => $carrier !== '' ? $carrier : '—',
                'service_name' => $serviceName !== '' ? $serviceName : '—',
                'price_cents'  => $priceCents,
                'days'         => $days,
                'id'           => $id,
            ];
        }

        usort(
            $out,
            static fn (array $a, array $b): int => ($a['price_cents'] <=> $b['price_cents']) ?: strcmp($a['carrier'] . $a['service_name'], $b['carrier'] . $b['service_name']),
        );

        $out = array_values($out);
        error_log('[Packlink] Mapped options: ' . json_encode($out, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));

        return $out;
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

        try {
            $raw = $this->httpPostJson($url, $payload);
        } catch (\Throwable $e) {
            error_log('[Packlink] createShipment: ' . $e->getMessage());

            return $fail($e->getMessage());
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
        $candidates = [
            $raw['tracking_number'] ?? null,
            $raw['trackingNumber'] ?? null,
            $raw['carrier_tracking_number'] ?? null,
            $raw['carrier_tracking'] ?? null,
            $raw['tracking'] ?? null,
        ];
        if (isset($raw['shipment']) && is_array($raw['shipment'])) {
            $s = $raw['shipment'];
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
     * @param array<string, mixed> $payload
     * @return mixed
     */
    private function httpPostJson(string $url, array $payload)
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('Packlink: curl_init falló.');
        }

        $body = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE,
        );

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
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

        error_log('[Packlink] createShipment HTTP ' . (string) $httpCode . ' body: ' . (string) ($resp === false ? '' : mb_substr((string) $resp, 0, 2000)));

        if ($resp === false) {
            throw new \RuntimeException('Packlink: error HTTP: ' . ($err !== '' ? $err : 'desconocido'));
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Packlink: HTTP ' . (string) $httpCode . ' — ' . mb_substr((string) $resp, 0, 500));
        }

        $decoded = json_decode((string) $resp, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Packlink: JSON inválido en respuesta de envío.');
        }

        return $decoded;
    }
}

