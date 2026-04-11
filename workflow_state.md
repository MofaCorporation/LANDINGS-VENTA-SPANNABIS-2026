# workflow_state.md — Tarumba's Farm ([TODO:DOMAIN]) — Estado dinámico (Blueprint + Próximos pasos)

## 0) Propósito (qué es y qué NO es)
Este archivo es el **estado vivo** del proyecto para que Cursor (y humanos) no pierdan el hilo.
- Aquí vive: fase actual, prioridades, cola de trabajo, decisiones, riesgos, próximos pasos.
- Aquí NO vive: arquitectura estable (eso va en `project_config.md`).

**OBJ actual:** `OBJ-TIENDA-REDSYS`

**Checkout transferencia (2026-04):** Pedidos con estado `pending_transfer`; TPV tarjeta desactivado vía `CheckoutController::ALLOW_CARD_CHECKOUT`. Antes de desplegar, aplicar `context/migration_orders_status_pending_transfer.sql` en MySQL. Datos IBAN en `src/Services/BankTransferDetails.php`.

---

## 1) Estado actual (rellenar con evidencia)
**[TODO-STATE]** Completar con datos reales del repo y despliegue:

| Campo | Valor |
|-------|--------|
| Rama fuente de verdad | `[TODO]` |
| Commit / tag objetivo | `[TODO]` |
| Entorno | `[TODO]` (local / staging / prod) |
| URL(s) staging / prod | `[TODO]` |
| Última verificación DoD PHP | `[TODO]` |
| P0 abiertos conocidos | `[TODO]` |
| Flujos críticos OK end-to-end | `[TODO]` |

---

## 2) Fase actual (solo una)
Marca una:
- [x] **ANALYSIS** (recon / diagnóstico con evidencia)
- [ ] **BUILD** (implementación controlada)
- [ ] **VALIDATION** (cierre: QA, regresión, hardening, release)

**Criterio para pasar de fase:**
- ANALYSIS → BUILD: rutas y esquema acordados; referencias `EJEMPLOS/` y `/context` al día.
- BUILD → VALIDATION: landings + checkout + Redsys probados en sandbox; i18n ES/EN.
- VALIDATION → DONE: checklist tienda completo y evidencia registrada.

---

## 3) DoD “verde” (PHP — ajustar cuando exista CI)
Bloqueante (manual o CI):
1) `php -l` en archivos PHP tocados
2) Revisión: prepared statements + escape HTML
3) Prueba manual o automatizada de rutas afectadas
4) Paridad claves `es.json` / `en.json` si hubo i18n

**[TODO]** Si se añade PHPUnit / PHPStan / pipeline: listar comandos aquí y en `project_config.md`.

---

## 4) STOP points
| ID | Área | Regla |
|----|------|--------|
| `DB-MIGRATION-001` | DB | Migraciones SQL + snapshot `context/db_schema.sql` |
| `REDSYS-SIGN-001` | Pagos | Firma, endpoints, idempotencia notify |
| `I18N-LANG-001` | i18n | Claves y slugs ES/EN |
| `SEC-XSS-001` | Salida HTML | Escape sistemático |
| `DS-TF-001` | Diseño | Tokens y clases `tf-*`, referencia `EJEMPLOS/` |

---

## 5) Checklist “Tienda lista” (baseline)
Cada ítem: PASS / FAIL con evidencia (pasos, captura, commit).

### 5.1 Landings (orden de producto)
- [ ] `FLOW-LP-001` Toxic Mutant — ES + EN
- [ ] `FLOW-LP-002` Nitro Bud — ES + EN
- [ ] `FLOW-LP-003` DJ Piggy — ES + EN
- [ ] `FLOW-LP-004` Holy Boss — ES + EN
- [ ] `FLOW-LP-005` Lady Cupcake — ES + EN

### 5.2 Checkout y pago
- [ ] `FLOW-CHECKOUT-010` Resumen pedido + form Redsys
- [ ] `REDSYS-NOTIFY-010` Notify valida firma y actualiza pedido
- [ ] `REDSYS-OKKO-010` Páginas ok/ko coherentes (sin confiar en ok para estado pagado)

### 5.3 i18n y SEO básico
- [ ] `UX-I18N-010` Selector de idioma; `lang` en `<html>`; hreflang donde aplique

### 5.4 Diseño
- [ ] `DS-TF-010` Variables por variedad; tipografía Bangers / Satoshi; estética sticker acorde a `EJEMPLOS/`

---

## 6) Cola de trabajo
### P0 — Bloqueantes
1) **[vacío]**

### P1 — Importantes
1) **[vacío]**

### P2 — Mejoras / deuda
1) **[vacío]**

---

## 7) Registro de decisiones (Decision Log)
| Fecha | ID | Decisión | Motivo | Impacto |
|-------|-----|----------|--------|---------|
| 2026-04-08 | `GOV-TF-001` | Gobernanza aplicada desde cursor-governance-template adaptada a PHP + Redsys + Tarumba's Farm | Alinear agentes y humanos | Nuevo `/context` y `.cursor/rules` |
| 2026-04-08 | `LANG-RULES-ES-ENIDS` | Reglas en ES, IDs EN | Consistencia en PRs y reglas | — |

---

## 8) Uso en Cursor
- Adjuntar `agents.md`, `project_config.md`, `workflow_state.md` al iniciar una tarea.
- Antes de STOP points: mini-plan en la cola P0/P1.
- Al cerrar: entrada en **Notas de ejecución**.

---

## 9) Notas de ejecución (log corto, solo hechos)
| Fecha | ID | Resumen | Evidencia |
|-------|-----|---------|-----------|
| 2026-04-08 | `GOV-TF-002` | Repo GitHub creado y push `main` | `gh repo create los-monstruos-de-tarumba`, remoto `origin` |
| 2026-04-09 | `DB-MIGRATION-001` | Añadida migración + snapshot para tabla `leads` (captura post-pago) | `migrations/2026-04-09_leads.sql`, `context/db_schema.sql` |
