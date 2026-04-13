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

    /**
     * Texto sin escapar HTML (solo para datos de confianza: APIs, TPV, metadatos).
     *
     * @param array<string, string> $replace
     */
    public static function raw(string $key, array $replace = []): string
    {
        $keys  = explode('.', $key);
        $value = self::$strings;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $key;
            }
            $value = $value[$k];
        }

        if (!is_string($value)) {
            return $key;
        }

        foreach ($replace as $placeholder => $replacement) {
            $value = str_replace(':' . $placeholder, $replacement, $value);
        }

        return $value;
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

    /**
     * Ruta del request (empieza por /), sin query ni fragmento.
     * Evita parse_url() solo con path: en algunos entornos devuelve null y rompe el conmutador.
     */
    public static function requestPath(string $requestUri): string
    {
        $requestUri = $requestUri === '' ? '/' : $requestUri;

        if (preg_match('#^https?://#i', $requestUri)) {
            $p = parse_url($requestUri, PHP_URL_PATH);

            $path = ($p !== null && $p !== false && $p !== '') ? $p : '/';
        } else {
            $path = $requestUri;
            $qPos = strpos($path, '?');
            if ($qPos !== false) {
                $path = substr($path, 0, $qPos);
            }
            $hPos = strpos($path, '#');
            if ($hPos !== false) {
                $path = substr($path, 0, $hPos);
            }
            if ($path === '' || ($path[0] ?? '') !== '/') {
                $path = '/' . ltrim($path, '/');
            }
        }

        $path = rtrim($path, '/') ?: '/';

        if (function_exists('base_path')) {
            $bp = base_path();
            if ($bp !== '' && ($path === $bp || str_starts_with($path, $bp . '/'))) {
                $path = substr($path, strlen($bp)) ?: '/';
                $path = rtrim($path, '/') ?: '/';
            }
        }

        return $path;
    }

    /**
     * Query string sin "?" (cadena vacía si no hay).
     */
    public static function requestQuery(string $requestUri): string
    {
        $requestUri = $requestUri === '' ? '/' : $requestUri;

        if (preg_match('#^https?://#i', $requestUri)) {
            $q = parse_url($requestUri, PHP_URL_QUERY);

            return ($q !== null && $q !== false) ? $q : '';
        }

        $qPos = strpos($requestUri, '?');
        if ($qPos === false) {
            return '';
        }

        $q = substr($requestUri, $qPos + 1);
        $hPos = strpos($q, '#');
        if ($hPos !== false) {
            $q = substr($q, 0, $hPos);
        }

        return $q;
    }

    /**
     * URL de mismo recurso en otro idioma: sustituye /es/ o /en/ y conserva query.
     *
     * @param string $requestUri REQUEST_URI típico (p. ej. /en/nitro-bud?variety=x) o URL absoluta
     */
    public static function switchUrl(string $requestUri, string $targetLang): string
    {
        if (!in_array($targetLang, self::$supported, true)) {
            $targetLang = 'es';
        }

        $path  = self::requestPath($requestUri);
        $query = self::requestQuery($requestUri);

        $newPath = null;
        foreach (self::$supported as $lang) {
            $prefix = '/' . $lang;
            if (str_starts_with($path, $prefix . '/') || $path === $prefix) {
                $newPath = $path === $prefix
                    ? '/' . $targetLang
                    : '/' . $targetLang . substr($path, strlen($prefix));
                break;
            }
        }

        if ($newPath === null) {
            $newPath = '/' . $targetLang . ($path === '/' ? '' : $path);
        }

        if (str_ends_with($newPath, '/catalogo-secreto') && $targetLang === 'en') {
            $newPath = substr($newPath, 0, -strlen('/catalogo-secreto')) . '/secret-catalog';
        } elseif (str_ends_with($newPath, '/secret-catalog') && $targetLang === 'es') {
            $newPath = substr($newPath, 0, -strlen('/secret-catalog')) . '/catalogo-secreto';
        }

        return $newPath . ($query !== '' ? '?' . $query : '');
    }
}
