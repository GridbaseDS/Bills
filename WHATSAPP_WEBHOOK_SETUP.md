# Configuración de Webhooks de WhatsApp Cloud API

## 📍 Paso 1: Datos para la Configuración de Meta

En la pantalla de "Configure Webhooks" de Meta for Developers, ingresa estos valores:

### **URL de devolución de llamada (Callback URL)**
```
https://bills.gridbase.com.do/api/whatsapp/webhook
```

### **Identificador de verificación (Verify Token)**
```
GridBase_WhatsApp_2026_SecureToken
```

> ⚠️ **Importante:** Este token debe coincidir exactamente con el valor en tu archivo `.env` (`WHATSAPP_VERIFY_TOKEN`)

---

## 📍 Paso 2: Guardar y Verificar

1. Click en **"Verificar y guardar"**
2. Meta enviará una petición GET a tu servidor para verificar el webhook
3. Si todo está correcto, verás un mensaje de éxito ✅

---

## 📍 Paso 3: Suscribirse a Eventos

Después de guardar, debes seleccionar los eventos que quieres recibir:

### Eventos Recomendados:
- ✅ **messages** - Para recibir mensajes entrantes de clientes
- ✅ **message_status** - Para saber si tus mensajes fueron entregados, leídos, etc.

Click en **"Subscribe"** o **"Suscribirse"** para cada uno.

---

## 🔍 Verificación

### Ver los Logs
Para verificar que los webhooks están llegando, revisa los logs de Laravel:

```bash
tail -f storage/logs/laravel.log
```

Busca entradas como:
- `WhatsApp Webhook verified successfully` ✅
- `WhatsApp Webhook received` 📨
- `WhatsApp: Incoming message` 💬
- `WhatsApp: Message status update` 📊

---

## 🧪 Probar el Webhook

Una vez configurado, puedes probar enviando mensajes desde:

1. **Desde el Panel de Meta:**
   - Ve a **WhatsApp > Getting Started**
   - Usa el "Send a test message" para enviar un mensaje al número de prueba
   
2. **Desde tu App:**
   - Envía una factura por WhatsApp
   - Verifica en los logs que el estado se actualiza cuando el cliente la ve

---

## 🛠️ Troubleshooting

### Error: "Webhook verification failed"
- Verifica que `WHATSAPP_VERIFY_TOKEN` en `.env` coincida con el token en Meta
- Asegúrate de que la URL sea accesible públicamente (no localhost)
- Verifica que no haya middleware bloqueando las rutas de webhook

### No recibo webhooks
- Verifica que los eventos estén suscritos en Meta
- Revisa los logs de Laravel en `storage/logs/laravel.log`
- Verifica que la app de Meta esté en modo producción (no solo desarrollo)

### Error 403 en verificación
- El token no coincide
- Limpia la configuración de cache: `php artisan config:clear`

---

## 📚 Recursos

- [WhatsApp Cloud API Docs](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Webhooks Guide](https://developers.facebook.com/docs/whatsapp/cloud-api/guides/set-up-webhooks)
- [Message Templates](https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates)

---

## 🔐 Seguridad

El webhook verifica que las peticiones vengan de WhatsApp mediante:
- Token de verificación en el handshake inicial
- Validación del objeto `whatsapp_business_account`
- Logs completos de todas las peticiones recibidas

Para mayor seguridad, considera agregar:
- Verificación de IP de origen
- Validación de firma de webhook (si Meta la proporciona)
- Rate limiting en las rutas de webhook
