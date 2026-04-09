<?php

declare(strict_types=1);

/**
 * Credenciales por defecto (Docker / local). Sobrescribe con config/database.php si lo usas.
 */
return [
    'host'     => getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? getenv('DB_HOST') : '127.0.0.1',
    'dbname'   => getenv('DB_NAME') !== false && getenv('DB_NAME') !== '' ? getenv('DB_NAME') : 'ecommerce',
    'user'     => getenv('DB_USER') !== false && getenv('DB_USER') !== '' ? getenv('DB_USER') : 'app',
    'pass'     => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    'charset'  => 'utf8mb4',
];
