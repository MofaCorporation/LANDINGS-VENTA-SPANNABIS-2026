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

        <div class="tf-catalog-stack">
            <?php foreach ($catalogItems as $item) :
                $heroUrl = asset_url($item['hero']);
                $landingHref = url_lang('/' . $item['slug']);
                ?>
            <a href="<?= htmlspecialchars($landingHref, ENT_QUOTES, 'UTF-8') ?>" class="tf-catalog-card tf-panel-sticker">
                <div class="tf-catalog-card__media">
                    <img src="<?= htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="tf-catalog-card__img" width="800" height="600" loading="lazy" decoding="async">
                </div>
                <div class="tf-catalog-card__body">
                    <div class="tf-catalog-card__text">
                        <h2 class="tf-catalog-card__title tf-title-bangers"><?= Lang::t($item['nameKey']) ?></h2>
                        <p class="tf-catalog-card__tagline"><?= Lang::t($item['taglineKey']) ?></p>
                    </div>
                    <div class="tf-catalog-card__cta-wrap">
                        <span class="tf-catalog-card__cta"><?= Lang::t('catalog.cta_see_more') ?></span>
                    </div>
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
