<?php

declare(strict_types=1);

namespace App\Controllers;

final class ProductController extends BaseController
{
    public function djPiggy(): void
    {
        $this->renderProductLanding(
            [
                'prefix'           => 'product.dj_piggy',
                'variety'          => 'dj-piggy',
                'hero'             => '/assets/img/productos/dj-piggy/hero-dj-piggy-web.png',
                'buyEs'            => '/assets/img/productos/dj-piggy/comprar-dj-piggy.png',
                'buyEn'            => '/assets/img/productos/dj-piggy/buy-seeds-dj-piggy.png',
                'h1'               => ['DJ', 'PIGGY'],
                'paragraphCount'   => 5,
                'listCount'        => 5,
                'charCount'        => 4,
                'symptoms'         => [
                    ['icon' => 'graphic_eq', 'border' => 'pc'],
                    ['icon' => 'palette', 'border' => 'sec'],
                    ['icon' => 'nightlife', 'border' => 'ter'],
                    ['icon' => 'headphones', 'border' => 'white'],
                ],
            ],
            'meta.dj_piggy.document_title',
            'meta.dj_piggy.description',
            '--pc: #ff00aa; --pc-dim: #d6008e; --sec: #00ffcc; --ter: #ffff00; --bg: #0e0610; --surf: #0e0610; --surf-cont: #1a1020; --on-pc: #2a0018; --nav-stroke: #00ffcc;',
            '/assets/js/djPiggy-BWVwacbI.js',
        );
    }

    public function holyBoss(): void
    {
        $this->renderProductLanding(
            [
                'prefix'           => 'product.holy_boss',
                'variety'          => 'holy-boss',
                'hero'             => '/assets/img/productos/holy-boss/hero-holy-boss-web.png',
                'buyEs'            => '/assets/img/productos/holy-boss/comprar-holy-boss.png',
                'buyEn'            => '/assets/img/productos/holy-boss/buy-seeds-holy-boss.png',
                'h1'               => ['HOLY', 'BOSS'],
                'paragraphCount'   => 10,
                'listCount'        => 5,
                'charCount'        => 4,
                'symptoms'         => [
                    ['icon' => 'wb_sunny', 'border' => 'pc'],
                    ['icon' => 'psychology', 'border' => 'sec'],
                    ['icon' => 'center_focus_strong', 'border' => 'ter'],
                    ['icon' => 'bolt', 'border' => 'white'],
                ],
            ],
            'meta.holy_boss.document_title',
            'meta.holy_boss.description',
            '--pc: #f5e6a8; --pc-dim: #d4c278; --sec: #8b5cf6; --ter: #38bdf8; --bg: #08080a; --surf: #08080a; --surf-cont: #12121a; --on-pc: #1a1508; --nav-stroke: #8b5cf6;',
            null,
        );
    }

    public function ladyCupcake(): void
    {
        $this->renderProductLanding(
            [
                'prefix'           => 'product.lady_cupcake',
                'variety'          => 'lady-cupcake',
                'hero'             => '/assets/img/productos/lady-cupcake/hero-lady-cupcake-web.png',
                'buyEs'            => '/assets/img/productos/lady-cupcake/comprar-coleccionable-pepe.png',
                'buyEn'            => '/assets/img/productos/lady-cupcake/comprar-coleccionable-pepe.png',
                'h1'               => ['LADY', 'CUPCAKE'],
                'paragraphCount'   => 2,
                'listCount'        => 5,
                'charCount'        => 5,
                'symptoms'         => [
                    ['icon' => 'spa', 'border' => 'pc'],
                    ['icon' => 'sentiment_satisfied', 'border' => 'sec'],
                    ['icon' => 'cake', 'border' => 'ter'],
                    ['icon' => 'favorite', 'border' => 'white'],
                ],
            ],
            'meta.lady_cupcake.document_title',
            'meta.lady_cupcake.description',
            '--pc: #ffb6c1; --pc-dim: #ff8da1; --sec: #a78bfa; --ter: #67e8f9; --bg: #0f0a0c; --surf: #0f0a0c; --surf-cont: #1a1216; --on-pc: #3d1520; --nav-stroke: #a78bfa;',
            null,
        );
    }

    public function nitroBud(): void
    {
        $this->renderProductLanding(
            [
                'prefix'           => 'product.nitro_bud',
                'variety'          => 'nitro-bud',
                'hero'             => '/assets/img/productos/nitro-bud/hero-nitro-bud-web.png',
                'buyEs'            => '/assets/img/productos/nitro-bud/comprar-nitro-bud.png',
                'buyEn'            => '/assets/img/productos/nitro-bud/buy-seeds-nitro-buds.png',
                'h1'               => ['NITRO', 'BUD'],
                'paragraphCount'   => 8,
                'listCount'        => 6,
                'charCount'        => 4,
                'symptoms'         => [
                    ['icon' => 'speed', 'border' => 'pc'],
                    ['icon' => 'balance', 'border' => 'sec'],
                    ['icon' => 'bolt', 'border' => 'ter'],
                    ['icon' => 'timer', 'border' => 'white'],
                ],
            ],
            'meta.nitro_bud.document_title',
            'meta.nitro_bud.description',
            '--pc: #ff6b00; --pc-dim: #e85d00; --sec: #00d4ff; --ter: #fff200; --bg: #0a0a0c; --surf: #0a0a0c; --surf-cont: #141418; --on-pc: #1a0a00; --nav-stroke: #00d4ff;',
            null,
        );
    }

    public function toxicMutant(): void
    {
        $this->renderProductLanding(
            [
                'prefix'           => 'product.toxic_mutant',
                'variety'          => 'toxic-mutant',
                'hero'             => '/assets/img/productos/toxic-mutant/hero-toxic-mutat-web.png',
                'buyEs'            => '/assets/img/productos/toxic-mutant/comprar-toxic-mutant.png',
                'buyEn'            => '/assets/img/productos/toxic-mutant/buy-seeds-toxic-mutant.png',
                'h1'               => ['TOXIC', 'MUTANT'],
                'paragraphCount'   => 5,
                'listCount'        => 6,
                'charCount'        => 4,
                'symptoms'         => [
                    ['icon' => 'explosion', 'border' => 'pc'],
                    ['icon' => 'fitness_center', 'border' => 'sec'],
                    ['icon' => 'open_in_full', 'border' => 'ter'],
                    ['icon' => 'psychology', 'border' => 'white'],
                ],
            ],
            'meta.toxic_mutant.document_title',
            'meta.toxic_mutant.description',
            '--pc: #bdfc00; --pc-dim: #b1ed00; --sec: #ff51fa; --ter: #c1fffe; --bg: #0e0e0e; --surf: #0e0e0e; --surf-cont: #1a1919; --on-pc: #445d00; --nav-stroke: #ff51fa;',
            null,
        );
    }

    /**
     * @param array<string, mixed> $landing
     */
    private function renderProductLanding(
        array $landing,
        string $pageTitleKey,
        string $metaDescriptionKey,
        string $htmlThemeStyle,
        ?string $extraModuleSrc = null,
    ): void {
        $this->render('products/landing', [
            'landing'            => $landing,
            'pageTitleKey'       => $pageTitleKey,
            'metaDescriptionKey' => $metaDescriptionKey,
            'htmlThemeStyle'     => $htmlThemeStyle,
            'extraModuleSrc'     => $extraModuleSrc,
        ]);
    }
}
