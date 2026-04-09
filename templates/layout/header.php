<?php

declare(strict_types=1);

use App\Lang\Lang;

$pageTitleKey       = $pageTitleKey ?? 'site.default_title';
$metaDescriptionKey = $metaDescriptionKey ?? 'site.default_title';
$htmlThemeStyle     = $htmlThemeStyle ?? '';
$extraModuleSrc     = $extraModuleSrc ?? null;
$checkoutUi         = !empty($checkoutUi);

$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$path        = Lang::requestPath($requestUri);
$hrefEs      = htmlspecialchars(base_url() . Lang::switchUrl($requestUri, 'es'), ENT_QUOTES, 'UTF-8');
$hrefEn      = htmlspecialchars(base_url() . Lang::switchUrl($requestUri, 'en'), ENT_QUOTES, 'UTF-8');
$hrefSelf    = htmlspecialchars(base_url() . $path, ENT_QUOTES, 'UTF-8');
$langCurrent = Lang::current();
$langSwitchHref = htmlspecialchars(base_path() . Lang::switchUrl($requestUri, $langCurrent === 'es' ? 'en' : 'es'), ENT_QUOTES, 'UTF-8');
$langSwitchLabel = Lang::t('nav.lang_switch');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCurrent, ENT_QUOTES, 'UTF-8') ?>"<?= !$checkoutUi && $htmlThemeStyle !== '' ? ' class="dark" style="' . htmlspecialchars($htmlThemeStyle, ENT_QUOTES, 'UTF-8') . '"' : (!$checkoutUi ? ' class="dark"' : '') ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= Lang::t($metaDescriptionKey) ?>">
    <title><?= Lang::t($pageTitleKey) ?></title>
    <link rel="canonical" href="<?= $hrefSelf ?>">
    <link rel="alternate" hreflang="es" href="<?= $hrefEs ?>">
    <link rel="alternate" hreflang="en" href="<?= $hrefEn ?>">
    <link rel="alternate" hreflang="x-default" href="<?= $hrefEs ?>">
    <?php if ($checkoutUi) : ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://api.fontshare.com">
    <link rel="preconnect" href="https://cdn.fontshare.com">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/checkout-fonts-satoshi.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/checkout.css'), ENT_QUOTES, 'UTF-8') ?>">
    <?php else : ?>
    <link rel="preconnect" href="https://api.fontshare.com/">
    <link rel="preconnect" href="https://cdn.fontshare.com/">
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/mount-DElUb8cY.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/main.css'), ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <?php if ($extraModuleSrc !== null && $extraModuleSrc !== '') : ?>
    <script type="module" src="<?= htmlspecialchars(asset_url($extraModuleSrc), ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endif; ?>
</head>
<body<?= $checkoutUi ? ' class="checkout-page"' : '' ?>>
<?php if (!$checkoutUi) : ?>
<div id="root">
<div class="fixed top-4 right-4 z-50">
    <?php echo '<!-- DEBUG switchUrl: ' .
    Lang::switchUrl($_SERVER['REQUEST_URI'] ?? '/',
    Lang::current() === 'es' ? 'en' : 'es') .
    ' | REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'null') .
    ' -->'; ?>
    <a href="<?= $langSwitchHref ?>" class="text-xs font-bold uppercase tracking-wide text-white border-2 border-white bg-black px-3 py-1 hover:-translate-y-0.5 transition-transform inline-block"><?= $langSwitchLabel ?></a>
</div>
<?php endif; ?>
