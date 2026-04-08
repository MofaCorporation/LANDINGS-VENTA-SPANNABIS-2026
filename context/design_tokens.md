# context/design_tokens.md — Tokens visuales Tarumba's Farm
| Campo | Valor |
|-------|--------|
| **ID** | `CTX-DESIGN-TOKENS-001` |
| **Owner** | `[TODO]` |
| **LastUpdated** | 2026-04-08 |
| **SourceOfTruth** | `EJEMPLOS/*_files/mount-DElUb8cY.css`, HTML en `EJEMPLOS/*.html` |

## 0) Propósito
Documentar variables CSS, fuentes y clases para que **ninguna página** se desvíe del sistema aprobado.

## 1) Modo
- Siempre **dark** (`<html class="dark">` en referencias).

## 2) Tipografía (globales en `:root` / theme)
| Token / variable | Valor (referencia) |
|------------------|-------------------|
| `--font-headline` | `"Satoshi", ui-sans-serif, system-ui, sans-serif` |
| `--font-body` | igual Satoshi |
| `--font-bangers` | `"Bangers", ui-sans-serif, system-ui, sans-serif` |

**Imports:** Google Fonts Bangers; Fontshare Satoshi (ver `mount-DElUb8cY.css`).

### Uso obligatorio
| Rol | Implementación |
|-----|----------------|
| Títulos impacto / marca | Clase **`tf-title-bangers`**, **UPPERCASE**, color vía `text-[var(--pc)]` o blanco según bloque |
| Headlines | `font-[family-name:var(--font-headline)]` + **`font-bold`** |
| Cuerpo | `font-[family-name:var(--font-body)]` |

Clases adicionales en CSS compilado: `tf-nav-bangers`, `tf-bangers-fill`, `tf-btn-sticker`, `tf-panel-sticker`, `sticker-shadow` (`box-shadow: 8px 8px #000`).

## 3) Variables de color por variedad (en `<html style="...">`)
Cada landing define el **mismo conjunto de variables** con valores distintos:

| Variable | Rol |
|----------|-----|
| `--pc` | Color primario (acento principal) |
| `--pc-dim` | Primario atenuado |
| `--sec` | Secundario (acento contraste, bordes sticker) |
| `--ter` | Terciario (badges, acentos) |
| `--bg` | Fondo página principal |
| `--surf` | Superficie de sección |
| `--surf-cont` | Contenedores / paneles |
| `--on-pc` | Texto sobre fondo primario |
| `--nav-stroke` | Trazo / sombra de navegación y stickers |

### Valores por producto (copiar de referencias `EJEMPLOS/*.html`)
| Variedad | `--pc` | `--pc-dim` | `--sec` | `--ter` | `--bg` | `--surf` | `--surf-cont` | `--on-pc` | `--nav-stroke` |
|----------|--------|------------|---------|---------|--------|----------|---------------|-----------|----------------|
| Toxic Mutant | `#bdfc00` | `#b1ed00` | `#ff51fa` | `#c1fffe` | `#0e0e0e` | `#0e0e0e` | `#1a1919` | `#445d00` | `#ff51fa` |
| Nitro Bud | `#ff6b00` | `#e85d00` | `#00d4ff` | `#fff200` | `#0a0a0c` | `#0a0a0c` | `#141418` | `#1a0a00` | `#00d4ff` |
| DJ Piggy | `#ff00aa` | `#d6008e` | `#00ffcc` | `#ffff00` | `#0e0610` | `#0e0610` | `#1a1020` | `#2a0018` | `#00ffcc` |
| Holy Boss | `#f5e6a8` | `#d4c278` | `#8b5cf6` | `#38bdf8` | `#08080a` | `#08080a` | `#12121a` | `#1a1508` | `#8b5cf6` |
| Lady Cupcake | `#ffb6c1` | `#ff8da1` | `#a78bfa` | `#67e8f9` | `#0f0a0c` | `#0f0a0c` | `#1a1216` | `#3d1520` | `#a78bfa` |

## 4) Colores neutros recurrentes (texto en referencias)
Usados en párrafos en los HTML de ejemplo; **solo** estos o variables anteriores:
- `#f0f0f0`, `#f6f6f6`, `#f2f2f2`, `#e4e2e2`, `#bcbcbc`, `#a0a0a0`
- Negro `#000`, `#1a1919`, blanco `#fff` / `white`

## 5) Patrones de layout / utilidades (Tailwind en referencia)
- Fondos: `bg-[var(--bg)]`, `bg-[var(--surf)]`, `bg-[var(--surf-cont)]`, `bg-black`
- Bordes: `border-4`, `border-8`, `border-[var(--pc)]`, `border-black`
- Sombras cómic: `sticker-shadow`; cards con `hover:-translate-y-2 transition-transform`
- Imágenes producto: `border-8 border-black sticker-shadow`, overlays `bg-[var(--pc)]/20 mix-blend-multiply`
- Iconos: `material-symbols-outlined` cuando aplique

## 6) Checkout
Referencia: `EJEMPLOS/Finalizar pedido — Tarumba's Farm_files/checkout.css` + HTML asociado — **no** desviar sin actualizar este documento.

## 7) Prohibido (DS-TF-001)
- Nuevos hex de marca fuera de la tabla por variedad sin aprobación
- Sustituir Bangers/Satoshi por otras familias tipográficas en UI
- Eliminar bordes gruesos / sombras offset características del brand

## 8) Notas de cambios
| Fecha | Resumen |
|-------|---------|
| 2026-04-08 | Extracción desde EJEMPLOS HTML + mount-DElUb8cY.css |
