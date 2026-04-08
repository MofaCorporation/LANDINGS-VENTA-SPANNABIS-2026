<?php

declare(strict_types=1);

return [
    'base_url' => rtrim((string) (getenv('BASE_URL') !== false ? getenv('BASE_URL') : 'http://localhost:8080'), '/'),
    'env'      => getenv('APP_ENV') !== false ? getenv('APP_ENV') : 'development',
    'debug'    => filter_var(getenv('APP_DEBUG') !== false ? getenv('APP_DEBUG') : 'false', FILTER_VALIDATE_BOOLEAN),
];
