<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Lead;
use App\Models\Order;
use App\Services\CheckoutCatalog;
use App\Services\MailService;
use App\Services\RedsysService;

final class RedsysController extends BaseController
{
    public function notify(): void
    {
        $version  = (string) ($_POST['Ds_SignatureVersion'] ?? '');
        $params64 = (string) ($_POST['Ds_MerchantParameters'] ?? '');
        $sig      = (string) ($_POST['Ds_Signature'] ?? '');

        try {
            $redsys = new RedsysService(RedsysService::loadConfig());
            $data   = $redsys->validateNotification($version, $params64, $sig);
        } catch (\Throwable $e) {
            error_log('Redsys notify: ' . $e->getMessage());
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'INVALID';

            return;
        }

        $orderRef = self::notifParam($data, 'Ds_Order');
        $respRaw  = self::notifParam($data, 'Ds_Response');
        $code     = (int) $respRaw;

        if ($orderRef === '') {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'NO_ORDER';

            return;
        }

        if ($code < 100) {
            try {
                $transitioned = Order::markAsPaid($orderRef, $data);
                if ($transitioned) {
                    $this->onPaid($orderRef);
                }
            } catch (\Throwable $e) {
                error_log('Order::markAsPaid: ' . $e->getMessage());
            }
            http_response_code(200);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'OK';

            return;
        }

        try {
            Order::markAsFailed($orderRef, $code);
        } catch (\Throwable $e) {
            error_log('Order::markAsFailed: ' . $e->getMessage());
        }

        http_response_code(200);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'KO';
    }

    public function ok(): void
    {
        $variety = isset($_SESSION['checkout_variety']) && is_string($_SESSION['checkout_variety'])
            ? $_SESSION['checkout_variety']
            : null;

        $this->render('checkout_ok', [
            'pageTitleKey'       => 'checkout.ok_page_title',
            'metaDescriptionKey' => 'checkout.meta_description',
            'checkoutUi'         => true,
            'extraModuleSrc'     => null,
            'checkoutVariety'    => $variety,
        ]);
    }

    public function ko(): void
    {
        $variety = isset($_SESSION['checkout_variety']) && is_string($_SESSION['checkout_variety'])
            ? $_SESSION['checkout_variety']
            : null;

        $this->render('checkout_ko', [
            'pageTitleKey'       => 'checkout.ko_page_title',
            'metaDescriptionKey' => 'checkout.meta_description',
            'checkoutUi'         => true,
            'extraModuleSrc'     => null,
            'checkoutVariety'    => $variety,
        ]);
    }

    /** @param array<string, mixed> $params */
    private static function notifParam(array $params, string $name): string
    {
        $want = strtolower($name);
        foreach ($params as $k => $v) {
            if (strtolower((string) $k) === $want) {
                return (string) $v;
            }
        }

        return '';
    }

    private function onPaid(string $orderRef): void
    {
        $order = Order::getForEmail($orderRef);
        if ($order === null) {
            return;
        }

        $ship = $order['shipping'] ?? [];
        $lang = is_array($ship) && isset($ship['lang']) && is_string($ship['lang']) ? strtolower(substr($ship['lang'], 0, 2)) : 'es';
        if ($lang !== 'en' && $lang !== 'es') {
            $lang = 'es';
        }

        $customerEmail = (string) ($order['customer_email'] ?? '');
        $customerName  = (string) ($order['customer_name'] ?? '');

        // Lead: solo tras pago confirmado (idempotente por UNIQUE(email))
        try {
            Lead::upsert($customerEmail, $customerName, $lang, 'checkout');
        } catch (\Throwable $e) {
            error_log('Lead::upsert: ' . $e->getMessage());
        }

        $mail = new MailService(MailService::loadConfig());

        // Email cliente
        if ($customerEmail !== '') {
            $subjectClient = $lang === 'en'
                ? "Thanks for your order! · Tarumba's Farm"
                : "¡Gracias por tu pedido! · Tarumba's Farm";

            [$htmlClient, $textClient] = $this->renderCustomerEmail($lang, $order);

            try {
                $mail->send($customerEmail, $subjectClient, $htmlClient, $textClient);
            } catch (\Throwable $e) {
                error_log('Mail customer: ' . $e->getMessage());
            }
        }

        // Email interno
        $internalTo = 'pepebulkov@tarumbasfarm.com';
        $subjectInternal = '🌱 Nuevo pedido #' . $orderRef . " — Tarumba's Farm";
        [$htmlInternal, $textInternal] = $this->renderInternalEmail($order);
        try {
            $mail->send($internalTo, $subjectInternal, $htmlInternal, $textInternal);
        } catch (\Throwable $e) {
            error_log('Mail internal: ' . $e->getMessage());
        }
    }

