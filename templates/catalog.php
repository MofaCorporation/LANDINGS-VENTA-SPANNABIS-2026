<?php

declare(strict_types=1);

use App\Lang\Lang;

/** @var list<array{slug: string, hero: string, nameKey: string, taglineKey: string}> $catalogItems */

$logo = asset_url('/assets/img/ui/logo-tarumbas-farm.png');
?>
<header class="border-b-8 border-black bg-[var(--surf)] px-6 pb-8 pt-24 text-white md:pt-28">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-center gap-6 text-center">
        <a href="<?= htmlspecialchars(url_lang('/'), ENT_QUOTES, 'UTF-8') ?>" class="inline-block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--sec)] focus-visible:ring-offset-4 focus-visible:ring-offset-[var(--bg)]">
            <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t('site.brand') ?>" width="320" height="107" class="h-16 w-auto max-w-[min(100%,280px)] object-contain md:h-20 md:max-w-[320px]" decoding="async">
        </a>
    </div>
</header>

<main class="bg-[var(--bg)] px-6 py-14 text-white md:py-20">
    <div class="mx-auto max-w-7xl">
        <div class="mb-12 text-center md:mb-16">
            <h1 class="tf-title-bangers mb-4 text-5xl uppercase text-[var(--pc)] md:text-7xl lg:text-8xl"><?= Lang::t('catalog.heading') ?></h1>
            <p class="font-[family-name:var(--font-headline)] text-xl font-bold text-[var(--sec)] md:text-2xl"><?= Lang::t('catalog.subheading') ?></p>
        </div>

        <div class="tf-catalog-stack">
            <?php foreach ($catalogItems as $item) :
                $heroUrl = asset_url($item['hero']);
                $landingHref = url_lang('/' . $item['slug']);
                ?>
            <article class="tf-catalog-card tf-panel-sticker">
                <a href="<?= htmlspecialchars($landingHref, ENT_QUOTES, 'UTF-8') ?>" class="tf-catalog-card__media-link focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--sec)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--bg)]">
                    <div class="tf-catalog-card__media">
                        <img src="<?= htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="tf-catalog-card__img" width="800" height="600" loading="lazy" decoding="async">
                    </div>
                </a>
                <div class="tf-catalog-card__body">
                    <div class="tf-catalog-card__text">
                        <h2 class="tf-catalog-card__title tf-title-bangers"><?= Lang::t($item['nameKey']) ?></h2>
                        <p class="tf-catalog-card__tagline"><?= Lang::t($item['taglineKey']) ?></p>
                    </div>
                    <div class="tf-catalog-card__cta-wrap">
                        <a href="<?= htmlspecialchars($landingHref, ENT_QUOTES, 'UTF-8') ?>" class="tf-catalog-card__cta"><?= Lang::t('catalog.cta_see_more') ?></a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<section class="border-t-4 border-[#1a1919] bg-black px-6 pb-6 pt-12 text-white">
    <div class="max-w-3xl mx-auto text-center">
        <p class="font-[family-name:var(--font-body)] text-sm md:text-base text-[#e4e2e2] leading-relaxed"><?= Lang::t('product.common.legal') ?></p>
    </div>
</section>

<footer class="border-t-4 border-[var(--surf-cont)] bg-[var(--bg)] px-6 py-8 text-white md:py-10">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 md:flex-row md:gap-8">
        <div class="flex shrink-0 items-center justify-center md:justify-start">
            <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t('site.brand') ?>" width="320" height="107" class="h-14 w-auto max-w-[min(100%,300px)] object-contain object-center md:h-[4.5rem] md:max-w-[360px] lg:h-20 lg:max-w-[400px]" decoding="async">
        </div>
        <div class="flex gap-8 flex-wrap justify-center">
            <a class="font-[family-name:var(--font-headline)] text-xs font-bold text-[#a0a0a0] hover:text-white hover:line-through transition-all" href="#"><?= Lang::t('footer.terms') ?></a>
            <a class="font-[family-name:var(--font-headline)] text-xs font-bold text-[#a0a0a0] hover:text-white hover:line-through transition-all" href="#"><?= Lang::t('footer.legal') ?></a>
            <a class="font-[family-name:var(--font-headline)] text-xs font-bold text-[#a0a0a0] hover:text-white hover:line-through transition-all" href="#"><?= Lang::t('footer.ig') ?></a>
            <a class="font-[family-name:var(--font-headline)] text-xs font-bold text-[#a0a0a0] hover:text-white hover:line-through transition-all" href="#"><?= Lang::t('footer.x') ?></a>
        </div>
        <div class="font-[family-name:var(--font-headline)] text-xs font-bold text-[var(--sec)]"><?= Lang::t('footer.copy', ['year' => (string) date('Y')]) ?></div>
    </div>
</footer>
