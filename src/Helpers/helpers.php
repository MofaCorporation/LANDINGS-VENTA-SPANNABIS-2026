<?php

declare(strict_types=1);

use App\Lang\Lang;

function base_path(): string
{
    static $cached = null;
    if ($cached === null) {
        $cfg    = require dirname(__DIR__, 2) . '/config/app.php';
        $cached = isset($cfg['base_path']) && is_string($cfg['base_path']) ? $cfg['base_path'] : '';
    }

    return $cached;
}

function url_lang(string $path): string
{
    $path = '/' . ltrim($path, '/');
    $bp   = base_path();

    return $bp . '/' . Lang::current() . $path;
}

/**
 * Ruta absoluta en el mismo host para estáticos (/assets/...), con prefijo BASE_PATH.
 */
function asset_url(string $path): string
{
    $path = '/' . ltrim($path, '/');

    return base_path() . $path;
}

function base_url(): string
{
    static $cached = null;
    if ($cached === null) {
        $cfg    = require dirname(__DIR__, 2) . '/config/app.php';
        $cached = rtrim((string) $cfg['base_url'], '/');
    }

    return $cached;
}

function generate_order_id(): string
{
    return 'ORD' . gmdate('ymdHis') . substr((string) random_int(100, 999), 0, 3);
}

function format_price_cents(int $cents, string $currency = 'EUR'): string
{
    $amount = $cents / 100;

    return number_format($amount, 2, ',', ' ') . ' ' . ($currency === 'EUR' ? '€' : $currency);
}

function checkout_csrf_token(): string
{
    if (empty($_SESSION['csrf_checkout'])) {
        $_SESSION['csrf_checkout'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf_checkout'];
}

function checkout_verify_csrf(string $token): bool
{
    $expected = $_SESSION['csrf_checkout'] ?? '';

    return $expected !== '' && hash_equals($expected, $token);
}
