# Sistema de idiomas ES/EN en PHP (i18n)

## Enfoque elegido: JSON + clase Lang + URLs semánticas

Este sistema es ligero, sin dependencias externas, y funciona perfectamente con PHP puro + un router básico.

---

## Estructura de archivos de traducción

```
/src/Lang/
    es.json
    en.json
```

**`/src/Lang/es.json`**

```json
{
    "nav": {
        "home": "Inicio",
        "products": "Productos",
        "checkout": "Comprar",
        "lang_switch": "English"
    },
    "product": {
        "add_to_cart": "Añadir al carrito",
        "buy_now": "Comprar ahora",
        "price": "Precio",
        "stock": "En stock",
        "description": "Descripción"
    },
    "checkout": {
        "title": "Tu pedido",
        "name": "Nombre completo",
        "email": "Correo electrónico",
        "pay": "Pagar con tarjeta",
        "total": "Total",
        "secure": "Pago seguro con Redsys"
    },
    "errors": {
        "not_found": "Página no encontrada",
        "generic": "Ha ocurrido un error. Por favor, inténtalo de nuevo."
    }
}
```

**`/src/Lang/en.json`**

```json
{
    "nav": {
        "home": "Home",
        "products": "Products",
        "checkout": "Buy",
        "lang_switch": "Español"
    },
    "product": {
        "add_to_cart": "Add to cart",
        "buy_now": "Buy now",
        "price": "Price",
        "stock": "In stock",
        "description": "Description"
    },
    "checkout": {
        "title": "Your order",
        "name": "Full name",
        "email": "Email address",
        "pay": "Pay by card",
        "total": "Total",
        "secure": "Secure payment with Redsys"
    },
    "errors": {
        "not_found": "Page not found",
        "generic": "An error occurred. Please try again."
    }
}
```

---

## Clase Lang

**`/src/Lang/Lang.php`**

```php
<?php

namespace App\Lang;

class Lang
{
    private static string $current = 'es';
    private static array  $strings = [];
    private static array  $supported = ['es', 'en'];

    /**
     * Inicializa el idioma. Llamar una vez al arranque en index.php.
     * Detecta el idioma en este orden:
     *   1. Primer segmento de la URL (/es/... o /en/...)
     *   2. Sesión almacenada
     *   3. Idioma del navegador (Accept-Language)
     *   4. 'es' por defecto
     */
    public static function init(string $uriSegment = ''): void
    {
        $lang = null;

        // 1. URL
        if (in_array($uriSegment, self::$supported)) {
            $lang = $uriSegment;
        }

        // 2. Sesión
        if (!$lang && isset($_SESSION['lang']) && in_array($_SESSION['lang'], self::$supported)) {
            $lang = $_SESSION['lang'];
        }

        // 3. Accept-Language del navegador
        if (!$lang) {
            $browser = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'es', 0, 2);
            $lang    = in_array($browser, self::$supported) ? $browser : 'es';
        }

        self::$current = $lang;
        $_SESSION['lang'] = $lang;

        self::load($lang);
    }

    private static function load(string $lang): void
    {
        $file = __DIR__ . "/{$lang}.json";
        if (!file_exists($file)) {
            throw new \RuntimeException("Archivo de idioma no encontrado: {$file}");
        }
        self::$strings = json_decode(file_get_contents($file), true);
    }

    /**
     * Traduce una clave con notación de punto.
     * Ejemplo: Lang::t('product.add_to_cart')
     *
     * @param  string $key     Clave con puntos, ej: 'checkout.pay'
     * @param  array  $replace Sustituciones, ej: ['name' => 'Juan']
     */
    public static function t(string $key, array $replace = []): string
    {
        $keys  = explode('.', $key);
        $value = self::$strings;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $key; // Devuelve la clave si no existe (nunca pantalla en blanco)
            }
            $value = $value[$k];
        }

        if (!is_string($value)) {
            return $key;
        }

        // Sustituciones tipo :name → valor
        foreach ($replace as $placeholder => $replacement) {
            $value = str_replace(':' . $placeholder, $replacement, $value);
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function current(): string
    {
        return self::$current;
    }

    public static function supported(): array
    {
        return self::$supported;
    }

    /**
     * Genera la URL equivalente en el otro idioma.
     * Ejemplo: /es/producto-1 → /en/product-1
     */
    public static function switchUrl(string $currentUrl, string $targetLang): string
    {
        foreach (self::$supported as $lang) {
            if (str_starts_with($currentUrl, '/' . $lang . '/')) {
                return '/' . $targetLang . substr($currentUrl, strlen('/' . $lang));
            }
            if ($currentUrl === '/' . $lang) {
                return '/' . $targetLang;
            }
        }
        return '/' . $targetLang . $currentUrl;
    }
}
```

---

## Arranque en index.php

**`/public/index.php`**

```php
<?php

session_start();
require __DIR__ . '/../vendor/autoload.php';

use App\Lang\Lang;

// Extraer el primer segmento de la URL para detectar idioma
$uri         = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments    = explode('/', trim($uri, '/'));
$langSegment = $segments[0] ?? '';

Lang::init($langSegment);

// Continúa con el router...
require __DIR__ . '/../src/Router.php';
```

