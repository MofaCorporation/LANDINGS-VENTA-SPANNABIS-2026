<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;

/**
 * Efectos secundarios tras marcar un pedido como pagado (lead, Brevo EGG_TYPE, correos).
 */
final class OrderPostPaidActions
{
    /**
     * Flujo completo notificación Redsys (lead, Brevo, email cliente + interno).
     *
     * @param array<string, mixed> $order Order::getForEmail()
     */
    public static function afterGatewayPaid(array $order): void
    {
        self::upsertLead($order);
        BrevoContactService::syncEggTypeForOrder($order);
        self::sendMails($order, true);
    }

    /**
     * Tras confirmación manual de transferencia en panel admin (sin email interno).
     *
     * @param array<string, mixed> $order Order::getForEmail()
     */
    public static function afterManualTransferPaid(array $order): void
    {
        self::upsertLead($order);
        BrevoContactService::syncEggTypeForOrder($order);
        self::sendMails($order, false);
    }

    /** @param array<string, mixed> $order */
    private static function upsertLead(array $order): void
    {
        $ship = $order['shipping'] ?? [];
        $lang = is_array($ship) && isset($ship['lang']) && is_string($ship['lang']) ? strtolower(substr($ship['lang'], 0, 2)) : 'es';
        if ($lang !== 'en' && $lang !== 'es') {
            $lang = 'es';
        }

        $customerEmail = (string) ($order['customer_email'] ?? '');
        $customerName  = (string) ($order['customer_name'] ?? '');

        try {
            Lead::upsert($customerEmail, $customerName, $lang, 'checkout');
        } catch (\Throwable $e) {
            error_log('Lead::upsert: ' . $e->getMessage());
        }
    }

    /** @param array<string, mixed> $order */
    private static function sendMails(array $order, bool $sendInternal): void
    {
        $ship = $order['shipping'] ?? [];
        $lang = is_array($ship) && isset($ship['lang']) && is_string($ship['lang']) ? strtolower(substr($ship['lang'], 0, 2)) : 'es';
        if ($lang !== 'en' && $lang !== 'es') {
            $lang = 'es';
        }

        $orderRef = (string) ($order['order_ref'] ?? '');
        $customerEmail = (string) ($order['customer_email'] ?? '');

        $mail = new MailService(MailService::loadConfig());

        if ($customerEmail !== '') {
            $subjectClient = $lang === 'en'
                ? "Thanks for your order! · Tarumba's Farm"
                : "¡Gracias por tu pedido! · Tarumba's Farm";

            [$htmlClient, $textClient] = OrderPaidEmailTemplates::customer($lang, $order);

            try {
                $mail->send($customerEmail, $subjectClient, $htmlClient, $textClient);
            } catch (\Throwable $e) {
                error_log('Mail customer: ' . $e->getMessage());
            }
        }

        if (!$sendInternal || $orderRef === '') {
            return;
        }

        $internalTo = 'pepebulkov@tarumbasfarm.com';
        $subjectInternal = '🌱 Nuevo pedido #' . $orderRef . " — Tarumba's Farm";
        [$htmlInternal, $textInternal] = OrderPaidEmailTemplates::internal($order);
        try {
            $mail->send($internalTo, $subjectInternal, $htmlInternal, $textInternal);
        } catch (\Throwable $e) {
            error_log('Mail internal: ' . $e->getMessage());
        }
    }
}
