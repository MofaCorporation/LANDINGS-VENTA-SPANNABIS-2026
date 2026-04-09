<?php

declare(strict_types=1);

/**
 * Config por defecto (local / env). En servidor, crea `config/mail.php` (no versionado).
 *
 * Envío vía API HTTP Brevo (sin SMTP en PHP):
 * - api_key: clave de la API (cabecera `api-key`)
 * - from_email / from_name: remitente verificado en Brevo
 */
return [
    'api_key'     => getenv('BREVO_API_KEY') !== false ? getenv('BREVO_API_KEY') : '',
    'from_email'  => getenv('MAIL_FROM_EMAIL') !== false && getenv('MAIL_FROM_EMAIL') !== '' ? getenv('MAIL_FROM_EMAIL') : 'pepebulkov@tarumbasfarm.com',
    'from_name'   => getenv('MAIL_FROM_NAME') !== false && getenv('MAIL_FROM_NAME') !== '' ? getenv('MAIL_FROM_NAME') : "Tarumba's Farm",
    'reply_to'    => getenv('MAIL_REPLY_TO') !== false ? getenv('MAIL_REPLY_TO') : '',
];
