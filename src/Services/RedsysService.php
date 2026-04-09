<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Firma y validación alineadas con la librería sermepa (HMAC-SHA256 + 3DES-CBC).
 *
 * @see redsys-php-flujo.md
 */
final class RedsysService
{
    private string $secretKeyB64;
    private string $merchantCode;
    private string $terminal;
    private bool $sandbox;

    /** @param array<string, mixed> $config */
    public function __construct(private array $config)
    {
        $this->secretKeyB64 = (string) ($config['secret_key'] ?? '');
        $this->merchantCode = (string) ($config['merchant_code'] ?? '');
        $this->terminal     = (string) ($config['terminal'] ?? '');
        $this->sandbox      = (bool) ($config['sandbox'] ?? true);
    }

    /**
     * @param array{
     *   amount_cents: int,
     *   order_id: string,
     *   lang: string,
     *   notify_url: string,
     *   ok_url: string,
     *   ko_url: string,
     *   product_description?: string
     * } $order
     * @return array{Ds_SignatureVersion: string, Ds_MerchantParameters: string, Ds_Signature: string}
     */
    public function buildPaymentParams(array $order): array
    {
        if ($this->secretKeyB64 === '') {
            throw new \RuntimeException('Redsys: falta secret_key en configuración.');
        }

        $amount   = (string) (int) $order['amount_cents'];
        $orderId  = (string) $order['order_id'];
        $langCode = (($order['lang'] ?? 'es') === 'en') ? '002' : '001';

        $params = [
            'DS_MERCHANT_AMOUNT'             => $amount,
            'DS_MERCHANT_ORDER'              => $orderId,
            'DS_MERCHANT_MERCHANTCODE'       => $this->merchantCode,
            'DS_MERCHANT_CURRENCY'           => '978',
            'DS_MERCHANT_TRANSACTIONTYPE'    => '0',
            'DS_MERCHANT_TERMINAL'           => $this->terminal,
            'DS_MERCHANT_MERCHANTURL'        => (string) $order['notify_url'],
            'DS_MERCHANT_URLOK'              => (string) $order['ok_url'],
            'DS_MERCHANT_URLKO'              => (string) $order['ko_url'],
            'DS_MERCHANT_CONSUMERLANGUAGE'   => $langCode,
        ];

        if (!empty($order['product_description'])) {
            $params['DS_MERCHANT_PRODUCTDESCRIPTION'] = substr((string) $order['product_description'], 0, 120);
        }

        // TODO(Redsys-debug): quitar tras diagnosticar SIS0432 / parámetros vacíos
        error_log('[Redsys] buildPaymentParams $params (antes de firmar): ' . print_r($params, true));

        $merchantParameters = base64_encode(json_encode($params, JSON_UNESCAPED_SLASHES));
        $signature          = $this->signMerchantParameters($merchantParameters, $orderId);

        return [
            'Ds_SignatureVersion'   => 'HMAC_SHA256_V1',
            'Ds_MerchantParameters' => $merchantParameters,
            'Ds_Signature'          => $signature,
        ];
    }

    public function getEndpointUrl(): string
    {
        return $this->sandbox
            ? 'https://sis-t.redsys.es:25443/sis/realizarPago'
            : 'https://sis.redsys.es/sis/realizarPago';
    }

