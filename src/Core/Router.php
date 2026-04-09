<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Controllers\ProductController;
use App\Controllers\RedsysController;
use App\Lang\Lang;

final class Router
{
    public static function dispatch(string $uri): void
    {
        $uri = '/' . trim($uri, '/');
        $uri = rtrim($uri, '/') ?: '/';

        $segments = $uri === '/' ? [] : array_values(array_filter(explode('/', trim($uri, '/'))));

        if (isset($segments[0]) && $segments[0] === 'redsys') {
            Lang::init('es');
            $path = '/' . implode('/', $segments);
            match ($path) {
                '/redsys/notify' => (new RedsysController())->notify(),
                default            => self::notFound(),
            };

            return;
        }

        $supported = Lang::supported();

        $lang       = null;
        $routeParts = $segments;

        if (isset($segments[0]) && in_array($segments[0], $supported, true)) {
            $lang       = $segments[0];
            $routeParts = array_slice($segments, 1);
        } elseif ($segments !== []) {
            header('Location: ' . base_path() . '/es/' . implode('/', $segments), true, 302);
            exit;
        }

        if ($lang === null) {
            $lang = 'es';
        }

        Lang::init($lang);

        $path = '/' . implode('/', $routeParts);
        if ($path === '/') {
            (new HomeController())->index();
            return;
        }

        // production-v1: solo lady-cupcake + checkout (resto de landings → 404).
        // TODO(post-TPV): habilitar rutas dj-piggy, holy-boss,
        // nitro-bud, toxic-mutant cuando el banco apruebe el TPV
        match ($path) {
            '/lady-cupcake' => (new ProductController())->ladyCupcake(),
            '/checkout'     => (new CheckoutController())->index(),
            '/checkout/shipping-rates' => (new CheckoutController())->index(),
            '/checkout/ok'  => (new RedsysController())->ok(),
            '/checkout/ko'  => (new RedsysController())->ko(),
            default         => self::notFound(),
        };
    }

    private static function notFound(): void
    {
        http_response_code(404);
        require dirname(__DIR__, 2) . '/templates/404.php';
    }
}
