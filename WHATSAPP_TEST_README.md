# 🧪 WhatsApp API Test Center

Herramienta de pruebas para validar la integración de WhatsApp Cloud API sin necesidad de crear facturas o cotizaciones reales.

## 🌐 Acceso

**URL:** `https://bills.gridbase.com.do/whatsapp-test`

---

## ✨ Funcionalidades

### 1. **Mensaje Simple**
Envía mensajes de texto planos a cualquier número de WhatsApp.

**Casos de uso:**
- Verificar conectividad con la API
- Probar formato de números
- Enviar mensajes personalizados de prueba

### 2. **Notificación de Factura**
Simula el envío de una notificación de factura con todos los datos necesarios.

**Incluye:**
- Nombre del cliente
- Número de factura
- Monto total
- Moneda
- Link de pago (opcional)

### 3. **Notificación de Cotización**
Simula el envío de una notificación de cotización.

**Incluye:**
- Nombre del cliente
- Número de cotización
- Monto total
- Moneda

---

## 📱 Formato de Números

Los números de WhatsApp deben incluir:
- **Código de país** (sin +)
- **Número completo** (sin espacios, guiones ni paréntesis)

### Ejemplos:
```
✅ Correcto:
- 18091234567 (República Dominicana)
- 18292345678 (República Dominicana)
- 13051234567 (Estados Unidos)
- 525551234567 (México)

❌ Incorrecto:
- +1 809 123 4567
- (809) 123-4567
- 809-123-4567
```

---

## 🔍 Verificación de Estado

La página muestra automáticamente:
- ✅ **Estado de WhatsApp:** Habilitado o Deshabilitado
- 📞 **Phone Number ID:** Configurado o no
- 🏢 **Business Account ID:** Configurado o no
- 🔑 **Access Token:** Configurado o no

---

## 🚀 Uso Rápido

### 1. **Prueba Básica**
1. Ve a `/whatsapp-test`
2. Selecciona tab **"Mensaje Simple"**
3. Ingresa tu número de WhatsApp
4. Escribe un mensaje de prueba
5. Click en **"Enviar Mensaje"**

### 2. **Simular Factura**
1. Tab **"Notificación de Factura"**
2. Completa los datos (ya vienen pre-llenados)
3. Opcionalmente agrega un link de pago
4. Click en **"Enviar Notificación"**

### 3. **Simular Cotización**
1. Tab **"Notificación de Cotización"**
2. Completa los datos
3. Click en **"Enviar Notificación"**

---

## 📊 Resultados

Los mensajes de respuesta incluyen:
- ✅ **Éxito:** Mensaje enviado correctamente
- ❌ **Error:** Descripción del problema
- 📝 **Data:** Información de respuesta de la API (ID del mensaje, etc.)

---

## 🔐 Seguridad

⚠️ **IMPORTANTE:** Esta página está actualmente **pública** para facilitar las pruebas.

### Para producción, se recomienda:
1. Agregar autenticación (middleware `auth:sanctum`)
2. Limitar acceso por IP
3. Agregar rate limiting
4. Registrar todos los envíos en logs

### Proteger la ruta:
```php
// En routes/web.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/whatsapp-test', [WhatsAppTestController::class, 'index']);
    // ... otras rutas
});
```

---

## 🐛 Troubleshooting

### Error: "WhatsApp Deshabilitado"
**Causa:** Falta configurar credenciales en `.env`

**Solución:**
```bash
WHATSAPP_ACCESS_TOKEN=tu_token
WHATSAPP_PHONE_ID=tu_phone_id
WHATSAPP_BUSINESS_ACCOUNT_ID=tu_account_id
```

### Error: "Invalid phone number"
**Causa:** Formato de número incorrecto

**Solución:** Usar formato internacional sin símbolos (ej: `18091234567`)

### Error: "API Error"
**Causa:** Token expirado o inválido

**Solución:** Genera un nuevo token permanente en Meta for Developers

---

## 📝 Logs

Todos los envíos se registran en `storage/logs/laravel.log`

**Ver logs en tiempo real:**
```bash
tail -f storage/logs/laravel.log
```

**Buscar envíos de WhatsApp:**
```bash
grep "WhatsApp" storage/logs/laravel.log
```

---

## 🎯 Mejoras Futuras

- [ ] Historial de mensajes enviados
- [ ] Estadísticas de envío
- [ ] Validación de números en tiempo real
- [ ] Preview del mensaje antes de enviar
- [ ] Envío masivo para pruebas
- [ ] Integración con números de prueba de Meta

---

## 📚 Links Útiles

- [WhatsApp Cloud API Docs](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [API Reference](https://developers.facebook.com/docs/whatsapp/cloud-api/reference)
- [Error Codes](https://developers.facebook.com/docs/whatsapp/cloud-api/support/error-codes)

---

**Desarrollado por:** GridBase Digital Solutions  
**Versión:** 1.0.0  
**Última actualización:** Mayo 2026
