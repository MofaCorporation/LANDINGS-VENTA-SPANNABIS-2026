# agents.md — Tarumba's Farm ([TODO:DOMAIN]) — Manual de Gobernanza para Agentes (Cursor)

## 0) Propósito (por qué existe este archivo)
Este repo se trabaja con IA como **agente autónomo**. Este documento es la carta de navegación para:
- Evitar cambios grandes e injustificados (“vibecoding”).
- Reducir ruido cognitivo: **solo el contexto necesario, cuando toca**.
- Mantener **seguridad** (SQL, XSS, pagos Redsys) como prioridad.
- Asegurar entregas verificables con el **DoD PHP** definido en este repo.

---

## 1) Identidad: nombres canónicos (fuente de verdad)
- **Producto / marca:** Tarumba's Farm
- **Nombre comercial extendido / campaña:** Los monstruos de Tarumba (colección de 5 variedades)
- **Dominio / producción:** `[TODO:DOMAIN]`
  - Ej: `BASE_URL`, `info@[TODO:DOMAIN]`
- **Repo / paquete técnico:** `los-monstruos-de-tarumba` (GitHub)

Regla: documentación de producto usa **Tarumba's Farm**. Infra/URLs usan el dominio real. El repo técnico puede usar el slug de GitHub.

---

## 2) Alcance real del producto (NO negociar)
- **Tienda de coleccionables** con **5 variedades**, cada una con **landing propia** (ES + EN).
- **Un checkout** con **TPV Redsys** (form POST firmado + notificación server-to-server).
- **Multiidioma** ES/EN con `src/Lang/es.json`, `src/Lang/en.json` y clase `Lang`.
- **Sin framework PHP:** micro-router propio, Composer PSR-4, front controller en `public/index.php`, Apache + `.htaccess`.

Fuera de alcance salvo decisión explícita: panel de administración completo, otros TPV, apps móviles nativas, foros.

---

## 3) Objetivo de las próximas 2–4 semanas (DECIDIDO)
**Dejar la tienda operativa y lista para cobrar en producción** (landings + checkout + Redsys + i18n + diseño aprobado).

Implicaciones:
- Priorizar **P0=0** en rutas públicas, pago, i18n y coherencia visual con `EJEMPLOS/`.
- Cada cambio debe cerrar un riesgo real: SQL injection, XSS, firma Redsys, claves i18n rotas, diseño incoherente.

---

## 4) Stack y restricciones duras (gobernanza técnica)
- PHP 8.2+ sin framework (router propio)
- MySQL 8 / MariaDB
- HTML5 + CSS3 + JavaScript vanilla + **Alpine.js** (carrito)
- TPV Redsys (HMAC, 3DES según `context/redsys_flow.md`)
- i18n: JSON + `App\Lang\Lang`
- Apache 2.4 + `.htaccess` (document root: `public/`)
- Docker local: `docker-compose.yml` **cuando exista en el repo** (ver `project_config.md`)
- Composer (autoload PSR-4 `App\` → `src/`)

### 4.1 Seguridad (bloqueante)
- **Prepared statements** para toda consulta con datos externos.
- **Salida HTML:** `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` para contenido dinámico en vistas (salvo bloques donde el diseño exija HTML controlado y esté auditado).
- **Redsys:** validar firma en **notify**; **no** marcar pedido pagado solo por URL OK del usuario.
- Secretos solo en `.env` / `config/*.php` **fuera de git** (ver `.gitignore` y `TEMPLATE_VARS.md`).

### 4.2 Base de datos / migraciones
- Cambios de esquema: scripts SQL versionados (p. ej. `migrations/` o documentados en `context/db_schema.sql` tras aplicar).
- STOP antes de tocar tablas `products`, `orders`, sesiones o campos Redsys sin plan.

### 4.3 Redsys
- Endpoints sandbox vs producción según configuración.
- Idempotencia razonable en notify (evitar doble aplicación del mismo pago).

### 4.4 i18n
- Toda UI visible vía claves `Lang::t()` / equivalente; **misma clave** en `es.json` y `en.json`.
- Slugs y contenido de producto: columnas o campos por idioma según `context/i18n_map.md`.

---

## 5) Definition of Done (DoD) real (PHP)
Bloqueante para considerar una tarea cerrada:
1) **Sintaxis PHP válida** en archivos tocados: `php -l <archivo>` (o script/composer equivalente si se añade).
2) **SQL:** solo prepared statements con datos externos.
3) **Output HTML:** escape coherente (`htmlspecialchars` donde aplique).
4) **Redsys:** firma validada en notify; flujo documentado respetado.
5) **i18n:** claves nuevas en **ES y EN**.
6) **Diseño:** coherente con el sistema Tarumba's Farm (`context/design_tokens.md`, referencias en `EJEMPLOS/`).

Si el repo añade **PHPUnit** u otro runner, el DoD se amplía con “tests verdes” y se actualiza `project_config.md`.

---

## 6) Workflow obligatorio (modo agente serio)
Ciclo por defecto:
1) **RECON** — Leer código real, rutas, `context/*`, ejemplos HTML/CSS en `EJEMPLOS/`. No inventar tablas ni endpoints.
2) **PLAN** — Plan corto: riesgos, archivos, STOP points (DB, Redsys, i18n, diseño).
3) **EXEC** — Cambios mínimos; sin refactors oportunistas en la misma entrega.
4) **VERIFY** — DoD de la sección 5; prueba manual de rutas afectadas.

Entrega mínima por tarea:
- Qué cambió y por qué.
- Archivos tocados.
- Cómo reproducir antes/después.
- Evidencia de verificación (comandos, checklist).

---

## 7) Reglas en Cursor: idioma y convención de IDs (DECIDIDO)
**Idioma de reglas `.cursor/rules/*.mdc`: español, con IDs estables en inglés.**

Ejemplos: `CTX-ROUTING-001`, `PHP-SQL-001`, `REDSYS-NOTIFY-001`, `I18N-LANG-001`, `DS-TF-001` (design system), `QA-DOD-PHP-001`.

---

## 8) Mapa de fuentes de verdad (anti-alucinación)
Orden preferido:
1) Código y `context/db_schema.sql` reales.
2) `estructura-proyecto.md`, `redsys-php-flujo.md`, `i18n-sistema-idiomas.md` en la raíz del repo.
3) `project_config.md`, `agents.md`, `workflow_state.md`.
4) Referencia visual: archivos en `EJEMPLOS/` (landings y checkout aprobados).

---

## 9) Preferencias de implementación
- Reutilizar patrones de `estructura-proyecto.md` (controladores, `RedsysService`, `Lang`).
- **No** introducir otro sistema de estilos: respetar tokens y clases en `context/design_tokens.md`.
- Carrito: Alpine.js como en la especificación del proyecto.

---

## 10) Antipatrones prohibidos
- Consultas SQL concatenadas con input de usuario.
- Echo de variables sin escape en HTML.
- Confiar en `/checkout/ok` para marcar pagado.
- Clave i18n solo en un idioma.
- Colores o tipografías fuera del design system aprobado.
- Inventar rutas o columnas no documentadas.

---

## 11) Qué hacer si falta información
- Pedir evidencia o marcar `[TODO]` con siguiente paso concreto.
- Si la tarea toca **Redsys**, **migraciones**, **i18n routing** o **tokens de diseño**: **STOP** y plan antes de ejecutar.
