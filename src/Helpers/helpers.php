<?php

declare(strict_types=1);

use App\Lang\Lang;

function url_lang(string $path): string
{
    $path = '/' . ltrim($path, '/');

    return '/' . Lang::current() . $path;
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
