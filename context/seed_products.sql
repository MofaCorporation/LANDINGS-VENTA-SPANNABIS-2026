-- Inicial: 5 variedades (slugs = kebab-case, mismo valor ES/EN)
-- Ejecutar tras crear tablas: mysql -u app -p ecommerce < context/seed_products.sql

USE ecommerce;

INSERT INTO products (slug_es, slug_en, name_es, name_en, description_es, description_en, price_cents, stock, image, active) VALUES
('toxic-mutant', 'toxic-mutant', 'Toxic Mutant', 'Toxic Mutant', '', '', 3490, 999, '/assets/img/productos/toxic-mutant/hero-toxic-mutat-web.png', 1),
('nitro-bud', 'nitro-bud', 'Nitro Bud', 'Nitro Bud', '', '', 2990, 999, '/assets/img/productos/nitro-bud/hero-nitro-bud-web.png', 1),
('dj-piggy', 'dj-piggy', 'DJ Piggy', 'DJ Piggy', '', '', 3290, 999, '/assets/img/productos/dj-piggy/hero-dj-piggy-web.png', 1),
('holy-boss', 'holy-boss', 'Holy Boss', 'Holy Boss', '', '', 3190, 999, '/assets/img/productos/holy-boss/hero-holy-boss-web.png', 1),
('lady-cupcake', 'lady-cupcake', 'Lady Cupcake', 'Lady Cupcake', '', '', 3090, 999, '/assets/img/productos/lady-cupcake/hero-lady-cupcake-web.png', 1)
ON DUPLICATE KEY UPDATE
    name_es = VALUES(name_es),
    name_en = VALUES(name_en),
    price_cents = VALUES(price_cents),
    image = VALUES(image),
    active = VALUES(active);
