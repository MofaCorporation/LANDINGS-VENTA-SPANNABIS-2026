<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Lead
{
    public static function upsert(string $email, string $name, string $lang, string $source = 'checkout'): void
    {
        $email = trim($email);
        $name  = trim($name);
        $lang  = strtolower(substr(trim($lang), 0, 2));
        $source = trim($source);

        if ($email === '') {
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        if ($lang !== 'en' && $lang !== 'es') {
            $lang = 'es';
        }
        if ($source === '') {
            $source = 'checkout';
        }

        $pdo = Database::get();

        $sql = 'INSERT INTO leads (email, name, lang, source)
                VALUES (:email, :name, :lang, :source)
                ON DUPLICATE KEY UPDATE
                  name = VALUES(name),
                  lang = VALUES(lang),
                  source = VALUES(source)';

        $st = $pdo->prepare($sql);
        $st->execute([
            'email'  => $email,
            'name'   => $name !== '' ? $name : null,
            'lang'   => $lang,
            'source' => $source,
        ]);
    }
}

