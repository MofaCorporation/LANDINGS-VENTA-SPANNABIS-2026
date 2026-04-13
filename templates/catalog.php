<?php

declare(strict_types=1);

use App\Lang\Lang;

/** @var list<array{slug: string, hero: string, nameKey: string, taglineKey: string}> $catalogItems */

$logo = asset_url('/assets/img/ui/logo-tarumbas-farm.png');

if (!isset($langSwitchHref, $langSwitchFlagSrc, $langSwitchAlt, $langSwitchText)) {
    $requestUriLang = $_SERVER['REQUEST_URI'] ?? '/';
    $langCur        = Lang::current();
    $langSwitchHref = htmlspecialchars(
        base_path() . Lang::switchUrl($requestUriLang, $langCur === 'es' ? 'en' : 'es'),
        ENT_QUOTES,
        'UTF-8'
    );
    $langSwitchFlagSrc = $langCur === 'es' ? 'https://flagcdn.com/24x18/gb.png' : 'https://flagcdn.com/24x18/es.png';
    $langSwitchAlt     = $langCur === 'es' ? 'EN' : 'ES';
    $langSwitchText    = $langCur === 'es' ? 'ENGLISH' : 'ESPAÑOL';
}
?>
<style>
/* Una sola fila de idioma: la del header del catálogo (misma UI que landings) */
#root > .tf-lang-switch { display: none !important; }
</style>

<header class="tf-catalog-site-header">
    <div style="position:absolute;top:1rem;right:1rem;z-index:20;">
        <a href="<?= $langSwitchHref ?>" class="tf-lang-switch__btn"><img src="<?= htmlspecialchars($langSwitchFlagSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($langSwitchAlt, ENT_QUOTES, 'UTF-8') ?>" width="24" height="18" style="width:20px;height:auto;vertical-align:middle;margin-right:4px;display:inline-block"> <?= htmlspecialchars($langSwitchText, ENT_QUOTES, 'UTF-8') ?></a>
    </div>
    <div class="tf-catalog-site-header__inner">
        <a href="<?= htmlspecialchars(url_lang('/'), ENT_QUOTES, 'UTF-8') ?>" class="tf-catalog-site-header__logo-link">
            <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t('site.brand') ?>" width="320" height="107" class="tf-catalog-site-header__logo" decoding="async">
        </a>
    </div>
</header>

<main class="tf-catalog-main">
    <div class="tf-catalog-main__inner">
        <div class="tf-catalog-intro">
            <h1 class="tf-catalog-intro__title tf-title-bangers"><?= Lang::t('catalog.heading') ?></h1>
            <p class="tf-catalog-intro__sub"><?= Lang::t('catalog.subheading') ?></p>
        </div>

        <style>
        @media (max-width: 767px) {
          a.catalog-secret-card { flex-direction: column !important; flex-wrap: nowrap !important; }
          a.catalog-secret-card > div:first-child {
            width: 100% !important; max-width: 100% !important; flex: 0 0 auto !important; height: 200px !important; min-height: 200px !important;
            border-right: none !important; border-bottom: 3px solid #000 !important;
          }
          a.catalog-secret-card > div:last-child {
            width: 100% !important; max-width: 100% !important; flex: 0 0 auto !important;
          }
        }
        </style>

        <div style="display:flex;flex-direction:column;align-items:center;width:100%;max-width:700px;margin:0 auto;box-sizing:border-box;">
            <?php foreach ($catalogItems as $item) :
                $heroUrl = asset_url($item['hero']);
                $landingHref = url_lang('/' . $item['slug']);
                ?>
            <a class="catalog-secret-card" href="<?= htmlspecialchars($landingHref, ENT_QUOTES, 'UTF-8') ?>" style="
              display:flex;
              flex-direction:row;
              align-items:stretch;
              flex-wrap:nowrap;
              box-sizing:border-box;
              width:100%;
              max-width:700px;
              margin:0 auto 4rem;
              background:#1a1020;
              border:3px solid #000;
              box-shadow:6px 6px 0 #000;
              text-decoration:none;
              color:#fff;
              overflow:hidden;
            ">
                <div style="flex:0 0 50%;width:50%;max-width:50%;box-sizing:border-box;height:200px;min-height:200px;overflow:hidden;background:#0b0b10;border-right:3px solid #000;">
                    <img src="<?= htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="800" height="600" loading="lazy" decoding="async" style="width:100%;height:100%;object-fit:cover;object-position:center;display:block;margin:0;padding:0;border:0;">
                </div>
                <div style="flex:0 0 50%;width:50%;max-width:50%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:space-between;padding:1.5rem;min-width:0;overflow-wrap:break-word;">
                    <div>
                        <h2 style="margin:0;font-family:Bangers,'Segoe UI',Impact,sans-serif;font-size:clamp(1.35rem,3.5vw,2.35rem);font-weight:400;line-height:1;text-transform:uppercase;color:#a3ff12;"><?= Lang::t($item['nameKey']) ?></h2>
                        <p style="margin:0.65rem 0 0;font-family:system-ui,-apple-system,sans-serif;font-size:0.95rem;font-weight:700;line-height:1.35;color:#e4e2e2;"><?= Lang::t($item['taglineKey']) ?></p>
                    </div>
                    <span style="display:block;box-sizing:border-box;margin-top:1.1rem;text-align:center;padding:0.65rem 0.75rem;border:3px solid #000;background:#a3ff12;color:#0e1a00;font-family:system-ui,-apple-system,sans-serif;font-size:0.78rem;font-weight:900;letter-spacing:0.06em;text-transform:uppercase;box-shadow:0 0 12px rgba(163,255,18,0.55),4px 4px 0 #000;"><?= Lang::t('catalog.cta_see_more') ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php
/* Mismo contenido que landing.php (aviso legal + footer). Estilos embebidos: el bundle Tailwind puede no incluir estas utilidades en catálogo. */
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
