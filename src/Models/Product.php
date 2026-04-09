<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Product
{
    /**
     * @return array{
     *   id: int,
     *   slug_es: string,
     *   slug_en: string,
     *   name_es: string,
     *   name_en: string,
     *   price_cents: int,
     *   image: string|null,
     *   active: int
     * }|null
     */
    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::get();
        $st  = $pdo->prepare(
            'SELECT id, slug_es, slug_en, name_es, name_en, price_cents, image, active
             FROM products
             WHERE (slug_es = :s OR slug_en = :s2) AND active = 1
             LIMIT 1',
        );
        $st->execute(['s' => $slug, 's2' => $slug]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}
