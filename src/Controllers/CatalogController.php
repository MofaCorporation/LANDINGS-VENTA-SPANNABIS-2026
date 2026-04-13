<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Lang\Lang;

final class CatalogController extends BaseController
{
    private const HTML_THEME_STYLE = '--pc: #a3ff12; --pc-dim: #8fd010; --sec: #00ffcc; --ter: #ff51fa; --bg: #0b0b10; --surf: #0e0e12; --surf-cont: #15151f; --on-pc: #0e1a00; --nav-stroke: #00ffcc;';

    /**
     * @return list<array{slug: string, hero: string, nameKey: string, taglineKey: string}>
     */
    private static function catalogItems(): array
    {
        return [
            [
                'slug'       => 'dj-piggy',
                'hero'       => '/assets/img/productos/dj-piggy/hero-dj-piggy-web.png',
                'nameKey'    => 'catalog.items.dj_piggy.name',
                'taglineKey' => 'product.dj_piggy.subtitle',
            ],
            [
                'slug'       => 'holy-boss',
                'hero'       => '/assets/img/productos/holy-boss/hero-holy-boss-web.png',
                'nameKey'    => 'catalog.items.holy_boss.name',
                'taglineKey' => 'product.holy_boss.subtitle',
            ],
            [
                'slug'       => 'lady-cupcake',
                'hero'       => '/assets/img/productos/lady-cupcake/hero-lady-cupcake-web.png',
                'nameKey'    => 'catalog.items.lady_cupcake.name',
                'taglineKey' => 'product.lady_cupcake.subtitle',
            ],
            [
                'slug'       => 'nitro-bud',
                'hero'       => '/assets/img/productos/nitro-bud/hero-nitro-bud-web.png',
                'nameKey'    => 'catalog.items.nitro_bud.name',
                'taglineKey' => 'product.nitro_bud.subtitle',
            ],
            [
                'slug'       => 'toxic-mutant',
                'hero'       => '/assets/img/productos/toxic-mutant/hero-toxic-mutat-web.png',
                'nameKey'    => 'catalog.items.toxic_mutant.name',
                'taglineKey' => 'product.toxic_mutant.subtitle',
            ],
        ];
    }

    public function catalogoSecreto(): void
    {
        if (Lang::current() !== 'es') {
            header('Location: ' . self::absoluteCatalogUrl('en'), true, 302);
            exit;
        }
        $this->renderCatalog();
    }

    public function secretCatalog(): void
    {
        if (Lang::current() !== 'en') {
            header('Location: ' . self::absoluteCatalogUrl('es'), true, 302);
            exit;
        }
        $this->renderCatalog();
    }

    private static function absoluteCatalogUrl(string $lang): string
    {
        $suffix = $lang === 'en' ? '/en/secret-catalog' : '/es/catalogo-secreto';

        return rtrim(base_url(), '/') . base_path() . $suffix;
    }

    private function renderCatalog(): void
    {
        $this->render('catalog', [
            'catalogItems'       => self::catalogItems(),
            'pageTitleKey'       => 'catalog.meta.document_title',
            'metaDescriptionKey' => 'catalog.meta.description',
            'htmlThemeStyle'     => self::HTML_THEME_STYLE,
            'metaNoIndex'        => true,
            'checkoutUi'         => false,
        ]);
    }
}
