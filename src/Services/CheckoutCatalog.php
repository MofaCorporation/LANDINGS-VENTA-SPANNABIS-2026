<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;

final class CheckoutCatalog
{
    /** @var array<string, array{price_cents: int, hero: string, meta_key: string}>|null */
    private static ?array $json = null;

    /** @return list<string> */
    public static function varietySlugs(): array
    {
        self::loadJson();

        return array_keys(self::$json ?? []);
    }

    /**
     * @return array{
     *   variety: string,
     *   meta_key: string,
     *   title_lang_key: string,
     *   hero: string,
     *   price_cents: int,
     *   product_id: int
     * }|null
     */
    public static function resolve(string $variety): ?array
    {
        self::loadJson();
        if (self::$json === null || !isset(self::$json[$variety])) {
            return null;
        }

        $entry  = self::$json[$variety];
        $dbRow  = Product::findBySlug($variety);
        $price  = $dbRow !== null ? (int) $dbRow['price_cents'] : (int) $entry['price_cents'];
        $hero   = $dbRow !== null && ($dbRow['image'] ?? '') !== '' ? (string) $dbRow['image'] : (string) $entry['hero'];
        $prodId = $dbRow !== null ? (int) $dbRow['id'] : 0;

        if ($prodId === 0) {
            return null;
        }

        $metaKey = (string) $entry['meta_key'];

        return [
            'variety'           => $variety,
            'meta_key'          => $metaKey,
            'title_lang_key'    => 'meta.' . $metaKey . '.document_title',
            'tagline_lang_key'  => 'product.' . $metaKey . '.lead',
            'hero'              => $hero,
            'price_cents'       => $price,
            'product_id'        => $prodId,
        ];
    }

    /**
     * @return list<array{
     *   variety: string,
     *   meta_key: string,
     *   title_lang_key: string,
     *   tagline_lang_key: string,
     *   hero: string,
     *   price_cents: int,
     *   product_id: int
     * }>
     */
    public static function allResolved(): array
    {
        $out = [];
        foreach (self::varietySlugs() as $slug) {
            $row = self::resolve($slug);
            if ($row !== null) {
                $out[] = $row;
            }
        }

        return $out;
    }

    private static function loadJson(): void
    {
        if (self::$json !== null) {
            return;
        }

        $path = dirname(__DIR__) . '/Lang/catalog.json';
        if (!is_readable($path)) {
            self::$json = [];

            return;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            self::$json = [];

            return;
        }

        $data = json_decode($raw, true);
        self::$json = is_array($data) ? $data : [];
    }
}
