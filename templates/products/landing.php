<?php

declare(strict_types=1);

use App\Lang\Lang;

/**
 * @var array{
 *   prefix: string,
 *   variety: string,
 *   hero: string,
 *   buyEs: string,
 *   buyEn: string,
 *   h1: array{0: string, 1: string},
 *   paragraphCount: int,
 *   listCount: int,
 *   charCount: int,
 *   symptoms: list<array{icon: string, border: 'pc'|'sec'|'ter'|'white'}>
 * } $landing
 */
$L           = $landing;
$prefix      = $L['prefix'];
$buyImg      = asset_url(Lang::current() === 'es' ? $L['buyEs'] : $L['buyEn']);
$checkoutUrl = url_lang('/checkout') . '?variety=' . rawurlencode($L['variety']);
$logo        = asset_url('/assets/img/ui/logo-tarumbas-farm.png');
$heroUrl     = asset_url($L['hero']);

$borderStyle = static function (string $b): string {
    $c = $b === 'white' ? '#ffffff' : 'var(--' . $b . ')';

    return 'border-color:' . $c . ';box-shadow:8px 8px 0 0 ' . $c;
};

$iconColor = static function (string $b): string {
    return $b === 'white' ? '#ffffff' : 'var(--' . $b . ')';
};
?>
<header class="relative flex min-h-screen flex-col justify-center items-start overflow-x-hidden bg-[var(--bg)] px-6 pb-12 pt-10 text-white md:pb-14 md:pt-12">
<div class="absolute inset-0 z-0 opacity-40 mix-blend-screen pointer-events-none">
<img class="w-full h-full object-cover" alt="" src="<?= htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') ?>" width="1200" height="800" fetchpriority="high">
</div>
<div class="relative z-10 max-w-4xl">
<h1 class="tf-title-bangers text-7xl md:text-9xl leading-[0.85] uppercase mb-4 text-[var(--pc)]"><?= htmlspecialchars($L['h1'][0], ENT_QUOTES, 'UTF-8') ?><br><?= htmlspecialchars($L['h1'][1], ENT_QUOTES, 'UTF-8') ?></h1>
<p class="font-[family-name:var(--font-headline)] font-bold text-2xl md:text-4xl text-[var(--sec)] mb-8 bg-black inline-block px-4 py-1 -rotate-1 border-2 border-[var(--sec)]"><?= Lang::t($prefix . '.subtitle') ?></p>
<p class="font-[family-name:var(--font-body)] text-lg md:text-xl text-[#f0f0f0] mb-6 max-w-2xl"><?= Lang::t($prefix . '.lead') ?></p>
<div class="mt-8 flex flex-wrap gap-4 md:mt-10">
<a href="<?= htmlspecialchars($checkoutUrl, ENT_QUOTES, 'UTF-8') ?>" class="group relative z-20 inline-block max-w-full overflow-visible border-0 bg-transparent p-0 align-top shadow-none outline-none focus-visible:ring-2 focus-visible:ring-[var(--sec)] focus-visible:ring-offset-4 focus-visible:ring-offset-[var(--bg)] transition-transform active:scale-[0.98]"><img src="<?= htmlspecialchars($buyImg, ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t('product.common.buy_alt') ?>" width="480" height="200" class="relative z-20 h-auto w-auto max-w-[min(100vw-3rem,320px)] object-contain object-left drop-shadow-[2px_2px_0_var(--nav-stroke)] transition-transform group-hover:translate-x-0.5 sm:max-w-[360px] md:max-w-[440px]" decoding="async" fetchpriority="high"></a>
</div>
</div>
</header>

