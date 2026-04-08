<?php

declare(strict_types=1);

session_start();

$root = dirname(__DIR__);

if (is_readable($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
} else {
    require $root . '/bootstrap/autoload.php';
}

$config = require $root . '/config/app.php';
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim((string) $config['base_url'], '/'));
}

use App\Core\Router;

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

Router::dispatch($uri);
