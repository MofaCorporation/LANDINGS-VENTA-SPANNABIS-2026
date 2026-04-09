<?php

declare(strict_types=1);

use App\Lang\Lang;

/** @var array<string, mixed> $checkoutLine */
/** @var string $variety */
/** @var list<array<string, mixed>> $varietyOptions */
/** @var list<array<string, mixed>> $varietyChips */
/** @var string|null $formError */
/** @var array<string, string>|null $redsysPayment */
/** @var string|null $redsysUrl */
/** @var int $freeShippingSubtotal */
/** @var list<array<string, mixed>> $cartLines */
/** @var list<array<string, mixed>> $cartView */
/** @var string $catalogJson */
/** @var array<string, mixed> $checkoutOld */
/** @var array<string, string> $checkoutFieldErrors */
/** @var string $payValidateMsgsJson */
/** @var array<string, true> $countryCodeSet */
/** @var list<string> $countryCodesOrdered */

$line            = $checkoutLine ?? [];
$formError       = $formError ?? null;
$redsysPayment   = $redsysPayment ?? null;
$redsysUrl       = $redsysUrl ?? null;
$varietyOptions  = $varietyOptions ?? [];
$varietyChips    = $varietyChips ?? [];
$freeShippingSubtotal = $freeShippingSubtotal ?? 5000;
$cartLines       = $cartLines ?? [];
$cartView        = $cartView ?? [];
$catalogJson     = $catalogJson ?? '[]';
$checkoutOld     = $checkoutOld ?? [];
$checkoutFieldErrors = $checkoutFieldErrors ?? [];
$payValidateMsgsJson = $payValidateMsgsJson ?? '{}';
$countryCodeSet = $countryCodeSet ?? [];
$countryCodesOrdered = $countryCodesOrdered ?? [];

$cartSubtotalCents = 0;
foreach ($cartLines as $cl) {
    if (is_array($cl)) {
        $cartSubtotalCents += (int) ($cl['subtotal'] ?? 0);
    }
}

$shipZero   = format_price_cents(0);
$logoUrl    = asset_url('/assets/img/ui/logo-tarumbas-farm.png');
$langHref   = htmlspecialchars(base_path() . Lang::switchUrl($_SERVER['REQUEST_URI'] ?? '/', Lang::current() === 'es' ? 'en' : 'es'), ENT_QUOTES, 'UTF-8');
$cartEmpty  = $cartView === [];
$checkoutActionUrl = htmlspecialchars(url_lang('/checkout'), ENT_QUOTES, 'UTF-8');
$shippingRatesUrl  = htmlspecialchars(url_lang('/checkout/shipping-rates'), ENT_QUOTES, 'UTF-8');

$guestChecked = array_key_exists('guest', $checkoutOld) ? !empty($checkoutOld['guest']) : true;
$shipStdOn    = ($checkoutOld['shipping'] ?? 'standard') !== 'pickup';
$payCardOn    = ($checkoutOld['payment'] ?? 'card') === 'card';

$fe = static function (string $k) use ($checkoutFieldErrors): bool {
    return isset($checkoutFieldErrors[$k]);
};
$feMsg = static function (string $k) use ($checkoutFieldErrors): string {
    return isset($checkoutFieldErrors[$k]) ? Lang::t($checkoutFieldErrors[$k]) : '';
};
?>
<header class="checkout-header">
  <div class="checkout-header__inner">
    <a class="checkout-logo" href="<?= htmlspecialchars(url_lang('/'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= Lang::t('site.brand') ?>">
      <img class="checkout-logo__img" src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="1024" height="1024" decoding="async">
    </a>
    <h1 class="checkout-title"><?= Lang::t('checkout.heading') ?></h1>
    <a class="checkout-lang" href="<?= $langHref ?>"><?= Lang::t('nav.lang_switch') ?></a>
  </div>
</header>

