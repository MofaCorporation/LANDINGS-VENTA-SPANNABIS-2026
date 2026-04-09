<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Lang\Lang;
use App\Models\Order;
use App\Services\CheckoutCatalog;
use App\Services\PacklinkService;
use App\Services\RedsysService;

final class CheckoutController extends BaseController
{
    // Envío gratis si subtotal >= 30000 (300€)
    private const FREE_SHIPPING_SUBTOTAL_CENTS = 30000;

    private const SESSION_CART = 'cart';

    private const SESSION_FORM_OLD = 'checkout_form_old';

    private const SESSION_FIELD_ERRORS = 'checkout_field_errors';

    private const SESSION_PACKLINK_QUOTE = 'packlink_quote';
    private const PACKLINK_QUOTE_TTL_SECONDS = 15 * 60;

    /** @return array<string, true> */
    private static function countryCodeSet(): array
    {
        // ISO 3166-1 alpha-2 (incluye territorios) — labels via i18n: checkout.countries.<CODE>
        $codes = [
            'AD','AE','AF','AG','AI','AL','AM','AO','AQ','AR','AS','AT','AU','AW','AX','AZ',
            'BA','BB','BD','BE','BF','BG','BH','BI','BJ','BL','BM','BN','BO','BQ','BR','BS','BT','BV','BW','BY','BZ',
            'CA','CC','CD','CF','CG','CH','CI','CK','CL','CM','CN','CO','CR','CU','CV','CW','CX','CY','CZ',
            'DE','DJ','DK','DM','DO','DZ',
            'EC','EE','EG','EH','ER','ES','ET',
            'FI','FJ','FK','FM','FO','FR',
            'GA','GB','GD','GE','GF','GG','GH','GI','GL','GM','GN','GP','GQ','GR','GS','GT','GU','GW','GY',
            'HK','HM','HN','HR','HT','HU',
            'ID','IE','IL','IM','IN','IO','IQ','IR','IS','IT',
            'JE','JM','JO','JP',
            'KE','KG','KH','KI','KM','KN','KP','KR','KW','KY','KZ',
            'LA','LB','LC','LI','LK','LR','LS','LT','LU','LV','LY',
            'MA','MC','MD','ME','MF','MG','MH','MK','ML','MM','MN','MO','MP','MQ','MR','MS','MT','MU','MV','MW','MX','MY','MZ',
            'NA','NC','NE','NF','NG','NI','NL','NO','NP','NR','NU','NZ',
            'OM',
            'PA','PE','PF','PG','PH','PK','PL','PM','PN','PR','PS','PT','PW','PY',
            'QA',
            'RE','RO','RS','RU','RW',
            'SA','SB','SC','SD','SE','SG','SH','SI','SJ','SK','SL','SM','SN','SO','SR','SS','ST','SV','SX','SY','SZ',
            'TC','TD','TF','TG','TH','TJ','TK','TL','TM','TN','TO','TR','TT','TV','TW','TZ',
            'UA','UG','UM','US','UY','UZ',
            'VA','VC','VE','VG','VI','VN','VU',
            'WF','WS',
            'YE','YT',
            'ZA','ZM','ZW',
        ];
        $set = [];
        foreach ($codes as $c) {
            $set[$c] = true;
        }

        return $set;
    }

    private static function normSpaces(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return $s;
    }

    /** @return list<string> */
    private static function words(string $s): array
    {
        $s = self::normSpaces($s);
        if ($s === '') {
            return [];
        }
        $parts = preg_split('/\s+/u', $s) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim((string) $p);
            if ($p !== '') {
                $out[] = $p;
            }
        }

