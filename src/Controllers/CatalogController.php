<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Lang\Lang;

final class CatalogController extends BaseController
{
    private const HTML_THEME_STYLE = '--pc: #a3ff12; --pc-dim: #8fd010; --sec: #00ffcc; --ter: #ff51fa; --bg: #0b0b10; --surf: #0e0e12; --surf-cont: #15151f; --on-pc: #0e1a00; --nav-stroke: #00ffcc;';

    /**
     * Nombres de variedad siempre en inglés (no i18n). Copy y CTA vía claves catalog.* en es.json / en.json.
     *
     * @return list<array{name: string, slug: string, hero: string, copy_key: string, cta_key: string}>
     */
    private static function catalogItems(): array
    {
        return [
            [
                'name'      => 'DJ PIGGY',
                'slug'      => 'dj-piggy',
                'hero'      => '/assets/img/productos/dj-piggy/hero-dj-piggy-web.png',
                'copy_key'  => 'catalog.dj_piggy_copy',
                'cta_key'   => 'catalog.dj_piggy_cta',
            ],
            [
                'name'      => 'HOLY BOSS',
                'slug'      => 'holy-boss',
                'hero'      => '/assets/img/productos/holy-boss/hero-holy-boss-web.png',
                'copy_key'  => 'catalog.holy_boss_copy',
                'cta_key'   => 'catalog.holy_boss_cta',
            ],
            [
                'name'      => 'LADY CUPCAKE',
                'slug'      => 'lady-cupcake',
                'hero'      => '/assets/img/productos/lady-cupcake/hero-lady-cupcake-web.png',
                'copy_key'  => 'catalog.lady_cupcake_copy',
                'cta_key'   => 'catalog.lady_cupcake_cta',
            ],
            [
                'name'      => 'NITRO RACER',
                'slug'      => 'nitro-bud',
                'hero'      => '/assets/img/productos/nitro-bud/hero-nitro-bud-web.png',
                'copy_key'  => 'catalog.nitro_racer_copy',
                'cta_key'   => 'catalog.nitro_racer_cta',
            ],
            [
                'name'      => 'TOXIC MUTANT',
                'slug'      => 'toxic-mutant',
                'hero'      => '/assets/img/productos/toxic-mutant/hero-toxic-mutat-web.png',
                'copy_key'  => 'catalog.toxic_mutant_copy',
                'cta_key'   => 'catalog.toxic_mutant_cta',
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
            'catalogItems'         => self::catalogItems(),
            'pageTitleKey'         => 'catalog.meta.document_title',
            'metaDescriptionKey'   => 'catalog.meta.description',
            'htmlThemeStyle'       => self::HTML_THEME_STYLE,
            'metaNoIndex'          => true,
            'checkoutUi'           => false,
            'useSiteFooterBlock'   => true,
        ]);
    }
}
