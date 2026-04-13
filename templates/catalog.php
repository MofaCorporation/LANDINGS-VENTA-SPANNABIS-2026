<?php

declare(strict_types=1);

use App\Lang\Lang;

/** @var list<array{slug: string, hero: string, nameKey: string, taglineKey: string}> $catalogItems */

$logo = asset_url('/assets/img/ui/logo-tarumbas-farm.png');
?>
<header class="tf-catalog-site-header">
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
            width: 100% !important; max-width: 100% !important; flex: 0 0 auto !important;
            border-right: none !important; border-bottom: 3px solid #000 !important;
          }
          a.catalog-secret-card > div:last-child {
            width: 100% !important; max-width: 100% !important; flex: 0 0 auto !important;
          }
        }
        </style>

        <div style="display:flex;flex-direction:column;align-items:center;width:100%;box-sizing:border-box;">
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
              max-width:900px;
              margin:0 auto 2rem;
              background:#1a1020;
              border:3px solid #000;
              box-shadow:6px 6px 0 #000;
              text-decoration:none;
              color:#fff;
              overflow:hidden;
            ">
                <div style="flex:0 0 50%;width:50%;max-width:50%;box-sizing:border-box;height:300px;overflow:hidden;background:#0b0b10;border-right:3px solid #000;">
                    <img src="<?= htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="800" height="600" loading="lazy" decoding="async" style="width:100%;height:100%;object-fit:cover;object-position:center;display:block;margin:0;padding:0;border:0;">
                </div>
                <div style="flex:0 0 50%;width:50%;max-width:50%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:space-between;padding:1.25rem 1rem;min-width:0;overflow-wrap:break-word;">
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

<section class="tf-catalog-legal" aria-label="<?= Lang::t('footer.legal') ?>">
    <p class="tf-catalog-legal__text"><?= Lang::t('product.common.legal') ?></p>
</section>

<footer class="tf-catalog-footer">
    <div class="tf-catalog-footer__inner">
        <div class="tf-catalog-footer__logo-wrap">
            <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t('site.brand') ?>" width="320" height="107" class="tf-catalog-footer__logo" decoding="async">
        </div>
        <div class="tf-catalog-footer__links">
            <a class="tf-catalog-footer__link" href="#"><?= Lang::t('footer.terms') ?></a>
            <a class="tf-catalog-footer__link" href="#"><?= Lang::t('footer.legal') ?></a>
            <a class="tf-catalog-footer__link" href="#"><?= Lang::t('footer.ig') ?></a>
            <a class="tf-catalog-footer__link" href="#"><?= Lang::t('footer.x') ?></a>
        </div>
        <div class="tf-catalog-footer__copy"><?= Lang::t('footer.copy', ['year' => (string) date('Y')]) ?></div>
    </div>
</footer>