<div class="checkout-wrap">
  <?php if ($redsysPayment !== null && $redsysUrl !== null) : ?>
    <div class="tf-card checkout-redirect-card">
      <p class="tf-section-title" style="margin-bottom:0.75rem"><?= Lang::t('checkout.redirect_notice') ?></p>
      <p><?= Lang::t('checkout.secure_note') ?></p>
      <form id="redsys-form" method="post" action="<?= htmlspecialchars($redsysUrl, ENT_QUOTES, 'UTF-8') ?>" class="fixed left-0 top-0 h-px w-px overflow-hidden opacity-0" aria-hidden="true">
        <input type="hidden" name="Ds_SignatureVersion" value="<?= htmlspecialchars($redsysPayment['Ds_SignatureVersion'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="Ds_MerchantParameters" value="<?= htmlspecialchars($redsysPayment['Ds_MerchantParameters'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="Ds_Signature" value="<?= htmlspecialchars($redsysPayment['Ds_Signature'], ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit"><?= Lang::t('checkout.submit') ?></button>
      </form>
      <script>
        document.getElementById('redsys-form').submit();
      </script>
    </div>
  <?php else : ?>
  <div
    class="checkout-layout"
    data-checkout-layout
    data-free-from-cents="<?= (int) $freeShippingSubtotal ?>"
    data-ship-zero-label="<?= htmlspecialchars($shipZero, ENT_QUOTES, 'UTF-8') ?>"
    data-shipping-rates-url="<?= $shippingRatesUrl ?>"
  >
  <form
    class="checkout-main"
    id="checkout-form"
    method="post"
    action="<?= $checkoutActionUrl ?>"
    novalidate
    data-checkout-main
  >
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(checkout_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="shipping_quote_key" id="js-shipping-quote-key" value="">
    <input type="hidden" name="shipping_option_id" id="js-shipping-option-id" value="">
    <script type="application/json" id="checkout-pay-validate-msgs"><?= $payValidateMsgsJson ?></script>

      <?php if ($formError !== null && $formError !== '') : ?>
        <div class="checkout-form-error" role="alert"><?= $formError ?></div>
      <?php endif; ?>

      <section class="tf-card<?= $fe('cart') ? ' tf-card--invalid' : '' ?>" id="checkout-section-cart" aria-labelledby="sec-add-order">
        <h2 class="tf-section-title" id="sec-add-order"><?= Lang::t('checkout.section_add_to_order') ?></h2>
        <div class="field-grid field-grid--2 checkout-product-row">
          <label class="field" for="checkout-variety">
            <span><?= Lang::t('checkout.label_variety') ?></span>
            <select id="checkout-variety" name="variety" class="field-select" autocomplete="off" required>
              <?php foreach ($varietyOptions as $opt) :
                  $slug = (string) $opt['variety'];
                  $sel  = $slug === $variety ? ' selected' : '';
                  $unit = (int) ($opt['price_cents'] ?? 0);
                  $hero = htmlspecialchars(asset_url((string) ($opt['hero'] ?? '')), ENT_QUOTES, 'UTF-8');
                  $title = htmlspecialchars(Lang::raw((string) $opt['title_lang_key']), ENT_QUOTES, 'UTF-8');
                  $tag   = htmlspecialchars(Lang::raw((string) $opt['tagline_lang_key']), ENT_QUOTES, 'UTF-8');
                  ?>
                <option
                  value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
                  data-unit-cents="<?= $unit ?>"
                  data-hero="<?= $hero ?>"
                  data-title="<?= $title ?>"
                  data-tagline="<?= $tag ?>"
                  <?= $sel ?>
                ><?= Lang::t((string) $opt['title_lang_key']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="field" for="checkout-pack">
            <span><?= Lang::t('checkout.label_pack') ?></span>
            <select id="checkout-pack" name="pack" class="field-select" autocomplete="off">
              <option value="1"><?= Lang::t('checkout.pack_1') ?></option>
              <option value="3"><?= Lang::t('checkout.pack_3') ?></option>
              <option value="5"><?= Lang::t('checkout.pack_5') ?></option>
              <option value="10"><?= Lang::t('checkout.pack_10') ?></option>
            </select>
          </label>
        </div>

        <div class="variety-preview" aria-label="<?= Lang::t('checkout.preview_aria') ?>">
          <div class="variety-preview__media">
            <img id="js-variety-preview-img" class="variety-preview__img" src="<?= htmlspecialchars(asset_url((string) $line['hero']), ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t((string) $line['title_lang_key']) ?>" width="256" height="256" decoding="async">
          </div>
          <div class="variety-preview__meta">
            <p id="js-variety-preview-name" class="variety-preview__name"><?= Lang::t((string) $line['title_lang_key']) ?></p>
            <p id="js-variety-preview-tagline" class="variety-preview__tagline"><?= Lang::t((string) $line['tagline_lang_key']) ?></p>
            <p id="js-variety-preview-pack-total" class="variety-preview__tagline" style="margin-top:0.35rem;font-weight:700"><?= Lang::t('checkout.preview_pack_total') ?> <span id="js-variety-preview-pack-price"><?= htmlspecialchars(format_price_cents((int) ($line['price_cents'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></span></p>
          </div>
        </div>

        <div class="variety-strip" aria-label="<?= Lang::t('checkout.strip_aria') ?>">
          <div id="js-variety-strip" class="variety-strip__inner" role="list">
            <?php foreach ($varietyChips as $chip) :
                $slug = (string) $chip['variety'];
                ?>
              <button type="button" class="variety-chip" role="listitem" data-strain="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" aria-pressed="false">
                <div class="variety-chip__thumb">
                  <img class="variety-chip__img" src="<?= htmlspecialchars(asset_url((string) $chip['hero']), ENT_QUOTES, 'UTF-8') ?>" alt="" width="200" height="200" decoding="async">
                </div>
                <p class="variety-chip__name"><?= Lang::t((string) $chip['title_lang_key']) ?></p>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="checkout-add-row">
          <button type="button" id="checkout-add-line" class="cta-add-to-order" aria-label="<?= Lang::t('checkout.cta_add_aria') ?>">
            <img class="cta-add-to-order__img" src="<?= htmlspecialchars(asset_url('/assets/img/ui/añadir-al-carrito-pepe.png'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t('checkout.cta_add_alt') ?>" width="1200" height="240" decoding="async">
          </button>
        </div>
        <p class="field-error-msg field-error-msg--block" id="checkout-err-cart" role="alert"<?= $fe('cart') ? '' : ' hidden' ?>><?= $fe('cart') ? $feMsg('cart') : '' ?></p>
      </section>

      <section class="tf-card" aria-labelledby="sec-contact">
        <h2 class="tf-section-title tf-section-title--with-end-icon" id="sec-contact">
          <span><?= Lang::t('checkout.section_contact') ?></span>
          <img class="tf-section-title__end-icon" src="<?= htmlspecialchars(asset_url('/assets/img/ui/contacto-email-pepe.png'), ENT_QUOTES, 'UTF-8') ?>" alt="" width="1024" height="1024" decoding="async">
        </h2>
        <div class="field-grid">
          <label class="field<?= $fe('email') ? ' field--invalid' : '' ?>" data-checkout-field="email" for="checkout-field-email">
            <span><?= Lang::t('checkout.label_email') ?></span>
            <input type="email" id="checkout-field-email" name="email" autocomplete="email" required placeholder="<?= Lang::t('checkout.ph_email') ?>" value="<?= htmlspecialchars((string) ($checkoutOld['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <span class="field-error-msg" id="checkout-err-email" role="alert"<?= $fe('email') ? '' : ' hidden' ?>><?= $fe('email') ? $feMsg('email') : '' ?></span>
          </label>
        </div>
      </section>

      <section class="tf-card" aria-labelledby="sec-address">
        <h2 class="tf-section-title tf-section-title--with-end-icon" id="sec-address">
          <span><?= Lang::t('checkout.section_address') ?></span>
          <img class="tf-section-title__end-icon" src="<?= htmlspecialchars(asset_url('/assets/img/ui/direccion%20-de-envio-pepe.png'), ENT_QUOTES, 'UTF-8') ?>" alt="" width="1024" height="1024" decoding="async">
        </h2>
        <div class="field-grid">
          <label class="field<?= $fe('name') ? ' field--invalid' : '' ?>" data-checkout-field="name" for="checkout-field-name">
            <span><?= Lang::t('checkout.label_name') ?></span>
            <input type="text" id="checkout-field-name" name="name" autocomplete="name" required placeholder="<?= Lang::t('checkout.ph_name') ?>" value="<?= htmlspecialchars((string) ($checkoutOld['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <span class="field-error-msg" id="checkout-err-name" role="alert"<?= $fe('name') ? '' : ' hidden' ?>><?= $fe('name') ? $feMsg('name') : '' ?></span>
          </label>
          <label class="field<?= $fe('address') ? ' field--invalid' : '' ?>" data-checkout-field="address" for="checkout-field-address">
            <span><?= Lang::t('checkout.label_address') ?></span>
            <input type="text" id="checkout-field-address" name="address" autocomplete="street-address" required placeholder="<?= Lang::t('checkout.ph_address') ?>" value="<?= htmlspecialchars((string) ($checkoutOld['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <span class="field-error-msg" id="checkout-err-address" role="alert"<?= $fe('address') ? '' : ' hidden' ?>><?= $fe('address') ? $feMsg('address') : '' ?></span>
          </label>
          <div class="field-grid field-grid--2">
            <label class="field<?= $fe('postal') ? ' field--invalid' : '' ?>" data-checkout-field="postal" for="checkout-field-postal">
              <span><?= Lang::t('checkout.label_postal') ?></span>
              <input type="text" id="checkout-field-postal" name="postal" autocomplete="postal-code" required placeholder="<?= Lang::t('checkout.ph_postal') ?>" value="<?= htmlspecialchars((string) ($checkoutOld['postal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              <span class="field-error-msg" id="checkout-err-postal" role="alert"<?= $fe('postal') ? '' : ' hidden' ?>><?= $fe('postal') ? $feMsg('postal') : '' ?></span>
            </label>
            <label class="field<?= $fe('city') ? ' field--invalid' : '' ?>" data-checkout-field="city" for="checkout-field-city">
              <span><?= Lang::t('checkout.label_city') ?></span>
              <input type="text" id="checkout-field-city" name="city" autocomplete="address-level2" required placeholder="<?= Lang::t('checkout.ph_city') ?>" value="<?= htmlspecialchars((string) ($checkoutOld['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              <span class="field-error-msg" id="checkout-err-city" role="alert"<?= $fe('city') ? '' : ' hidden' ?>><?= $fe('city') ? $feMsg('city') : '' ?></span>
            </label>
          </div>
          <div class="field-grid field-grid--2">
            <label class="field<?= $fe('province') ? ' field--invalid' : '' ?>" data-checkout-field="province" for="checkout-field-province">
              <span><?= Lang::t('checkout.label_province') ?></span>
              <input type="text" id="checkout-field-province" name="province" autocomplete="address-level1" required placeholder="<?= Lang::t('checkout.ph_province') ?>" value="<?= htmlspecialchars((string) ($checkoutOld['province'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              <span class="field-error-msg" id="checkout-err-province" role="alert"<?= $fe('province') ? '' : ' hidden' ?>><?= $fe('province') ? $feMsg('province') : '' ?></span>
            </label>
            <label class="field<?= $fe('country') ? ' field--invalid' : '' ?>" data-checkout-field="country" for="checkout-field-country">
              <span><?= Lang::t('checkout.label_country') ?></span>
              <select id="checkout-field-country" name="country" class="field-select" autocomplete="country" required>
                <?php
                $countryOld = strtoupper((string) ($checkoutOld['country'] ?? ''));
                $countryCodes = $countryCodesOrdered;
                if ($countryCodes === []) {
                    $countryCodes = array_keys($countryCodeSet);
                    sort($countryCodes, SORT_STRING);
                    $countryCodes = array_values(array_filter($countryCodes, static fn (string $c): bool => $c !== 'ES'));
                }
                ?>
                <option value=""><?= Lang::t('checkout.country_select_placeholder') ?></option>
                <option value="ES"<?= $countryOld === 'ES' ? ' selected' : '' ?>><?= Lang::t('checkout.countries.ES') ?></option>
                <option disabled>──────────</option>
                <?php foreach ($countryCodes as $code) :
                    $sel = $countryOld === $code ? ' selected' : '';
                    ?>
                  <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"<?= $sel ?>><?= Lang::t('checkout.countries.' . $code) ?></option>
                <?php endforeach; ?>
              </select>
              <span class="field-error-msg" id="checkout-err-country" role="alert"<?= $fe('country') ? '' : ' hidden' ?>><?= $fe('country') ? $feMsg('country') : '' ?></span>
            </label>
          </div>
          <label class="field<?= $fe('phone') ? ' field--invalid' : '' ?>" data-checkout-field="phone" for="checkout-field-phone">
            <span><?= Lang::t('checkout.label_phone') ?></span>
            <input type="tel" id="checkout-field-phone" name="phone" autocomplete="tel" placeholder="<?= Lang::t('checkout.ph_phone') ?>" value="<?= htmlspecialchars((string) ($checkoutOld['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <span class="field-error-msg" id="checkout-err-phone" role="alert"<?= $fe('phone') ? '' : ' hidden' ?>><?= $fe('phone') ? $feMsg('phone') : '' ?></span>
          </label>
        </div>
        <label class="check-row">
          <input type="checkbox" name="guest" value="1"<?= $guestChecked ? ' checked' : '' ?>>
          <span><?= Lang::t('checkout.guest_check') ?></span>
        </label>
        <label class="check-row">
          <input type="checkbox" name="create_account" value="1"<?= !empty($checkoutOld['create_account']) ? ' checked' : '' ?>>
          <span><?= Lang::t('checkout.account_check') ?></span>
        </label>
      </section>

      <section class="tf-card" aria-labelledby="sec-ship">
        <h2 class="tf-section-title tf-section-title--with-end-icon" id="sec-ship">
          <span><?= Lang::t('checkout.section_shipping') ?></span>
          <img class="tf-section-title__end-icon" src="<?= htmlspecialchars(asset_url('/assets/img/ui/envios-pepe.png'), ENT_QUOTES, 'UTF-8') ?>" alt="" width="643" height="640" decoding="async">
        </h2>
        <div class="ship-dynamic" data-ship-dynamic>
          <div class="ship-dynamic__status">
            <span class="ship-dynamic__spinner" data-ship-spinner hidden></span>
            <p class="hint" data-ship-hint><?= Lang::t('checkout.ship_hint_dynamic') ?></p>
            <p class="field-error-msg field-error-msg--block" data-ship-error hidden><?= Lang::t('checkout.ship_error') ?></p>
          </div>
          <div class="ship-options" data-ship-options></div>
          <p class="hint" data-ship-free-hint><?= Lang::t('checkout.ship_free_hint') ?></p>
          <p class="hint"><?= Lang::t('checkout.ship_note') ?></p>
        </div>
      </section>

      <section class="tf-card<?= $fe('payment') ? ' tf-card--invalid' : '' ?>" id="checkout-section-pay" aria-labelledby="sec-pay">
        <h2 class="tf-section-title" id="sec-pay"><?= Lang::t('checkout.section_payment') ?></h2>
        <div class="pay-options" data-checkout-field="payment">
          <label class="opt">
            <div class="opt__main">
              <input type="radio" name="payment" value="card" data-checkout-pay-card<?= $payCardOn ? ' checked' : '' ?>>
              <span><?= Lang::t('checkout.pay_card') ?></span>
            </div>
          </label>
          <label class="opt">
            <div class="opt__main">
              <input type="radio" name="payment" value="transfer" data-checkout-pay-transfer<?= $payCardOn ? '' : ' checked' ?>>
              <span><?= Lang::t('checkout.pay_transfer') ?></span>
            </div>
          </label>
        </div>
        <p class="field-error-msg field-error-msg--block" id="checkout-err-payment" role="alert"<?= $fe('payment') ? '' : ' hidden' ?>><?= $fe('payment') ? $feMsg('payment') : '' ?></p>
        <p class="secure-hint"><?= Lang::t('checkout.pay_secure_hint') ?></p>
      </section>

      <div class="tf-card checkout-cta-mobile-wrap">
        <button type="submit" class="cta-final checkout-cta--mobile" aria-label="<?= Lang::t('checkout.cta_pay_aria') ?>">
          <img class="cta-final__img" src="<?= htmlspecialchars(asset_url('/assets/img/ui/pagar-ahora-pepe.png'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t('checkout.submit') ?>" width="843" height="255" decoding="async">
        </button>
      </div>
  </form>

    <script type="application/json" id="checkout-catalog-json"><?= $catalogJson ?></script>

    <aside class="checkout-aside" aria-label="<?= Lang::t('checkout.summary_aria') ?>">
      <details class="tf-card details-mobile" open>
        <summary class="details-summary"><?= Lang::t('checkout.summary_toggle') ?></summary>
        <div class="summary-inner">
          <h2 class="tf-section-title"><?= Lang::t('checkout.summary_title') ?></h2>
          <p class="order-empty js-order-empty"<?= $cartEmpty ? '' : ' hidden' ?>><?= Lang::t('checkout.order_empty') ?></p>
          <ul
            class="order-lines-list"
            id="js-order-lines"
            <?= $cartEmpty ? 'hidden' : '' ?>
            aria-live="polite"
            aria-label="<?= Lang::t('checkout.order_lines_aria') ?>"
          >
            <?php foreach ($cartView as $i => $row) :
                $sub = (int) ($row['subtotal_cents'] ?? 0);
                $qty = (int) ($row['quantity'] ?? 1);
                $lineText = ($row['title'] ?? '') . ' — ' . ($row['pack_label'] ?? '');
                if ($qty > 1) {
                    $lineText .= ' ×' . $qty;
                }
                ?>
              <li class="order-line-row" data-line-subtotal-cents="<?= $sub ?>">
                <div class="order-line-main">
                  <p class="order-line-text"><?= $lineText ?></p>
                </div>
                <span class="order-line-price"><?= htmlspecialchars(format_price_cents($sub), ENT_QUOTES, 'UTF-8') ?></span>
                <button type="submit" class="order-line-remove" form="cart-remove-<?= (int) $i ?>" aria-label="<?= Lang::t('checkout.remove_line_aria') ?>">×</button>
              </li>
            <?php endforeach; ?>
          </ul>
          <div class="summary-row">
            <span><?= Lang::t('checkout.line_sub') ?></span>
            <span class="js-summary-sub"><?= htmlspecialchars(format_price_cents($cartSubtotalCents), ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <div class="summary-row">
            <span><?= Lang::t('checkout.line_ship') ?></span>
            <span class="js-summary-ship"><?= htmlspecialchars(format_price_cents(0), ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <div class="summary-row summary-row--total">
            <span><?= Lang::t('checkout.line_total') ?></span>
            <span class="js-summary-total"><?= htmlspecialchars(format_price_cents($cartSubtotalCents), ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <button type="submit" class="cta-final checkout-cta--desktop" form="checkout-form" aria-label="<?= Lang::t('checkout.cta_pay_aria') ?>">
            <img class="cta-final__img" src="<?= htmlspecialchars(asset_url('/assets/img/ui/pagar-ahora-pepe.png'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= Lang::t('checkout.submit') ?>" width="843" height="255" decoding="async">
          </button>
        </div>
      </details>
    </aside>
  </div>

  <form id="checkout-cart-add-form" method="post" action="<?= $checkoutActionUrl ?>" hidden>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(checkout_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="cart_action" value="add">
    <input type="hidden" name="variety" id="checkout-cart-add-variety" value="<?= htmlspecialchars($variety, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="pack" id="checkout-cart-add-pack" value="1">
    <input type="hidden" name="quantity" value="1">
  </form>
  <?php foreach ($cartView as $i => $_row) : ?>
  <form id="cart-remove-<?= (int) $i ?>" method="post" action="<?= $checkoutActionUrl ?>" hidden>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(checkout_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="cart_action" value="remove">
    <input type="hidden" name="line_index" value="<?= (int) $i ?>">
  </form>
  <?php endforeach; ?>

  <script type="module" src="<?= htmlspecialchars(asset_url('/assets/js/checkout.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <?php endif; ?>
</div>

<footer class="checkout-foot">
  <p><?= Lang::t('checkout.foot_legal') ?></p>
  <p class="checkout-foot__help"><?= Lang::t('checkout.foot_help_prefix') ?> <a class="checkout-foot__mailto" href="mailto:tarumbasfarm@gmail.com">tarumbasfarm@gmail.com</a></p>
</footer>
