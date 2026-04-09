USE ecommerce;

ALTER TABLE orders
    ADD COLUMN shipping_json JSON NULL AFTER customer_email;
