<?php

declare(strict_types=1);

use App\Lang\Lang;

/** @var string|null $checkoutVariety */
$checkoutVariety = $checkoutVariety ?? null;
$retryHref       = ($checkoutVariety !== null && $checkoutVariety !== '')
    ? url_lang('/checkout') . '?variety=' . rawurlencode($checkoutVariety)
    : url_lang('/');
$langHref = htmlspecialchars(base_path() . Lang::switchUrl($_SERVER['REQUEST_URI'] ?? '/', Lang::current() === 'es' ? 'en' : 'es'), ENT_QUOTES, 'UTF-8');
$logoUrl  = asset_url('/assets/img/ui/logo-tarumbas-farm.png');
?>
<header class="checkout-header">
  <div class="checkout-header__inner">
    <a class="checkout-logo" href="<?= htmlspecialchars(url_lang('/'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= Lang::t('site.brand') ?>">
      <img class="checkout-logo__img" src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="1024" height="1024" decoding="async">
    </a>
    <h1 class="checkout-title"><?= Lang::t('checkout.ko_heading') ?></h1>
    <a class="checkout-lang" href="<?= $langHref ?>"><?= Lang::t('nav.lang_switch') ?></a>
  </div>
</header>

<div class="checkout-wrap">
  <div class="tf-card" style="text-align:center;padding:2rem 1.25rem 2.25rem">
    <p class="checkout-result-lead"><?= Lang::t('checkout.ko_lead') ?></p>
    <a class="checkout-back-link" href="<?= htmlspecialchars($retryHref, ENT_QUOTES, 'UTF-8') ?>"><?= Lang::t('checkout.ko_retry') ?></a>
    <p style="margin-top:1rem">
      <a class="checkout-foot__mailto" href="<?= htmlspecialchars(url_lang('/lady-cupcake'), ENT_QUOTES, 'UTF-8') ?>"><?= Lang::t('checkout.ko_home') ?></a>
    </p>
  </div>
</div>

<footer class="checkout-foot">
  <p><?= Lang::t('checkout.foot_legal') ?></p>
  <p class="checkout-foot__help"><?= Lang::t('checkout.foot_help_prefix') ?> <a class="checkout-foot__mailto" href="mailto:tarumbasfarm@gmail.com">tarumbasfarm@gmail.com</a></p>
</footer>
