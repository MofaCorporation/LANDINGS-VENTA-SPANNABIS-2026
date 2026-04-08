# Estructura del proyecto — ecommerce PHP con Redsys

## Árbol de carpetas completo

```
proyecto-ecommerce/
│
├── public/                          ← Document root del servidor web (único dir expuesto)
│   ├── index.php                    ← Punto de entrada único (front controller)
│   ├── .htaccess                    ← Rewrite rules para URLs limpias
│   └── assets/
│       ├── css/
│       │   ├── main.css             ← Estilos globales y reset
│       │   ├── components.css       ← Botones, cards, forms reutilizables
│       │   └── checkout.css         ← Estilos específicos del checkout
│       ├── js/
│       │   ├── main.js              ← JS global (menú, lang switcher)
│       │   ├── cart.js              ← Lógica del carrito (Alpine.js o vanilla)
│       │   └── checkout.js          ← Validación del form + auto-submit Redsys
│       └── img/
│           ├── productos/           ← Imágenes de los 5 productos
│           └── ui/                  ← Logos, iconos, badges
│
├── src/                             ← Código PHP — nunca accesible desde el navegador
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── ProductController.php    ← Sirve las 5 landing pages
│   │   ├── CheckoutController.php   ← Carrito + formulario de pago
│   │   └── RedsysController.php     ← Notify, ok, ko
│   │
│   ├── Models/
│   │   ├── Product.php              ← Consultas a tabla products
│   │   └── Order.php                ← Crear pedido, markAsPaid, markAsFailed
│   │
│   ├── Services/
│   │   └── RedsysService.php        ← Firma HMAC, buildPaymentParams, validateNotification
│   │
│   ├── Lang/
│   │   ├── Lang.php                 ← Clase de gestión de idiomas
│   │   ├── es.json                  ← Strings en español
│   │   └── en.json                  ← Strings en inglés
│   │
│   ├── Core/
│   │   ├── Database.php             ← Singleton PDO con prepared statements
│   │   └── Router.php               ← Router ligero basado en match()
│   │
│   └── Helpers/
│       └── helpers.php              ← Funciones globales: generateOrderId(), formatPrice(), etc.
│
├── templates/                       ← Vistas HTML con PHP embebido
│   ├── layout/
│   │   ├── header.php               ← <head>, nav, selector de idioma
│   │   └── footer.php               ← Footer, scripts JS
│   │
│   ├── home.php
│   │
│   ├── products/
│   │   ├── landing-1.php            ← Landing page producto 1
│   │   ├── landing-2.php            ← Landing page producto 2
│   │   ├── landing-3.php            ← Landing page producto 3
│   │   ├── landing-4.php            ← Landing page producto 4
│   │   └── landing-5.php            ← Landing page producto 5
│   │
│   ├── checkout.php                 ← Resumen del pedido + form → Redsys
│   ├── checkout_ok.php              ← Página de confirmación de pago
│   ├── checkout_ko.php              ← Página de error de pago
│   └── 404.php
│
├── config/
│   ├── database.php                 ← Credenciales MySQL (nunca en git)
│   ├── redsys.php                   ← Claves Redsys (nunca en git)
│   └── app.php                      ← BASE_URL, entorno, debug flag
│
├── vendor/                          ← Generado por Composer (nunca en git)
│
├── .env.example                     ← Plantilla de variables de entorno
├── .gitignore
├── composer.json
└── README.md
```

---

## Archivos de configuración clave

### `.htaccess` (redirige todo al index.php)

```apache
Options -Indexes
RewriteEngine On

# No reescribir si el archivo/directorio existe (para assets)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Todo lo demás va al front controller
RewriteRule ^(.*)$ index.php [QSA,L]

# Seguridad: negar acceso a archivos sensibles
<FilesMatch "\.(env|json|md|lock|gitignore)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### `composer.json`

```json
{
    "name": "tuempresa/ecommerce",
    "require": {
        "php": ">=8.2"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        },
        "files": [
            "src/Helpers/helpers.php"
        ]
    },
    "config": {
        "optimize-autoloader": true
    }
}
```

### `config/database.php`

```php
<?php
return [
    'host'    => $_ENV['DB_HOST']     ?? 'localhost',
    'dbname'  => $_ENV['DB_NAME']     ?? 'ecommerce',
    'user'    => $_ENV['DB_USER']     ?? 'root',
    'pass'    => $_ENV['DB_PASS']     ?? '',
    'charset' => 'utf8mb4',
];
```

### `.env.example`

```
DB_HOST=localhost
DB_NAME=ecommerce
DB_USER=root
DB_PASS=

REDSYS_MERCHANT_CODE=999008881
REDSYS_TERMINAL=001
REDSYS_SECRET_KEY=
REDSYS_SANDBOX=true

APP_ENV=development
BASE_URL=https://localhost
```

---

## Clase Database (PDO Singleton)

**`/src/Core/Database.php`**

```php
<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';
            $dsn    = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

            try {
                self::$instance = new PDO($dsn, $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // En producción, nunca mostrar detalles de conexión
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(500);
                exit('Error de sistema. Por favor, inténtalo más tarde.');
            }
        }

        return self::$instance;
    }
}
```

---

## Esquema SQL inicial

```sql
-- Ejecutar en MySQL 8 / MariaDB 10.11

CREATE DATABASE IF NOT EXISTS ecommerce
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ecommerce;

CREATE TABLE products (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug_es        VARCHAR(200) NOT NULL UNIQUE,
    slug_en        VARCHAR(200) NOT NULL UNIQUE,
    name_es        VARCHAR(300) NOT NULL,
    name_en        VARCHAR(300) NOT NULL,
    description_es TEXT,
    description_en TEXT,
    price_cents    INT UNSIGNED NOT NULL,
    stock          INT UNSIGNED DEFAULT 0,
    image          VARCHAR(300),
    active         TINYINT(1) DEFAULT 1,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug_es (slug_es),
    INDEX idx_slug_en (slug_en)
);

CREATE TABLE orders (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_ref      VARCHAR(20)  NOT NULL UNIQUE,   -- ID enviado a Redsys
    product_id     INT UNSIGNED NOT NULL,
    amount_cents   INT UNSIGNED NOT NULL,
    currency       CHAR(3) DEFAULT 'EUR',
    status         ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    customer_email VARCHAR(254),
    redsys_response JSON,                           -- Guarda la respuesta completa de Redsys
    paid_at        DATETIME,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE sessions (
    id         VARCHAR(128) PRIMARY KEY,
    data       TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## `.gitignore`

```
/vendor/
/.env
/config/database.php
/config/redsys.php
*.log
.DS_Store
Thumbs.db
```

---

## Checklist de puesta en marcha local (Laragon)

1. Clonar el proyecto en `C:\laragon\www\ecommerce`
2. Crear base de datos `ecommerce` en phpMyAdmin y ejecutar el SQL de arriba
3. Copiar `.env.example` a `.env` y rellenar credenciales
4. Ejecutar `composer install` en la raíz del proyecto
5. En Laragon: apuntar el document root a `/public`
6. Acceder a `http://ecommerce.test/es/`

> En producción, asegúrate de que el document root del virtualhost apunte a `/public`, no a la raíz del proyecto.