    /**
     * @param array{
     *   order_ref: string,
     *   amount_cents: int,
     *   customer_name: string|null,
     *   customer_email: string|null,
     *   shipping: array<string, mixed>,
     *   product: array{name_es: string, name_en: string, slug_es: string, slug_en: string}
     * } $order
     * @return array{0: string, 1: string} html + text
     */
    private function renderCustomerEmail(string $lang, array $order): array
    {
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        $orderRef = (string) $order['order_ref'];
        $ship     = $order['shipping'] ?? [];
        $name     = (string) ($order['customer_name'] ?? '');

        $cartLines = [];
        if (is_array($ship) && isset($ship['cart_lines']) && is_array($ship['cart_lines'])) {
            $cartLines = $ship['cart_lines'];
        }

        $address = is_array($ship) && isset($ship['address_line']) ? (string) $ship['address_line'] : '';
        $postal  = is_array($ship) && isset($ship['postal']) ? (string) $ship['postal'] : '';
        $city    = is_array($ship) && isset($ship['city']) ? (string) $ship['city'] : '';
        $province = is_array($ship) && isset($ship['province']) ? (string) $ship['province'] : '';
        $country = is_array($ship) && isset($ship['country']) ? (string) $ship['country'] : '';

        $shippingMethod = is_array($ship) && isset($ship['shipping']) ? (string) $ship['shipping'] : '';
        $shippingCents  = is_array($ship) && isset($ship['shipping_cents']) ? (int) $ship['shipping_cents'] : null;
        $totalCents     = is_array($ship) && isset($ship['total_cents']) ? (int) $ship['total_cents'] : (int) $order['amount_cents'];

        $title = $lang === 'en' ? 'ORDER CONFIRMED' : 'PEDIDO CONFIRMADO';
        $hello = $name !== ''
            ? ($lang === 'en' ? ('Hi ' . $name . ',') : ('Hola ' . $name . ','))
            : ($lang === 'en' ? 'Hi,' : 'Hola,');

        $labelSummary = $lang === 'en' ? 'Order summary' : 'Resumen del pedido';
        $labelShipTo  = $lang === 'en' ? 'Shipping address' : 'Dirección de envío';
        $labelShipMet = $lang === 'en' ? 'Shipping method' : 'Método de envío';
        $labelTotal   = $lang === 'en' ? 'Total' : 'Total';
        $labelOrder   = $lang === 'en' ? 'Order' : 'Pedido';
        $labelThanks  = $lang === 'en'
            ? 'Thanks for supporting Tarumba’s Farm.'
            : 'Gracias por apoyar a Tarumba\'s Farm.';

        $shipLabel = $shippingMethod === 'pickup'
            ? ($lang === 'en' ? 'Pickup' : 'Recogida')
            : ($lang === 'en' ? 'Standard shipping' : 'Envío estándar');
        if ($shippingCents !== null) {
            $shipLabel .= ' (' . format_price_cents((int) $shippingCents) . ')';
        }

        $linesHtml = '';
        $linesTxt  = '';
        if (is_array($cartLines) && $cartLines !== []) {
            foreach ($cartLines as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $variety = isset($line['variety']) ? (string) $line['variety'] : '';
                $pack    = isset($line['pack']) ? (int) $line['pack'] : 1;
                $qty     = isset($line['quantity']) ? (int) $line['quantity'] : 1;
                $subtotal = isset($line['subtotal']) ? (int) $line['subtotal'] : null;

                $resolved = $variety !== '' ? CheckoutCatalog::resolve($variety) : null;
                $vName = $resolved !== null
                    ? (string) ($lang === 'en' ? ($resolved['title_en'] ?? '') : ($resolved['title_es'] ?? ''))
                    : $variety;
                if ($vName === '') {
                    $vName = $variety !== '' ? $variety : '—';
                }

                $qtyUnits = $pack * $qty;
                $lineLabel = $vName . ' × ' . (string) $qtyUnits;
                $linesHtml .= '<tr><td style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,.12)"><span style="font-weight:700">' . $e($lineLabel) . '</span>'
                    . ($subtotal !== null ? '<div style="color:#bcbcbc;margin-top:4px;font-size:14px">' . $e(format_price_cents((int) $subtotal)) . '</div>' : '')
                    . '</td></tr>';
                $linesTxt .= '- ' . $lineLabel . ($subtotal !== null ? (' — ' . format_price_cents((int) $subtotal)) : '') . "\n";
            }
        } else {
            $fallbackName = $lang === 'en' ? (string) $order['product']['name_en'] : (string) $order['product']['name_es'];
            if ($fallbackName === '') {
                $fallbackName = $lang === 'en' ? 'Product' : 'Producto';
            }
            $linesHtml = '<tr><td style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,.12)"><span style="font-weight:700">' . $e($fallbackName) . '</span></td></tr>';
            $linesTxt  = '- ' . $fallbackName . "\n";
        }

        $addrOneLine = trim($address . ', ' . $postal . ' ' . $city . ', ' . $province . ', ' . $country, " ,");

        $html = '<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;background:#0b0b10;color:#f6f6f6;font-family:Arial,Helvetica,sans-serif">'
            . '<div style="max-width:720px;margin:0 auto;padding:24px">'
            . '<div style="border:6px solid #000;background:linear-gradient(135deg,#111827,#0b0b10);box-shadow:10px 10px #000;padding:22px">'
            . '<div style="font-family:\'Bangers\',Arial,sans-serif;letter-spacing:1px;text-transform:uppercase;font-size:34px;line-height:1;color:#a3ff12">'
            . $e($title)
            . '</div>'
            . '<div style="margin-top:14px;font-size:16px;color:#f0f0f0">' . $e($hello) . '</div>'
            . '<div style="margin-top:10px;font-size:15px;color:#e4e2e2">'
            . ($lang === 'en'
                ? 'Your payment is confirmed. Here are the details.'
                : 'Tu pago está confirmado. Aquí tienes los detalles.')
            . '</div>'
            . '<div style="margin-top:18px;border:4px solid #1a1919;background:#0f172a;padding:16px">'
            . '<div style="font-weight:800;margin-bottom:10px">' . $e($labelOrder) . ': <span style="color:#a3ff12">#' . $e($orderRef) . '</span></div>'
            . '<div style="font-weight:800;margin-bottom:10px">' . $e($labelSummary) . '</div>'
            . '<table role="presentation" style="width:100%;border-collapse:collapse">' . $linesHtml . '</table>'
            . '<div style="margin-top:14px;display:flex;gap:12px;flex-wrap:wrap">'
            . '<div style="flex:1;min-width:220px;border:4px solid #000;background:#0b1220;padding:12px">'
            . '<div style="font-weight:800;margin-bottom:6px">' . $e($labelShipTo) . '</div>'
            . '<div style="color:#f2f2f2;font-size:14px">' . $e($addrOneLine !== '' ? $addrOneLine : '—') . '</div>'
            . '</div>'
            . '<div style="flex:1;min-width:220px;border:4px solid #000;background:#0b1220;padding:12px">'
            . '<div style="font-weight:800;margin-bottom:6px">' . $e($labelShipMet) . '</div>'
            . '<div style="color:#f2f2f2;font-size:14px">' . $e($shipLabel !== '' ? $shipLabel : '—') . '</div>'
            . '</div>'
            . '</div>'
            . '<div style="margin-top:14px;border-top:1px solid rgba(255,255,255,.12);padding-top:12px;font-size:18px;font-weight:900">'
            . $e($labelTotal) . ': <span style="color:#a3ff12">' . $e(format_price_cents($totalCents)) . '</span>'
            . '</div>'
            . '<div style="margin-top:16px;color:#bcbcbc;font-size:14px">' . $e($labelThanks) . '</div>'
            . '</div>'
            . '<div style="margin-top:14px;color:#a0a0a0;font-size:12px">'
            . $e("Tarumba's Farm")
            . '</div>'
            . '</div>'
            . '</div></body></html>';

        $text =
            ($lang === 'en' ? "ORDER CONFIRMED" : "PEDIDO CONFIRMADO") . "\n"
            . ($lang === 'en' ? "Order: #" : "Pedido: #") . $orderRef . "\n\n"
            . ($lang === 'en' ? "Items:" : "Artículos:") . "\n"
            . $linesTxt . "\n"
            . ($lang === 'en' ? "Shipping address:" : "Dirección de envío:") . "\n"
            . ($addrOneLine !== '' ? $addrOneLine : '—') . "\n\n"
            . ($lang === 'en' ? "Shipping method:" : "Método de envío:") . ' ' . ($shipLabel !== '' ? $shipLabel : '—') . "\n"
            . ($lang === 'en' ? "Total:" : "Total:") . ' ' . format_price_cents($totalCents) . "\n";

        return [$html, $text];
    }