<section class="relative border-t-8 border-[var(--pc)] bg-[var(--surf)] px-6 py-16 text-white md:py-20">
<div class="max-w-6xl mx-auto flex flex-col gap-8 md:flex-row md:items-start md:gap-10">
<div class="w-full md:w-1/2">
<div class="bg-[var(--surf-cont)] p-8 border-l-8 border-[var(--pc)] relative">
<h2 class="tf-title-bangers mb-5 text-5xl uppercase text-white"><?= Lang::t($prefix . '.block_title') ?></h2>
<?php for ($i = 1; $i <= $L['paragraphCount']; $i++) : ?>
<p class="font-[family-name:var(--font-body)] text-2xl font-normal leading-relaxed mb-4 text-[#f6f6f6]"><?= Lang::t($prefix . '.p' . $i) ?></p>
<?php endfor; ?>
<p class="font-[family-name:var(--font-headline)] font-bold text-xl text-[var(--pc)] mb-4"><?= Lang::t($prefix . '.tagline') ?></p>
<p class="font-[family-name:var(--font-body)] text-lg text-[#e4e2e2] mb-4"><?= Lang::t($prefix . '.spec_lead') ?></p>
<ul class="list-disc pl-6 space-y-1">
<?php for ($i = 1; $i <= $L['listCount']; $i++) : ?>
<li class="font-[family-name:var(--font-body)] text-lg text-[#e4e2e2]"><?= Lang::t($prefix . '.li' . $i) ?></li>
<?php endfor; ?>
</ul>
<div class="absolute -top-10 -right-4 bg-[var(--ter)] p-4 font-[family-name:var(--font-headline)] text-sm font-black uppercase tracking-wide text-black -rotate-12 border-4 border-black sticker-shadow md:-right-10 md:text-base"><?= Lang::t($prefix . '.sticker') ?></div>
</div>
</div>
<div class="flex w-full flex-col gap-6 md:mt-24 md:w-1/2">
<div class="relative shrink-0">
<img class="w-full grayscale brightness-150 contrast-125 border-8 border-black sticker-shadow" alt="" src="<?= htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') ?>" width="900" height="900" loading="lazy">
<div class="absolute inset-0 bg-[var(--pc)]/20 mix-blend-multiply pointer-events-none"></div>
</div>
<aside class="tf-panel-sticker w-full bg-[var(--surf-cont)] p-6 md:p-8" aria-label="<?= Lang::t($prefix . '.chars_aria') ?>">
<h3 class="tf-title-bangers mb-4 text-2xl uppercase text-[var(--pc)] md:mb-5 md:text-3xl"><?= Lang::t('product.common.chars_heading') ?></h3>
<ul class="list-none space-y-3">
<?php for ($i = 1; $i <= $L['charCount']; $i++) : ?>
<li class="flex gap-3"><span class="shrink-0 font-[family-name:var(--font-headline)] font-bold text-[var(--pc)]" aria-hidden="true">•</span><span class="font-[family-name:var(--font-body)] text-base leading-snug text-[#f2f2f2] md:text-lg"><?= Lang::t($prefix . '.c' . $i) ?></span></li>
<?php endfor; ?>
</ul>
</aside>
</div>
</div>
</section>

<section class="bg-black px-6 py-16 text-white md:py-20">
<div class="max-w-7xl mx-auto">
<div class="mb-10">
<span class="bg-[var(--sec)] text-black font-black px-4 py-1 text-xl uppercase tracking-widest"><?= Lang::t('product.common.symptoms_label') ?></span>
<h2 class="tf-title-bangers mt-3 text-6xl uppercase text-white"><?= Lang::t('product.common.symptoms_heading') ?></h2>
</div>
<div class="grid grid-cols-1 gap-5 md:grid-cols-2 md:gap-6 lg:grid-cols-4">
<?php foreach ($L['symptoms'] as $idx => $sym) :
    $n = $idx + 1;
    $st = $prefix . '.s' . $n . 't';
    $sd = $prefix . '.s' . $n . 'd';
    ?>
<div class="bg-[var(--surf-cont)] border-4 p-8 flex flex-col gap-6 hover:-translate-y-2 transition-transform cursor-pointer" style="<?= htmlspecialchars($borderStyle($sym['border']), ENT_QUOTES, 'UTF-8') ?>">
<span class="material-symbols-outlined text-6xl" style="color:<?= htmlspecialchars($iconColor($sym['border']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sym['icon'], ENT_QUOTES, 'UTF-8') ?></span>
<h3 class="tf-title-bangers text-3xl uppercase text-white"><?= Lang::t($st) ?></h3>
<p class="font-[family-name:var(--font-body)] text-[#e4e2e2]"><?= Lang::t($sd) ?></p>
</div>
<?php endforeach; ?>
</div>
</div>
</section>

