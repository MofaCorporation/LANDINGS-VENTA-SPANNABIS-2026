<?php

declare(strict_types=1);

use App\Lang\Lang;

/** @var list<array{name: string, slug: string, hero: string, taglineKey: string}> $catalogItems */

$logo = asset_url('/assets/img/ui/logo-tarumbas-farm.png');

$catalogTargetLang = Lang::current() === 'es' ? 'en' : 'es';
$catalogLangPath   = Lang::switchUrl($_SERVER['REQUEST_URI'] ?? '/', $catalogTargetLang);
$catalogLangHref   = htmlspecialchars(base_path() . $catalogLangPath, ENT_QUOTES, 'UTF-8');
$catalogSwitchFlagSrc = $catalogTargetLang === 'en' ? 'https://flagcdn.com/24x18/gb.png' : 'https://flagcdn.com/24x18/es.png';
$catalogSwitchAlt     = $catalogTargetLang === 'en' ? 'EN' : 'ES';
$catalogSwitchText    = $catalogTargetLang === 'en' ? 'ENGLISH' : 'ESPAÑOL';
?>
<style>
/* Una sola fila de idioma: la del header del catálogo (misma UI que landings) */
#root > .tf-lang-switch { display: none !important; }
</style>

<?php
$catalogHeaderTitle = Lang::current() === 'es' ? 'EL JARDÍN SECRETO' : 'THE SECRET GARDEN';
?>
<header style="display:flex;align-items:center;gap:2rem;padding:1rem 2rem;padding-right:10rem;background:#0e0610;border-bottom:8px solid #000;box-sizing:border-box;flex-wrap:wrap;position:relative;color:#fff;">
    <div style="position:absolute;top:1rem;right:1rem;z-index:20;">
        <a href="<?= $catalogLangHref ?>" class="tf-lang-switch__btn"><img src="<?= htmlspecialchars($catalogSwitchFlagSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($catalogSwitchAlt, ENT_QUOTES, 'UTF-8') ?>" width="24" height="18" style="width:20px;height:auto;vertical-align:middle;margin-right:4px;display:inline-block"> <?= htmlspecialchars($catalogSwitchText, ENT_QUOTES, 'UTF-8') ?></a>
    </div>
    <a href="<?= htmlspecialchars(url_lang('/'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline-flex;line-height:0;text-decoration:none;flex-shrink:0;">
        <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t('site.brand') ?>" width="320" height="107" style="height:60px;width:auto;max-width:min(100%,280px);object-fit:contain;display:block;" decoding="async">
    </a>
    <h1 style="font-family:Bangers,'Segoe UI',Impact,sans-serif;color:#ff00aa;font-size:2rem;margin:0;font-weight:400;line-height:1.1;text-transform:uppercase;letter-spacing:0.02em;flex:1;min-width:12rem;"><?= htmlspecialchars($catalogHeaderTitle, ENT_QUOTES, 'UTF-8') ?></h1>
</header>

<main class="tf-catalog-main">
    <div class="tf-catalog-main__inner">
        <div class="tf-catalog-intro">
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
                        <h2 style="margin:0;font-family:Bangers,'Segoe UI',Impact,sans-serif;font-size:clamp(1.35rem,3.5vw,2.35rem);font-weight:400;line-height:1;text-transform:uppercase;color:#a3ff12;"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p style="margin:0.65rem 0 0;font-family:system-ui,-apple-system,sans-serif;font-size:0.95rem;font-weight:700;line-height:1.35;color:#e4e2e2;"><?= Lang::t($item['taglineKey']) ?></p>
                    </div>
                    <span style="display:block;box-sizing:border-box;margin-top:1.1rem;text-align:center;padding:0.65rem 0.75rem;border:3px solid #000;background:#a3ff12;color:#0e1a00;font-family:system-ui,-apple-system,sans-serif;font-size:0.78rem;font-weight:900;letter-spacing:0.06em;text-transform:uppercase;box-shadow:0 0 12px rgba(163,255,18,0.55),4px 4px 0 #000;"><?= Lang::t('catalog.cta_see_more') ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?php /* Aviso legal + pie: templates/layout/footer.php (useSiteFooterBlock) */ ?>
