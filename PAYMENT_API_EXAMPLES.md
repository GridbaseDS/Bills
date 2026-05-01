# API de Links de Pago - Ejemplos de Uso

## Endpoints Disponibles

Todos los endpoints requieren autenticación mediante token Sanctum.

Base URL: `{APP_URL}/api`

### 1. Generar Link de Pago

**POST** `/invoices/{id}/payment-link/generate`

Genera un link de pago único para una factura.

**Parámetros opcionales:**
```json
{
  "expires_in_days": 30
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": {
    "payment_url": "https://tudominio.com/pay/ABC123...",
    "payment_token": "ABC123XYZ...",
    "expires_at": "2026-05-31T12:00:00.000Z"
  }
}
```

**Ejemplo con cURL:**
```bash
curl -X POST https://tudominio.com/api/invoices/1/payment-link/generate \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"expires_in_days": 60}'
```

**Ejemplo con JavaScript:**
```javascript
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

---

### 2. Enviar Link por Email

**POST** `/invoices/{id}/payment-link/send-email`

Envía el link de pago al email del cliente.

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Link de pago enviado por email exitosamente"
}
```

**Ejemplo con cURL:**
```bash
curl -X POST https://tudominio.com/api/invoices/1/payment-link/send-email \
  -H "Authorization: Bearer {token}"
```

**Ejemplo con JavaScript:**
```javascript
const response = await fetch('/api/invoices/1/payment-link/send-email', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
  }
});
```

---

### 3. Enviar Link por WhatsApp

**POST** `/invoices/{id}/payment-link/send-whatsapp`

Envía el link de pago al WhatsApp del cliente.

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Link de pago enviado por WhatsApp exitosamente"
}
```

**Ejemplo con cURL:**
```bash
curl -X POST https://tudominio.com/api/invoices/1/payment-link/send-whatsapp \
  -H "Authorization: Bearer {token}"
```

---

### 4. Enviar Link por Email y WhatsApp

**POST** `/invoices/{id}/payment-link/send-both`

Envía el link de pago por ambos canales.

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": {
    "email": true,
    "whatsapp": true
  },
  "message": "Links de pago enviados"
}
```

**Ejemplo con JavaScript:**
```javascript
const response = await fetch('/api/invoices/1/payment-link/send-both', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
  }
});
```

---

### 5. Verificar Validez del Link

**GET** `/invoices/{id}/payment-link/check`

Verifica si el link de pago actual es válido.

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": {
    "is_valid": true,
    "payment_token": "ABC123XYZ...",
    "expires_at": "2026-05-31T12:00:00.000Z",
    "payment_url": "https://tudominio.com/pay/ABC123..."
  }
}
```

**Ejemplo con cURL:**
```bash
curl -X GET https://tudominio.com/api/invoices/1/payment-link/check \
  -H "Authorization: Bearer {token}"
```

**Ejemplo con JavaScript:**
```javascript
const response = await fetch('/api/invoices/1/payment-link/check', {
  headers: {
    'Authorization': 'Bearer ' + token
  }
});

const data = await response.json();
if (data.data.is_valid) {
  console.log('Link válido:', data.data.payment_url);
} else {
  console.log('El link ha expirado o la factura ya está pagada');
}
```

---

### 6. Regenerar Link de Pago

**POST** `/invoices/{id}/payment-link/regenerate`

Regenera un nuevo link de pago (invalida el anterior).

**Parámetros opcionales:**
```json
{
  "expires_in_days": 30
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": {
    "payment_url": "https://tudominio.com/pay/XYZ789...",
    "payment_token": "XYZ789ABC...",
    "expires_at": "2026-06-01T12:00:00.000Z"
  },
  "message": "Link de pago regenerado exitosamente"
}
```

**Ejemplo con cURL:**
```bash
curl -X POST https://tudominio.com/api/invoices/1/payment-link/regenerate \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"expires_in_days": 45}'
```

---

### 7. Obtener Información de Pago

**GET** `/invoices/{id}/payment-link/info`

Obtiene información completa de la factura y su estado de pago.

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": {
    "invoice_number": "INV-2026-001",
    "total": 1500.00,
    "amount_paid": 500.00,
    "remaining_balance": 1000.00,
    "currency": "USD",
    "status": "partial",
    "due_date": "2026-06-15T00:00:00.000Z",
    "payment_link": {
      "is_valid": true,
      "url": "https://tudominio.com/pay/ABC123...",
      "expires_at": "2026-05-31T12:00:00.000Z"
    },
    "client": {
      "name": "Empresa ABC",
      "email": "cliente@example.com",
      "phone": "+1 809-555-0100",
      "whatsapp": "+1 809-555-0100"
    },
    "payments": [
      {
        "id": 1,
        "amount": 500.00,
        "payment_method": "paypal",
        "payment_date": "2026-05-01T10:30:00.000Z",
        "reference": "PAYPAL-123456789"
      }
    ]
  }
}
```

**Ejemplo con cURL:**
```bash
curl -X GET https://tudominio.com/api/invoices/1/payment-link/info \
  -H "Authorization: Bearer {token}"
```

---

## Ejemplos de Integración Frontend

### Componente React - Botón de Enviar Link

