<?php

declare(strict_types=1);

namespace App\Services;

final class PacklinkService
{
    private const ORIGIN_COUNTRY = 'ES';
    private const ORIGIN_ZIP     = '08773';

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

            $carrier = $this->pickString($item, ['carrier_name', 'carrier', 'carrierName', 'carrier_name_text']);
            if ($carrier === '' && isset($item['carrier']) && is_array($item['carrier'])) {
                $carrier = $this->pickString($item['carrier'], ['name', 'title']);
            }

            $serviceName = $this->pickString($item, ['service_name', 'name', 'service', 'label', 'title', 'description']);
            $days        = $this->pickIntNullable($item, ['transit_time', 'transitTime', 'delivery_time', 'deliveryTime', 'days']);

            $price = $this->pickNumberNullable($item, ['price', 'total_price', 'totalPrice', 'amount']);
            if ($price === null && isset($item['price']) && is_array($item['price'])) {
                $price = $this->pickNumberNullable($item['price'], ['amount', 'value']);
            }

            if ($carrier === '' && $serviceName === '') {
                continue;
            }
            if ($price === null) {
                continue;
            }

            $priceCents = (int) round(((float) $price) * 100);
            if ($priceCents < 0) {
                continue;
            }

            $id = $this->pickString($item, ['id', 'service_id', 'serviceId', 'carrier_service_id', 'code']);
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

        return array_values($out);
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
}

