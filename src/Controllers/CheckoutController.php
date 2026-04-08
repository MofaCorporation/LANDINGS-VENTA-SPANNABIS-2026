<?php

declare(strict_types=1);

namespace App\Controllers;

final class CheckoutController extends BaseController
{
    public function index(): void
    {
        http_response_code(501);
        echo 'Checkout — pendiente de implementación.';
    }
}
