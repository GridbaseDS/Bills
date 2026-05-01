# Sistema de Pagos con PayPal

## Descripción

Este sistema permite a los clientes pagar sus facturas en línea mediante links de pago únicos que se integran con PayPal. Cada factura puede generar su propio link de pago seguro con un token único que expira después de un período configurable.

## Características

- ✅ Links de pago únicos por factura
- ✅ Tokens de seguridad con expiración
- ✅ Integración completa con PayPal
- ✅ Soporte para pagos parciales
- ✅ Actualización automática del estado de facturas
- ✅ Registro de pagos con referencias de PayPal
- ✅ Interfaz responsive y moderna
- ✅ Vista de factura expirada
- ✅ Soporte para múltiples monedas

## Instalación

### 1. Ejecutar la migración

```bash
php artisan migrate
```

Esto agregará los campos `payment_token` y `payment_token_expires_at` a la tabla de facturas.

### 2. Configurar PayPal

#### Obtener credenciales de PayPal:

1. Ve a [PayPal Developer](https://developer.paypal.com/)
2. Inicia sesión con tu cuenta de PayPal
3. Ve a "Dashboard" → "My Apps & Credentials"
4. En la sección "REST API apps", crea una nueva app o usa una existente
5. Copia el "Client ID" y el "Secret"

#### Configurar variables de entorno:

Agrega las siguientes variables a tu archivo `.env`:

```env
PAYPAL_MODE=sandbox          # Usa 'sandbox' para pruebas, 'live' para producción
PAYPAL_CLIENT_ID=tu_client_id_aqui
PAYPAL_CLIENT_SECRET=tu_client_secret_aqui
```

**Importante:** 
- Para pruebas, usa las credenciales de **Sandbox**
- Para producción, usa las credenciales de **Live** y cambia `PAYPAL_MODE=live`

## Uso

### Generar un link de pago para una factura

```php
use App\Models\Invoice;

$invoice = Invoice::find(1);

// Generar token de pago (válido por 30 días por defecto)
$invoice->generatePaymentToken();

// O especificar días de validez
$invoice->generatePaymentToken(60); // Válido por 60 días

// Obtener la URL completa de pago
$paymentUrl = $invoice->getPaymentUrl();

// Ejemplo: https://tudominio.com/pay/ABC123XYZ...
```

### Enviar el link de pago al cliente

Puedes incluir el link de pago en:
- Emails de facturas
- Mensajes de WhatsApp
- Recordatorios de pago

```php
// En tu servicio de email
$paymentUrl = $invoice->getPaymentUrl();
$emailBody = "Para pagar tu factura, haz clic aquí: $paymentUrl";
```

### Verificar si un token es válido

```php
if ($invoice->isPaymentTokenValid()) {
    // El token es válido y la factura puede ser pagada
} else {
    // El token expiró o la factura ya está pagada/cancelada
}
```

### Obtener el balance restante

```php
$remainingBalance = $invoice->getRemainingBalance();
```

## Rutas disponibles

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/pay/{token}` | Muestra la página de pago |
| POST | `/pay/{token}/create-order` | Crea una orden de PayPal |
| POST | `/pay/{token}/capture-order` | Captura el pago de PayPal |

Todas estas rutas son **públicas** y no requieren autenticación.

## Flujo de pago

1. **Cliente accede al link**: El cliente hace clic en el link de pago único
2. **Validación del token**: El sistema verifica que el token sea válido
3. **Muestra la factura**: Se despliega la información completa de la factura
4. **Botón de PayPal**: El cliente ve el botón de PayPal
5. **Proceso de pago**: Al hacer clic, se redirige a PayPal para completar el pago
6. **Captura del pago**: Una vez aprobado, el sistema captura el pago
7. **Actualización automática**: 
   - Se crea un registro de pago
   - Se actualiza el `amount_paid` de la factura
   - Se cambia el estado a `paid` si está completamente pagada, o `partial` si es un pago parcial
   - Se registra la fecha de pago (`paid_at`)

## Estados de factura

El sistema maneja automáticamente los siguientes estados:

- **draft**: Borrador
- **sent**: Enviada
- **viewed**: Vista por el cliente
- **partial**: Pago parcial
- **paid**: Pagada completamente
- **overdue**: Vencida
- **cancelled**: Cancelada

## Seguridad

- Los tokens de pago son únicos y aleatorios (64 caracteres)
- Los tokens expiran después del período configurado
- No se puede pagar una factura ya pagada o cancelada
- Todas las transacciones se registran con referencias de PayPal
- Los pagos se procesan de forma segura a través de PayPal

## Pruebas con PayPal Sandbox

1. Configura `PAYPAL_MODE=sandbox` en tu `.env`
2. Usa las credenciales de Sandbox de tu app de PayPal
3. Para probar pagos, necesitarás cuentas de prueba:
   - Ve a [PayPal Sandbox Accounts](https://developer.paypal.com/dashboard/accounts)
   - Crea una cuenta personal de prueba (para actuar como comprador)
   - Usa esas credenciales para hacer login en el checkout de PayPal durante las pruebas

## Monedas soportadas

El sistema soporta todas las monedas que PayPal acepta, configurándose automáticamente según el campo `currency` de la factura:

- USD (Dólar estadounidense)
- EUR (Euro)
- GBP (Libra esterlina)
- CAD (Dólar canadiense)
- DOP (Peso dominicano)
- Y muchas más...

## Troubleshooting

### El botón de PayPal no aparece

- Verifica que `PAYPAL_CLIENT_ID` esté configurado correctamente
- Revisa la consola del navegador para errores de JavaScript
- Asegúrate de que el balance restante sea mayor a 0

### Error "Token de pago inválido"

- El token puede haber expirado
- La factura puede estar marcada como pagada o cancelada
- Genera un nuevo token con `$invoice->generatePaymentToken()`

### Error al crear la orden de PayPal

- Verifica tus credenciales de PayPal
- Revisa los logs de Laravel en `storage/logs/laravel.log`
- Asegúrate de estar usando el modo correcto (sandbox/live)

### El pago se aprueba pero no se registra

- Revisa los logs del servidor
- Verifica que la conexión a la base de datos esté funcionando
- Comprueba que no haya errores en la captura del pago

## Personalización

### Cambiar el tiempo de expiración por defecto

En el modelo `Invoice`, puedes cambiar el valor por defecto:

```php
public function generatePaymentToken($expiresInDays = 60) { // Cambiar a 60 días
    // ...
}
```

### Personalizar la página de pago

Edita la vista `resources/views/payment/show.blade.php` para personalizar:
- Colores y estilos
- Logo de la empresa
- Información adicional
- Términos y condiciones

### Personalizar la página de expiración

Edita la vista `resources/views/payment/expired.blade.php`.

## API de PayPal

El controlador `PaymentController` maneja la comunicación con PayPal mediante:
- Autenticación OAuth 2.0
- REST API de PayPal v2
- Endpoints de checkout

## Soporte

Para más información sobre la API de PayPal:
- [Documentación oficial de PayPal](https://developer.paypal.com/docs/)
- [Guía de integración de Checkout](https://developer.paypal.com/docs/checkout/)

---

**Nota:** Asegúrate de probar completamente el sistema en modo sandbox antes de implementarlo en producción.
