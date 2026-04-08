<?php

declare(strict_types=1);

namespace App\Controllers;

final class RedsysController
{
    public function notify(): void
    {
        http_response_code(501);
        echo 'Redsys notify — pendiente.';
    }

    public function ok(): void
    {
        http_response_code(501);
        echo 'Checkout OK — pendiente.';
    }

    public function ko(): void
    {
        http_response_code(501);
        echo 'Checkout KO — pendiente.';
    }
}
