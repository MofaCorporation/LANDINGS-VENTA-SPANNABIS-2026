<?php

declare(strict_types=1);

use App\Lang\Lang;

$pageTitleKey         = $pageTitleKey ?? 'site.default_title';
$metaDescriptionKey   = $metaDescriptionKey ?? 'site.default_title';
$htmlThemeStyle        = $htmlThemeStyle ?? '';
$extraModuleSrc = $extraModuleSrc ?? null;

$path      = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$hrefEs    = htmlspecialchars(base_url() . Lang::switchUrl($path, 'es'), ENT_QUOTES, 'UTF-8');
$hrefEn    = htmlspecialchars(base_url() . Lang::switchUrl($path, 'en'), ENT_QUOTES, 'UTF-8');
$hrefSelf  = htmlspecialchars(base_url() . $path, ENT_QUOTES, 'UTF-8');
$langCurrent = Lang::current();
?>
<!DOCTYPE html>
<html class="dark" lang="<?= htmlspecialchars($langCurrent, ENT_QUOTES, 'UTF-8') ?>"<?= $htmlThemeStyle !== '' ? ' style="' . htmlspecialchars($htmlThemeStyle, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= Lang::t($metaDescriptionKey) ?>">
    <title><?= Lang::t($pageTitleKey) ?></title>
    <link rel="canonical" href="<?= $hrefSelf ?>">
    <link rel="alternate" hreflang="es" href="<?= $hrefEs ?>">
    <link rel="alternate" hreflang="en" href="<?= $hrefEn ?>">
    <link rel="alternate" hreflang="x-default" href="<?= $hrefEs ?>">
    <link rel="preconnect" href="https://api.fontshare.com/">
    <link rel="preconnect" href="https://cdn.fontshare.com/">
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/mount-DElUb8cY.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <?php if ($extraModuleSrc !== null && $extraModuleSrc !== '') : ?>
    <script type="module" src="<?= htmlspecialchars($extraModuleSrc, ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endif; ?>
</head>
<body>
<div id="root">
<div class="fixed top-4 right-4 z-50">
    <a href="<?= htmlspecialchars(Lang::switchUrl($path, $langCurrent === 'es' ? 'en' : 'es'), ENT_QUOTES, 'UTF-8') ?>" class="text-xs font-bold uppercase tracking-wide text-white border-2 border-white bg-black px-3 py-1 hover:-translate-y-0.5 transition-transform inline-block"><?= Lang::t('nav.lang_switch') ?></a>
</div>