<section class="border-b-8 border-black bg-[var(--bg)] px-6 py-14 text-white md:py-16" aria-labelledby="tf-criterio-heading">
<div class="mx-auto grid max-w-5xl gap-8 md:grid-cols-2 md:items-start md:gap-10">
<div>
<h2 id="tf-criterio-heading" class="tf-title-bangers mb-4 text-4xl uppercase text-white md:mb-5 md:text-6xl"><?= Lang::t('product.common.criteria_heading') ?></h2>
<p class="mb-6 font-[family-name:var(--font-headline)] text-xl font-black uppercase leading-tight tracking-wide text-[var(--sec)] md:text-2xl"><?= Lang::t('product.common.criteria_tagline') ?></p>
<ul class="space-y-3 border-l-4 border-[var(--pc)] pl-5 md:space-y-4 md:pl-6">
<li class="font-[family-name:var(--font-body)] text-lg leading-relaxed text-[#f0f0f0] md:text-xl"><?= Lang::t($prefix . '.crit1') ?></li>
<li class="font-[family-name:var(--font-body)] text-lg leading-relaxed text-[#f0f0f0] md:text-xl"><?= Lang::t($prefix . '.crit2') ?></li>
<li class="font-[family-name:var(--font-body)] text-lg leading-relaxed text-[#f0f0f0] md:text-xl"><?= Lang::t($prefix . '.crit3') ?></li>
</ul>
</div>
<aside class="tf-panel-sticker w-full bg-[var(--surf-cont)] p-6 md:mt-12 md:p-8" aria-label="<?= Lang::t($prefix . '.made_for_aria') ?>">
<p class="tf-bangers-fill mb-4 text-2xl uppercase leading-none text-[var(--pc)] md:mb-5 md:text-3xl"><?= Lang::t('product.common.made_for_prefix') ?></p>
<p class="font-[family-name:var(--font-body)] text-base leading-relaxed text-[#f2f2f2] md:text-lg"><?= Lang::t($prefix . '.made_for') ?></p>
</aside>
</div>
</section>

<section class="border-t-4 border-[#1a1919] bg-black px-6 pb-6 pt-12 text-white">
<div class="max-w-3xl mx-auto text-center">
<p class="font-[family-name:var(--font-body)] text-sm md:text-base text-[#e4e2e2] leading-relaxed"><?= Lang::t('product.common.legal') ?></p>
</div>
</section>

<section class="flex flex-col items-center justify-center bg-black px-6 pb-20 pt-10 text-white md:pb-24 md:pt-12">
<div class="max-w-4xl text-center">
<?php
$dareKey     = $prefix . '.dare.text';
$dareRaw     = Lang::raw($dareKey);
$hasDareText = $dareRaw !== $dareKey && $dareRaw !== '';
?>
<h2 class="tf-title-bangers <?= $hasDareText ? 'mb-4 md:mb-5' : 'mb-8 md:mb-10' ?> text-6xl uppercase text-white md:text-9xl"><?= Lang::t('product.common.dare_heading') ?></h2>
<?php if ($hasDareText) : ?>
<p class="mx-auto mb-8 max-w-lg font-[family-name:var(--font-body)] text-lg leading-snug text-[#f0f0f0] md:mb-10 md:text-xl"><?= Lang::t($dareKey) ?></p>
<?php endif; ?>

<div class="mx-auto mt-2 max-w-full pt-2 md:mt-4 md:pt-4">
<a href="<?= htmlspecialchars($checkoutUrl, ENT_QUOTES, 'UTF-8') ?>" class="group relative z-10 mx-auto inline-block w-auto max-w-full overflow-visible border-0 bg-transparent p-0 shadow-none outline-none focus-visible:ring-2 focus-visible:ring-[var(--sec)] focus-visible:ring-offset-4 focus-visible:ring-offset-black transition-transform active:scale-[0.98]"><img src="<?= htmlspecialchars($buyImg, ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t('product.common.buy_alt') ?>" width="640" height="260" class="relative z-10 mx-auto block h-auto w-auto max-w-[min(100vw-3rem,520px)] object-contain drop-shadow-[2px_2px_0_var(--nav-stroke)] transition-transform group-hover:translate-x-0.5 md:max-w-[640px]" decoding="async"></a>
</div>
<p class="mt-8 font-[family-name:var(--font-headline)] font-bold uppercase tracking-widest text-[#bcbcbc] md:mt-10"><?= Lang::t('product.common.stock_limited') ?></p>
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
