# context/flows_core.md — Flujos core (Tarumba's Farm)
| Campo | Valor |
|-------|--------|
| **ID** | `CTX-FLOWS-CORE-001` |
| **Owner** | `[TODO]` |
| **LastUpdated** | 2026-04-08 |
| **SourceOfTruth** | Controllers, templates, `EJEMPLOS/` |

## 0) Propósito
Describir flujos para QA reproducible y para que el agente no invente pasos.

---

## FLOW-HOME-010 — Home por idioma
- **Trigger:** `GET /es/` o `GET /en/`
- **Esperado:** idioma activo coherente; enlaces a las 5 landings y checkout
- **Fallos típicos:** prefijo idioma perdido en links

---

## FLOW-LP-ORDER-010 — Landings (orden de negocio)
| # | Variedad | Notas |
|---|----------|--------|
| 1 | Toxic Mutant | Referencia `EJEMPLOS/TOXIC MUTANT · Tarumba's Farm.html` |
| 2 | Nitro Bud | `EJEMPLOS/NITRO BUD · Tarumba's Farm.html` |
| 3 | DJ Piggy | `EJEMPLOS/DJ PIGGY · Tarumba's Farm.html` |
| 4 | Holy Boss | `EJEMPLOS/HOLY BOSS · Tarumba's Farm.html` |
| 5 | Lady Cupcake | `EJEMPLOS/LADY CUPCAKE · Tarumba's Farm.html` |

Cada flujo:
- **Trigger:** GET landing con slug correcto en ES o EN
- **Esperado:** variables CSS de esa variedad; tipografía y componentes `tf-*`; CTA a checkout con producto/variety correcto
- **Prueba:** cambiar idioma y mantener equivalencia de producto (mismo `id` interno o `variety`)

---

## FLOW-CART-010 — Carrito (Alpine.js)
- **Trigger:** añadir desde landing
- **Esperado:** estado en cliente (y/o sesión según implementación); persistencia mínima para llegar a checkout
- **Fallos:** pérdida de carrito al cambiar idioma si no está diseñado

---

## FLOW-CHECKOUT-010 — Checkout + redirección Redsys
1. Usuario con ítems (o producto seleccionado) llega a `/es/checkout` o `/en/checkout`
2. Servidor crea/actualiza `orders` en `pending` con `order_ref` único
3. Se construye firma Redsys y se renderiza form auto-post a TPV
4. Usuario completa pago en Redsys

**No marcar paid en este paso.**

---

## FLOW-REDSYS-NOTIFY-010 — Notificación
1. Redsys POST a `/redsys/notify`
2. Validar firma
3. Si OK de negocio: `Order::markAsPaid` (o equivalente) + guardar JSON respuesta
4. Si KO: `markAsFailed`
5. Idempotencia si el mismo notify se repite

---

## FLOW-REDSYS-RETURN-010 — URLOK / URLKO
- Mostrar confirmación o error al usuario
- **Solo** feedback UX; estado autoritativo = notify + BD

---

## FLOW-I18N-SWITCH-010 — Cambio de idioma
- Enlace generado con `Lang::switchUrl()`
- Misma página equivalente en el otro locale (slug mapeado vía BD o tabla de rutas)

---

## Smoke release (manual)
- [ ] Cada landing ES + EN carga sin 404
- [ ] Checkout muestra resumen y envía a sandbox Redsys
- [ ] Notify de prueba actualiza estado (entorno de desarrollo)
- [ ] OK/KO visibles
- [ ] Selector idioma en las 6 superficies principales (5 landings + checkout)

---

## Notas de cambios
| Fecha | ID | Resumen |
|-------|-----|---------|
| 2026-04-08 | CTX-FLOWS-CORE-001 | Versión inicial |
