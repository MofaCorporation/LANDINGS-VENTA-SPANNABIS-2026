# Flujo completo TPV Redsys en PHP

## Requisitos previos

- PHP 8.2+
- HTTPS obligatorio en producción (Redsys rechaza HTTP)
- Credenciales del comercio: `DS_MERCHANT_MERCHANTCODE`, `DS_MERCHANT_TERMINAL`, clave secreta
- Librería oficial Redsys o implementación propia con HMAC-SHA256

### Instalación de la librería vía Composer

```bash
composer require ssheduardo/sermepa
```

O usa la clase oficial descargada del portal de desarrolladores de Redsys (redsys.es → área técnica).

---

## Visión general del flujo

```
Usuario → Tu checkout.php → Firma el pedido → Redirige a Redsys
    → Usuario paga en Redsys → Redsys notifica tu URL (POST)
    → Validas la firma → Marcas pedido como pagado → Redirige al usuario
```

Hay **tres URLs** que debes configurar en tu panel Redsys y en el código:

| Parámetro | Descripción |
|-----------|-------------|
| `DS_MERCHANT_URLOK` | Redirige al usuario si el pago es correcto |
| `DS_MERCHANT_URLKO` | Redirige al usuario si el pago falla |
| `DS_MERCHANT_MERCHANTURL` | URL de notificación server-to-server (la más importante) |

---

## Paso 1 — Crear el formulario de pago

**`/src/Services/RedsysService.php`**

```php
<?php

namespace App\Services;

class RedsysService
{
    private string $secretKey;
    private string $merchantCode;
    private string $terminal;
    private bool   $sandbox;

    public function __construct(array $config)
    {
        $this->secretKey    = $config['secret_key'];
        $this->merchantCode = $config['merchant_code'];
        $this->terminal     = $config['terminal'];
        $this->sandbox      = $config['sandbox'] ?? true;
    }

    /**
     * Genera los parámetros firmados para el formulario POST a Redsys.
     */
    public function buildPaymentParams(array $order): array
    {
        $params = [
            'DS_MERCHANT_AMOUNT'        => $order['amount_cents'],   // en céntimos: 10.50€ = 1050
            'DS_MERCHANT_ORDER'         => $order['order_id'],       // alfanumérico, máx 12 chars
            'DS_MERCHANT_MERCHANTCODE'  => $this->merchantCode,
            'DS_MERCHANT_CURRENCY'      => '978',                    // 978 = EUR
            'DS_MERCHANT_TRANSACTIONTYPE' => '0',                    // 0 = cargo
            'DS_MERCHANT_TERMINAL'      => $this->terminal,
            'DS_MERCHANT_MERCHANTURL'   => $order['notify_url'],
            'DS_MERCHANT_URLOK'         => $order['ok_url'],
            'DS_MERCHANT_URLKO'         => $order['ko_url'],
            'DS_MERCHANT_CONSUMERLANGUAGE' => $order['lang'] === 'en' ? '002' : '001',
        ];

        $base64Params = base64_encode(json_encode($params));
        $signature    = $this->generateSignature($base64Params, $order['order_id']);

        return [
            'Ds_SignatureVersion' => 'HMAC_SHA256_V1',
            'Ds_MerchantParameters' => $base64Params,
            'Ds_Signature'          => $signature,
        ];
    }

    /**
     * Genera la firma HMAC-SHA256.
     */
    private function generateSignature(string $base64Params, string $orderId): string
    {
        // Diversificar la clave con el número de pedido
        $key = $this->diversifyKey($orderId);
        return base64_encode(hash_hmac('sha256', $base64Params, $key, true));
    }

    private function diversifyKey(string $orderId): string
    {
        $key = base64_decode($this->secretKey);
        return $this->encrypt3DES($orderId, $key);
    }

    private function encrypt3DES(string $data, string $key): string
    {
        $iv = "\0\0\0\0\0\0\0\0"; // IV de ceros
        return openssl_encrypt(
            str_pad($data, ceil(strlen($data) / 8) * 8, "\0"),
            'des-ede3-cbc',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        );
    }

    /**
     * Devuelve la URL del TPV (sandbox o producción).
     */
    public function getEndpointUrl(): string
    {
        return $this->sandbox
            ? 'https://sis-t.redsys.es:25443/sis/realizarPago'
            : 'https://sis.redsys.es/sis/realizarPago';
    }

    /**
     * Valida la notificación recibida de Redsys.
     * Devuelve el array de parámetros si la firma es válida, o lanza excepción.
     */
    public function validateNotification(
        string $signatureVersion,
        string $merchantParams,
        string $signature
    ): array {
        $params  = json_decode(base64_decode($merchantParams), true);
        $orderId = $params['Ds_Order'];

        $expectedSignature = $this->generateSignature($merchantParams, $orderId);

        // Comparación segura contra timing attacks
        if (!hash_equals($expectedSignature, $signature)) {
            throw new \RuntimeException('Firma Redsys inválida');
        }

        return $params;
    }
}
```

