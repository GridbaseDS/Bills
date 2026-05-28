# Gridbase Bills — Documentación de API v1

¡Bienvenido a la documentación oficial de la API de **Gridbase Bills**! Esta API te permite integrar facturación y cotizaciones automáticas desde cualquier sistema externo: páginas web, tiendas online (WooCommerce, Shopify), ERPs, CRMs o aplicaciones móviles.

---

## 1. Introducción

La API de Gridbase Bills está diseñada bajo los principios REST. Retorna respuestas en formato JSON, utiliza códigos de estado HTTP estándar y requiere autenticación mediante Bearer Tokens (API Keys).

---

## 2. Autenticación

Todas las peticiones a la API deben incluir tu API Key en el header `Authorization` usando el esquema Bearer Token:

```http
Authorization: Bearer gb_tu_api_key_aqui
```

> ⚠️ **IMPORTANTE:** Nunca expongas tu API Key en código del lado del cliente (como JavaScript en el navegador). Realiza siempre las peticiones desde tu servidor backend.

### Obtener tu API Key
1. Inicia sesión como administrador en Gridbase Bills.
2. Dirígete a **Configuración** → 🔑 **API Keys**.
3. Haz clic en **"Nueva API Key"**.
4. Asigna un nombre descriptivo (ej: "E-Commerce") y selecciona los permisos requeridos.
5. Copia el token generado (solo se mostrará una vez).

---

## 3. URL Base

Todas las rutas de la API están bajo el prefijo:
`https://bills.gridbase.com.do/api/v1`

### Headers Requeridos

| Header | Valor | Requerido |
|--------|-------|-----------|
| `Authorization` | `Bearer gb_tu_api_key_aqui` | **Sí** |
| `Content-Type` | `application/json` | **Sí** (para POST/PUT) |
| `Accept` | `application/json` | Recomendado |

---

## 4. Rate Limiting

Cada API Key tiene un límite de peticiones por minuto (configurable por el administrador; 60/min por defecto). Las respuestas de la API contienen los siguientes cabeceras:

- `X-RateLimit-Limit`: Peticiones permitidas por minuto.
- `X-RateLimit-Remaining`: Peticiones restantes en la ventana actual.
- `Retry-After`: Segundos a esperar hasta que se renueve el límite (solo si recibes un estado `429`).

---

## 5. Manejo de Errores

La API siempre retorna un objeto JSON con el campo `success`. Si ocurre un error, se incluirán detalles en español.

### Estructura de Error (HTTP 422 - Validación)
```json
{
    "success": false,
    "error": "Datos de validación inválidos.",
    "message": "Los datos proporcionados no pasaron las reglas de validación.",
    "errors": {
        "client.email": ["El correo electrónico no es válido."]
    }
}
```

### Estructura de Éxito
```json
{
    "success": true,
    "message": "Operación completada exitosamente.",
    "data": { ... }
}
```

---

## 6. Endpoints de Facturas (Invoices)

### Crear Factura
*   **Método:** `POST`
*   **Ruta:** `/invoices`
*   **Permiso Requerido:** `invoices.create`

#### Parámetros del Body (JSON)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `client_id` | `integer` | Opcional* | ID de un cliente existente. |
| `client` | `object` | Opcional* | Objeto de cliente para Upsert automático (ver sección 9). |
| `client.tax_id` | `string` | Opcional | RNC o Cédula. |
| `client.company_name` | `string` | Opcional | Nombre de la empresa. |
| `client.contact_name` | `string` | Opcional | Nombre del contacto. |
| `client.email` | `string` | Opcional | Correo electrónico. |
| `items` | `array` | **Sí** | Array de ítems de la factura. |
| `items[].description` | `string` | **Sí** | Descripción del servicio/producto. |
| `items[].quantity` | `number` | **Sí** | Cantidad (mín: 0.01). |
| `items[].unit_price` | `number` | **Sí** | Precio unitario. |
| `currency` | `string` | Opcional | `DOP`, `USD`, `EUR` (default: `DOP`). |
| `tax_rate` | `number` | Opcional | Tasa de impuesto en % (ej: 18). |
| `discount_type` | `string` | Opcional | `percentage` o `fixed`. |
| `discount_value` | `number` | Opcional | Valor del descuento. |
| `notes` | `string` | Opcional | Notas públicas en la factura. |

*\*Nota: Debes proveer `client_id` o `client` (objeto completo), pero no ambos.*

---