```jsx
import React, { useState } from 'react';

function SendPaymentLinkButton({ invoiceId, token }) {
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  const sendPaymentLink = async (method) => {
    setLoading(true);
    setMessage('');

    try {
      const response = await fetch(
        `/api/invoices/${invoiceId}/payment-link/send-${method}`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        }
      );

      const data = await response.json();
      
      if (data.success) {
        setMessage(`Link enviado por ${method} exitosamente`);
      } else {
        setMessage('Error al enviar el link');
      }
    } catch (error) {
      setMessage('Error de conexión');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <button 
        onClick={() => sendPaymentLink('email')}
        disabled={loading}
      >
        Enviar por Email
      </button>
      
      <button 
        onClick={() => sendPaymentLink('whatsapp')}
        disabled={loading}
      >
        Enviar por WhatsApp
      </button>
      
      <button 
        onClick={() => sendPaymentLink('both')}
        disabled={loading}
      >
        Enviar por Ambos
      </button>
      
      {message && <p>{message}</p>}
    </div>
  );
}
```

### Componente Vue - Mostrar Link de Pago

```vue
<template>
  <div class="payment-link-card">
    <div v-if="loading">Cargando...</div>
    
    <div v-else-if="paymentLink.is_valid">
      <h3>Link de Pago</h3>
      <input 
        type="text" 
        :value="paymentLink.url" 
        readonly 
        @click="copyToClipboard"
      />
      <p>Expira: {{ formatDate(paymentLink.expires_at) }}</p>
      
      <button @click="copyToClipboard">Copiar Link</button>
      <button @click="sendEmail">Enviar por Email</button>
      <button @click="regenerate">Regenerar Link</button>
    </div>
    
    <div v-else>
      <p>El link ha expirado</p>
      <button @click="regenerate">Generar Nuevo Link</button>
    </div>
  </div>
</template>

<script>
export default {
  props: ['invoiceId', 'token'],
  
  data() {
    return {
      loading: true,
      paymentLink: {
        is_valid: false,
        url: null,
        expires_at: null
      }
    };
  },
  
  async mounted() {
    await this.checkPaymentLink();
  },
  
  methods: {
    async checkPaymentLink() {
      this.loading = true;
      
      try {
        const response = await fetch(
          `/api/invoices/${this.invoiceId}/payment-link/check`,
          {
            headers: {
              'Authorization': `Bearer ${this.token}`
            }
          }
        );
        
        const data = await response.json();
        this.paymentLink = data.data;
      } catch (error) {
        console.error('Error:', error);
      } finally {
        this.loading = false;
      }
    },
    
    async regenerate() {
      try {
        const response = await fetch(
          `/api/invoices/${this.invoiceId}/payment-link/regenerate`,
          {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${this.token}`,
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ expires_in_days: 30 })
          }
        );
        
        const data = await response.json();
        this.paymentLink = data.data;
        alert('Link regenerado exitosamente');
      } catch (error) {
        alert('Error al regenerar el link');
      }
    },
    
    async sendEmail() {
      try {
        await fetch(
          `/api/invoices/${this.invoiceId}/payment-link/send-email`,
          {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${this.token}`
            }
          }
        );
        alert('Email enviado exitosamente');
      } catch (error) {
        alert('Error al enviar email');
      }
    },
    
    copyToClipboard() {
      navigator.clipboard.writeText(this.paymentLink.url);
      alert('Link copiado al portapapeles');
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString();
    }
  }
};
</script>
```

---

## Comando Artisan

Generar link de pago desde la línea de comandos:

```bash
# Generar link de pago para factura ID 1 (válido por 30 días)
php artisan invoice:payment-link 1

# Generar link válido por 60 días
php artisan invoice:payment-link 1 --days=60
```

---

## Uso desde PHP

```php
use App\Models\Invoice;
use App\Services\PaymentLinkService;

// Obtener servicio
$paymentService = new PaymentLinkService();

// O usar inyección de dependencias
$paymentService = app(PaymentLinkService::class);

// Generar link
$invoice = Invoice::find(1);
$paymentUrl = $paymentService->generatePaymentLink($invoice);

// Enviar por email
$paymentService->sendPaymentLinkEmail($invoice);

// Enviar por WhatsApp
$paymentService->sendPaymentLinkWhatsApp($invoice);

// Enviar por ambos
$results = $paymentService->sendPaymentLinkBoth($invoice);

// Verificar validez
if ($paymentService->isPaymentLinkValid($invoice)) {
    echo "Link válido: " . $invoice->getPaymentUrl();
}

// Regenerar
$newUrl = $paymentService->regeneratePaymentLink($invoice, 45);
```

---

## Webhooks de PayPal

Para recibir notificaciones de pagos de PayPal, puedes configurar webhooks:

1. Ve a tu app en PayPal Developer Dashboard
2. Configura un webhook con URL: `https://tudominio.com/api/webhooks/paypal`
3. Selecciona eventos: `PAYMENT.CAPTURE.COMPLETED`

(Nota: La implementación de webhooks requiere código adicional)

---

## Notas de Seguridad

- Los tokens de pago son únicos y de un solo uso por factura
- Los tokens expiran automáticamente
- Los links no funcionan para facturas ya pagadas o canceladas
- Todos los endpoints de administración requieren autenticación
- La ruta de pago pública (`/pay/{token}`) no requiere autenticación

---

Para más información, consulta [PAYPAL_INTEGRATION.md](./PAYPAL_INTEGRATION.md)
