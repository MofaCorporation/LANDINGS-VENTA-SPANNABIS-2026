# context/routes_map.md — Mapa de rutas (Anti-404)
| Campo | Valor |
|-------|--------|
| **ID** | `CTX-ROUTES-MAP-001` |
| **Owner** | `[TODO]` |
| **LastUpdated** | 2026-04-08 |
| **SourceOfTruth** | `public/index.php`, `src/Core/Router.php`, `.htaccess`, código real |

## 0) Propósito
Listar rutas que la **UI pública** usa y que no deben romperse. Orden de producto en catálogo / landings:

1. Toxic Mutant  
2. Nitro Bud  
3. DJ Piggy  
4. Holy Boss  
5. Lady Cupcake  

## 1) Convención
- Rutas sin dominio.
- Idioma en prefijo: `/es/...`, `/en/...` (según `i18n-sistema-idiomas.md`).
- Slugs internos sugeridos (confirmar en código): kebab-case en ambos idiomas o pares `slug_es` / `slug_en` en BD.

## 2) Rutas públicas — tabla

| Ruta (patrón) | Owner UI | Criticality | Notas |
|---------------|----------|-------------|--------|
| `/es/` | home / nav | P0 | Home ES |
| `/en/` | home / nav | P0 | Home EN |
| `/es/{slug-toxic-mutant}` | landing 1 | P0 | Slug desde `products.slug_es` |
| `/en/{slug-toxic-mutant}` | landing 1 | P0 | `products.slug_en` |
| `/es/{slug-nitro-bud}` | landing 2 | P0 | |
| `/en/{slug-nitro-bud}` | landing 2 | P0 | |
| `/es/{slug-dj-piggy}` | landing 3 | P0 | |
| `/en/{slug-dj-piggy}` | landing 3 | P0 | |
| `/es/{slug-holy-boss}` | landing 4 | P0 | |
| `/en/{slug-holy-boss}` | landing 4 | P0 | |
| `/es/{slug-lady-cupcake}` | landing 5 | P0 | |
| `/en/{slug-lady-cupcake}` | landing 5 | P0 | |
| `/es/checkout` | CTA comprar | P0 | Carrito + form Redsys |
| `/en/checkout` | CTA comprar | P0 | |
| `/redsys/notify` | Redsys POST | P0 | Sin UI; server-to-server |
| `/es/checkout/ok` | retorno usuario | P1 | No marca pago solo |
| `/en/checkout/ok` | retorno usuario | P1 | |
| `/es/checkout/ko` | retorno usuario | P1 | |
| `/en/checkout/ko` | retorno usuario | P1 | |

**Referencia HTML estática:** `EJEMPLOS/` usa URLs tipo `checkout.html?variety=toxic-mutant`; el router PHP puede mapear `?variety=` o slugs — documentar la forma final en RECON.

## 3) Assets estáticos
| Ruta | Notas |
|------|--------|
| `/assets/css/*`, `/assets/js/*`, `/assets/img/*` | Deben servirse como archivos (no rewrite a `index.php`) |

## 4) Pendientes / sospechosas
| Ruta | Acción |
|------|--------|
| `[TODO]` | Sincronizar con implementación real del Router |

## 5) Notas de cambios
| Fecha | ID | Resumen |
|-------|-----|---------|
| 2026-04-08 | CTX-ROUTES-MAP-001 | Versión inicial desde estructura-proyecto + orden de landings |
