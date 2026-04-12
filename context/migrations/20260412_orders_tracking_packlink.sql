-- Migración: tracking Packlink + estado shipped
-- ID: MIG-ORDERS-TRACKING-001
--
-- mysql -u ... -p ecommerce < context/migrations/20260412_orders_tracking_packlink.sql

ALTER TABLE orders
    ADD COLUMN tracking_number VARCHAR(100) NULL DEFAULT NULL AFTER paid_at,
    ADD COLUMN label_url VARCHAR(500) NULL DEFAULT NULL AFTER tracking_number,
    ADD COLUMN packlink_error VARCHAR(500) NULL DEFAULT NULL AFTER label_url;

ALTER TABLE orders
    MODIFY COLUMN status ENUM(
        'pending',
        'pending_transfer',
        'paid',
        'failed',
        'refunded',
        'shipped'
    ) DEFAULT 'pending';
