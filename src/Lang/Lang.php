<?php

declare(strict_types=1);

namespace App\Lang;

final class Lang
{
    private static string $current = 'es';

    /** @var array<string, mixed> */
    private static array $strings = [];

    /** @var list<string> */
    private static array $supported = ['es', 'en'];

    public static function init(string $uriSegment = ''): void
    {
        $lang = null;

        if (in_array($uriSegment, self::$supported, true)) {
            $lang = $uriSegment;
        }

        if ($lang === null && isset($_SESSION['lang']) && in_array($_SESSION['lang'], self::$supported, true)) {
            $lang = $_SESSION['lang'];
        }

        if ($lang === null) {
            $browser = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'es', 0, 2);
            $lang    = in_array($browser, self::$supported, true) ? $browser : 'es';
        }

        self::$current = $lang;
        $_SESSION['lang'] = $lang;

        self::load($lang);
    }

    private static function load(string $lang): void
    {
        $file = __DIR__ . '/' . $lang . '.json';
        if (!is_readable($file)) {
            throw new \RuntimeException('Archivo de idioma no encontrado: ' . $file);
        }
        $json = file_get_contents($file);
        if ($json === false) {
            throw new \RuntimeException('No se pudo leer: ' . $file);
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException('JSON de idioma inválido: ' . $file);
        }

        $extraFile = __DIR__ . '/' . $lang . '.products.json';
        if (is_readable($extraFile)) {
            $extraJson = file_get_contents($extraFile);
            if ($extraJson !== false) {
                $extra = json_decode($extraJson, true);
                if (is_array($extra)) {
                    $data = array_replace_recursive($data, $extra);
                }
            }
        }

        self::$strings = $data;
    }

    /**
     * @param array<string, string> $replace
     */
    public static function t(string $key, array $replace = []): string
    {
        $keys  = explode('.', $key);
        $value = self::$strings;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            }
            $value = $value[$k];
        }

        if (!is_string($value)) {
            return htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        }

        foreach ($replace as $placeholder => $replacement) {
            $value = str_replace(':' . $placeholder, $replacement, $value);
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function current(): string
    {
        return self::$current;
    }

    /** @return list<string> */
    public static function supported(): array
    {
        return self::$supported;
    }

    public static function switchUrl(string $currentUrl, string $targetLang): string
    {
        $path = parse_url($currentUrl, PHP_URL_PATH) ?: '/';
        foreach (self::$supported as $lang) {
            $prefix = '/' . $lang;
            if (str_starts_with($path, $prefix . '/') || $path === $prefix) {
                return $prefix === $path
                    ? '/' . $targetLang
                    : '/' . $targetLang . substr($path, strlen($prefix));
            }
        }

        return '/' . $targetLang . ($path === '/' ? '' : $path);
    }
}
