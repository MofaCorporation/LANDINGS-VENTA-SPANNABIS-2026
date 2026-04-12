/**
 * Checkout: preview de variedad/pack, carrito multi-línea (servidor) y resumen con envío.
 */
function formatMoney(cents, locale) {
  return new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR' }).format(cents / 100);
}

function readCatalog() {
  const el = document.getElementById('checkout-catalog-json');
  if (!el || !el.textContent) {
    return [];
  }
  try {
    const data = JSON.parse(el.textContent.trim());
    return Array.isArray(data) ? data : [];
  } catch {
    return [];
  }
}

const layout = document.querySelector('[data-checkout-layout]');
const mainForm = document.querySelector('[data-checkout-main]');

if (layout && mainForm) {
  const freeFrom = parseInt(layout.dataset.freeFromCents, 10) || 0;
  const locale = document.documentElement.lang === 'en' ? 'en-GB' : 'es-ES';
  const packSel = document.getElementById('checkout-pack');
  const varietySel = document.getElementById('checkout-variety');
  const catalog = readCatalog();
  const shippingRatesUrl = layout.dataset.shippingRatesUrl || '';
  const shipOptionsWrap = layout.querySelector('[data-ship-options]');
  const shipSpinner = layout.querySelector('[data-ship-spinner]');
  const shipError = layout.querySelector('[data-ship-error]');
  const shipFreeHint = layout.querySelector('[data-ship-free-hint]');
  const quoteKeyInput = document.getElementById('js-shipping-quote-key');
  const optionIdInput = document.getElementById('js-shipping-option-id');

  let currentShipCents = 0;
  let currentQuoteKey = '';

  const byVariety = new Map(catalog.map((row) => [row.variety, row]));

  function rowFromSelectedOption() {
    const opt = varietySel?.selectedOptions?.[0];
    if (!opt) {
      return null;
    }
    const unit = parseInt(opt.getAttribute('data-unit-cents'), 10) || 0;
    const hero = opt.getAttribute('data-hero') || '';
    const title = opt.getAttribute('data-title') || '';
    const tagline = opt.getAttribute('data-tagline') || '';
    const v = opt.value || '';
    if (!v) {
      return null;
    }
    return { variety: v, unitCents: unit, hero, title, tagline };
  }

  function currentRow() {
    const v = varietySel?.value ?? '';
    const fromJson = byVariety.get(v);
    if (fromJson) {
      return fromJson;
    }
    return rowFromSelectedOption();
  }

  function updatePreview() {
    const row = currentRow();
    const pack = parseInt(packSel?.value, 10) || 1;
    const img = document.getElementById('js-variety-preview-img');
    const nameEl = document.getElementById('js-variety-preview-name');
    const tagEl = document.getElementById('js-variety-preview-tagline');
    const packPriceEl = document.getElementById('js-variety-preview-pack-price');
    if (!row) {
      return;
    }
    if (img && row.hero) {
      img.src = row.hero;
    }
    if (img && row.title) {
      img.alt = row.title;
    }
    if (nameEl && row.title) {
      nameEl.textContent = row.title;
    }
    if (tagEl && row.tagline !== undefined) {
      tagEl.textContent = row.tagline;
    }
    const unit = parseInt(row.unitCents, 10) || 0;
    if (packPriceEl) {
      packPriceEl.textContent = formatMoney(unit * pack, locale);
    }

    document.querySelectorAll('.variety-chip[data-strain]').forEach((chip) => {
      const strain = chip.getAttribute('data-strain');
      const pressed = strain === (varietySel?.value ?? '');
      chip.setAttribute('aria-pressed', pressed ? 'true' : 'false');
    });
  }

  function cartSubtotalFromDom() {
    let sub = 0;
    document.querySelectorAll('[data-line-subtotal-cents]').forEach((el) => {
      sub += parseInt(el.getAttribute('data-line-subtotal-cents'), 10) || 0;
    });
    return sub;
  }

  function recalcSummary() {
    const sub = cartSubtotalFromDom();
    const ship = sub >= freeFrom ? 0 : (currentShipCents || 0);
    const total = sub + ship;
    const elSub = layout.querySelector('.js-summary-sub');
    const elShip = layout.querySelector('.js-summary-ship');
    const elTot = layout.querySelector('.js-summary-total');
    if (elSub) {
      elSub.textContent = formatMoney(sub, locale);
    }
    if (elShip) {
      elShip.textContent = formatMoney(ship, locale);
    }
    if (elTot) {
      elTot.textContent = formatMoney(total, locale);
    }
    if (shipFreeHint) {
      shipFreeHint.textContent = sub >= freeFrom
        ? (document.documentElement.lang === 'en' ? 'Free shipping applied.' : 'Envío gratuito aplicado.')
        : (document.documentElement.lang === 'en' ? 'Shipping calculated in real time.' : 'Envío calculado en tiempo real.');
    }
  }

  function syncOrderLinesVisibility() {
    const ul = document.getElementById('js-order-lines');
    const empty = document.querySelector('.js-order-empty');
    const lines = ul?.querySelectorAll('.order-line-row') ?? [];
    const hasLines = lines.length > 0;
    if (ul) {
      if (hasLines) {
        ul.removeAttribute('hidden');
      } else {
        ul.setAttribute('hidden', 'hidden');
      }
    }
    if (empty) {
      if (hasLines) {
        empty.setAttribute('hidden', 'hidden');
      } else {
        empty.removeAttribute('hidden');
      }
    }
  }

  packSel?.addEventListener('change', () => {
    updatePreview();
  });

  varietySel?.addEventListener('change', () => {
    updatePreview();
  });

  function setShipLoading(on) {
    if (!shipSpinner) return;
    if (on) shipSpinner.removeAttribute('hidden');
    else shipSpinner.setAttribute('hidden', 'hidden');
  }

  function setShipError(message) {
    if (!shipError) return;
    if (!message) {
      shipError.textContent = '';
      shipError.setAttribute('hidden', 'hidden');
      return;
    }
    shipError.textContent = message;
    shipError.removeAttribute('hidden');
  }

  function escapeHtml(s) {
    return (s || '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
  }

  function readPayMsgs() {
    const el = document.getElementById('checkout-pay-validate-msgs');
    const raw = el?.textContent != null ? String(el.textContent).trim() : '';
    if (!raw) {
      return {};
    }
    try {
      const o = JSON.parse(raw);
      return o && typeof o === 'object' ? o : {};
    } catch {
      return {};
    }
  }

  /** Textos si el JSON embebido no trae las claves packlinkBadge* (parseo / orden DOM). */
  const PACKLINK_BADGE_FALLBACK = {
    es: {
      best_price: 'MEJOR PRECIO',
      standard: 'ESTÁNDAR',
      pickup_point: 'PUNTO DE RECOGIDA',
      express: 'EXPRESS',
    },
    en: {
      best_price: 'BEST PRICE',
      standard: 'STANDARD',
      pickup_point: 'PICKUP POINT',
      express: 'EXPRESS',
    },
  };

  const PACKLINK_BADGE_PROP = {
    best_price: 'packlinkBadgeBestPrice',
    standard: 'packlinkBadgeStandard',
    pickup_point: 'packlinkBadgePickupPoint',
    express: 'packlinkBadgeExpress',
  };

  function packlinkBadgeDisplayText(badgeKeyRaw, payMsgsMap) {
    const k = badgeKeyRaw != null ? String(badgeKeyRaw).trim() : '';
    if (!k || !Object.prototype.hasOwnProperty.call(PACKLINK_BADGE_PROP, k)) {
      return '';
    }
    const prop = PACKLINK_BADGE_PROP[k];
    const fromJson = payMsgsMap[prop];
    if (fromJson != null && String(fromJson).trim() !== '') {
      return String(fromJson).trim();
    }
    const lang = document.documentElement.lang === 'en' ? 'en' : 'es';
    return PACKLINK_BADGE_FALLBACK[lang][k] || '';
  }

  const payMsgs = readPayMsgs();

  function renderShippingOptions(options, quoteKey) {
    if (!shipOptionsWrap) return;
    shipOptionsWrap.innerHTML = '';

    if (!Array.isArray(options) || options.length === 0) {
      shipOptionsWrap.innerHTML = `<p class="hint">${document.documentElement.lang === 'en' ? 'Enter country and postal code to see shipping options.' : 'Introduce país y código postal para ver opciones de envío.'}</p>`;
      currentShipCents = 0;
      currentQuoteKey = '';
      if (quoteKeyInput) quoteKeyInput.value = '';
      if (optionIdInput) optionIdInput.value = '';
      recalcSummary();
      return;
    }

    currentQuoteKey = quoteKey || '';
    if (quoteKeyInput) quoteKeyInput.value = currentQuoteKey;

    // Primera opción: mejor precio (orden fijo backend: barato → estándar → recogida → express)
    const defaultId = options[0]?.id != null ? String(options[0].id) : '';
    optionIdInput && (optionIdInput.value = defaultId);
    currentShipCents = Number(options[0]?.price_cents) || 0;

    const payMsgsForShip = readPayMsgs();

    const name = 'shipping_option_ui';
    options.forEach((opt, idx) => {
      const id = opt.id != null ? String(opt.id) : '';
      const carrier = escapeHtml(opt.carrier != null ? String(opt.carrier) : '—');
      const service = escapeHtml(opt.service_name != null ? String(opt.service_name) : '—');
      const cents = Number(opt.price_cents) || 0;
      const days = opt.days === null || opt.days === undefined ? null : (parseInt(String(opt.days), 10) || null);
      const labelPrice = formatMoney(cents, locale);
      const labelDays = days ? (document.documentElement.lang === 'en' ? `${days} days` : `${days} días`) : '';
      const checked = idx === 0 ? ' checked' : '';
      const bk = opt.badge_key != null ? String(opt.badge_key) : (opt.badgeKey != null ? String(opt.badgeKey) : '');
      const badgeText = packlinkBadgeDisplayText(bk, payMsgsForShip);
      const badgeHtml = badgeText
        ? `<span class="opt__badge">${escapeHtml(`[${badgeText}]`)}</span>`
        : '';

      const row = document.createElement('label');
      row.className = 'opt';
      row.innerHTML = `
        <div class="opt__main">
          <input type="radio" name="${name}" value="${escapeHtml(id)}"${checked}>
          <span class="opt__body">
            <span class="opt__row">
              ${badgeHtml}
              <span class="opt__label">${carrier} · ${service}${labelDays ? ` <small class="hint">(${labelDays})</small>` : ''}</span>
            </span>
          </span>
        </div>
        <span class="opt__price">${escapeHtml(labelPrice)}</span>
      `;

      row.querySelector('input')?.addEventListener('change', () => {
        const selectedId = id;
        const selectedCents = cents;
        if (optionIdInput) optionIdInput.value = selectedId;
        currentShipCents = selectedCents;
        recalcSummary();
      });

      shipOptionsWrap.appendChild(row);
    });

    recalcSummary();
  }

  let shipAbort = null;
  let shipTimer = null;

  async function fetchShippingRates() {
    if (!shippingRatesUrl) return;
    const country = (mainForm.querySelector('#checkout-field-country')?.value || '').trim();
    const postal = (mainForm.querySelector('#checkout-field-postal')?.value || '').trim();
    const csrf = (mainForm.querySelector('input[name="csrf"]')?.value || '').trim();

    // Solo pedir si hay datos mínimos
    if (!country || postal.length < 4) {
      setShipError('');
      renderShippingOptions([], '');
      return;
    }

    if (shipAbort) {
      try { shipAbort.abort(); } catch {}
    }
    shipAbort = new AbortController();

    setShipError('');
    setShipLoading(true);

    const fd = new FormData();
    fd.set('csrf', csrf);
    fd.set('checkout_action', 'shipping_rates');
    fd.set('country_code', country);
    fd.set('postal_code', postal);
    fd.set('cart_subtotal', String(cartSubtotalFromDom()));

    try {
      const res = await fetch(shippingRatesUrl, { method: 'POST', body: fd, signal: shipAbort.signal });
      const data = await res.json().catch(() => null);
      if (!res.ok || !data || data.ok !== true) {
        throw new Error('bad_response');
      }
      const opts = Array.isArray(data.options) ? data.options : [];
      renderShippingOptions(opts, data.quote_key || '');
    } catch (e) {
      if (e && e.name === 'AbortError') return;
      setShipError(document.documentElement.lang === 'en'
        ? 'Could not load shipping rates. Please try again.'
        : 'No se pudo calcular el envío. Inténtalo de nuevo.');
      renderShippingOptions([], '');
    } finally {
      setShipLoading(false);
    }
  }

  function scheduleShippingFetch() {
    if (shipTimer) clearTimeout(shipTimer);
    shipTimer = setTimeout(fetchShippingRates, 350);
  }

  document.querySelectorAll('.variety-chip[data-strain]').forEach((chip) => {
    chip.addEventListener('click', () => {
      const strain = chip.getAttribute('data-strain');
      if (!strain || !varietySel) {
        return;
      }
      varietySel.value = strain;
      varietySel.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });

  const addForm = document.getElementById('checkout-cart-add-form');
  const addVarietyHidden = document.getElementById('checkout-cart-add-variety');
  const addPackHidden = document.getElementById('checkout-cart-add-pack');

  document.getElementById('checkout-add-line')?.addEventListener('click', () => {
    document.getElementById('checkout-section-cart')?.classList.remove('tf-card--invalid');
    const ce = document.getElementById('checkout-err-cart');
    if (ce) {
      ce.textContent = '';
      ce.setAttribute('hidden', 'hidden');
    }
    if (!addForm || !varietySel || !packSel) {
      return;
    }
    if (addVarietyHidden) {
      addVarietyHidden.value = varietySel.value;
    }
    if (addPackHidden) {
      addPackHidden.value = packSel.value;
    }
    addForm.requestSubmit();
  });

  function isEmailFormatOk(value) {
    const v = (value || '').trim();
    if (!v) {
      return false;
    }
    const probe = document.createElement('input');
    probe.type = 'email';
    probe.value = v;
    return probe.checkValidity();
  }

  function clearPayValidationUi() {
    mainForm.querySelectorAll('.field[data-checkout-field].field--invalid').forEach((lab) => {
      lab.classList.remove('field--invalid');
    });
    document.getElementById('checkout-section-cart')?.classList.remove('tf-card--invalid');
    document.getElementById('checkout-section-pay')?.classList.remove('tf-card--invalid');
    mainForm.querySelector('.pay-options[data-checkout-field="payment"]')?.classList.remove('pay-options--invalid');
    mainForm.querySelectorAll('.field-error-msg').forEach((node) => {
      node.textContent = '';
      node.setAttribute('hidden', 'hidden');
    });
  }

  function showFieldError(field, message) {
    const label = mainForm.querySelector(`.field[data-checkout-field="${field}"]`);
    const errEl = document.getElementById(`checkout-err-${field}`);
    if (label) {
      label.classList.add('field--invalid');
    }
    if (errEl) {
      errEl.textContent = message;
      errEl.removeAttribute('hidden');
    }
  }

  function showCartError(message) {
    document.getElementById('checkout-section-cart')?.classList.add('tf-card--invalid');
    const errEl = document.getElementById('checkout-err-cart');
    if (errEl) {
      errEl.textContent = message;
      errEl.removeAttribute('hidden');
    }
  }

  function showPaymentError(message) {
    document.getElementById('checkout-section-pay')?.classList.add('tf-card--invalid');
    mainForm.querySelector('.pay-options[data-checkout-field="payment"]')?.classList.add('pay-options--invalid');
    const errEl = document.getElementById('checkout-err-payment');
    if (errEl) {
      errEl.textContent = message;
      errEl.removeAttribute('hidden');
    }
  }

  function scrollToFirstError(order, failed) {
    for (let i = 0; i < order.length; i += 1) {
      const key = order[i];
      if (!failed.has(key)) {
        continue;
      }
      const map = {
        cart: document.getElementById('checkout-section-cart'),
        email: document.getElementById('checkout-field-email'),
        name: document.getElementById('checkout-field-name'),
        address: document.getElementById('checkout-field-address'),
        postal: document.getElementById('checkout-field-postal'),
        city: document.getElementById('checkout-field-city'),
        province: document.getElementById('checkout-field-province'),
        country: document.getElementById('checkout-field-country'),
        phone: document.getElementById('checkout-field-phone'),
        payment: document.getElementById('checkout-section-pay'),
      };
      const el = map[key];
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (typeof el.focus === 'function' && el.tagName !== 'SECTION') {
          setTimeout(() => {
            el.focus({ preventScroll: true });
          }, 400);
        }
      }
      break;
    }
  }

  function validateBeforePay() {
    clearPayValidationUi();
    const failed = new Set();
    const order = ['cart', 'email', 'name', 'address', 'postal', 'city', 'province', 'country', 'phone', 'payment'];

    const cartLines = document.querySelectorAll('#js-order-lines .order-line-row');
    if (cartLines.length === 0) {
      failed.add('cart');
      showCartError(payMsgs.cartEmpty || '—');
    }

    const emailEl = mainForm.querySelector('#checkout-field-email');
    const emailVal = (emailEl?.value || '').trim();
    if (!emailVal) {
      failed.add('email');
      showFieldError('email', payMsgs.emailRequired || '—');
    } else if (!isEmailFormatOk(emailVal)) {
      failed.add('email');
      showFieldError('email', payMsgs.emailInvalid || '—');
    }

    const normSpaces = (s) => (s || '').trim().replace(/\s+/g, ' ');

    const isNameValid = (v) => {
      const s = normSpaces(v);
      if (!s) return false;
      if (!/^[\p{L}\p{M}\s-]+$/u.test(s)) return false;
      const parts = s.split(' ').filter(Boolean);
      if (parts.length < 2) return false;
      for (const w of parts) {
        if (!/^[\p{L}\p{M}-]{3,}$/u.test(w)) return false;
      }
      return true;
    };

    const isCityValid = (v) => {
      const s = normSpaces(v);
      if (s.length < 2) return false;
      if (/^\d+$/.test(s)) return false;
      if (!/^[\p{L}\p{M}\s-]+$/u.test(s)) return false;
      return true;
    };

    const isProvinceValid = (v) => {
      const s = normSpaces(v);
      if (s.length < 2) return false;
      if (!/^[\p{L}\p{M}\s-]+$/u.test(s)) return false;
      return true;
    };

    const isAddressValid = (v) => {
      const s = (v || '').trim();
      if (s.length < 5) return false;
      if (!/\d/.test(s)) return false;
      return true;
    };

    const isPostalValid = (v) => {
      const s = normSpaces(v);
      if (s.length < 4 || s.length > 10) return false;
      if (!/^[0-9]+$/.test(s)) return false;
      return true;
    };

    const isPhoneValidOrEmpty = (v) => {
      const s = (v || '').trim();
      if (!s) return true;
      if (!/^[0-9\s()+-]+$/.test(s)) return false;
      const digits = s.replace(/\D+/g, '');
      return digits.length >= 7 && digits.length <= 15;
    };

    const nameVal = (mainForm.querySelector('#checkout-field-name')?.value || '');
    const nameNorm = normSpaces(nameVal);
    if (!nameNorm) {
      failed.add('name');
      showFieldError('name', payMsgs.nameRequired || '—');
    } else if (!isNameValid(nameNorm)) {
      failed.add('name');
      showFieldError('name', payMsgs.nameInvalid || '—');
    }

    const addrVal = (mainForm.querySelector('#checkout-field-address')?.value || '');
    const addrNorm = (addrVal || '').trim();
    if (!addrNorm) {
      failed.add('address');
      showFieldError('address', payMsgs.addressRequired || '—');
    } else if (!isAddressValid(addrNorm)) {
      failed.add('address');
      showFieldError('address', payMsgs.addressInvalid || '—');
    }

    const postalVal = (mainForm.querySelector('#checkout-field-postal')?.value || '');
    const postalNorm = normSpaces(postalVal);
    if (!postalNorm) {
      failed.add('postal');
      showFieldError('postal', payMsgs.postalRequired || '—');
    } else if (!isPostalValid(postalNorm)) {
      failed.add('postal');
      showFieldError('postal', payMsgs.postalInvalid || '—');
    }

    const cityVal = (mainForm.querySelector('#checkout-field-city')?.value || '');
    const cityNorm = normSpaces(cityVal);
    if (!cityNorm) {
      failed.add('city');
      showFieldError('city', payMsgs.cityRequired || '—');
    } else if (!isCityValid(cityNorm)) {
      failed.add('city');
      showFieldError('city', payMsgs.cityInvalid || '—');
    }

    const provVal = (mainForm.querySelector('#checkout-field-province')?.value || '');
    const provNorm = normSpaces(provVal);
    if (!provNorm) {
      failed.add('province');
      showFieldError('province', payMsgs.provinceRequired || '—');
    } else if (!isProvinceValid(provNorm)) {
      failed.add('province');
      showFieldError('province', payMsgs.provinceInvalid || '—');
    }

    const countryVal = (mainForm.querySelector('#checkout-field-country')?.value || '').trim();
    if (!countryVal) {
      failed.add('country');
      showFieldError('country', payMsgs.countryRequired || '—');
    } else if (countryVal.length !== 2) {
      failed.add('country');
      showFieldError('country', payMsgs.countryInvalid || '—');
    }

    const phoneVal = mainForm.querySelector('#checkout-field-phone')?.value || '';
    if (phoneVal.length > 40) {
      failed.add('phone');
      showFieldError('phone', payMsgs.phoneTooLong || '—');
    } else if (!isPhoneValidOrEmpty(phoneVal)) {
      failed.add('phone');
      showFieldError('phone', payMsgs.phoneInvalid || '—');
    }

    const payTransfer = mainForm.querySelector('[data-checkout-pay-transfer]');
    const payMethodOk = payTransfer?.checked === true;
    if (!payMethodOk) {
      failed.add('payment');
      showPaymentError(payMsgs.paymentTransfer || '—');
    }

    if (failed.size > 0) {
      scrollToFirstError(order, failed);
    }
    return failed.size === 0;
  }

  mainForm.addEventListener('submit', (ev) => {
    if (!validateBeforePay()) {
      ev.preventDefault();
      ev.stopPropagation();
    }
  });

  mainForm.querySelectorAll('#checkout-field-email, #checkout-field-name, #checkout-field-address, #checkout-field-postal, #checkout-field-city, #checkout-field-province, #checkout-field-country, #checkout-field-phone').forEach((input) => {
    input.addEventListener('input', () => {
      const field = input.closest('.field[data-checkout-field]')?.getAttribute('data-checkout-field');
      if (!field) {
        return;
      }
      input.closest('.field')?.classList.remove('field--invalid');
      const err = document.getElementById(`checkout-err-${field}`);
      if (err) {
        err.textContent = '';
        err.setAttribute('hidden', 'hidden');
      }
    });
  });

  // Recalcular envío al cambiar país o CP
  mainForm.querySelector('#checkout-field-country')?.addEventListener('change', () => {
    scheduleShippingFetch();
  });
  mainForm.querySelector('#checkout-field-postal')?.addEventListener('input', () => {
    scheduleShippingFetch();
  });

  mainForm.querySelectorAll('[data-checkout-pay-transfer]').forEach((radio) => {
    radio.addEventListener('change', () => {
      document.getElementById('checkout-section-pay')?.classList.remove('tf-card--invalid');
      mainForm.querySelector('.pay-options[data-checkout-field="payment"]')?.classList.remove('pay-options--invalid');
      const pe = document.getElementById('checkout-err-payment');
      if (pe) {
        pe.textContent = '';
        pe.setAttribute('hidden', 'hidden');
      }
    });
  });

  updatePreview();
  syncOrderLinesVisibility();
  recalcSummary();
  // Primer cálculo si ya hay valores precargados
  scheduleShippingFetch();
}
