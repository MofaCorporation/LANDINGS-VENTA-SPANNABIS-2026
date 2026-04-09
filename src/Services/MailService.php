<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Envío vía API HTTP de Brevo (sin Composer ni SMTP manual).
 *
 * @see https://developers.brevo.com/reference/sendtransacemail
 */
final class MailService
{
    private const BREVO_SEND_URL = 'https://api.brevo.com/v3/smtp/email';

    /** @param array<string, mixed> $config */
    public function __construct(private array $config)
    {
    }

    /** @return array<string, mixed> */
    public static function loadConfig(): array
    {
        $root     = dirname(__DIR__, 2);
        $primary  = $root . '/config/mail.php';
        $fallback = $root . '/config/mail.default.php';

        $cfg = is_readable($primary) ? require $primary : require $fallback;
        if (!is_array($cfg)) {
            $cfg = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require $fallback;

        /** @var array<string, mixed> $merged */
        $merged = array_replace($defaults, $cfg);

        return $merged;
    }

    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): void
    {
        $apiKey = trim((string) ($this->config['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('Mail: falta api_key en configuración (Brevo).');
        }

        $fromEmail = trim((string) ($this->config['from_email'] ?? ''));
        $fromName  = (string) ($this->config['from_name'] ?? "Tarumba's Farm");
        if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Mail: from_email inválido o vacío.');
        }

        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Mail: destinatario inválido.');
        }

        $payload = [
            'sender'      => [
                'name'  => $fromName,
                'email' => $fromEmail,
            ],
            'to'          => [['email' => $to]],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
        ];

        $alt = $textBody !== ''
            ? $textBody
            : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        if ($alt !== '') {
            $payload['textContent'] = $alt;
        }

        $replyTo = trim((string) ($this->config['reply_to'] ?? ''));
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $payload['replyTo'] = ['email' => $replyTo];
        }

        $json = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE,
        );

        $ch = curl_init(self::BREVO_SEND_URL);
        if ($ch === false) {
            throw new \RuntimeException('Mail: curl_init falló.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
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
            throw new \RuntimeException('Mail: error cURL: ' . ($curlErr !== '' ? $curlErr : 'desconocido'));
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $snippet = mb_substr((string) $body, 0, 500);
            throw new \RuntimeException('Mail: Brevo HTTP ' . (string) $httpCode . ' — ' . $snippet);
        }
    }
}
