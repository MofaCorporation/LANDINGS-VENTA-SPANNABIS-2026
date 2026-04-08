# project_config.md — Tarumba's Farm ([TODO:DOMAIN]) — Configuración estática del proyecto (Fuente de verdad)

## 0) Propósito
Este documento fija lo **estable** del proyecto: stack, arquitectura, convenciones y líneas rojas.
El estado dinámico vive en `workflow_state.md`.

---

## 1) Identidad y naming (canónico)
| Campo | Valor |
|-------|--------|
| Marca / producto | Tarumba's Farm |
| Colección | Los monstruos de Tarumba (5 variedades) |
| Producción / URL | `[TODO:DOMAIN]` |
| Repo GitHub (técnico) | `los-monstruos-de-tarumba` |

---

## 2) Objetivo actual (2–4 semanas)
**OBJ-TIENDA-REDSYS:** Tienda pública funcional: 5 landings × 2 idiomas + checkout Redsys + diseño alineado con `EJEMPLOS/`.

---

## 3) Stack técnico
| Capa | Tecnología |
|------|------------|
| Lenguaje | PHP 8.2+ |
| Framework | Ninguno (micro-router propio) |
| BD | MySQL 8 / MariaDB |
| Front | HTML5, CSS3, JS vanilla |
| Reactividad carrito | Alpine.js |
| Pagos | TPV Redsys |
| i18n | `src/Lang/Lang.php` + `es.json` / `en.json` |
| Servidor | Apache 2.4 + `.htaccess` |
| Autoload | Composer PSR-4 (`App\` → `src/`) |
| IDE | Cursor |
| Entorno local | Docker (`docker-compose.yml` en el repo cuando esté añadido; `[TODO]` si aún no existe en el clon) |

**Regla anti-alucinación:** versiones exactas de extensiones PHP y paquetes Composer se leen de `composer.json` y del entorno real.

---

## 4) Seguridad (bloqueante)
- Credenciales: `.env` y/o `config/database.php`, `config/redsys.php` **no** commiteados.
- HTTPS obligatorio en producción para Redsys.
- Validación estricta de notificaciones Redsys (firma).

---

## 5) STOP points (no tocar sin plan + verificación)
ID | Área | Regla |
|----|------|--------|
| `DB-MIGRATION-001` | MySQL | Cambios de esquema (`products`, `orders`, sesiones, campos Redsys): migración + rollback + actualizar `context/db_schema.sql` |
| `REDSYS-SIGN-001` | Pagos | Firma, claves, URLs notify/ok/ko, sandbox/prod |
| `I18N-LANG-001` | i18n | Nuevas claves, slugs ES/EN, `Lang::init` / prefijos de URL |
| `SEC-XSS-001` | Salida HTML | Cualquier cambio que debilite el escape sistemático en plantillas |
| `DS-TF-001` | Diseño | Variables CSS por producto, tipografías, clases `tf-*`, patrones de `EJEMPLOS/` |

---

## 6) Calidad: DoD (PHP)
Ver `agents.md` sección 5 y regla `QA-DOD-PHP-001`.

Resumen:
- `php -l` en archivos PHP modificados.
- Prepared statements.
- `htmlspecialchars` en outputs HTML dinámicos.
- Redsys: firma validada en notify.
- Paridad de claves `es.json` / `en.json`.
- Coherencia con `context/design_tokens.md`.

---

## 7) Convenciones de cambios
- Cambios mínimos por PR/commit lógico.
- Documentar riesgos si se toca un STOP point.
- Tras cambios de esquema o rutas: actualizar `/context` correspondiente.

---

## 8) Sistema de IDs (EN) para reglas y tickets
Formato: `<AREA>-<TOPIC>-<NNN>`

Ejemplos: `OBJ-TIENDA-REDSYS`, `DB-MIGRATION-001`, `REDSYS-SIGN-001`, `I18N-LANG-001`, `DS-TF-001`, `QA-DOD-PHP-001`.

---

## 9) Arquitectura (resumen)
- `public/index.php` → front controller.
- `src/Core/Router.php` — enrutado.
- `src/Controllers/*` — HTTP.
- `src/Models/*` — acceso a datos (PDO).
- `src/Services/RedsysService.php` — firma y validación.
- `templates/*` — vistas PHP.
- `config/*` — app, BD, Redsys (secretos fuera de git).

---

## 10) UI y design system
- **Dark mode** siempre.
- Referencia obligatoria: landings y checkout en `EJEMPLOS/`.
- Tokens y clases: `context/design_tokens.md`.
- Prohibido introducir paletas o fuentes nuevas sin actualizar gobernanza y ejemplos.

---

## 11) Carpeta `/context`
Artefactos que el agente debe consultar antes de inventar comportamiento:
- `db_schema.sql`, `routes_map.md`, `redsys_flow.md`, `i18n_map.md`, `flows_core.md`, `design_tokens.md`.

---

## 12) Qué NO hacer
- Mezclar otra pasarela de pago sin decisión explícita.
- Sustituir el sistema visual de Tarumba's Farm por otro “theme”.
- Commitear secretos o `.env` con valores reales.
