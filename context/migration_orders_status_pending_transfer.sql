-- Añade estado `pending_transfer` para pedidos pagados por transferencia (sin TPV).
-- Ejecutar en la BD ecommerce antes de desplegar el checkout con transferencia.
--
-- mysql -u ... -p ecommerce < context/migration_orders_status_pending_transfer.sql

ALTER TABLE orders
    MODIFY COLUMN status ENUM('pending','pending_transfer','paid','failed','refunded') NOT NULL DEFAULT 'pending';
