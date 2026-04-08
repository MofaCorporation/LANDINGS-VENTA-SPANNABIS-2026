# TEMPLATE_VARS.md — Variables canónicas (Tarumba's Farm / Los monstruos de Tarumba)

## 0) Propósito
Fuente de verdad de **placeholders** y valores de configuración que deben mantenerse alineados entre documentación, `.env.example` y despliegue.
Sustituir `[TODO:…]` en RECON o al preparar producción.

---

## 1) Variables

| Variable | Descripción | Uso típico |
|----------|-------------|------------|
| `<PRODUCT_NAME>` | Marca | Tarumba's Farm |
| `<COLLECTION_NAME>` | Nombre campaña / repo legible | Los monstruos de Tarumba |
| `<DOMAIN>` | Dominio producción | `agents.md`, `project_config.md`, hreflang, `BASE_URL` |
| `<REPO_NAME>` | Slug GitHub | `los-monstruos-de-tarumba` |
| `<BASE_URL>` | URL absoluta base (HTTPS) | Redsys `notify` / `ok` / `ko`, enlaces canónicos |
| `<DB_HOST>` | Host MySQL | Docker service o localhost |
| `<DB_NAME>` | Nombre BD | p. ej. `ecommerce` o `tarumbas_farm` |
| `<DB_USER>` | Usuario BD | nunca en git |
| `<DB_PASS>` | Contraseña BD | nunca en git |
| `<REDSYS_MERCHANT_CODE>` | Código comercio Redsys | panel Redsys |
| `<REDSYS_TERMINAL>` | Terminal | p. ej. `001` |
| `<REDSYS_SECRET_KEY>` | Clave firma SHA-256 (Base64) | **placeholder** en docs; real solo en servidor |
| `<REDSYS_SANDBOX>` | `true` / `false` | desarrollo vs producción |
| `<DEFAULT_LOCALES>` | Idiomas UI | `es`, `en` |
| `<CI_BRANCHES>` | Ramas protegidas | `[TODO]` cuando exista CI |
| `<DOD_COMMANDS>` | Comandos verificación | `php -l`, tests futuros |

---

## 2) Placeholders Redsys (ejemplo — NO usar en producción)

> Los valores de ejemplo provienen de documentación genérica; **sustituir** por credenciales reales del panel Redsys.

| Campo | Placeholder documental |
|-------|-------------------------|
| Merchant code | `999008881` (ejemplo típico en docs) |
| Secret | `[REDSYS_SECRET_KEY]` |

---

## 3) Colores del sistema de diseño (por variedad)

Definidos en **atributo `style` del `<html>`** en las landings de referencia (`EJEMPLOS/*.html`). Cada producto tiene su propio juego; el código PHP debe inyectar el mismo patrón.

| Variedad | `--pc` | `--sec` | `--bg` | Notas |
|----------|--------|---------|--------|--------|
| Toxic Mutant | `#bdfc00` | `#ff51fa` | `#0e0e0e` | + `--pc-dim`, `--ter`, `--surf`, `--surf-cont`, `--on-pc`, `--nav-stroke` |
| Nitro Bud | `#ff6b00` | `#00d4ff` | `#0a0a0c` | idem |
| DJ Piggy | `#ff00aa` | `#00ffcc` | `#0e0610` | idem |
| Holy Boss | `#f5e6a8` | `#8b5cf6` | `#08080a` | idem |
| Lady Cupcake | `#ffb6c1` | `#a78bfa` | `#0f0a0c` | idem |

Detalle completo: `context/design_tokens.md`.

---

## 4) Reglas
1. No commitear valores reales de `<REDSYS_SECRET_KEY>`, `<DB_PASS>`, etc.
2. Tras clonar: completar `[TODO:DOMAIN]` y URLs Redsys en `config/app.php` o `.env`.
3. Buscar en el repo restos de `<PRODUCT_NAME>` o `[TODO:DOMAIN]` antes de release.

---

## 5) Tabla rápida de sustitución (valores actuales de gobernanza)

| Placeholder | Valor en este repo (doc) |
|-------------|---------------------------|
| `<PRODUCT_NAME>` | Tarumba's Farm |
| `<COLLECTION_NAME>` | Los monstruos de Tarumba |
| `<REPO_NAME>` | los-monstruos-de-tarumba |
| `<DOMAIN>` | `[TODO:DOMAIN]` |
| `<DEFAULT_LOCALES>` | es, en |
