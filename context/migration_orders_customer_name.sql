-- Si ya tenías la tabla `orders` sin `customer_name`, ejecuta:
-- mysql -u ... ecommerce < context/migration_orders_customer_name.sql

USE ecommerce;

ALTER TABLE orders
    ADD COLUMN customer_name VARCHAR(200) DEFAULT NULL AFTER status;
