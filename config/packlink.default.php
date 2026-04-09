<?php

declare(strict_types=1);

$apiKey = getenv('PACKLINK_PRO_API_KEY');
if ($apiKey === false) {
    $apiKey = '';
}

$sandbox = getenv('PACKLINK_PRO_SANDBOX');
if ($sandbox === false || $sandbox === '') {
    $sandbox = 'false';
}

return [
    'api_key'  => (string) $apiKey,
    'sandbox'  => filter_var($sandbox, FILTER_VALIDATE_BOOLEAN),
];

