<?php

declare(strict_types=1);

/**
 * Redsys TPV. Clave de prueba del comercio sandbox 999008881 (documentación pública Redsys).
 * En producción: variables de entorno y clave real; nunca commitear secretos reales.
 */
$sandbox = getenv('REDSYS_SANDBOX');
if ($sandbox === false || $sandbox === '') {
    $sandbox = 'true';
}

$secret = getenv('REDSYS_SECRET_KEY');
if ($secret === false || $secret === '') {
    $secret = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';
}

return [
    'sandbox'        => filter_var($sandbox, FILTER_VALIDATE_BOOLEAN),
    'merchant_code'  => getenv('REDSYS_MERCHANT_CODE') !== false && getenv('REDSYS_MERCHANT_CODE') !== ''
        ? getenv('REDSYS_MERCHANT_CODE')
        : '999008881',
    'terminal'       => getenv('REDSYS_TERMINAL') !== false && getenv('REDSYS_TERMINAL') !== ''
        ? getenv('REDSYS_TERMINAL')
        : '001',
    'secret_key'     => $secret,
];
