<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Limita intentos de login al panel admin por cliente (IP).
 * Persistencia en archivo bajo el directorio temporal del sistema.
 */
final class AdminLoginRateLimiter
{
    private const MAX_FAILURES = 5;

    private const LOCKOUT_SECONDS = 900;

    /** Bcrypt válido para igualar coste de password_verify cuando el usuario no coincide. */
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public static function dummyPasswordHash(): string
    {
        return self::DUMMY_HASH;
    }

    public static function isLocked(): bool
    {
        $path  = self::filePath();
        $data  = self::read($path);
        $until = (int) ($data['until'] ?? 0);
        if ($until > 0 && $until <= time()) {
            self::write($path, ['failures' => 0, 'until' => 0]);

            return false;
        }

        return $until > time();
    }

    public static function secondsUntilUnlock(): int
    {
        $until = (int) (self::read(self::filePath())['until'] ?? 0);
        if ($until <= time()) {
            return 0;
        }

        return $until - time();
    }

    public static function recordFailure(): void
    {
        if (self::isLocked()) {
            return;
        }

        $path = self::filePath();
        $data = self::read($path);

        $failures = (int) ($data['failures'] ?? 0) + 1;
        $until    = 0;
        if ($failures >= self::MAX_FAILURES) {
            $until    = time() + self::LOCKOUT_SECONDS;
            $failures = 0;
        }

        self::write($path, ['failures' => $failures, 'until' => $until]);
    }

    public static function clear(): void
    {
        $path = self::filePath();
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /** @return array{failures?: int, until?: int} */
    private static function read(string $path): array
    {
        if (!is_readable($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return [];
        }
        try {
            $decoded = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array{failures: int, until: int} $data */
    private static function write(string $path, array $data): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            return;
        }
        $tmp = $path . '.' . bin2hex(random_bytes(4));
        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return;
        }
        if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
            return;
        }
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
        }
    }

    private static function filePath(): string
    {
        $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'tarumba-admin-login';

        return $base . DIRECTORY_SEPARATOR . self::clientKey() . '.json';
    }

    private static function clientKey(): string
    {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0';

        return hash('sha256', (string) $ip . '|' . self::salt());
    }

    private static function salt(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $root = dirname(__DIR__, 2);
        $path = $root . '/config/app.php';
        $part = is_readable($path) ? hash_file('sha256', $path) : 'no-app-config';

        $cached = hash('sha256', 'tarumba-admin-brute-v1|' . $part);

        return $cached;
    }
}
