# context/i18n_map.md — i18n ES/EN (PHP + JSON)
| Campo | Valor |
|-------|--------|
| **ID** | `CTX-I18N-MAP-001` |
| **Owner** | `[TODO]` |
| **LastUpdated** | 2026-04-08 |
| **SourceOfTruth** | `src/Lang/Lang.php`, `src/Lang/es.json`, `src/Lang/en.json`, router |

## 0) Propósito
Definir cómo funciona i18n para evitar strings sueltos y claves huérfanas.

## 1) Locales
| Locale | Fichero |
|--------|---------|
| `es` | `src/Lang/es.json` |
| `en` | `src/Lang/en.json` |

**Default:** `es` si no hay match (`Lang::init` según `i18n-sistema-idiomas.md`).

## 2) Detección de idioma (orden)
1. Primer segmento URL `/es/` o `/en/`
2. Sesión `$_SESSION['lang']`
3. `Accept-Language`
4. Fallback `es`

## 3) API en plantillas
- `Lang::t('nav.home')`, etc.
- Comprobar en código si `Lang::t()` ya aplica `htmlspecialchars` (doc ejemplo: sí) — **no doble-escape**.

## 4) Convención de claves (ID: `I18N-KEYS-001`)
Formato recomendado: `<area>.<name>` con segmentos en **snake_case** o jerarquía anidada en JSON.

Áreas sugeridas: `nav`, `product`, `checkout`, `errors`, `footer`, `meta`, `landings` (subclaves por variedad si hace falta).

**Regla:** misma estructura de claves en ES y EN.

## 5) Contenido de producto (BD)
- Nombres y descripciones: `name_es` / `name_en`, `description_es` / `description_en`
- Slugs: `slug_es` / `slug_en`
- El router resuelve la landing según idioma activo + slug

## 6) SEO
- `<html lang="<?= Lang::current() ?>">`
- `hreflang` alternates ES/EN en landings y home (ver `i18n-sistema-idiomas.md`)

## 7) QA (ID: `QA-I18N-010`)
- Cualquier PR con textos UI: diff de claves entre `es.json` y `en.json`

## 8) Notas de cambios
| Fecha | Resumen |
|-------|---------|
| 2026-04-08 | Versión inicial desde i18n-sistema-idiomas.md |