    /**
     * Valida la notificación S2S. $merchantParamsBase64 es el valor crudo de Ds_MerchantParameters (POST).
     *
     * @return array<string, mixed>
     */
    public function validateNotification(
        string $signatureVersion,
        string $merchantParamsBase64,
        string $signatureReceived,
    ): array {
        if ($signatureVersion !== '' && $signatureVersion !== 'HMAC_SHA256_V1') {
            throw new \RuntimeException('Versión de firma no soportada.');
        }

        if ($this->secretKeyB64 === '' || $merchantParamsBase64 === '' || $signatureReceived === '') {
            throw new \RuntimeException('Parámetros de notificación incompletos.');
        }

        // application/x-www-form-urlencoded convierte '+' en espacio; restaurar para Base64 y HMAC.
        $merchantParamsBase64 = str_replace(' ', '+', $merchantParamsBase64);

        $decoded = base64_decode(strtr($merchantParamsBase64, '-_', '+/'), true);
        if ($decoded === false) {
            throw new \RuntimeException('Ds_MerchantParameters inválido.');
        }

        /** @var array<string, mixed>|null $params */
        $params = json_decode($decoded, true);
        if (!is_array($params)) {
            throw new \RuntimeException('JSON de notificación inválido.');
        }

        $orderId = $this->extractOrderFromNotification($params);
        if ($orderId === '') {
            throw new \RuntimeException('Pedido no encontrado en notificación.');
        }

        $keyRaw = base64_decode($this->secretKeyB64, true);
        if ($keyRaw === false) {
            throw new \RuntimeException('Clave Redsys (base64) inválida.');
        }
        $diversified = $this->encrypt3des($orderId, $keyRaw);
        if ($diversified === false) {
            throw new \RuntimeException('Error al derivar clave Redsys (3DES).');
        }
        $hmac        = hash_hmac('sha256', $merchantParamsBase64, $diversified, true);
        $expectedUrl = strtr(base64_encode($hmac), '+/', '-_');
        $expectedStd = base64_encode($hmac);
        $normalized  = strtr(trim($signatureReceived), ' ', '+');

        if (!hash_equals($expectedUrl, $normalized) && !hash_equals($expectedStd, $normalized)) {
            throw new \RuntimeException('Firma Redsys inválida.');
        }

        return $params;
    }

    private function signMerchantParameters(string $merchantParametersBase64, string $orderId): string
    {
        $keyRaw = base64_decode($this->secretKeyB64, true);
        if ($keyRaw === false) {
            throw new \RuntimeException('Clave Redsys (base64) inválida.');
        }

        $diversified = $this->encrypt3des($orderId, $keyRaw);
        if ($diversified === false) {
            throw new \RuntimeException('Error al derivar clave Redsys (3DES).');
        }
        $hmac        = hash_hmac('sha256', $merchantParametersBase64, $diversified, true);

        return base64_encode($hmac);
    }

    private function encrypt3des(string $data, string $key): string|false
    {
        $iv          = "\0\0\0\0\0\0\0\0";
        $dataPadded  = $data;
        $len         = strlen($dataPadded);
        if ($len % 8 !== 0) {
            $dataPadded = str_pad($dataPadded, $len + (8 - $len % 8), "\0");
        }

        return openssl_encrypt($dataPadded, 'DES-EDE3-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
    }

    /** @param array<string, mixed> $parameters */
    private function extractOrderFromNotification(array $parameters): string
    {
        foreach ($parameters as $k => $v) {
            if (strtolower((string) $k) === 'ds_order') {
                return (string) $v;
            }
        }

        return '';
    }

    /** @return array<string, mixed> */
    public static function loadConfig(): array
    {
        $root    = dirname(__DIR__, 2);
        $primary = $root . '/config/redsys.php';
        $fallback = $root . '/config/redsys.default.php';

        $cfg = is_readable($primary) ? require $primary : require $fallback;
        if (!is_array($cfg)) {
            $cfg = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require $fallback;
        $merged   = array_replace($defaults, $cfg);

        if (($merged['secret_key'] ?? '') === '' && !empty($merged['sandbox'])) {
            $merged['secret_key'] = $defaults['secret_key'];
        }

        // Evitar SIS0432: redsys.php o env mal puestos no deben dejar comercio/terminal vacíos
        foreach (['merchant_code', 'terminal', 'secret_key'] as $key) {
            if (!isset($merged[$key]) || (is_string($merged[$key]) && trim($merged[$key]) === '')) {
                $merged[$key] = $defaults[$key] ?? $merged[$key];
            }
        }

        return $merged;
    }
}
