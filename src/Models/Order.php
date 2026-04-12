<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Order
{
    /**
     * `shipping_json` debe incluir el detalle multi-línea (`cart_lines`) cuando aplique.
     *
     * @return array{id: int, order_ref: string}
     */
    public static function createPending(
        int $productId,
        int $amountCents,
        string $customerName,
        string $customerEmail,
        ?string $shippingJson = null,
        string $status = 'pending',
    ): array {
        if ($status !== 'pending' && $status !== 'pending_transfer') {
            throw new \InvalidArgumentException('Estado de pedido inicial no válido.');
        }

        $pdo = Database::get();
        $tempRef = 'TMP' . bin2hex(random_bytes(5));

        $ins = $pdo->prepare(
            'INSERT INTO orders (order_ref, product_id, amount_cents, currency, status, customer_name, customer_email, shipping_json)
             VALUES (:ref, :pid, :amt, \'EUR\', :st, :cname, :cemail, :sjson)',
        );
        $ins->execute([
            'ref'    => $tempRef,
            'pid'    => $productId,
            'amt'    => $amountCents,
            'st'     => $status,
            'cname'  => $customerName,
            'cemail' => $customerEmail,
            'sjson'  => $shippingJson,
        ]);

        $id = (int) $pdo->lastInsertId();
        if ($id <= 0) {
            throw new \RuntimeException('No se pudo crear el pedido.');
        }

        $orderRef = self::allocateUniqueOrderRef($pdo, $id);

        $upd = $pdo->prepare('UPDATE orders SET order_ref = :oref WHERE id = :id AND order_ref = :old');
        $upd->execute(['oref' => $orderRef, 'id' => $id, 'old' => $tempRef]);

        return ['id' => $id, 'order_ref' => $orderRef];
    }

    /**
     * Referencia Redsys: 4–12 caracteres, solo alfanuméricos, única por fila y por intento (sufijo aleatorio).
     * Evita SIS0051 al recargar la pasarela: cada pedido nuevo obtiene un order_ref distinto del anterior.
     */
    private static function allocateUniqueOrderRef(PDO $pdo, int $id): string
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $candidate = self::buildOrderRefCandidate($id);
            $st        = $pdo->prepare('SELECT 1 FROM orders WHERE order_ref = :r LIMIT 1');
            $st->execute(['r' => $candidate]);
            if ($st->fetchColumn() === false) {
                return $candidate;
            }
        }

        throw new \RuntimeException('No se pudo generar order_ref único.');
    }

    /** Redsys: mín. 4, máx. 12, [A-Za-z0-9] */
    private static function buildOrderRefCandidate(int $id): string
    {
        $idStr = (string) $id;
        if (strlen($idStr) > 8) {
            $idStr = substr($idStr, -8);
        }
        if (strlen($idStr) < 4) {
            $idStr = str_pad($idStr, 4, '0', STR_PAD_LEFT);
        }

        $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

        $ref = $idStr . $suffix;
        if (strlen($ref) > 12) {
            $ref = substr($idStr, -8) . $suffix;
        }

        $ref = preg_replace('/[^0-9A-Za-z]/', '', $ref) ?? '';
        if ($ref === '') {
            $ref = strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
        }
        if (strlen($ref) < 4) {
            $ref = str_pad($ref, 4, '0', STR_PAD_LEFT);
        }
        if (strlen($ref) > 12) {
            $ref = substr($ref, 0, 12);
        }

        return $ref;
    }

    /** @param array<string, mixed> $params */
    public static function markAsPaid(string $orderRef, array $params): bool
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id, status FROM orders WHERE order_ref = :r FOR UPDATE');
            $st->execute(['r' => $orderRef]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                $pdo->rollBack();

                return false;
            }

            if ($row['status'] === 'paid') {
                $pdo->commit();

                return false;
            }

            $json = json_encode(
                $params,
                JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE,
            );
            $up = $pdo->prepare(
                'UPDATE orders SET status = \'paid\', redsys_response = :resp, paid_at = NOW() WHERE id = :id',
            );
            $up->execute(['resp' => $json, 'id' => (int) $row['id']]);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function markAsFailed(string $orderRef, int $code): void
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id, status FROM orders WHERE order_ref = :r FOR UPDATE');
            $st->execute(['r' => $orderRef]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                $pdo->rollBack();

                return;
            }

            if ($row['status'] === 'paid') {
                $pdo->commit();

                return;
            }

            $payload = json_encode(
                ['Ds_Response' => $code, 'failed_at' => gmdate('c')],
                JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE,
            );
            $up = $pdo->prepare(
                'UPDATE orders SET status = \'failed\', redsys_response = :resp WHERE id = :id',
            );
            $up->execute(['resp' => $payload, 'id' => (int) $row['id']]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Datos para emails / post-pago (sin depender de sesión).
     *
     * @return array{
     *   order_ref: string,
     *   status: string,
     *   amount_cents: int,
     *   customer_name: string|null,
     *   customer_email: string|null,
     *   shipping: array<string, mixed>,
     *   product: array{name_es: string, name_en: string, slug_es: string, slug_en: string}
     * }|null
     */
    public static function getForEmail(string $orderRef): ?array
    {
        $pdo = Database::get();
        $st  = $pdo->prepare(
            'SELECT
                o.order_ref,
                o.status,
                o.amount_cents,
                o.customer_name,
                o.customer_email,
                o.shipping_json,
                o.tracking_number,
                o.label_url,
                o.packlink_error,
                p.name_es,
                p.name_en,
                p.slug_es,
                p.slug_en
             FROM orders o
             INNER JOIN products p ON p.id = o.product_id
             WHERE o.order_ref = :r
             LIMIT 1',
        );
        $st->execute(['r' => $orderRef]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $shipping = [];
        if (!empty($row['shipping_json']) && is_string($row['shipping_json'])) {
            $decoded = json_decode($row['shipping_json'], true);
            if (is_array($decoded)) {
                $shipping = $decoded;
            }
        }

        return [
            'order_ref'        => (string) $row['order_ref'],
            'status'           => (string) $row['status'],
            'amount_cents'     => (int) $row['amount_cents'],
            'customer_name'    => isset($row['customer_name']) ? (string) $row['customer_name'] : null,
            'customer_email'   => isset($row['customer_email']) ? (string) $row['customer_email'] : null,
            'shipping'         => $shipping,
            'tracking_number'  => isset($row['tracking_number']) && $row['tracking_number'] !== null ? (string) $row['tracking_number'] : null,
            'label_url'        => isset($row['label_url']) && $row['label_url'] !== null ? (string) $row['label_url'] : null,
            'packlink_error'   => isset($row['packlink_error']) && $row['packlink_error'] !== null ? (string) $row['packlink_error'] : null,
            'product'          => [
                'name_es' => (string) $row['name_es'],
                'name_en' => (string) $row['name_en'],
                'slug_es' => (string) $row['slug_es'],
                'slug_en' => (string) $row['slug_en'],
            ],
        ];
    }

    /**
     * Persiste tracking y URL de etiqueta (Packlink u otro). Llamar antes de markAsShipped()
     * cuando el envío ya tiene número de seguimiento.
     */
    public static function saveTrackingAndLabel(string $orderRef, ?string $trackingNumber, ?string $labelUrl): void
    {
        $pdo = Database::get();
        $st  = $pdo->prepare(
            'UPDATE orders SET tracking_number = :tn, label_url = :lu WHERE order_ref = :r',
        );
        $st->execute([
            'tn' => $trackingNumber !== null && $trackingNumber !== '' ? mb_substr($trackingNumber, 0, 100) : null,
            'lu' => $labelUrl !== null && $labelUrl !== '' ? mb_substr($labelUrl, 0, 500) : null,
            'r'  => $orderRef,
        ]);
    }

    public static function setPacklinkError(string $orderRef, ?string $message): void
    {
        $pdo = Database::get();
        $msg = $message !== null ? mb_substr(trim($message), 0, 500) : null;
        $st  = $pdo->prepare('UPDATE orders SET packlink_error = :e WHERE order_ref = :r');
        $st->execute(['e' => $msg !== '' ? $msg : null, 'r' => $orderRef]);
    }

    /**
     * Pasa de `paid` a `shipped` (idempotente si ya está `shipped`).
     * No escribe tracking_number: usar saveTrackingAndLabel() en el mismo flujo.
     */
    public static function markAsShipped(string $orderRef): bool
    {
        $pdo = Database::get();
        $st  = $pdo->prepare(
            "UPDATE orders SET status = 'shipped' WHERE order_ref = :r AND status IN ('paid', 'shipped')",
        );
        $st->execute(['r' => $orderRef]);

        return $st->rowCount() > 0;
    }

    /**
     * Tracking manual desde el panel (pedido pagado, envío estándar).
     *
     * @return bool true si se actualizó la fila
     */
    public static function saveManualTracking(string $orderRef, string $trackingNumber, ?string $labelUrl): bool
    {
        $trackingNumber = trim($trackingNumber);
        if ($trackingNumber === '') {
            return false;
        }

        $pdo = Database::get();
        $st  = $pdo->prepare(
            'UPDATE orders SET tracking_number = :tn, label_url = :lu, packlink_error = NULL, status = \'shipped\'
             WHERE order_ref = :r AND status IN (\'paid\', \'shipped\') AND shipping_json IS NOT NULL',
        );
        $st->execute([
            'tn' => mb_substr($trackingNumber, 0, 100),
            'lu' => $labelUrl !== null && trim($labelUrl) !== '' ? mb_substr(trim($labelUrl), 0, 500) : null,
            'r'  => $orderRef,
        ]);

        return $st->rowCount() > 0;
    }

    /**
     * Listado para panel admin: más recientes primero.
     *
     * @return list<array<string, mixed>>
     */
    public static function listForAdmin(?string $statusFilter): array
    {
        $pdo = Database::get();
        $sql = 'SELECT o.order_ref, o.created_at, o.status, o.amount_cents, o.customer_name, o.customer_email,
                       o.shipping_json, o.tracking_number, o.label_url, o.packlink_error, p.slug_es, p.name_es
                FROM orders o
                INNER JOIN products p ON p.id = o.product_id';
        $params = [];
        $forUi = ['pending_transfer', 'paid', 'failed', 'shipped'];
        if ($statusFilter !== null && $statusFilter !== '' && $statusFilter !== 'all' && in_array($statusFilter, $forUi, true)) {
            $sql .= ' WHERE o.status = :st';
            $params['st'] = $statusFilter;
        }
        $sql .= ' ORDER BY o.created_at DESC';

        $st = $pdo->prepare($sql);
        $st->execute($params);

        /** @var list<array<string, mixed>> */
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
