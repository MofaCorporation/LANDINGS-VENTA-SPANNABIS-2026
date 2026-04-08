<?php

declare(strict_types=1);

use App\Lang\Lang;
?>
<main class="min-h-screen bg-black text-white px-6 py-16">
  <div class="max-w-3xl mx-auto">
    <h1 class="tf-title-bangers text-5xl uppercase mb-6 text-center"><?= Lang::t('site.default_title') ?></h1>
    <p class="font-[family-name:var(--font-body)] text-center text-[#e4e2e2] mb-10"><?= Lang::t('nav.home') ?> — <?= Lang::t('site.brand') ?></p>
    <ul class="space-y-4 font-[family-name:var(--font-headline)] font-bold text-lg">
      <li><a class="text-[#00ffcc] hover:underline" href="<?= htmlspecialchars(url_lang('/toxic-mutant'), ENT_QUOTES, 'UTF-8') ?>">Toxic Mutant</a></li>
      <li><a class="text-[#00ffcc] hover:underline" href="<?= htmlspecialchars(url_lang('/nitro-bud'), ENT_QUOTES, 'UTF-8') ?>">Nitro Bud</a></li>
      <li><a class="text-[#00ffcc] hover:underline" href="<?= htmlspecialchars(url_lang('/dj-piggy'), ENT_QUOTES, 'UTF-8') ?>">DJ Piggy</a></li>
      <li><a class="text-[#00ffcc] hover:underline" href="<?= htmlspecialchars(url_lang('/holy-boss'), ENT_QUOTES, 'UTF-8') ?>">Holy Boss</a></li>
      <li><a class="text-[#00ffcc] hover:underline" href="<?= htmlspecialchars(url_lang('/lady-cupcake'), ENT_QUOTES, 'UTF-8') ?>">Lady Cupcake</a></li>
    </ul>
  </div>
</main>
