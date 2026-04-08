<?php

declare(strict_types=1);

namespace App\Controllers;

abstract class BaseController
{
    /** @param array<string, mixed> $vars */
    protected function render(string $template, array $vars = []): void
    {
        $root = dirname(__DIR__, 2);
        extract($vars, EXTR_SKIP);
        require $root . '/templates/layout/header.php';
        require $root . '/templates/' . $template . '.php';
        require $root . '/templates/layout/footer.php';
    }
}