        return $out;
    }

    private static function isNameValid(string $s): bool
    {
        $s = self::normSpaces($s);
        if ($s === '') {
            return false;
        }
        // Solo letras, espacios, guiones y acentos (unicode letters + marks)
        if (!preg_match('/^[\p{L}\p{M}\s-]+$/u', $s)) {
            return false;
        }
        $w = self::words($s);
        if (count($w) < 2) {
            return false;
        }
        foreach ($w as $word) {
            if (!preg_match('/^[\p{L}\p{M}-]{3,}$/u', $word)) {
                return false;
            }
        }

        return true;
    }

    private static function isCityValid(string $s): bool
    {
        $s = self::normSpaces($s);
        if (mb_strlen($s) < 2) {
            return false;
        }
        if (preg_match('/^\d+$/u', $s)) {
            return false;
        }
        if (!preg_match('/^[\p{L}\p{M}\s-]+$/u', $s)) {
            return false;
        }

        return true;
    }

    private static function isProvinceValid(string $s): bool
    {
        $s = self::normSpaces($s);
        if (mb_strlen($s) < 2) {
            return false;
        }
        if (!preg_match('/^[\p{L}\p{M}\s-]+$/u', $s)) {
            return false;
        }

        return true;
    }

    private static function isAddressValid(string $s): bool
    {
        $s = trim($s);
        if (mb_strlen($s) < 5) {
            return false;
        }
        // Debe contener al menos un número (número de calle)
        if (!preg_match('/\d/u', $s)) {
            return false;
        }

        return true;
    }

    private static function isPostalValid(string $s): bool
    {
        $s = self::normSpaces($s);
        $len = mb_strlen($s);
        if ($len < 4 || $len > 10) {
            return false;
        }
        if (!preg_match('/^[0-9]+$/', $s)) {
            return false;
        }

        return true;
    }

    private static function isPhoneValidOrEmpty(string $s): bool
    {
        $s = trim($s);
        if ($s === '') {
            return true;
        }
        if (!preg_match('/^[0-9\s()+-]+$/', $s)) {
            return false;
        }
        $digits = preg_replace('/\D+/', '', $s) ?? '';
        $n = strlen($digits);
        return $n >= 7 && $n <= 15;
    }

    /** @return list<int> */
    private static function allowedPacks(): array
    {
        return [1, 3, 5, 10];
    }

    public function index(): void
    {
        $variety = isset($_GET['variety']) && is_string($_GET['variety']) ? trim($_GET['variety']) : '';
        if ($variety !== '' && !preg_match('/^[a-z0-9-]{1,64}$/', $variety)) {
            $variety = '';
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $subAction = isset($_POST['checkout_action']) && is_string($_POST['checkout_action']) ? trim($_POST['checkout_action']) : '';
            if ($subAction === 'shipping_rates') {
                $this->postShippingRates();
                return;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();

            return;
        }

        $this->handleGet($variety);
    }

    /** @return list<array<string, mixed>> */
    private function cartSession(): array
    {
        $c = $_SESSION[self::SESSION_CART] ?? null;
        if (!is_array($c)) {
            return [];
        }

        return array_values(array_filter($c, static fn ($row): bool => is_array($row)));
    }

    /** @param list<array<string, mixed>> $lines */
    private function saveCartSession(array $lines): void
    {
        $_SESSION[self::SESSION_CART] = $lines;
    }

    /** @return list<array<string, mixed>> */
    private function sanitizeCartLines(array $lines): array
    {
        $out = [];
        foreach ($lines as $row) {
            if (!is_array($row)) {
                continue;
            }
            $v = isset($row['variety']) && is_string($row['variety']) ? $row['variety'] : '';
            $pack = isset($row['pack']) ? (int) $row['pack'] : 0;
            $qty  = isset($row['quantity']) ? (int) $row['quantity'] : 1;
            if ($v === '' || !in_array($pack, self::allowedPacks(), true)) {
                continue;
            }
            if ($qty < 1) {
                $qty = 1;
            }
            if ($qty > 99) {
                $qty = 99;
            }
            $resolved = CheckoutCatalog::resolve($v);
            if ($resolved === null) {
                continue;
            }
            $unit = (int) $resolved['price_cents'];
            $sub  = $unit * $pack * $qty;
            $out[] = [
                'variety'      => $v,
                'pack'         => $pack,
                'quantity'     => $qty,
                'price_cents'  => $unit,
                'subtotal'     => $sub,
                'product_id'   => (int) $resolved['product_id'],
                'title_lang_key' => (string) $resolved['title_lang_key'],
            ];
        }

        return $out;
    }

    private function redirectToCheckout(string $variety): void
    {
        $q = $variety !== '' ? '?variety=' . rawurlencode($variety) : '';
        header('Location: ' . url_lang('/checkout') . $q, true, 303);
        exit;
    }

    private function handleGet(string $varietyParam): void
    {
        $this->saveCartSession($this->sanitizeCartLines($this->cartSession()));

        $slugs = CheckoutCatalog::varietySlugs();
        if ($slugs === []) {
            $this->notFoundPage();

            return;
        }

        $variety = $varietyParam;
        if ($variety === '' || !in_array($variety, $slugs, true)) {
            $sess = $_SESSION['checkout_variety'] ?? null;
            $variety = is_string($sess) && in_array($sess, $slugs, true) ? $sess : $slugs[0];
        }

        $line = CheckoutCatalog::resolve($variety);
        if ($line === null) {
            $variety = $slugs[0];
            $line    = CheckoutCatalog::resolve($variety);
            if ($line === null) {
                $this->notFoundPage();

                return;
            }
        }

        $_SESSION['checkout_variety'] = $variety;
        checkout_csrf_token();

        $err = $_SESSION['checkout_flash_error'] ?? null;
        unset($_SESSION['checkout_flash_error']);

        $checkoutOld = [];
        $oldRaw = $_SESSION[self::SESSION_FORM_OLD] ?? null;
        if (is_array($oldRaw)) {
            $checkoutOld = $oldRaw;
            unset($_SESSION[self::SESSION_FORM_OLD]);
        }

        $checkoutFieldErrors = [];
        $fer = $_SESSION[self::SESSION_FIELD_ERRORS] ?? null;
        if (is_array($fer)) {
            $checkoutFieldErrors = $fer;
            unset($_SESSION[self::SESSION_FIELD_ERRORS]);
        }

        if (($err === null || $err === '') && $checkoutFieldErrors !== []) {
            $err = Lang::t('checkout.error_validation_summary');
        }

        $all   = CheckoutCatalog::allResolved();
        $chips = array_values(array_filter($all, static fn (array $r): bool => $r['variety'] !== $variety));

        $cartLines = $this->cartSession();
        $cartView  = $this->buildCartView($cartLines);

        $catalogForJs = [];
        foreach ($all as $r) {
            $catalogForJs[] = [
                'variety'          => $r['variety'],
                'unitCents'        => (int) $r['price_cents'],
                'hero'             => asset_url((string) $r['hero']),
                'title'            => Lang::raw((string) $r['title_lang_key']),
                'tagline'          => Lang::raw((string) $r['tagline_lang_key']),
                'titleLangKey'     => (string) $r['title_lang_key'],
                'taglineLangKey'   => (string) $r['tagline_lang_key'],
            ];
        }

        $this->render('checkout', [
            'pageTitleKey'         => 'checkout.page_title',
            'metaDescriptionKey'   => 'checkout.meta_description',
            'checkoutUi'             => true,
            'extraModuleSrc'         => null,
            'checkoutLine'           => $line,
            'variety'                => $variety,
            'varietyOptions'         => $all,
            'varietyChips'           => $chips,
            'formError'              => is_string($err) ? $err : null,
            'redsysPayment'          => null,
            'redsysUrl'              => null,
            'freeShippingSubtotal'   => self::FREE_SHIPPING_SUBTOTAL_CENTS,
            'cartLines'              => $cartLines,
            'cartView'               => $cartView,
            'catalogJson'            => json_encode(
                $catalogForJs,
                JSON_THROW_ON_ERROR
                | JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS,
            ),
            'checkoutOld'            => $checkoutOld,
            'checkoutFieldErrors'    => $checkoutFieldErrors,
            'payValidateMsgsJson'    => $this->buildPayValidateMsgsJson(),
            'countryCodeSet'         => self::countryCodeSet(),
        ]);
    }

    private function postShippingRates(): void
    {
        if (!checkout_verify_csrf((string) ($_POST['csrf'] ?? ''))) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => 'csrf'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $country = isset($_POST['country_code']) && is_string($_POST['country_code']) ? strtoupper(trim($_POST['country_code'])) : '';
        $postal  = isset($_POST['postal_code']) && is_string($_POST['postal_code']) ? trim($_POST['postal_code']) : '';

        if ($country !== '' && strlen($country) !== 2) {
            $country = substr($country, 0, 2);
        }
        if ($postal !== '' && !preg_match('/^\d{4,10}$/', $postal)) {
            $postal = '';
        }

        // Subtotal lo calcula el servidor para seguridad, pero aceptamos el enviado para UX (no confiable).
        $cart = $this->sanitizeCartLines($this->cartSession());
        $this->saveCartSession($cart);
        $subtotal = 0;
        foreach ($cart as $row) {
            $subtotal += (int) ($row['subtotal'] ?? 0);
        }

        $free = $subtotal >= self::FREE_SHIPPING_SUBTOTAL_CENTS;

        try {
            $rates = [];
            if ($country !== '' && $postal !== '') {
                $svc = new PacklinkService(PacklinkService::loadConfig());
                $rates = $svc->getShippingRates($country, (int) $postal);
            }

            if ($rates === []) {
                // Sin opciones (o datos insuficientes)
                $this->savePacklinkQuote(null);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => true, 'free_shipping' => $free, 'subtotal_cents' => $subtotal, 'quote_key' => null, 'options' => []], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($free) {
                foreach ($rates as &$r) {
                    $r['price_cents'] = 0;
                }
                unset($r);
            }

            $quoteKey = $this->savePacklinkQuote([
                'created_at'     => time(),
                'quote_key'      => bin2hex(random_bytes(16)),
                'country'        => $country,
                'postal'         => $postal,
                'subtotal_cents' => $subtotal,
                'free'           => $free,
                'options'        => $rates,
            ]);

            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(
                [
                    'ok' => true,
                    'free_shipping' => $free,
                    'subtotal_cents' => $subtotal,
                    'quote_key' => $quoteKey,
                    'options' => $rates,
                ],
                JSON_UNESCAPED_UNICODE,
            );
            exit;
        } catch (\Throwable $e) {
            error_log('Packlink shipping rates: ' . $e->getMessage());
            $this->savePacklinkQuote(null);
            http_response_code(502);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => 'packlink_unavailable'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** @param array<string, mixed>|null $quote */
    private function savePacklinkQuote(?array $quote): ?string
    {
        if ($quote === null) {
            unset($_SESSION[self::SESSION_PACKLINK_QUOTE]);
            return null;
        }

        if (!isset($quote['quote_key']) || !is_string($quote['quote_key']) || $quote['quote_key'] === '') {
            return null;
        }

        $_SESSION[self::SESSION_PACKLINK_QUOTE] = $quote;
        return (string) $quote['quote_key'];
    }

    /** @return array<string, mixed>|null */
    private function readPacklinkQuote(): ?array
    {
        $q = $_SESSION[self::SESSION_PACKLINK_QUOTE] ?? null;
        if (!is_array($q)) {
            return null;
        }
        $createdAt = isset($q['created_at']) ? (int) $q['created_at'] : 0;
        if ($createdAt <= 0 || (time() - $createdAt) > self::PACKLINK_QUOTE_TTL_SECONDS) {
            unset($_SESSION[self::SESSION_PACKLINK_QUOTE]);
            return null;
        }
        return $q;
    }

    /** @return array<string, mixed> */
    private function readCheckoutFormFromPost(): array
    {
        $s = static function (string $key): string {
            return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
        };

        $country = strtoupper(mb_substr($s('country'), 0, 2));

        return [
            'email'          => mb_substr($s('email'), 0, 254),
            'name'           => mb_substr($s('name'), 0, 200),
            'address'        => mb_substr($s('address'), 0, 500),
            'postal'         => mb_substr($s('postal'), 0, 32),
            'city'           => mb_substr($s('city'), 0, 120),
            'province'       => mb_substr($s('province'), 0, 120),
            'country'        => $country,
            'phone'          => mb_substr($s('phone'), 0, 40),
            'shipping'       => isset($_POST['shipping']) && $_POST['shipping'] === 'pickup' ? 'pickup' : 'standard',
            'payment'        => isset($_POST['payment']) && $_POST['payment'] === 'transfer' ? 'transfer' : 'card',
            'guest'          => isset($_POST['guest']),
            'create_account' => isset($_POST['create_account']),
        ];
    }

    private function resolveVarietyUrlForRedirect(string $postedV): string
    {
        $slugs = CheckoutCatalog::varietySlugs();
        if ($postedV !== '' && in_array($postedV, $slugs, true)) {
            return $postedV;
        }
        $sess = $_SESSION['checkout_variety'] ?? null;
        if (is_string($sess) && in_array($sess, $slugs, true)) {
            return $sess;
        }

        return $slugs[0] ?? 'dj-piggy';
    }

    /**
     * @param array<string, string> $fieldLangKeys
     */
    private function flashCheckoutValidation(array $old, array $fieldLangKeys, string $varietyUrl, ?string $bannerEscaped): void
    {
        $_SESSION[self::SESSION_FORM_OLD]      = $old;
        $_SESSION[self::SESSION_FIELD_ERRORS] = $fieldLangKeys;
        if ($bannerEscaped !== null && $bannerEscaped !== '') {
            $_SESSION['checkout_flash_error'] = $bannerEscaped;
        }
        header('Location: ' . url_lang('/checkout') . '?variety=' . rawurlencode($varietyUrl), true, 303);
        exit;
    }

    /**
     * @param array<string, mixed> $o
     *
     * @return array<string, string> map field id key => i18n key
     */
    private function validateCheckoutPayFields(array $o): array
    {
        $e = [];
        if ($o['email'] === '') {
            $e['email'] = 'checkout.field_required_email';
        } elseif (!filter_var((string) $o['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'checkout.field_invalid_email';
        }

        $name = (string) $o['name'];
        $nameLen = mb_strlen($name);
        if ($nameLen === 0) {
            $e['name'] = 'checkout.field_required_name';
        } elseif ($nameLen > 200) {
            $e['name'] = 'checkout.field_error_name_len';
        } elseif (!self::isNameValid($name)) {
            $e['name'] = 'checkout.field_invalid_name';
        }

        $addr = (string) $o['address'];
        if ($addr === '') {
            $e['address'] = 'checkout.field_required_address';
        } elseif (mb_strlen($addr) > 500) {
            $e['address'] = 'checkout.field_error_address_len';
        } elseif (!self::isAddressValid($addr)) {
            $e['address'] = 'checkout.field_invalid_address';
        }

        $postal = (string) $o['postal'];
        if ($postal === '') {
            $e['postal'] = 'checkout.field_required_postal';
        } elseif (mb_strlen($postal) > 32) {
            $e['postal'] = 'checkout.field_error_postal_len';
        } elseif (!self::isPostalValid($postal)) {
            $e['postal'] = 'checkout.field_invalid_postal';
        }

        $city = (string) $o['city'];
        if ($city === '') {
            $e['city'] = 'checkout.field_required_city';
        } elseif (mb_strlen($city) > 120) {
            $e['city'] = 'checkout.field_error_city_len';
        } elseif (!self::isCityValid($city)) {
            $e['city'] = 'checkout.field_invalid_city';
        }

        $prov = (string) $o['province'];
        if ($prov === '') {
            $e['province'] = 'checkout.field_required_province';
        } elseif (mb_strlen($prov) > 120) {
            $e['province'] = 'checkout.field_error_province_len';
        } elseif (!self::isProvinceValid($prov)) {
            $e['province'] = 'checkout.field_invalid_province';
        }

        $country = (string) $o['country'];
        if ($country === '') {
            $e['country'] = 'checkout.field_required_country';
        } elseif (!isset(self::countryCodeSet()[$country])) {
            $e['country'] = 'checkout.field_invalid_country';
        }

        $phone = (string) $o['phone'];
        if (mb_strlen($phone) > 40) {
            $e['phone'] = 'checkout.field_error_phone_len';
        } elseif (!self::isPhoneValidOrEmpty($phone)) {
            $e['phone'] = 'checkout.field_invalid_phone';
        }

        return $e;
    }

    private function buildPayValidateMsgsJson(): string
    {
        $m = [
            'cartEmpty'         => Lang::raw('checkout.field_error_cart_empty'),
            'emailRequired'     => Lang::raw('checkout.field_required_email'),
            'emailInvalid'      => Lang::raw('checkout.field_invalid_email'),
            'nameRequired'      => Lang::raw('checkout.field_required_name'),
            'nameInvalid'       => Lang::raw('checkout.field_invalid_name'),
            'addressRequired'   => Lang::raw('checkout.field_required_address'),
            'addressInvalid'    => Lang::raw('checkout.field_invalid_address'),
            'postalRequired'    => Lang::raw('checkout.field_required_postal'),
            'postalInvalid'     => Lang::raw('checkout.field_invalid_postal'),
            'cityRequired'      => Lang::raw('checkout.field_required_city'),
            'cityInvalid'       => Lang::raw('checkout.field_invalid_city'),
            'provinceRequired'  => Lang::raw('checkout.field_required_province'),
            'provinceInvalid'   => Lang::raw('checkout.field_invalid_province'),
            'countryRequired'   => Lang::raw('checkout.field_required_country'),
            'countryInvalid'    => Lang::raw('checkout.field_invalid_country'),
            'phoneTooLong'      => Lang::raw('checkout.field_error_phone_len'),
            'phoneInvalid'      => Lang::raw('checkout.field_invalid_phone'),
            'paymentCard'       => Lang::raw('checkout.field_error_payment_card'),
        ];

        return json_encode(
            $m,
            JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS,
        );
    }

    /**
     * @param list<array<string, mixed>> $cartLines
     *
     * @return list<array{variety: string, pack: int, quantity: int, subtotal_cents: int, title: string, pack_label: string}>
     */
    private function buildCartView(array $cartLines): array
    {
        $out = [];
        foreach ($cartLines as $row) {
            if (!is_array($row)) {
                continue;
            }
            $pack = (int) ($row['pack'] ?? 0);
            $pk   = 'checkout.pack_' . $pack;
            $out[] = [
                'variety'         => (string) ($row['variety'] ?? ''),
                'pack'            => $pack,
                'quantity'        => (int) ($row['quantity'] ?? 1),
                'subtotal_cents'  => (int) ($row['subtotal'] ?? 0),
                'title'           => Lang::t((string) ($row['title_lang_key'] ?? 'checkout.label_variety')),
                'pack_label'      => Lang::t($pk),
            ];
        }

        return $out;
    }

    private function handlePost(): void
    {
        if (!checkout_verify_csrf((string) ($_POST['csrf'] ?? ''))) {
            $_SESSION['checkout_flash_error'] = Lang::t('checkout.error_csrf');
            $this->redirectBackToCheckout();
            exit;
        }

        $action = isset($_POST['cart_action']) && is_string($_POST['cart_action']) ? trim($_POST['cart_action']) : '';
        if ($action === 'add') {
            $this->postCartAdd();
            exit;
        }
        if ($action === 'remove') {
            $this->postCartRemove();
            exit;
        }

        $this->postCheckoutPay();
    }

    private function postCartAdd(): void
    {
        $postedV = isset($_POST['variety']) && is_string($_POST['variety']) ? trim($_POST['variety']) : '';
        if (!in_array($postedV, CheckoutCatalog::varietySlugs(), true)) {
            $_SESSION['checkout_flash_error'] = Lang::t('checkout.error_session');
            $this->redirectBackToCheckout();
            exit;
        }

        $pack = isset($_POST['pack']) ? (int) $_POST['pack'] : 1;
        if (!in_array($pack, self::allowedPacks(), true)) {
            $pack = 1;
        }

        $resolved = CheckoutCatalog::resolve($postedV);
        if ($resolved === null) {
            $_SESSION['checkout_flash_error'] = Lang::t('checkout.error_session');
            $this->redirectBackToCheckout();
            exit;
        }

        $unit = (int) $resolved['price_cents'];
        $qty  = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
        if ($qty < 1) {
            $qty = 1;
        }
        if ($qty > 99) {
            $qty = 99;
        }

        $line = [
            'variety'          => $postedV,
            'pack'             => $pack,
            'quantity'         => $qty,
            'price_cents'      => $unit,
            'subtotal'         => $unit * $pack * $qty,
            'product_id'       => (int) $resolved['product_id'],
            'title_lang_key'   => (string) $resolved['title_lang_key'],
        ];

        $cart   = $this->cartSession();
        $cart[] = $line;
        $this->saveCartSession($this->sanitizeCartLines($cart));
        $_SESSION['checkout_variety'] = $postedV;

        $this->redirectToCheckout($postedV);
    }

    private function postCartRemove(): void
    {
        $idx = isset($_POST['line_index']) ? (int) $_POST['line_index'] : -1;
        $cart = $this->cartSession();
        if ($idx < 0 || $idx >= count($cart)) {
            $v = isset($_SESSION['checkout_variety']) && is_string($_SESSION['checkout_variety']) ? $_SESSION['checkout_variety'] : '';
            $this->redirectToCheckout($v);

            return;
        }

        unset($cart[$idx]);
        $this->saveCartSession(array_values($cart));

        $v = isset($_SESSION['checkout_variety']) && is_string($_SESSION['checkout_variety']) ? $_SESSION['checkout_variety'] : '';
        $this->redirectToCheckout($v);
    }

    private function postCheckoutPay(): void
    {
        $o = $this->readCheckoutFormFromPost();

        $postedV = isset($_POST['variety']) && is_string($_POST['variety']) ? trim($_POST['variety']) : '';
        $varietyUrl = $this->resolveVarietyUrlForRedirect($postedV);

        $cart = $this->sanitizeCartLines($this->cartSession());
        $this->saveCartSession($cart);

        if ($cart === []) {
            $this->flashCheckoutValidation(
                $o,
                ['cart' => 'checkout.field_error_cart_empty'],
                $varietyUrl,
                Lang::t('checkout.error_cart_empty'),
            );
        }

        if ($postedV !== '' && in_array($postedV, CheckoutCatalog::varietySlugs(), true)) {
            $_SESSION['checkout_variety'] = $postedV;
        }

        $variety = isset($_SESSION['checkout_variety']) && is_string($_SESSION['checkout_variety'])
            ? $_SESSION['checkout_variety']
            : (string) ($cart[0]['variety'] ?? '');

        if ($o['payment'] !== 'card') {
            $this->flashCheckoutValidation(
                $o,
                ['payment' => 'checkout.field_error_payment_card'],
                $varietyUrl,
                Lang::t('checkout.error_payment_card'),
            );
        }

        $fieldErrs = $this->validateCheckoutPayFields($o);
        if ($fieldErrs !== []) {
            $this->flashCheckoutValidation($o, $fieldErrs, $varietyUrl, null);
        }

        $shipping = $o['shipping'] === 'pickup' ? 'pickup' : 'standard';
        $email    = (string) $o['email'];
        $name     = (string) $o['name'];
        $address  = (string) $o['address'];
        $postal   = (string) $o['postal'];
        $city     = (string) $o['city'];
        $province = (string) $o['province'];
        $country  = (string) $o['country'];
        $phone    = (string) $o['phone'];

        $subtotal = 0;
        foreach ($cart as $row) {
            $subtotal += (int) ($row['subtotal'] ?? 0);
        }

        // Envío seleccionado via Packlink: validar contra quote en sesión (no confiar en el cliente).
        $shipCents = 0;
        $quoteKey = isset($_POST['shipping_quote_key']) && is_string($_POST['shipping_quote_key']) ? trim($_POST['shipping_quote_key']) : '';
        $optId    = isset($_POST['shipping_option_id']) && is_string($_POST['shipping_option_id']) ? trim($_POST['shipping_option_id']) : '';

        $free = $subtotal >= self::FREE_SHIPPING_SUBTOTAL_CENTS;
        if ($free) {
            $shipCents = 0;
        } else {
            $q = $this->readPacklinkQuote();
            if ($q === null || $quoteKey === '' || $optId === '' || (string) ($q['quote_key'] ?? '') !== $quoteKey) {
                $this->flashCheckoutValidation($o, ['shipping' => 'checkout.shipping_missing'], $varietyUrl, Lang::t('checkout.shipping_missing'));
            }

            $opts = $q['options'] ?? null;
            if (!is_array($opts)) {
                $this->flashCheckoutValidation($o, ['shipping' => 'checkout.shipping_missing'], $varietyUrl, Lang::t('checkout.shipping_missing'));
            }

            $matched = null;
            foreach ($opts as $r) {
                if (is_array($r) && isset($r['id']) && (string) $r['id'] === $optId) {
                    $matched = $r;
                    break;
                }
            }
            if ($matched === null) {
                $this->flashCheckoutValidation($o, ['shipping' => 'checkout.shipping_missing'], $varietyUrl, Lang::t('checkout.shipping_missing'));
            }
            $shipCents = (int) ($matched['price_cents'] ?? 0);
            if ($shipCents < 0) {
                $shipCents = 0;
            }
        }

        $totalCents = $subtotal + $shipCents;

        $guest         = !empty($o['guest']);
        $createAccount = !empty($o['create_account']);

        $linesForDb = [];
        foreach ($cart as $row) {
            $linesForDb[] = [
                'variety'     => (string) $row['variety'],
                'pack'        => (int) $row['pack'],
                'quantity'    => (int) $row['quantity'],
                'price_cents' => (int) $row['price_cents'],
                'subtotal'    => (int) $row['subtotal'],
                'product_id'  => (int) $row['product_id'],
            ];
        }

        $shippingPayload = [
            'cart_lines'       => $linesForDb,
            'shipping'         => $shipping,
            'payment'          => 'card',
            'email'            => $email,
            'address_line'     => $address,
            'postal'           => $postal,
            'city'             => $city,
            'province'         => $province,
            'country'          => $country,
            'phone'            => $phone,
            'guest'            => $guest,
            'create_account'   => $createAccount,
            'subtotal_cents'   => $subtotal,
            'shipping_cents'   => $shipCents,
            'total_cents'      => $totalCents,
        ];

        $shippingJson = json_encode(
            $shippingPayload,
            JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE,
        );

        $primaryProductId = (int) $cart[0]['product_id'];

        try {
            $created = Order::createPending(
                $primaryProductId,
                $totalCents,
                $name,
                $email,
                $shippingJson,
            );
        } catch (\Throwable) {
            $this->flashCheckoutValidation($o, [], $varietyUrl, Lang::t('checkout.error_order'));
        }

        $base   = rtrim((string) (defined('BASE_URL') ? BASE_URL : base_url()), '/');
        $lang   = Lang::current();
        $redsys = new RedsysService(RedsysService::loadConfig());

        $descParts = [];
        foreach ($cart as $row) {
            $r = CheckoutCatalog::resolve((string) $row['variety']);
            if ($r !== null) {
                $t = mb_substr(Lang::raw((string) $r['title_lang_key']), 0, 40);
                $descParts[] = $t . '×' . (string) ((int) $row['pack'] * (int) $row['quantity']);
            }
        }
        $desc = mb_substr(implode(', ', $descParts), 0, 120);
        if ($desc === '') {
            $desc = 'Tarumba order';
        }

        try {
            $paymentParams = $redsys->buildPaymentParams([
                'amount_cents'         => $totalCents,
                'order_id'             => $created['order_ref'],
                'lang'                 => $lang,
                'notify_url'           => $base . '/redsys/notify',
                'ok_url'               => $base . '/' . $lang . '/checkout/ok',
                'ko_url'               => $base . '/' . $lang . '/checkout/ko',
                'product_description'  => $desc,
            ]);
        } catch (\Throwable $e) {
            error_log('Redsys buildPaymentParams: ' . $e->getMessage());
            $this->flashCheckoutValidation($o, [], $varietyUrl, Lang::t('checkout.error_payment_init'));
        }

        unset($_SESSION['csrf_checkout']);
        $this->saveCartSession([]);

        $line = CheckoutCatalog::resolve($variety);
        if ($line === null) {
            $line = CheckoutCatalog::resolve((string) $cart[0]['variety']);
        }
        if ($line === null) {
            $this->notFoundPage();

            return;
        }

        $all   = CheckoutCatalog::allResolved();
        $chips = array_values(array_filter($all, static fn (array $r): bool => $r['variety'] !== $line['variety']));

        $this->render('checkout', [
            'pageTitleKey'        => 'checkout.page_title_pay',
            'metaDescriptionKey'  => 'checkout.meta_description',
            'checkoutUi'          => true,
            'extraModuleSrc'        => null,
            'checkoutLine'        => $line,
            'variety'               => (string) $line['variety'],
            'varietyOptions'        => $all,
            'varietyChips'          => $chips,
            'formError'             => null,
            'redsysPayment'         => $paymentParams,
            'redsysUrl'             => $redsys->getEndpointUrl(),
            'freeShippingSubtotal'  => self::FREE_SHIPPING_SUBTOTAL_CENTS,
            'cartLines'             => [],
            'cartView'              => [],
            'catalogJson'           => '[]',
            'checkoutOld'           => [],
            'checkoutFieldErrors'   => [],
            'payValidateMsgsJson'   => '{}',
        ]);
    }

    private function redirectBackToCheckout(): void
    {
        $v = isset($_SESSION['checkout_variety']) && is_string($_SESSION['checkout_variety'])
            ? $_SESSION['checkout_variety']
            : '';
        if ($v === '' && CheckoutCatalog::varietySlugs() !== []) {
            $v = CheckoutCatalog::varietySlugs()[0];
        }
        header('Location: ' . url_lang('/checkout') . ($v !== '' ? '?variety=' . rawurlencode($v) : ''), true, 303);
    }

    private function notFoundPage(): void
    {
        http_response_code(404);
        require dirname(__DIR__, 2) . '/templates/404.php';
        exit;
    }
}
