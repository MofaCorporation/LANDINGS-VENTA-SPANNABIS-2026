-- context/db_schema.sql — Tarumba's Farm (snapshot / referencia)
-- ID: CTX-DB-SCHEMA-001
--
-- PROPÓSITO
-- Evitar alucinaciones sobre tablas y columnas del ecommerce.
--
-- REGLA
-- Este archivo debe reflejar el esquema REAL aplicado en MySQL/MariaDB.
-- Tras migraciones: regenerar o editar de forma auditada y anotar en workflow_state.md.
--
-- Fuente inicial: estructura-proyecto.md (ajustar nombres de BD si el proyecto difiere)

CREATE DATABASE IF NOT EXISTS ecommerce
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ecommerce;

CREATE TABLE IF NOT EXISTS products (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug_es        VARCHAR(200) NOT NULL UNIQUE,
    slug_en        VARCHAR(200) NOT NULL UNIQUE,
    name_es        VARCHAR(300) NOT NULL,
    name_en        VARCHAR(300) NOT NULL,
    description_es TEXT,
    description_en TEXT,
    price_cents    INT UNSIGNED NOT NULL,
    stock          INT UNSIGNED DEFAULT 0,
    image          VARCHAR(300),
    active         TINYINT(1) DEFAULT 1,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug_es (slug_es),
    INDEX idx_slug_en (slug_en)
);

CREATE TABLE IF NOT EXISTS orders (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_ref      VARCHAR(20)  NOT NULL UNIQUE,
    product_id     INT UNSIGNED NOT NULL,
    amount_cents   INT UNSIGNED NOT NULL,
    currency       CHAR(3) DEFAULT 'EUR',
    status         ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    customer_email VARCHAR(254),
    redsys_response JSON,
    paid_at        DATETIME,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS sessions (
    id         VARCHAR(128) PRIMARY KEY,
    data       TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Datos semilla (opcional): las 5 variedades — slugs ejemplo; confirmar en RECON
-- INSERT INTO products (...) VALUES (...);