---

## Paso 2 — Plantilla del checkout

**`/templates/checkout.php`**

```php
<?php
// Obtener parámetros del pedido desde sesión/carrito
$redsys = new \App\Services\RedsysService(require __DIR__.'/../config/redsys.php');

$paymentData = $redsys->buildPaymentParams([
    'amount_cents' => $_SESSION['cart_total_cents'],
    'order_id'     => generateOrderId(),   // ej: 'ORD' . date('ymdHis')
    'lang'         => $_SESSION['lang'] ?? 'es',
    'notify_url'   => BASE_URL . '/redsys/notify',
    'ok_url'       => BASE_URL . '/checkout/ok',
    'ko_url'       => BASE_URL . '/checkout/ko',
]);
?>

<!-- El formulario se auto-envía por JS; el botón es fallback -->
<form id="redsys-form"
      method="POST"
      action="<?= htmlspecialchars($redsys->getEndpointUrl()) ?>">

    <input type="hidden" name="Ds_SignatureVersion"
           value="<?= htmlspecialchars($paymentData['Ds_SignatureVersion']) ?>">
    <input type="hidden" name="Ds_MerchantParameters"
           value="<?= htmlspecialchars($paymentData['Ds_MerchantParameters']) ?>">
    <input type="hidden" name="Ds_Signature"
           value="<?= htmlspecialchars($paymentData['Ds_Signature']) ?>">

    <button type="submit">Pagar ahora / Pay now</button>
</form>

<script>
    // Auto-submit para mejor UX (evitar que el usuario haga clic dos veces)
    document.getElementById('redsys-form').submit();
</script>
```

---

## Paso 3 — Controlador de notificación (server-to-server)

**`/src/Controllers/RedsysController.php`**

```php
<?php

namespace App\Controllers;

use App\Services\RedsysService;
use App\Models\Order;

class RedsysController
{
    public function notify(): void
    {
        $redsys = new RedsysService(require __DIR__.'/../../config/redsys.php');

        try {
            $params = $redsys->validateNotification(
                $_POST['Ds_SignatureVersion']   ?? '',
                $_POST['Ds_MerchantParameters'] ?? '',
                $_POST['Ds_Signature']          ?? ''
            );

            $responseCode = (int) $params['Ds_Response'];
            $orderId      = $params['Ds_Order'];

            if ($responseCode < 100) {
                // Código < 100 significa pago autorizado
                Order::markAsPaid($orderId, $params);
                http_response_code(200);
                echo 'OK';
            } else {
                Order::markAsFailed($orderId, $responseCode);
                http_response_code(200); // Siempre 200 a Redsys
                echo 'KO';
            }
        } catch (\RuntimeException $e) {
            // Firma inválida — posible ataque
            error_log('Redsys notify error: ' . $e->getMessage());
            http_response_code(400);
        }
    }

    public function ok(): void
    {
        // Página de confirmación visible al usuario tras pago correcto
        // NO fiarse de esta URL para marcar el pedido como pagado
        // Eso lo hace notify() server-to-server
        include __DIR__ . '/../../templates/checkout_ok.php';
    }

    public function ko(): void
    {
        include __DIR__ . '/../../templates/checkout_ko.php';
    }
}
```

> **Importante:** nunca marques el pedido como pagado en la URL de retorno (`/checkout/ok`). Usa **siempre** la notificación server-to-server (`/redsys/notify`), que Redsys envía independientemente de si el usuario cierra el navegador.

---

## Paso 4 — Configuración

**`/config/redsys.php`**

```php
<?php

return [
    'sandbox'       => true,   // false en producción
    'merchant_code' => '999008881',          // Tu código de comercio
    'terminal'      => '001',
    'secret_key'    => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7', // Clave SHA-256 del panel Redsys
];
```

---

## Códigos de respuesta Redsys más comunes

| Código | Significado |
|--------|-------------|
| 0000 | Pago autorizado |
| 0101 | Tarjeta caducada |
| 0102 | Tarjeta en excepción transitoria |
| 0106 | Intentos PIN excedidos |
| 0125 | Tarjeta no efectiva |
| 0129 | Código seguridad incorrecto |
| 0180 | Tarjeta ajena al servicio |
| 0184 | Error en autenticación titular |
| 0190 | Denegación sin especificar motivo |
| 0191 | Fecha de caducidad errónea |
| 9915 | Pago cancelado por el usuario |

Cualquier código **< 100** es autorización correcta. **≥ 100** es error o denegación.

---

## Checklist de puesta en producción

- [ ] Cambiar `sandbox` a `false` en `/config/redsys.php`
- [ ] Actualizar la clave secreta a la de producción
- [ ] Verificar que todas las URLs usan HTTPS
- [ ] Configurar las URLs de notificación en el panel de Redsys
- [ ] Probar con tarjeta de pruebas antes del go-live
- [ ] Revisar logs de `DS_RESPONSE` en los primeros pagos reales
