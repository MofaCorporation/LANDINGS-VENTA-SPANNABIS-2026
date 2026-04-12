<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Actualiza atributos de contacto en Brevo (API REST v3).
 *
 * @see https://developers.brevo.com/reference/updatecontact
 */
final class BrevoContactService
{
    private const CONTACT_PUT_BASE = 'https://api.brevo.com/v3/contacts/';

    /** Mapeo slug de variedad (landing) → valor EGG_TYPE en Brevo */
    private const VARIETY_SLUG_TO_EGG_TYPE = [
        'lady-cupcake' => 'glazed',
        'holy-boss'    => 'holy',
        'nitro-bud'    => 'nitro',
        'dj-piggy'     => 'party',
        'toxic-mutant' => 'radioactive',
    ];

    /** @param array<string, mixed> $config Mail/Brevo (api-key) */
    public function __construct(private array $config)
    {
    }

    public static function eggTypeFromVarietySlug(string $slug): ?string
    {
        return self::VARIETY_SLUG_TO_EGG_TYPE[$slug] ?? null;
    }

    /**
     * Prioriza la primera línea del carrito; si no hay, usa slug del producto del pedido.
     *
     * @param array<string, mixed> $order Estructura como Order::getForEmail()
     */
    public static function resolveVarietySlugFromOrder(array $order): ?string
    {
        $ship = $order['shipping'] ?? [];
        if (is_array($ship) && isset($ship['cart_lines']) && is_array($ship['cart_lines'])) {
            foreach ($ship['cart_lines'] as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $v = isset($line['variety']) ? trim((string) $line['variety']) : '';
                if ($v !== '') {
                    return $v;
                }
            }
        }

        $slug = isset($order['product']['slug_es']) ? trim((string) $order['product']['slug_es']) : '';

        return $slug !== '' ? $slug : null;
    }

    public function updateEggState(string $email, string $eggType): void
    {
        $email = trim($email);
        $eggType = trim($eggType);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Brevo contact: email inválido.');
        }
        if ($eggType === '') {
            throw new \InvalidArgumentException('Brevo contact: EGG_TYPE vacío.');
        }

        $apiKey = trim((string) ($this->config['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('Brevo contact: falta api_key (misma que Mail/Brevo).');
        }

        $url = self::CONTACT_PUT_BASE . rawurlencode($email);
        $payload = json_encode(
            [
                'attributes' => [
                    'EGG_TYPE' => $eggType,
                ],
            ],
            JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE,
        );

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Brevo contact: curl_init falló.');
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'api-key: ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body     = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('Brevo contact: error cURL: ' . ($curlErr !== '' ? $curlErr : 'desconocido'));
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $snippet = mb_substr((string) $body, 0, 500);
            throw new \RuntimeException('Brevo contact: HTTP ' . (string) $httpCode . ' — ' . $snippet);
        }
    }

    /** Sincroniza EGG_TYPE si el slug de variedad está mapeado (errores solo en log). */
    public static function syncEggTypeForOrder(array $order): void
    {
        $slug = self::resolveVarietySlugFromOrder($order);
        if ($slug === null) {
            return;
        }

        $egg = self::eggTypeFromVarietySlug($slug);
        if ($egg === null) {
            return;
        }

        $email = trim((string) ($order['customer_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $svc = new self(MailService::loadConfig());
            $svc->updateEggState($email, $egg);
        } catch (\Throwable $e) {
            error_log('BrevoContactService::syncEggTypeForOrder: ' . $e->getMessage());
        }
    }
}
