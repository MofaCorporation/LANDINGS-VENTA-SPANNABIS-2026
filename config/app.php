<?php

declare(strict_types=1);

$rawBasePath = getenv('BASE_PATH') !== false ? trim((string) getenv('BASE_PATH')) : 'drops';
if ($rawBasePath === '' || $rawBasePath === '/') {
    $basePath = '';
} else {
    $basePath = '/' . trim($rawBasePath, '/');
}

return [
    'base_url'  => rtrim((string) (getenv('BASE_URL') !== false ? getenv('BASE_URL') : 'http://localhost:8080/drops'), '/'),
    'base_path' => $basePath,
    'env'       => getenv('APP_ENV') !== false ? getenv('APP_ENV') : 'development',
    'debug'     => filter_var(getenv('APP_DEBUG') !== false ? getenv('APP_DEBUG') : 'false', FILTER_VALIDATE_BOOLEAN),
];
