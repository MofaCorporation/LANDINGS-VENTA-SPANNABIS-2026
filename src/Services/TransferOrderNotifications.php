<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;

/**
 * Emails tras confirmar pedido por transferencia (sin Redsys).
 */
final class TransferOrderNotifications
{
    public function __construct(private MailService $mail)
    {
    }

    public function sendForOrder(string $orderRef): void
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
        if ($customerEmail !== '') {
            $subjectClient = $lang === 'en'
                ? "Payment instructions · Tarumba's Farm"
                : 'Instrucciones de pago · Tarumba\'s Farm';

            [$htmlClient, $textClient] = $this->renderCustomerTransferEmail($lang, $order);

            try {
                $this->mail->send($customerEmail, $subjectClient, $htmlClient, $textClient);
            } catch (\Throwable $e) {
                error_log('Transfer mail customer: ' . $e->getMessage());
            }
        }

        $internalTo = 'pepebulkov@tarumbasfarm.com';
        $subjectInternal = '[PAGO PENDIENTE - TRANSFERENCIA] Nuevo pedido #' . $orderRef . " — Tarumba's Farm";
        [$htmlInternal, $textInternal] = $this->renderInternalPendingTransferEmail($order);

        try {
            $this->mail->send($internalTo, $subjectInternal, $htmlInternal, $textInternal);
        } catch (\Throwable $e) {
            error_log('Transfer mail internal: ' . $e->getMessage());
        }
    }

    /**
     * @param array{
     *   order_ref: string,
     *   amount_cents: int,
     *   customer_name: string|null,
     *   customer_email: string|null,
     *   shipping: array<string, mixed>
     * } $order
     *
     * @return array{0: string, 1: string}
     */
    private function renderCustomerTransferEmail(string $lang, array $order): array
    {
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        $orderRef = (string) $order['order_ref'];
        $ship     = $order['shipping'] ?? [];
        $name     = (string) ($order['customer_name'] ?? '');
        $totalCents = is_array($ship) && isset($ship['total_cents']) ? (int) $ship['total_cents'] : (int) $order['amount_cents'];

        $hello = $name !== ''
            ? ($lang === 'en' ? ('Hi ' . $name . ',') : ('Hola ' . $name . ','))
            : ($lang === 'en' ? 'Hi,' : 'Hola,');

        $title = $lang === 'en' ? 'BANK TRANSFER' : 'TRANSFERENCIA BANCARIA';
        $intro = $lang === 'en'
            ? 'Thank you for your order. Please make a bank transfer for the exact amount below and use the reference exactly as shown (so we can match your payment).'
            : 'Gracias por tu pedido. Realiza una transferencia bancaria por el importe exacto que indicamos y usa el concepto exactamente como aparece (así podremos vincular tu pago).';

        $lblAmount   = $lang === 'en' ? 'Amount to transfer' : 'Importe a transferir';
        $lblConcept  = $lang === 'en' ? 'Payment reference (concept)' : 'Concepto del pago';
        $lblHolder   = $lang === 'en' ? 'Account holder' : 'Titular';
        $lblIban     = 'IBAN';
        $lblBic      = 'BIC';
        $lblOrder    = $lang === 'en' ? 'Order' : 'Pedido';
        $footerNote  = $lang === 'en'
            ? 'We will prepare your shipment after the transfer is received. If you have questions, reply to this email or write to tarumbasfarm@gmail.com.'
            : 'Prepararemos el envío cuando recibamos la transferencia. Si tienes dudas, responde a este correo o escribe a tarumbasfarm@gmail.com.';

        $amountStr = format_price_cents($totalCents);

        $html = '<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;background:#0b0b10;color:#f6f6f6;font-family:Arial,Helvetica,sans-serif">'
            . '<div style="max-width:720px;margin:0 auto;padding:24px">'
            . '<div style="border:6px solid #000;background:linear-gradient(135deg,#111827,#0b0b10);box-shadow:10px 10px #000;padding:22px">'
            . '<div style="font-family:\'Bangers\',Arial,sans-serif;letter-spacing:1px;text-transform:uppercase;font-size:30px;line-height:1;color:#a3ff12">'
            . $e($title)
            . '</div>'
            . '<div style="margin-top:14px;font-size:16px;color:#f0f0f0">' . $e($hello) . '</div>'
            . '<div style="margin-top:10px;font-size:15px;color:#e4e2e2">' . $e($intro) . '</div>'
            . '<div style="margin-top:18px;border:4px solid #1a1919;background:#0f172a;padding:16px">'
            . '<div style="font-weight:800;margin-bottom:8px">' . $e($lblOrder) . ': <span style="color:#a3ff12">#' . $e($orderRef) . '</span></div>'
            . '<div style="margin-top:12px;font-size:18px;font-weight:900">' . $e($lblAmount) . ': <span style="color:#a3ff12">' . $e($amountStr) . '</span></div>'
            . '<div style="margin-top:14px;padding:12px;border:3px solid #000;background:#0b1220">'
            . '<div style="font-weight:800;margin-bottom:6px">' . $e($lblConcept) . '</div>'
            . '<div style="font-size:20px;font-weight:900;color:#a3ff12;letter-spacing:0.04em">' . $e($orderRef) . '</div>'
            . '</div>'
            . '<div style="margin-top:16px;padding:12px;border:3px solid #000;background:#0b1220;font-size:14px;line-height:1.6">'
            . '<div><strong>' . $e($lblHolder) . ':</strong> ' . $e(BankTransferDetails::HOLDER) . '</div>'
            . '<div style="margin-top:6px"><strong>' . $e($lblIban) . ':</strong> ' . $e(BankTransferDetails::IBAN) . '</div>'
            . '<div style="margin-top:6px"><strong>' . $e($lblBic) . ':</strong> ' . $e(BankTransferDetails::BIC) . '</div>'
            . '</div>'
            . '<div style="margin-top:16px;color:#bcbcbc;font-size:14px">' . $e($footerNote) . '</div>'
            . '</div>'
            . '<div style="margin-top:14px;color:#a0a0a0;font-size:12px">' . $e("Tarumba's Farm") . '</div>'
            . '</div></div></body></html>';

        $text =
            ($lang === 'en' ? 'BANK TRANSFER' : 'TRANSFERENCIA BANCARIA') . "\n\n"
            . ($lang === 'en' ? 'Order: #' : 'Pedido: #') . $orderRef . "\n"
            . ($lang === 'en' ? 'Amount: ' : 'Importe: ') . $amountStr . "\n\n"
            . ($lang === 'en' ? 'Payment reference (concept): ' : 'Concepto: ') . $orderRef . "\n\n"
            . ($lang === 'en' ? 'Account holder: ' : 'Titular: ') . BankTransferDetails::HOLDER . "\n"
            . 'IBAN: ' . BankTransferDetails::IBAN . "\n"
            . 'BIC: ' . BankTransferDetails::BIC . "\n";

        return [$html, $text];
    }

    /**
     * @param array{
     *   order_ref: string,
     *   amount_cents: int,
     *   customer_name: string|null,
     *   customer_email: string|null,
     *   shipping: array<string, mixed>
     * } $order
     *
     * @return array{0: string, 1: string}
     */
    private function renderInternalPendingTransferEmail(array $order): array
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
            . '<div style="background:#9a3412;color:#fff;padding:12px 14px;font-weight:900;font-size:15px;margin:-18px -18px 14px -18px">PAGO PENDIENTE - TRANSFERENCIA</div>'
            . '<div style="font-size:22px;font-weight:900">🌱 Nuevo pedido <span style="color:#a3ff12">#' . $e($orderRef) . '</span></div>'
            . '<div style="margin-top:10px;color:#fde68a;font-size:14px">Concepto bancario: <strong>' . $e($orderRef) . '</strong> · Importe: <strong>' . $e(format_price_cents($amountCents)) . '</strong></div>'
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
            "PAGO PENDIENTE - TRANSFERENCIA\n"
            . "Nuevo pedido #{$orderRef}\n"
            . 'Concepto bancario: ' . $orderRef . ' · Importe: ' . format_price_cents($amountCents) . "\n"
            . "Cliente: " . ($name !== '' ? $name : '—') . " · " . ($email !== '' ? $email : '—') . "\n"
            . "Teléfono: " . ($phone !== '' ? $phone : '—') . " · Idioma: " . $lang . "\n\n"
            . "Líneas:\n" . $linesTxt . "\n"
            . "Dirección:\n" . ($addrOneLine !== '' ? $addrOneLine : '—') . "\n\n"
            . "Envío: " . ($shippingMethod !== '' ? $shippingMethod : '—') . "\n"
            . "Total: " . format_price_cents($amountCents) . "\n";

        return [$html, $text];
    }
}
