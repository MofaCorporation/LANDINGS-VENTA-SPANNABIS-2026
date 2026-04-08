<?php

declare(strict_types=1);

/**
 * Autoload PSR-4 (App\) cuando `vendor/autoload.php` de Composer aún no existe.
 */
$root = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $rel  = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = $root . '/src/' . $rel . '.php';
    if (is_readable($path)) {
        require $path;
    }
});

require $root . '/src/Helpers/helpers.php';