### Listar Facturas
*   **Método:** `GET`
*   **Ruta:** `/invoices`
*   **Permiso Requerido:** `invoices.read`

#### Filtros (Query Parameters)
- `page`: Número de página (default: 1).
- `per_page`: Resultados por página (máx: 100).
- `status`: Filtrar por estado (`draft`, `sent`, `paid`, `cancelled`).
- `client_id`: Filtrar por ID de cliente.

---

### Ver Detalles de Factura
*   **Método:** `GET`
*   **Ruta:** `/invoices/{id}`
*   **Permiso Requerido:** `invoices.read`

---

### Descargar PDF de Factura
*   **Método:** `GET`
*   **Ruta:** `/invoices/{id}/pdf`
*   **Permiso Requerido:** `invoices.read`
*   **Respuesta:** Archivo binario con `Content-Type: application/pdf`.

---

## 7. Endpoints de Cotizaciones (Quotes)

### Crear Cotización
*   **Método:** `POST`
*   **Ruta:** `/quotes`
*   **Permiso Requerido:** `quotes.create`
*   **Parámetros:** Idénticos a *Crear Factura*, con la adición del campo `expiry_date` (fecha límite de validez).

---

### Listar Cotizaciones
*   **Método:** `GET`
*   **Ruta:** `/quotes`
*   **Permiso Requerido:** `quotes.read`

---

### Convertir Cotización a Factura
*   **Método:** `POST`
*   **Ruta:** `/quotes/{id}/convert`
*   **Permiso Requerido:** `quotes.convert`
*   **Respuesta:** Retorna el ID de la factura generada (`invoice_id`) y su número.

---

## 8. Endpoints de Clientes (Clients)

### Crear o Buscar Cliente
*   **Método:** `POST`
*   **Ruta:** `/clients`
*   **Permiso Requerido:** `clients.create`

### Listar Clientes
*   **Método:** `GET`
*   **Ruta:** `/clients`
*   **Permiso Requerido:** `clients.read`

### Ver Cliente
*   **Método:** `GET`
*   **Ruta:** `/clients/{id}`
*   **Permiso Requerido:** `clients.read`

---

## 9. Upsert Inteligente de Clientes

Cuando creas una factura o cotización pasando un objeto `client` en lugar de un `client_id`, la API ejecuta un flujo de **upsert**:

1. Busca un cliente registrado por su `tax_id` (RNC o Cédula).
2. Si no lo encuentra, realiza la búsqueda por su `email`.
3. Si existe coincidencia, **asocia la factura a ese cliente y actualiza su información** (evitando duplicados).
4. Si no existe, **crea automáticamente un nuevo cliente** en la base de datos.

---

## 10. Códigos de Respuesta HTTP

| Código | Estado | Significado |
|--------|--------|-------------|
| `200` | OK | La petición fue procesada con éxito. |
| `201` | Created | El recurso se creó exitosamente. |
| `401` | Unauthorized | API Key inválida, revocada o ausente. |
| `403` | Forbidden | La API Key no cuenta con el permiso granular requerido. |
| `404` | Not Found | El recurso solicitado no existe. |
| `409` | Conflict | La acción no puede completarse (ej: cotización ya convertida). |
| `422` | Unprocessable | Error de validación en los parámetros enviados. |
| `429` | Too Many Requests | Se superó el límite de peticiones por minuto. |

---

## 11. Ejemplo Completo (Node.js / Fetch API)

```javascript
const API_KEY = 'gb_tu_api_key_aqui';
const BASE_URL = 'https://bills.gridbase.com.do/api/v1';

async function crearFacturaAutomatica() {
    const response = await fetch(`${BASE_URL}/invoices`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${API_KEY}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            client: {
                tax_id: "131456789",
                company_name: "Acme Dominicana SRL",
                email: "contacto@acme.com.do",
                phone: "809-555-0199"
            },
            items: [
                {
                    description: "Servicios Mensuales de Soporte Cloud",
                    quantity: 1,
                    unit_price: 18500.00
                }
            ],
            currency: "DOP",
            tax_rate: 18,
            notes: "Generada automáticamente desde sistema centralizado."
        })
    });

    const data = await response.json();
    if (data.success) {
        console.log(`Factura creada: ${data.data.invoice_number} - Total: RD$${data.data.total}`);
    } else {
        console.error("Error API:", data.error);
    }
}

crearFacturaAutomatica();
```

---

*Desarrollado con ❤️ por Gridbase Digital Solutions. Soporte Técnico: soporte@gridbase.com.do*