    /**
     * @param array{
     *   order_ref: string,
     *   amount_cents: int,
     *   customer_name: string|null,
     *   customer_email: string|null,
     *   shipping: array<string, mixed>,
     *   product: array{name_es: string, name_en: string, slug_es: string, slug_en: string}
     * } $order
     * @return array{0: string, 1: string} html + text
     */
    private function renderInternalEmail(array $order): array
    {
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        $orderRef = (string) $order['order_ref'];
        $ship     = $order['shipping'] ?? [];
        $name     = (string) ($order['customer_name'] ?? '');
        $email    = (string) ($order['customer_email'] ?? '');
        $amountCents = is_array($ship) && isset($ship['total_cents']) ? (int) $ship['total_cents'] : (int) $order['amount_cents'];

        $address = is_array($ship) && isset($ship['address_line']) ? (string) $ship['address_line'] : '';
        $postal  = is_array($ship) && isset($ship['postal']) ? (string) $ship['postal'] : '';
        $city    = is_array($ship) && isset($ship['city']) ? (string) $ship['city'] : '';
        $province = is_array($ship) && isset($ship['province']) ? (string) $ship['province'] : '';
        $country = is_array($ship) && isset($ship['country']) ? (string) $ship['country'] : '';
        $phone   = is_array($ship) && isset($ship['phone']) ? (string) $ship['phone'] : '';
        $shippingMethod = is_array($ship) && isset($ship['shipping']) ? (string) $ship['shipping'] : '';
        $lang = is_array($ship) && isset($ship['lang']) && is_string($ship['lang']) ? strtolower(substr($ship['lang'], 0, 2)) : 'es';

        $cartLines = [];
        if (is_array($ship) && isset($ship['cart_lines']) && is_array($ship['cart_lines'])) {
            $cartLines = $ship['cart_lines'];
        }

        $linesHtml = '';
        $linesTxt  = '';
        foreach ($cartLines as $line) {
            if (!is_array($line)) {
                continue;
            }
            $variety = isset($line['variety']) ? (string) $line['variety'] : '';
            $pack    = isset($line['pack']) ? (int) $line['pack'] : 1;
            $qty     = isset($line['quantity']) ? (int) $line['quantity'] : 1;
            $subtotal = isset($line['subtotal']) ? (int) $line['subtotal'] : null;

            $resolved = $variety !== '' ? CheckoutCatalog::resolve($variety) : null;
            $vName = $resolved !== null
                ? (string) (($lang === 'en') ? ($resolved['title_en'] ?? '') : ($resolved['title_es'] ?? ''))
                : $variety;
            if ($vName === '') {
                $vName = $variety !== '' ? $variety : '—';
            }
            $qtyUnits = $pack * $qty;
            $lineLabel = $vName . ' × ' . (string) $qtyUnits;
            $linesHtml .= '<li><strong>' . $e($lineLabel) . '</strong>' . ($subtotal !== null ? ' — ' . $e(format_price_cents((int) $subtotal)) : '') . '</li>';
            $linesTxt  .= '- ' . $lineLabel . ($subtotal !== null ? (' — ' . format_price_cents((int) $subtotal)) : '') . "\n";
        }
        if ($linesHtml === '') {
            $linesHtml = '<li>—</li>';
            $linesTxt  = "- —\n";
        }

        $addrOneLine = trim($address . ', ' . $postal . ' ' . $city . ', ' . $province . ', ' . $country, " ,");

        $html = '<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;background:#0b0b10;color:#f6f6f6;font-family:Arial,Helvetica,sans-serif">'
            . '<div style="max-width:760px;margin:0 auto;padding:22px">'
            . '<div style="border:6px solid #000;background:#0f172a;box-shadow:10px 10px #000;padding:18px">'
            . '<div style="font-size:22px;font-weight:900">🌱 Nuevo pedido <span style="color:#a3ff12">#' . $e($orderRef) . '</span></div>'
            . '<div style="margin-top:10px;color:#e4e2e2;font-size:14px">Cliente: <strong>' . $e($name !== '' ? $name : '—') . '</strong> · ' . $e($email !== '' ? $email : '—') . '</div>'
            . '<div style="margin-top:6px;color:#e4e2e2;font-size:14px">Teléfono: ' . $e($phone !== '' ? $phone : '—') . ' · Idioma: ' . $e($lang) . '</div>'
            . '<div style="margin-top:14px;border-top:1px solid rgba(255,255,255,.12);padding-top:12px">'
            . '<div style="font-weight:800;margin-bottom:6px">Líneas</div>'
            . '<ul style="margin:0;padding-left:18px">' . $linesHtml . '</ul>'
            . '</div>'
            . '<div style="margin-top:14px;border-top:1px solid rgba(255,255,255,.12);padding-top:12px">'
            . '<div style="font-weight:800;margin-bottom:6px">Dirección</div>'
            . '<div style="color:#f2f2f2;font-size:14px">' . $e($addrOneLine !== '' ? $addrOneLine : '—') . '</div>'
            . '</div>'
            . '<div style="margin-top:14px;border-top:1px solid rgba(255,255,255,.12);padding-top:12px">'
            . '<div style="font-weight:800;margin-bottom:6px">Envío</div>'
            . '<div style="color:#f2f2f2;font-size:14px">' . $e($shippingMethod !== '' ? $shippingMethod : '—') . '</div>'
            . '</div>'
            . '<div style="margin-top:14px;border-top:1px solid rgba(255,255,255,.12);padding-top:12px;font-size:18px;font-weight:900">'
            . 'Total: <span style="color:#a3ff12">' . $e(format_price_cents($amountCents)) . '</span>'
            . '</div>'
            . '</div></div></body></html>';

        $text =
            "Nuevo pedido #{$orderRef}\n"
            . "Cliente: " . ($name !== '' ? $name : '—') . " · " . ($email !== '' ? $email : '—') . "\n"
            . "Teléfono: " . ($phone !== '' ? $phone : '—') . " · Idioma: " . $lang . "\n\n"
            . "Líneas:\n" . $linesTxt . "\n"
            . "Dirección:\n" . ($addrOneLine !== '' ? $addrOneLine : '—') . "\n\n"
            . "Envío: " . ($shippingMethod !== '' ? $shippingMethod : '—') . "\n"
            . "Total: " . format_price_cents($amountCents) . "\n";

        return [$html, $text];
    }
}
