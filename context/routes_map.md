# context/routes_map.md — Mapa de rutas (Anti-404)
| Campo | Valor |
|-------|--------|
| **ID** | `CTX-ROUTES-MAP-001` |
| **Owner** | `[TODO]` |
| **LastUpdated** | 2026-04-08 |
| **SourceOfTruth** | `public/index.php`, `src/Core/Router.php`, `.htaccess`, código real |

## 0) Propósito
Listar rutas que la **UI pública** usa y que no deben romperse. Las landings de producto comparten plantilla `templates/products/landing.php` y se sirven con prefijo de idioma `/es/` o `/en/`.

## 1) Convención
- Rutas sin dominio.
- Idioma en prefijo: `/es/...`, `/en/...` (según `i18n-sistema-idiomas.md`).
- Slugs de producto: kebab-case, **iguales en ES y EN** (mismo path tras el prefijo de idioma).

## 2) Rutas públicas — tabla

| Ruta (implementada) | Owner UI | Criticality | Notas |
|---------------------|----------|-------------|--------|
| `/es/` | home | P0 | Home ES |
| `/en/` | home | P0 | Home EN |
| `/es/toxic-mutant` | landing Toxic Mutant | P0 | `ProductController::toxicMutant()` |
| `/en/toxic-mutant` | landing Toxic Mutant | P0 | |
| `/es/nitro-bud` | landing Nitro Bud | P0 | `ProductController::nitroBud()` |
| `/en/nitro-bud` | landing Nitro Bud | P0 | |
| `/es/dj-piggy` | landing DJ Piggy | P0 | `ProductController::djPiggy()`; script extra `djPiggy-BWVwacbI.js` |
| `/en/dj-piggy` | landing DJ Piggy | P0 | |
| `/es/holy-boss` | landing Holy Boss | P0 | `ProductController::holyBoss()` |
| `/en/holy-boss` | landing Holy Boss | P0 | |
| `/es/lady-cupcake` | landing Lady Cupcake | P0 | `ProductController::ladyCupcake()` |
| `/en/lady-cupcake` | landing Lady Cupcake | P0 | |
| `/es/checkout` | CTA comprar | P0 | Carrito + form Redsys (pendiente 501) |
| `/en/checkout` | CTA comprar | P0 | |
| `/redsys/notify` | Redsys POST | P0 | Sin UI; server-to-server |
| `/es/checkout/ok` | retorno usuario | P1 | No marca pago solo |
| `/en/checkout/ok` | retorno usuario | P1 | |
| `/es/checkout/ko` | retorno usuario | P1 | |
| `/en/checkout/ko` | retorno usuario | P1 | |

**Query en checkout:** las CTAs usan `?variety=` con el mismo slug kebab (`toxic-mutant`, `nitro-bud`, `dj-piggy`, `holy-boss`, `lady-cupcake`).

**Traducciones de producto:** `src/Lang/es.json` / `en.json` + merge recursivo con `src/Lang/es.products.json` / `en.products.json` (claves `product.*` y `meta.*` adicionales).

## 3) Assets estáticos
| Ruta | Notas |
|------|--------|
| `/assets/css/*`, `/assets/js/*`, `/assets/img/*` | Deben servirse como archivos (no rewrite a `index.php`) |

## 4) Pendientes / sospechosas
| Ruta | Acción |
|------|--------|
| `/checkout` | Implementar flujo real (actualmente 501) |

## 5) Notas de cambios
| Fecha | ID | Resumen |
|-------|-----|---------|
| 2026-04-08 | CTX-ROUTES-MAP-001 | Versión inicial desde estructura-proyecto + orden de landings |
| 2026-04-08 | CTX-ROUTES-MAP-001 | Rutas concretas ES/EN para las 5 landings; plantilla unificada `landing.php` |
