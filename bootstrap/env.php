<?php

declare(strict_types=1);

/**
 * Carga un `.env` local (si existe) a variables de entorno.
 * - No sobreescribe variables ya definidas en el entorno.
 * - Formato soportado: KEY=VALUE (con comillas opcionales), comentarios con #.
 */
return (static function (): void {
    $root = dirname(__DIR__);
    $path = $root . '/.env';

    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        if ($key === '') {
            continue;
        }

        $value = trim(substr($line, $pos + 1));
        if ($value !== '') {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if (getenv($key) !== false) {
            continue;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
})();