---

## Uso en las plantillas PHP

```php
<!-- En cualquier .php del directorio /templates -->

<!-- Traducción simple -->
<button><?= Lang::t('product.add_to_cart') ?></button>

<!-- Con sustitución de variable -->
<!-- JSON: "welcome": "Bienvenido, :name" -->
<h1><?= Lang::t('welcome', ['name' => $user['name']]) ?></h1>

<!-- Selector de idioma en la cabecera -->
<?php
$currentUrl = $_SERVER['REQUEST_URI'];
$targetLang = Lang::current() === 'es' ? 'en' : 'es';
$switchUrl  = Lang::switchUrl($currentUrl, $targetLang);
?>
<a href="<?= $switchUrl ?>" class="lang-switcher">
    <?= Lang::t('nav.lang_switch') ?>
</a>

<!-- Mostrar idioma activo en el <html> para accesibilidad -->
<html lang="<?= Lang::current() ?>">
```

---

## Estructura de URLs con idioma

El router debe generar y reconocer URLs con prefijo de idioma:

```
/es/                    → Home en español
/en/                    → Home en inglés
/es/producto-auriculares
/en/product-headphones
/es/checkout
/en/checkout
```

**Fragmento de router compatible:**

```php
<?php

// /src/Router.php
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri  = rtrim($uri, '/') ?: '/';

// Eliminar el prefijo de idioma para el enrutado interno
$lang = Lang::current();
$path = preg_replace('#^/' . $lang . '#', '', $uri) ?: '/';

match (true) {
    $path === '/' || $path === ''     => (new \App\Controllers\HomeController)->index(),
    str_starts_with($path, '/producto-'),
    str_starts_with($path, '/product-') => (new \App\Controllers\ProductController)->show($path),
    $path === '/checkout'             => (new \App\Controllers\CheckoutController)->index(),
    $path === '/redsys/notify'        => (new \App\Controllers\RedsysController)->notify(),
    $path === '/checkout/ok'          => (new \App\Controllers\RedsysController)->ok(),
    $path === '/checkout/ko'          => (new \App\Controllers\RedsysController)->ko(),
    default => http_response_code(404) && include __DIR__ . '/../templates/404.php',
};
```

---

## Traducción de contenido de productos (base de datos)

Para los textos de los propios productos (nombre, descripción) hay dos enfoques:

### Opción A — Columnas separadas (recomendado para simplicidad)

```sql
CREATE TABLE products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug_es     VARCHAR(200) NOT NULL,
    slug_en     VARCHAR(200) NOT NULL,
    name_es     VARCHAR(300) NOT NULL,
    name_en     VARCHAR(300) NOT NULL,
    description_es TEXT,
    description_en TEXT,
    price_cents INT UNSIGNED NOT NULL,
    stock       INT UNSIGNED DEFAULT 0,
    active      TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

```php
// En el controlador, obtener el campo correcto según idioma
$lang    = Lang::current();
$product = $db->query(
    "SELECT id, slug_{$lang} AS slug, name_{$lang} AS name,
            description_{$lang} AS description, price_cents, stock
     FROM products WHERE slug_{$lang} = ? AND active = 1",
    [$slug]
)->fetch();
```

### Opción B — Campo JSON (más flexible, requiere MySQL 8+)

```sql
ALTER TABLE products
    ADD COLUMN name JSON NOT NULL,
    ADD COLUMN description JSON;

-- Insertar
INSERT INTO products (name, description, price_cents)
VALUES (
    '{"es": "Auriculares Pro", "en": "Pro Headphones"}',
    '{"es": "Descripción en español", "en": "English description"}',
    4999
);
```

```php
// Consulta con JSON_UNQUOTE + JSON_EXTRACT
$lang    = Lang::current();
$product = $db->query(
    "SELECT id,
            JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$lang}\"')) AS name,
            JSON_UNQUOTE(JSON_EXTRACT(description, '$.\"{$lang}\"')) AS description,
            price_cents
     FROM products WHERE id = ?",
    [$id]
)->fetch();
```

---

## SEO multiidioma — hreflang

Añadir en el `<head>` de cada página para que Google indexe correctamente ambos idiomas:

```php
<?php
$esUrl = 'https://tudominio.com/es' . $pageSlug;
$enUrl = 'https://tudominio.com/en' . $pageSlug;
?>
<link rel="alternate" hreflang="es" href="<?= $esUrl ?>">
<link rel="alternate" hreflang="en" href="<?= $enUrl ?>">
<link rel="alternate" hreflang="x-default" href="<?= $esUrl ?>">
```

---

## Checklist del sistema de idiomas

- [ ] Archivos `es.json` y `en.json` con todas las claves necesarias
- [ ] `Lang::init()` llamado antes de cualquier output
- [ ] `session_start()` antes de `Lang::init()`
- [ ] Atributo `lang=` en la etiqueta `<html>` usando `Lang::current()`
- [ ] Tags `hreflang` en el `<head>` de cada página
- [ ] Slugs de URL diferenciados por idioma en la tabla de productos
- [ ] El selector de idioma visible en header y mobile menu
- [ ] Probar cambio de idioma desde cada una de las 6 páginas
