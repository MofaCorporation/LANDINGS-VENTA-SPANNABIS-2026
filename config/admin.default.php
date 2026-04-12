<?php

declare(strict_types=1);

/**
 * Plantilla de credenciales del panel admin.
 *
 * En producción: copiar este archivo a `config/admin.php` (no versionar) y rellenar
 * `password_hash` con el resultado de:
 *   php -r "echo password_hash('TU_CLAVE_SEGURA', PASSWORD_DEFAULT);"
 *
 * Usuario por defecto: admin (cambiable con la clave `username`).
 */
return [
    'username'       => 'admin',
    'password_hash'  => '',
];
