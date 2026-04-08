# context/redsys_flow.md — Flujo TPV Redsys
| Campo | Valor |
|-------|--------|
| **ID** | `CTX-REDSYS-FLOW-001` |
| **Owner** | `[TODO]` |
| **LastUpdated** | 2026-04-08 |
| **SourceOfTruth** | `redsys-php-flujo.md`, `src/Services/RedsysService.php`, `src/Controllers/RedsysController.php` |

## 0) Resumen
```
Cliente → Checkout (PHP genera firma) → POST a Redsys (realizarPago)
       → Usuario paga en TPV Redsys
       → Redsys POST notify (server-to-server) → PHP valida firma → actualiza pedido
       → Redsys redirige usuario a URLOK / URLKO
```

## 1) URLs a configurar (panel Redsys + app)
| Parámetro interno | Uso |
|-------------------|-----|
| `DS_MERCHANT_MERCHANTURL` | Notify S2S (crítico) |
| `DS_MERCHANT_URLOK` | Redirección usuario OK |
| `DS_MERCHANT_URLKO` | Redirección usuario KO |

Valores típicos (sustituir `BASE_URL`):  
`BASE_URL/redsys/notify`, `BASE_URL/es/checkout/ok`, `BASE_URL/es/checkout/ko` (y equivalentes EN si el router lo exige).

## 2) Endpoints TPV
| Entorno | URL |
|---------|-----|
| Sandbox | `https://sis-t.redsys.es:25443/sis/realizarPago` |
| Producción | `https://sis.redsys.es/sis/realizarPago` |

## 3) Parámetros clave del formulario POST
| Campo enviado al navegador | Descripción |
|----------------------------|-------------|
| `Ds_SignatureVersion` | p. ej. `HMAC_SHA256_V1` |
| `Ds_MerchantParameters` | Base64(JSON de parámetros merchant) |
| `Ds_Signature` | Firma HMAC-SHA256 |

Parámetros dentro del JSON (merchant): cantidad en **céntimos**, `DS_MERCHANT_ORDER` (máx 12 chars alfanuméricos según restricciones Redsys), moneda `978` EUR, `DS_MERCHANT_TRANSACTIONTYPE` `0`, idioma consumidor `001` ES / `002` EN, etc. Ver `redsys-php-flujo.md`.

## 4) Notify (server-to-server)
1. Recibir POST `Ds_SignatureVersion`, `Ds_MerchantParameters`, `Ds_Signature`
2. `validateNotification()` → `hash_equals` firma recalculada vs recibida
3. Interpretar `Ds_Response` (autorización según regla del proyecto; ver `redsys-php-flujo.md`, típicamente código numérico inferior a 100)
4. Actualizar pedido en BD (`paid` / `failed`)
5. Responder HTTP adecuado (convención del proyecto; muchos comercios responden 200 con cuerpo OK/KO para Redsys)

**Nunca** marcar pagado solo por visita a `/checkout/ok`.

## 5) Idempotencia
- Si Redsys reenvía notify: comprobar si `order_ref` ya está `paid` y responder sin doble aplicación de negocio.

## 6) Secretos
- `config/redsys.php` o variables de entorno; no git

## 7) Notas de cambios
| Fecha | Resumen |
|-------|---------|
| 2026-04-08 | Basado en redsys-php-flujo.md |
