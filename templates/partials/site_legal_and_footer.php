<?php

declare(strict_types=1);

use App\Lang\Lang;

/** @var string $logo URL del logo (asset_url) — definido en la plantilla de página antes del footer */
if (!isset($logo) || !is_string($logo)) {
    $logo = asset_url('/assets/img/ui/logo-tarumbas-farm.png');
}
?>
<style>
.catalog-landing-legal { border-top: 4px solid #1a1919; background: #000; color: #fff; padding: 2.5rem 1.5rem 1.5rem; text-align: center; }
.catalog-landing-legal p { max-width: 48rem; margin: 0 auto; font-family: var(--font-body, system-ui, -apple-system, sans-serif); font-size: 0.875rem; line-height: 1.6; color: #e4e2e2; }
@media (min-width: 768px) { .catalog-landing-legal p { font-size: 1rem; } }
.catalog-landing-footer { border-top: 4px solid var(--surf-cont, #15151f); background: var(--bg, #0b0b10); color: #fff; padding: 2rem 1.5rem 2.5rem; }
.catalog-landing-footer__inner { max-width: 80rem; margin: 0 auto; display: flex; flex-direction: column; align-items: center; gap: 1.5rem; }
@media (min-width: 768px) {
  .catalog-landing-footer__inner { flex-direction: row; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 2rem; }
}
.catalog-landing-footer__logo { height: 3.5rem; width: auto; max-width: min(100%, 300px); object-fit: contain; display: block; }
@media (min-width: 768px) { .catalog-landing-footer__logo { height: 4.5rem; max-width: 360px; } }
@media (min-width: 1024px) { .catalog-landing-footer__logo { height: 5rem; max-width: 400px; } }
.catalog-landing-footer__links { display: flex; flex-wrap: wrap; justify-content: center; gap: 2rem; }
.catalog-landing-footer__links a { font-family: var(--font-headline, system-ui, -apple-system, sans-serif); font-size: 0.75rem; font-weight: 700; color: #a0a0a0; text-decoration: none; transition: color 0.15s ease; }
.catalog-landing-footer__links a:hover { color: #fff; text-decoration: line-through; }
.catalog-landing-footer__copy { font-family: var(--font-headline, system-ui, -apple-system, sans-serif); font-size: 0.75rem; font-weight: 700; color: var(--sec, #00ffcc); }
</style>

<section class="catalog-landing-legal" aria-label="<?= Lang::t('footer.legal') ?>">
<p><?= Lang::t('product.common.legal') ?></p>
</section>

<footer class="catalog-landing-footer">
<div class="catalog-landing-footer__inner">
<div>
<img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t('site.brand') ?>" width="320" height="107" class="catalog-landing-footer__logo" decoding="async">
</div>
<div class="catalog-landing-footer__links">
<a href="#"><?= Lang::t('footer.terms') ?></a>
<a href="#"><?= Lang::t('footer.legal') ?></a>
<a href="#"><?= Lang::t('footer.ig') ?></a>
<a href="#"><?= Lang::t('footer.x') ?></a>
</div>
<div class="catalog-landing-footer__copy"><?= Lang::t('footer.copy', ['year' => (string) date('Y')]) ?></div>
</div>
</footer>
