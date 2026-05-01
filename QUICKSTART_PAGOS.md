# 🚀 Inicio Rápido - Sistema de Pagos con PayPal

## ✅ ¿Qué se ha implementado?

Se ha agregado un sistema completo de pagos en línea con PayPal para que los clientes puedan pagar sus facturas mediante links únicos y seguros.

### Características principales:
- ✅ Links de pago únicos con tokens seguros
- ✅ Integración completa con PayPal
- ✅ Actualización automática de pagos en la base de datos
- ✅ Soporte para pagos parciales
- ✅ Páginas de pago modernas y responsive
- ✅ API REST completa para gestión de links
- ✅ Envío automático por Email y WhatsApp
- ✅ Comando Artisan para generar links

## 📋 Pasos para Activar

### 1. Ya ejecutado: Migración de base de datos ✅

```bash
php artisan migrate
```

Se agregaron campos a la tabla `invoices`:
- `payment_token`: Token único de 64 caracteres
- `payment_token_expires_at`: Fecha de expiración del token

### 2. Configurar PayPal

#### Obtener credenciales:
1. Ve a https://developer.paypal.com/
2. Crea una app o usa una existente
3. Copia tu **Client ID** y **Secret**

#### Para pruebas (Sandbox):
Edita tu archivo `.env`:

```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=tu_sandbox_client_id_aqui
PAYPAL_CLIENT_SECRET=tu_sandbox_secret_aqui
```

#### Para producción (Live):
```env
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=tu_live_client_id_aqui
PAYPAL_CLIENT_SECRET=tu_live_secret_aqui
```

### 3. Probar el sistema

#### Generar link de pago por consola:

```bash
# Para una factura existente (ejemplo ID 1)
php artisan invoice:payment-link 1

# Con validez personalizada (60 días)
php artisan invoice:payment-link 1 --days=60
```

#### Desde código PHP:

```php
use App\Models\Invoice;

$invoice = Invoice::find(1);

// Generar link de pago
$paymentUrl = $invoice->getPaymentUrl();
echo $paymentUrl; // https://tudominio.com/pay/ABC123XYZ...
```

#### Desde tu frontend/API:

```javascript
// Generar link de pago
const response = await fetch('/api/invoices/1/payment-link/generate', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ expires_in_days: 30 })
});

const data = await response.json();
console.log(data.data.payment_url);
```

## 🎯 Uso Principal

### Opción 1: Generar y enviar por email

```bash
# Desde código PHP
use App\Services\PaymentLinkService;

$service = app(PaymentLinkService::class);
$service->sendPaymentLinkEmail($invoice);
```

```javascript
// Desde API
fetch('/api/invoices/1/payment-link/send-email', {
  method: 'POST',
  headers: { 'Authorization': 'Bearer ' + token }
});
```

### Opción 2: Enviar por WhatsApp

```bash
# Desde código PHP
$service->sendPaymentLinkWhatsApp($invoice);
```

```javascript
// Desde API
fetch('/api/invoices/1/payment-link/send-whatsapp', {
  method: 'POST',
  headers: { 'Authorization': 'Bearer ' + token }
});
```

### Opción 3: Enviar por ambos canales

```bash
# Desde código PHP
$service->sendPaymentLinkBoth($invoice);
```

```javascript
// Desde API
fetch('/api/invoices/1/payment-link/send-both', {
  method: 'POST',
  headers: { 'Authorization': 'Bearer ' + token }
});
```

## 🔗 Rutas Públicas

Las siguientes rutas NO requieren autenticación (son públicas para que los clientes puedan pagar):

- `GET /pay/{token}` - Página de pago
- `POST /pay/{token}/create-order` - Crear orden de PayPal
- `POST /pay/{token}/capture-order` - Capturar pago

## 🔐 Rutas API (Requieren Autenticación)

```
POST   /api/invoices/{id}/payment-link/generate
POST   /api/invoices/{id}/payment-link/send-email
POST   /api/invoices/{id}/payment-link/send-whatsapp
POST   /api/invoices/{id}/payment-link/send-both
POST   /api/invoices/{id}/payment-link/regenerate
GET    /api/invoices/{id}/payment-link/check
GET    /api/invoices/{id}/payment-link/info
```

## 🧪 Probar en Sandbox

1. Configura modo sandbox en tu `.env`:
   ```env
   PAYPAL_MODE=sandbox
   ```

2. Crea cuentas de prueba en:
   https://developer.paypal.com/dashboard/accounts

3. Genera un link de pago y ábrelo en el navegador

4. Usa las credenciales de la cuenta de prueba para pagar

5. Verás que el pago se registra automáticamente en tu base de datos

## 📊 Verificar Pagos

```php
use App\Models\Invoice;

$invoice = Invoice::with('payments')->find(1);

echo "Total: {$invoice->total}\n";
echo "Pagado: {$invoice->amount_paid}\n";
echo "Saldo: {$invoice->getRemainingBalance()}\n";
echo "Estado: {$invoice->status}\n";

foreach ($invoice->payments as $payment) {
    echo "Pago: {$payment->payment_method} - {$payment->amount}\n";
    echo "Referencia PayPal: {$payment->reference}\n";
}
```

## 🎨 Personalizar

### Cambiar colores de la página de pago:
Edita: `resources/views/payment/show.blade.php`

Busca:
```css
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

### Cambiar tiempo de expiración por defecto:
Edita: `app/Models/Invoice.php`

Cambia en el método `generatePaymentToken()`:
```php
public function generatePaymentToken($expiresInDays = 60) { // Era 30, ahora 60
```

### Personalizar emails:
Edita: `app/Services/PaymentLinkService.php`

Busca el método `sendPaymentLinkEmail()` y modifica el HTML del email.

## 📚 Documentación Completa

- **Integración PayPal**: [PAYPAL_INTEGRATION.md](PAYPAL_INTEGRATION.md)
- **Ejemplos de API**: [PAYMENT_API_EXAMPLES.md](PAYMENT_API_EXAMPLES.md)

## ⚠️ Importante

1. **Sandbox vs Live**: Asegúrate de usar las credenciales correctas según el modo
2. **Seguridad**: Los tokens expiran automáticamente y son de un solo uso
3. **Estados**: Las facturas se actualizan automáticamente a 'paid' o 'partial'
4. **Logs**: Todos los pagos se registran en la tabla `payments` con referencias de PayPalç

## 🐛 Solución de Problemas

### El botón de PayPal no aparece
- Verifica que `PAYPAL_CLIENT_ID` esté en tu `.env`
- Abre la consola del navegador y busca errores
- Verifica que el saldo de la factura sea > 0

### Error al crear orden de PayPal
- Revisa `storage/logs/laravel.log`
- Verifica tus credenciales de PayPal
- Asegúrate de estar en el modo correcto (sandbox/live)

### El link de pago está expirado
- Regenera el token con:
  ```bash
  php artisan invoice:payment-link {id}
  ```

## 🎉 ¡Listo!

Tu sistema de pagos con PayPal está completamente configurado. Solo necesitas:

1. ✅ Agregar tus credenciales de PayPal al `.env`
2. ✅ Probar con una factura
3. ✅ Integrar el envío de links en tu flujo de trabajo

---

**¿Necesitas ayuda?** Consulta la documentación completa en los archivos mencionados arriba.
