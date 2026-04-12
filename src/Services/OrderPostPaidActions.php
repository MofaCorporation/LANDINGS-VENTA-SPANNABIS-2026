<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\Order;

/**
 * Efectos secundarios tras marcar un pedido como pagado (lead, Packlink, Brevo, correos).
 */
final class OrderPostPaidActions
{
    /**
     * Notificación Redsys (TPV): lead, envío Packlink si aplica, Brevo, email cliente + interno.
     *
     * @param array<string, mixed> $order Order::getForEmail() con status `paid`
     */
    public static function afterGatewayPaid(array $order): void
    {
        self::completePaidFulfillment($order, true);
    }

    /**
     * Confirmación manual transferencia en panel admin: mismo flujo de cumplimiento, sin email interno.
     *
     * @param array<string, mixed> $order Order::getForEmail() con status `paid`
     */
    public static function afterManualTransferPaid(array $order): void
    {
        self::completePaidFulfillment($order, false);
    }

    /** @param array<string, mixed> $order */
    private static function completePaidFulfillment(array $order, bool $sendInternalMail): void
    {
        self::upsertLead($order);
        self::tryPacklinkFulfill($order);

        $ref = (string) ($order['order_ref'] ?? '');
        $fresh = $ref !== '' ? Order::getForEmail($ref) : null;
        if ($fresh === null) {
            $fresh = $order;
        }

        BrevoContactService::syncEggTypeForOrder($fresh);
        self::sendPaidPreparationClientEmail($fresh);

        if ($sendInternalMail && $ref !== '') {
            self::sendInternalStaffEmail($fresh);
        }
    }

    /**
     * Recogida: marca shipped sin Packlink. Envío estándar: crea envío; si falla, deja `paid` + packlink_error.
     *
     * @param array<string, mixed> $order
     */
    private static function tryPacklinkFulfill(array $order): void
    {
        $ref = trim((string) ($order['order_ref'] ?? ''));
        if ($ref === '') {
            return;
        }

        if (($order['status'] ?? '') === 'shipped') {
            return;
        }

        $ship = $order['shipping'] ?? [];
        if (!is_array($ship)) {
            return;
        }

        if (($ship['shipping'] ?? '') === 'pickup') {
            Order::setPacklinkError($ref, null);
            Order::markAsShipped($ref);

            return;
        }

        try {
            $pl     = new PacklinkService(PacklinkService::loadConfig());
            $result = $pl->createShipment($order);
        } catch (\Throwable $e) {
            error_log('Packlink createShipment: ' . $e->getMessage());
            Order::setPacklinkError($ref, $e->getMessage());

            return;
        }

        if (!empty($result['ok'])) {
            Order::saveTrackingAndLabel(
                $ref,
                isset($result['tracking_number']) && is_string($result['tracking_number']) ? $result['tracking_number'] : null,
                isset($result['label_url']) && is_string($result['label_url']) ? $result['label_url'] : null,
            );
            Order::setPacklinkError($ref, null);
            Order::markAsShipped($ref);
        } else {
            $err = isset($result['error']) && is_string($result['error']) ? $result['error'] : 'Error desconocido Packlink';
            Order::setPacklinkError($ref, $err);
        }
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
    private static function sendPaidPreparationClientEmail(array $order): void
    {
        $ship = $order['shipping'] ?? [];
        $lang = is_array($ship) && isset($ship['lang']) && is_string($ship['lang']) ? strtolower(substr($ship['lang'], 0, 2)) : 'es';
        if ($lang !== 'en' && $lang !== 'es') {
            $lang = 'es';
        }

        $customerEmail = (string) ($order['customer_email'] ?? '');
        if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $subject = $lang === 'en'
            ? "Payment received, order in preparation! · Tarumba's Farm"
            : "¡Pago recibido y pedido en preparación! · Tarumba's Farm";

        $tn = isset($order['tracking_number']) && is_string($order['tracking_number']) ? $order['tracking_number'] : null;
        $lu = isset($order['label_url']) && is_string($order['label_url']) ? $order['label_url'] : null;

        [$html, $text] = OrderPaidEmailTemplates::customerPaidPreparing($lang, $order, $tn, $lu);

        $mail = new MailService(MailService::loadConfig());
        try {
            $mail->send($customerEmail, $subject, $html, $text);
        } catch (\Throwable $e) {
            error_log('Mail customer (paid preparation): ' . $e->getMessage());
        }
    }

    /** @param array<string, mixed> $order */
    private static function sendInternalStaffEmail(array $order): void
    {
        $orderRef = (string) ($order['order_ref'] ?? '');
        if ($orderRef === '') {
            return;
        }

        $mail = new MailService(MailService::loadConfig());
        $internalTo      = 'pepebulkov@tarumbasfarm.com';
        $subjectInternal = '🌱 Nuevo pedido #' . $orderRef . " — Tarumba's Farm";
        [$htmlInternal, $textInternal] = OrderPaidEmailTemplates::internal($order);
        try {
            $mail->send($internalTo, $subjectInternal, $htmlInternal, $textInternal);
        } catch (\Throwable $e) {
            error_log('Mail internal: ' . $e->getMessage());
        }
    }
}
