<?php

declare(strict_types=1);

use App\Lang\Lang;

?><!DOCTYPE html>
<html class="dark" lang="<?= htmlspecialchars(Lang::current(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 · <?= Lang::t('site.default_title') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/mount-DElUb8cY.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/main.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="bg-black text-white min-h-screen flex items-center justify-center px-6">
    <div class="text-center max-w-md">
        <h1 class="tf-title-bangers text-5xl uppercase mb-4">404</h1>
        <p class="font-[family-name:var(--font-body)] text-[#e4e2e2] mb-8"><?= Lang::t('errors.not_found') ?></p>
        <a href="<?= htmlspecialchars(url_lang('/'), ENT_QUOTES, 'UTF-8') ?>" class="inline-block border-2 border-white px-6 py-2 uppercase font-bold text-sm hover:-translate-y-1 transition-transform"><?= Lang::t('nav.home') ?></a>
    </div>
</body>
</html>
