# Manual de Usuario — Gridbase Bills
### Sistema de Facturación Electrónica para la República Dominicana

> **Versión:** 1.0 | **Última actualización:** Junio 2026 
> **Soporte:** Gridbase Digital Solutions - gridbase.com.do

---

## Tabla de Contenidos

1. [Introducción y Bienvenida](#1-introducción-y-bienvenida)
2. [Primeros Pasos — Acceso al Sistema](#2-primeros-pasos--acceso-al-sistema)
3. [Autenticación y Seguridad](#3-autenticación-y-seguridad)
4. [Panel Principal (Dashboard)](#4-panel-principal-dashboard)
5. [Gestión de Clientes](#5-gestión-de-clientes)
6. [Catálogo de Ítems / Productos y Servicios](#6-catálogo-de-ítems--productos-y-servicios)
7. [Facturación](#7-facturación)
8. [Cotizaciones](#8-cotizaciones)
9. [Facturas Recurrentes / Suscripciones](#9-facturas-recurrentes--suscripciones)
10. [Gastos / Control de Egresos](#10-gastos--control-de-egresos)
11. [Facturación Electrónica DGII (e-CF)](#11-facturación-electrónica-dgii-e-cf)
12. [Facturas Recibidas (Aprobaciones Comerciales)](#12-facturas-recibidas-aprobaciones-comerciales)
13. [Reportes DGII — 606 y 607](#13-reportes-dgii--606-y-607)
14. [Links de Pago y Cobro en Línea](#14-links-de-pago-y-cobro-en-línea)
15. [Configuración del Sistema](#15-configuración-del-sistema)
16. [Gestión de Usuarios](#16-gestión-de-usuarios)
17. [API Pública — Llaves de Acceso](#17-api-pública--llaves-de-acceso)
18. [Monedas y Tipos de Cambio](#18-monedas-y-tipos-de-cambio)
19. [Solución de Problemas Frecuentes](#19-solución-de-problemas-frecuentes)
20. [Glosario de Términos](#20-glosario-de-términos)
21. [Referencia Rápida de Estados](#21-referencia-rápida-de-estados)
22. [Apéndice: Tipos de Comprobantes Fiscales Electrónicos (e-CF)](#22-apéndice-tipos-de-comprobantes-fiscales-electrónicos-e-cf)

---

## 1. Introducción y Bienvenida

### ¿Qué es Gridbase Bills?

**Gridbase Bills** es un sistema integral de facturación electrónica diseñado específicamente para empresas y negocios en la República Dominicana. Con él puedes:

- Crear y enviar **facturas** y **cotizaciones** a tus clientes
- Emitir **Comprobantes Fiscales Electrónicos (e-CF)** válidos ante la DGII
- Cobrar en línea mediante **links de pago seguros** con PayPal
- Gestionar **facturas recurrentes** para clientes con pagos periódicos
- Controlar tus **gastos** y preparar los reportes **606 y 607** para la DGII
- Enviar documentos automáticamente por **correo electrónico** y **WhatsApp**
- Consultar **estadísticas en tiempo real** de tu negocio

### ¿A quién está dirigido este manual?

Este manual está escrito para **usuarios de negocio** — no se requiere conocimiento técnico. Si sabes usar un navegador web y un correo electrónico, puedes usar Gridbase Bills sin problemas.

### Requisitos del Sistema

| Requisito | Detalle |
|---|---|
| Navegador | Google Chrome, Firefox, Microsoft Edge, Safari (versiones actuales) |
| Conexión | Internet estable |
| Dispositivo | Computadora, tablet o teléfono inteligente |

> **Nota:** Gridbase Bills funciona mejor en **Google Chrome** en computadora de escritorio. Aunque es compatible con teléfonos, algunas funciones avanzadas son más cómodas de usar desde una pantalla grande.

---

## 2. Primeros Pasos — Acceso al Sistema

### Cómo Acceder

1. Abre tu navegador web (Chrome, Firefox, Edge o Safari)
2. Escribe la dirección del sistema en la barra de direcciones:
 ```
 https://bills.tudominio.com
 ```
 *(Tu proveedor de Gridbase te habrá dado la dirección exacta)*
3. Presiona **Enter**
4. Verás la pantalla de inicio de sesión

### Pantalla de Inicio de Sesión

La pantalla de login te pedirá:
- **Correo electrónico** — el que usas para tu cuenta
- **Contraseña** — la contraseña asignada por el administrador

> **Importante:** La primera vez que inicias sesión, el sistema te pedirá configurar tu **autenticación en dos pasos (2FA)**. Esta es una medida de seguridad obligatoria. Sigue las instrucciones del [Capítulo 3](#3-autenticación-y-seguridad) para completarla.

### Si Olvidaste tu Contraseña

Contacta al administrador de tu empresa para que restablezca tu contraseña. El administrador accede a la sección **Usuarios** del sistema.

---

## 3. Autenticación y Seguridad

### 3.1 Autenticación en Dos Pasos (2FA)

Gridbase Bills utiliza **autenticación en dos pasos** para proteger tu cuenta. Esto significa que, además de tu contraseña, necesitas ingresar un **código de 6 dígitos** que cambia cada 30 segundos desde una aplicación en tu teléfono.

#### ¿Por qué es importante el 2FA?

El 2FA protege tu cuenta incluso si alguien obtiene tu contraseña. Sin el código del teléfono, nadie más puede acceder.

#### Primera Vez: Configuración del 2FA

La primera vez que inicias sesión correctamente, el sistema detecta que no tienes 2FA configurado y te guía por la configuración:

**Paso 1 — Instala una aplicación autenticadora en tu teléfono:**

| Aplicación | Sistema | Dónde Descargar |
|---|---|---|
| Google Authenticator | Android / iPhone | Google Play / App Store |
| Microsoft Authenticator | Android / iPhone | Google Play / App Store |
| Authy | Android / iPhone / PC | authy.com |

**Paso 2 — Escanea el código QR:**

1. Después de ingresar tu correo y contraseña, el sistema te mostrará un **código QR**
2. Abre tu aplicación autenticadora en el teléfono
3. Toca el botón **"+"** o **"Agregar cuenta"**
4. Selecciona **"Escanear código QR"**
5. Apunta la cámara al código QR en la pantalla del computador

**Paso 3 — Ingresa el código de verificación:**

1. Tu aplicación generará un código de 6 dígitos (ejemplo: `482 391`)
2. Escribe ese código en el campo que te pide el sistema
3. Presiona **"Verificar"**

> **Advertencia:** **Guarda el secreto de respaldo.** Si pierdes tu teléfono, necesitarás el código secreto para recuperar acceso. Guárdalo en un lugar seguro (papel, gestor de contraseñas, etc.).

**Paso 4 — ¡Configuración completada!**

El sistema guardará tu configuración y te llevará directamente al panel principal.

---

#### Inicio de Sesión Normal (Con 2FA ya Configurado)

Una vez que el 2FA está configurado, el proceso de inicio de sesión es:

1. Ingresa tu **correo electrónico** y **contraseña**
2. Haz clic en **"Iniciar Sesión"**
3. El sistema te pedirá el **código de 6 dígitos**
4. Abre tu aplicación autenticadora en el teléfono
5. Ingresa el código actual (cambia cada 30 segundos)
6. Haz clic en **"Verificar"**
7. ¡Listo! Estás dentro del sistema

> **Nota:** El código de 6 dígitos cambia cada 30 segundos. Si el código que ingresaste expiró mientras lo escribías, simplemente espera el siguiente código y vuelve a intentarlo.

---

### 3.2 PIN Rápido (Acceso Acelerado)

Si accedes al sistema frecuentemente desde el mismo dispositivo, puedes configurar un **PIN de 6 dígitos** para iniciar sesión más rápido sin necesidad del código 2FA cada vez.

#### Configurar el PIN

1. Inicia sesión normalmente (con tu contraseña y código 2FA)
2. Ve a la sección de **Configuración de tu perfil** o busca la opción **"Configurar PIN"**
3. Elige un PIN de exactamente **6 dígitos**
4. El sistema guardará el PIN y un **token de dispositivo** único para tu navegador

#### Usar el PIN para Iniciar Sesión

1. En la pantalla de login, selecciona **"Acceso con PIN"**
2. Escribe tu correo electrónico
3. Ingresa tu PIN de 6 dígitos
4. Haz clic en **"Entrar"**

> **Advertencia:** El PIN está vinculado al navegador/dispositivo donde lo configuraste. Si usas otro navegador o borras las cookies, deberás iniciar sesión con tu contraseña y código 2FA nuevamente.

---

### 3.3 Cerrar Sesión

Para cerrar sesión de forma segura:

1. Busca el ícono de usuario o tu nombre en la parte superior de la pantalla
2. Haz clic en **"Cerrar Sesión"** o **"Salir"**
3. El sistema cerrará tu sesión y te llevará a la pantalla de login

> **Importante:** Siempre cierra sesión al terminar de trabajar, especialmente si usas una computadora compartida.

---

### 3.4 Preguntas Frecuentes — Autenticación

**¿Qué hago si perdí mi teléfono y no puedo obtener el código 2FA?**
Contacta al administrador de tu empresa. El administrador puede restablecer tu configuración de 2FA desde el módulo de Usuarios.

**¿Puedo usar el mismo código 2FA en dos dispositivos?**
Sí. Puedes instalar la misma cuenta autenticadora en múltiples dispositivos usando el mismo código QR durante la configuración inicial. Si ya lo configuraste, el administrador puede reiniciar tu 2FA para que puedas escanearlo de nuevo.

**¿El PIN es seguro?**
El PIN es una capa de conveniencia adicional, no un reemplazo de seguridad. Úsalo solo en dispositivos de tu confianza personal.

---

## 4. Panel Principal (Dashboard)

### ¿Qué es el Dashboard?

El Dashboard es la **pantalla de inicio** que ves al ingresar al sistema. Muestra un resumen visual del estado actual de tu negocio: cuánto has facturado, cuánto te deben, cuántas facturas están vencidas, y más.

### 4.1 Tarjetas de Indicadores (KPIs)

En la parte superior del dashboard verás varias tarjetas con números clave:

| Indicador | ¿Qué Significa? |
|---|---|
| **Ingresos Totales** | Suma de todo el dinero efectivamente cobrado (sin ITBIS) |
| **Pendiente de Cobro** | Total de facturas enviadas/vistas que aún no han sido pagadas |
| **Facturas Vencidas** | Monto total de facturas cuya fecha de vencimiento ya pasó sin pago |
| **Clientes Activos** | Cantidad de clientes registrados y activos en el sistema |
| **Ingresos Este Mes** | Lo cobrado en el mes actual (neto, sin ITBIS) |
| **Ingresos Mes Anterior** | Lo cobrado el mes pasado (para comparación) |
| **ITBIS Este Mes** | Total de ITBIS facturado este mes |
| **ITBIS Pendiente** | ITBIS de facturas no pagadas aún (no ingresó a tu cuenta) |

> **Nota:** Los **ingresos** mostrados en el Dashboard **excluyen el ITBIS**. El ITBIS que cobras a tus clientes no es ganancia tuya — se debe declarar y pagar a la DGII. El sistema muestra los números netos para que veas exactamente cuánto ganaste tú.

### 4.2 Estadísticas de Cotizaciones

Debajo de los KPIs principales verás un bloque con:
- **Total de Cotizaciones** enviadas
- **Cotizaciones Convertidas** en facturas
- **Tasa de Conversión** — porcentaje de cotizaciones que se convierten en ventas
- **Cotizaciones Pendientes** — valor total de cotizaciones en estado borrador o enviadas

### 4.3 Gráfico de Ingresos — Últimos 12 Meses

El gráfico de barras te muestra mes por mes:
- **Ingresos** — dinero cobrado ese mes
- **Gastos** — dinero gastado ese mes (del módulo de Gastos)

Esto te permite ver rápidamente los meses con mejor y peor rendimiento.

### 4.4 Facturas Recientes

Una tabla con las **últimas 5 facturas** creadas, mostrando:
- Número de factura
- Cliente
- Monto total
- Estado actual
- Fecha de emisión

Puedes hacer clic en cualquier factura para verla en detalle.

### 4.5 Facturas Vencidas

Una tabla de las **facturas más urgentes** — las que están vencidas ordenadas de la más antigua a la más nueva. Esto te ayuda a priorizar el cobro.

### 4.6 Actualizar el Dashboard

El dashboard se actualiza automáticamente al cargar la página. Para ver los datos más recientes, simplemente recarga la página (`F5` o el botón de actualizar del navegador).

---

## 5. Gestión de Clientes

### ¿Qué son los Clientes?

Los clientes son las personas o empresas a quienes les emites facturas y cotizaciones. Debes crear un perfil para cada cliente antes de poder facturarle.

### 5.1 Ver la Lista de Clientes

1. En el menú lateral, haz clic en **"Clientes"**
2. Verás una tabla con todos tus clientes registrados
3. Puedes buscar por nombre, empresa o correo electrónico usando la barra de búsqueda

### 5.2 Crear un Nuevo Cliente

1. Ve a **Clientes** en el menú
2. Haz clic en el botón **"Nuevo Cliente"** (o **"+"**)
3. Completa el formulario con la información del cliente:

#### Campos del Formulario de Cliente

| Campo | ¿Qué Escribir? | ¿Requerido? |
|---|---|---|
| **Nombre de Contacto** | Nombre de la persona responsable | Sí |
| **Empresa / Razón Social** | Nombre de la empresa (si aplica) | No |
| **Correo Electrónico** | Email donde recibirá facturas y cotizaciones | Sí |
| **Teléfono** | Número de teléfono fijo o celular | No |
| **WhatsApp** | Número completo con código de país (ej: `+1 809 555 0100`) | No |
| **RNC / Cédula** | Número de identificación fiscal del cliente | No* |
| **Dirección Línea 1** | Calle y número | No |
| **Dirección Línea 2** | Apartamento, suite, local (opcional) | No |
| **Ciudad** | Ciudad donde se ubica el cliente | No |
| **Provincia/Estado** | Provincia o estado | No |
| **Código Postal** | Código postal (si aplica) | No |
| **País** | País del cliente (por defecto: República Dominicana) | No |
| **Notas** | Información interna sobre el cliente | No |

> **Importante:** *Si vas a emitir **facturas electrónicas (e-CF)** a este cliente, el **RNC o Cédula** es obligatorio para poder incluirlo correctamente en los reportes de la DGII.

> **Consejo:** Si tienes configurado el **número de WhatsApp**, el sistema podrá enviarle automáticamente las facturas y cotizaciones por WhatsApp además del correo electrónico.

4. Haz clic en **"Guardar"** o **"Crear Cliente"**

### 5.3 Búsqueda Automática de RNC / Cédula

El sistema incluye una función que **busca automáticamente** la información de un contribuyente en la base de datos de la DGII. Esto evita errores de escritura en los datos fiscales.

#### Cómo Usar la Búsqueda Automática

**Para RNC (empresas):**
1. En el formulario de cliente, escribe el número de RNC en el campo correspondiente
2. El sistema buscará automáticamente el nombre registrado en la DGII
3. Si lo encuentra, completará el nombre de la empresa automáticamente

**Para Cédula (personas físicas):**
1. Escribe el número de cédula en el campo de identificación
2. El sistema consultará el registro y completará el nombre si está disponible

> **Nota:** Esta búsqueda automática requiere que el sistema tenga acceso a internet. Si la búsqueda falla, puedes escribir los datos manualmente.

### 5.4 Editar un Cliente

1. En la lista de Clientes, encuentra el cliente que quieres modificar
2. Haz clic en el ícono de editar () o en el nombre del cliente
3. Modifica los campos necesarios
4. Haz clic en **"Guardar"**

### 5.5 Ver el Perfil Completo de un Cliente

El perfil de un cliente muestra:
- Todos sus datos de contacto
- **Historial de facturas** emitidas a ese cliente
- **Historial de cotizaciones** enviadas
- Montos totales facturados, cobrados y pendientes

Para ver el perfil:
1. Ve a **Clientes**
2. Haz clic en el nombre del cliente
3. Selecciona **"Ver Perfil"** o **"Perfil Completo"**

### 5.6 Activar / Desactivar un Cliente

Si un cliente ya no está activo pero no quieres eliminarlo (para conservar el historial):
1. Edita el cliente
2. Desmarca la opción **"Cliente Activo"**
3. Guarda

Los clientes inactivos no aparecen en las listas de selección al crear facturas, pero su historial permanece intacto.

### 5.7 Eliminar un Cliente

> **Advertencia:** **No puedes eliminar un cliente que tenga facturas o cotizaciones asociadas.** El sistema te lo impedirá para proteger tu historial de facturación.

Para eliminar un cliente sin documentos asociados:
1. En la lista de clientes, haz clic en el ícono de eliminar ()
2. Confirma la eliminación en el cuadro de diálogo

### 5.8 Preguntas Frecuentes — Clientes

**¿Puedo tener dos clientes con el mismo correo electrónico?**
Sí, el sistema lo permite.

**¿Qué pasa si escribí mal el RNC del cliente?**
Simplemente edita el cliente y corrige el número. Las facturas ya emitidas no se modifican automáticamente; si necesitas corregir una factura, puedes editarla.

**¿Cómo busco un cliente rápidamente?**
Usa la barra de búsqueda en la parte superior de la lista de Clientes. Puedes buscar por nombre, empresa, correo o RNC.

---

## 6. Catálogo de Ítems / Productos y Servicios

### ¿Para qué sirve el Catálogo de Ítems?

El catálogo te permite guardar una lista de tus **productos y servicios** con su nombre y precio. Cuando creas una factura o cotización, puedes seleccionar estos ítems en lugar de escribirlos manualmente cada vez — ahorrando tiempo y evitando errores.

### 6.1 Ver el Catálogo

1. En el menú lateral, haz clic en **"Ítems"** o **"Catálogo"**
2. Verás la lista de todos los ítems guardados

### 6.2 Crear un Nuevo Ítem

1. Haz clic en **"Nuevo Ítem"** o **"+"**
2. Completa el formulario:

| Campo | ¿Qué Escribir? |
|---|---|
| **Nombre/Descripción** | El nombre del producto o servicio (ej: "Desarrollo de Sitio Web", "Consultoría por Hora") |
| **Precio Unitario** | El precio de venta del ítem |

3. Haz clic en **"Guardar"**

### 6.3 Usar el Catálogo al Facturar

Cuando creas una factura o cotización, al agregar una línea de producto:
1. Escribe el nombre del ítem en el campo de descripción
2. El sistema sugerirá ítems del catálogo que coincidan
3. Selecciona el ítem deseado
4. El precio se llenará automáticamente (puedes modificarlo si es necesario)

### 6.4 Editar o Eliminar un Ítem

- Para **editar**: haz clic en el ícono junto al ítem
- Para **eliminar**: haz clic en el ícono 

> **Nota:** Eliminar un ítem del catálogo **no afecta** las facturas ya emitidas que lo contienen. La eliminación solo lo quita de la lista de sugerencias al crear nuevas facturas.

---

## 7. Facturación

La facturación es el módulo central de Gridbase Bills. Desde aquí puedes crear, enviar, cobrar y gestionar todas tus facturas.

### 7.1 Crear una Factura

1. En el menú lateral, haz clic en **"Facturas"**
2. Haz clic en el botón **"Nueva Factura"** o **"+"**
3. Completa el formulario:

#### Sección: Información General

| Campo | ¿Qué Hacer? |
|---|---|
| **Cliente** | Selecciona el cliente de la lista. Si no existe, créalo primero en el módulo de Clientes |
| **Moneda** | Selecciona la moneda (DOP, USD, EUR, etc.) |
| **Fecha de Emisión** | La fecha en que emites la factura (por defecto: hoy) |
| **Fecha de Vencimiento** | La fecha límite de pago. Por defecto se calcula automáticamente según la configuración |

#### Sección: Factura Electrónica (e-CF)

Si tu empresa emite comprobantes fiscales electrónicos:

| Campo | ¿Qué Hacer? |
|---|---|
| **¿Es e-CF?** | Marca esta opción si la factura debe ser electrónica |
| **Tipo de e-CF** | Selecciona el tipo de comprobante (ver [Capítulo 11](#11-facturación-electrónica-dgii-e-cf) y [Apéndice 22](#22-apéndice-tipos-de-comprobantes-fiscales-electrónicos-e-cf)) |
| **Tipo de Ingresos** | Categoría de ingresos para el reporte 607 (por defecto: `01 - Ingresos por Operaciones`) |

#### Sección: Líneas de Productos / Servicios

Para cada producto o servicio que vas a facturar:

1. Haz clic en **"Agregar Ítem"** o **"+"**
2. **Descripción**: Escribe o selecciona del catálogo el nombre del producto/servicio
3. **Cantidad**: Cuántas unidades estás cobrando
4. **Precio Unitario**: El precio por unidad
5. El **Total de la línea** se calcula automáticamente (Cantidad × Precio)
6. Para agregar más líneas, repite el proceso
7. Para eliminar una línea, haz clic en el ícono de esa línea

#### Sección: Totales

| Campo | ¿Qué Significa? |
|---|---|
| **Subtotal** | Suma de todas las líneas |
| **Descuento** | Puedes aplicar un descuento en **porcentaje** (ej: 10%) o en **monto fijo** (ej: RD$ 500.00) |
| **ITBIS** | Impuesto al Valor Agregado. Escribe el porcentaje (18% estándar, 16% para algunos bienes) |
| **Total** | Monto final que pagará el cliente |

#### Sección: Notas y Términos

| Campo | ¿Qué Escribir? |
|---|---|
| **Notas** | Mensaje adicional para el cliente (instrucciones de pago, aclaraciones, agradecimientos) |
| **Términos** | Condiciones de pago y garantía |

#### Guardar la Factura

Haz clic en **"Crear Factura"** o **"Guardar"**. El sistema:
- Asigna automáticamente el **número de factura** siguiente (según el prefijo configurado)
- Si la factura es e-CF, inicia el proceso de envío a la DGII
- Envía automáticamente la factura al correo electrónico del cliente (si está configurado el SMTP)
- Si el cliente tiene WhatsApp configurado, también se la envía por WhatsApp

> **Consejo:** El sistema envía la factura **automáticamente al guardarla**. No necesitas hacer clic en un botón adicional de "enviar". Si quieres crear una factura sin enviarla, contacta al administrador para revisar las opciones de borrador.

---

### 7.2 Enviar una Factura Manualmente

Si la factura ya existe y quieres reenviarla:

1. En la lista de **Facturas**, encuentra la factura
2. Haz clic en ella para abrirla
3. Haz clic en el botón **"Enviar"** o el ícono de correo ()
4. El sistema enviará la factura por:
 - **Correo electrónico** (si el cliente tiene email registrado)
 - **WhatsApp** (si el cliente tiene número de WhatsApp registrado)
 - **Ambos canales** (si tiene los dos)

La factura se envía como un **PDF adjunto** con toda la información de tu empresa.

---

### 7.3 Registrar un Pago

Cuando un cliente paga una factura, regístralo en el sistema:

1. Abre la factura correspondiente
2. Haz clic en **"Registrar Pago"** o el botón de pago ()
3. Completa el formulario de pago:

| Campo | ¿Qué Escribir? |
|---|---|
| **Monto** | Cuánto pagó el cliente. Puede ser el total o un pago parcial |
| **Método de Pago** | Selecciona cómo pagó el cliente |
| **Fecha de Pago** | La fecha en que se realizó el pago (por defecto: hoy) |
| **Referencia** | Número de transacción, cheque o referencia bancaria (opcional) |
| **Notas** | Información adicional sobre el pago |

#### Métodos de Pago Disponibles

| Código | Descripción |
|---|---|
| `Transferencia Bancaria` | Pago por transferencia o depósito |
| `Efectivo` | Pago en efectivo |
| `Cheque` | Pago con cheque |
| `Tarjeta de Crédito` | Pago con tarjeta |
| `PayPal` | Pago procesado por PayPal |
| `Otro` | Cualquier otro método |

4. Haz clic en **"Guardar Pago"**

El sistema actualizará automáticamente el estado de la factura:
- Si pagó el **100%** → estado **PAGADA** 
- Si pagó **menos del total** → estado **PAGO PARCIAL** 

El cliente recibirá automáticamente un **correo/WhatsApp de confirmación** del pago.

#### Múltiples Pagos Parciales

Puedes registrar múltiples pagos para una misma factura. Cada pago se acumula hasta alcanzar el total. Esto es útil cuando el cliente paga en cuotas.

---

### 7.4 Ver el PDF de una Factura

1. Abre la factura
2. Haz clic en **"Ver PDF"** o el ícono de PDF ()
3. El PDF se abrirá en una nueva pestaña del navegador
4. Para descargarlo, haz clic en el botón de descarga del visor de PDF

También puedes descargar el PDF directamente haciendo clic en **"Descargar PDF"**.

---

### 7.5 Duplicar una Factura

La función de duplicar es útil cuando necesitas crear una factura similar a una anterior:

1. Abre la factura que quieres duplicar
2. Haz clic en **"Duplicar"** o el ícono de copiar
3. El sistema creará una **nueva factura** con:
 - El mismo cliente
 - Los mismos productos/servicios
 - El mismo monto
 - **Nueva fecha** (la de hoy)
 - **Nuevo número de factura**
 - Estado: **Borrador**
4. Modifica lo que necesites y guarda

---

### 7.6 Exportar Facturas a Excel/CSV

Para exportar todas tus facturas a un archivo de Excel:

1. Ve a **Facturas**
2. Busca el botón **"Exportar"** o **"Descargar CSV"**
3. El sistema descargará un archivo `.csv` que puedes abrir en Excel
4. El archivo incluye: número de factura, RNC del cliente, nombre del cliente, fecha, subtotal, descuento, ITBIS, total, estado y número e-CF

---

### 7.7 Notas de Crédito y Débito (e-CF tipos 33 y 34)

Las notas de crédito y débito son documentos electrónicos que modifican una factura electrónica ya emitida.

**Nota de Crédito (e-CF Tipo 34):** Se usa para reducir el monto de una factura ya emitida. Por ejemplo:
- Devolution de mercancía
- Descuento aplicado después de facturar
- Error en precio a favor del cliente

**Nota de Débito (e-CF Tipo 33):** Se usa para aumentar el monto de una factura ya emitida. Por ejemplo:
- Cobro adicional no incluido
- Error en precio a favor de la empresa

#### Cómo Crear una Nota de Crédito o Débito

1. Ve a **Facturas** → **Nueva Factura**
2. Activa la opción **"¿Es e-CF?"**
3. En **"Tipo de e-CF"**, selecciona:
 - **Tipo 34** para Nota de Crédito
 - **Tipo 33** para Nota de Débito
4. En **"NCF Modificado"**, ingresa el número de la factura original que estás modificando
5. Selecciona el **Código de Modificación** que corresponde:

| Código | Descripción |
|---|---|
| `01` | Anulación de comprobante fiscal |
| `02` | Corrección en montos o datos del comprobante |
| `03` | Descuento o bonificación |
| `04` | Devolución de mercancías |
| `05` | Permuta |
| `06` | Crédito incobrable |
| `07` | Errores en el tipo de comprobante |

6. Completa el resto de la factura normalmente
7. Guarda

El sistema enviará la nota a la DGII automáticamente.

---

### 7.8 Acciones Masivas

Puedes realizar acciones sobre múltiples facturas a la vez:

#### Seleccionar Facturas

1. En la lista de facturas, marca las casillas de verificación () junto a las facturas que quieres seleccionar
2. Aparecerá una barra de acciones en la parte inferior o superior de la lista

#### Acciones Disponibles

| Acción | ¿Qué Hace? |
|---|---|
| **Eliminar Seleccionadas** | Elimina todas las facturas seleccionadas |
| **Marcar como Pagadas** | Marca las facturas seleccionadas como pagadas (pago completo) |
| **Procesar e-CF** | Envía las facturas seleccionadas a la DGII para procesamiento electrónico |

> **Advertencia:** La eliminación masiva de facturas es **irreversible**. Asegúrate de seleccionar solo las facturas correctas antes de confirmar.

---

### 7.9 Estados de una Factura

| Estado | Ícono | ¿Qué Significa? |
|---|---|---|
| **Borrador** | | La factura fue creada pero no enviada |
| **Enviada** | | La factura fue enviada al cliente |
| **Vista** | | El cliente abrió el enlace de la factura |
| **Pago Parcial** | | El cliente pagó una parte del total |
| **Pagada** | | La factura está completamente saldada |
| **Vencida** | | La fecha de vencimiento pasó sin pago completo |
| **Cancelada** | | La factura fue cancelada manualmente |

---

### 7.10 Filtros y Búsqueda de Facturas

En la lista de facturas puedes filtrar por:
- **Estado** (borrador, enviada, pagada, etc.)
- **Rango de fechas**
- **Cliente**
- Búsqueda por número de factura

---

### 7.11 Descargar XML de Factura Electrónica

Para las facturas procesadas como e-CF, puedes descargar el XML firmado:

1. Abre la factura electrónica
2. Haz clic en **"Descargar XML"**
3. El archivo `.xml` se descargará a tu computadora

Este archivo es el comprobante oficial firmado digitalmente que fue enviado a la DGII.

---

### 7.12 Preguntas Frecuentes — Facturas

**¿Puedo editar una factura ya enviada?**
Sí, siempre que no sea una factura electrónica (e-CF) ya aprobada por la DGII. Para facturas electrónicas aprobadas, debes usar una Nota de Crédito o Débito.

**¿Qué pasa si el correo no llegó al cliente?**
1. Verifica que el correo del cliente esté correcto
2. Revisa la configuración de SMTP en Ajustes
3. Intenta reenviar la factura manualmente
4. Consulta el [Capítulo 19](#19-solución-de-problemas-frecuentes) para solución de problemas de correo

**¿Puedo facturar en dólares si soy empresa dominicana?**
Sí. Puedes crear facturas en USD, EUR u otras monedas. El sistema registra el tipo de cambio al momento de la factura para los reportes DGII en DOP.

**¿Cómo se calcula el ITBIS?**
El ITBIS se calcula sobre el subtotal después del descuento. Ejemplo: Si el subtotal es RD$ 10,000 con descuento de RD$ 1,000 y ITBIS de 18%, el ITBIS sería: (10,000 - 1,000) × 18% = RD$ 1,620.

**¿Puedo eliminar una factura pagada?**
Sí, técnicamente el sistema lo permite (si tienes los permisos). Sin embargo, **no se recomienda** eliminar facturas pagadas ya que afecta tus reportes contables y puede causar discrepancias con la DGII.

---

## 8. Cotizaciones

### ¿Qué es una Cotización?

Una cotización (también llamada presupuesto o propuesta) es un documento que le envías a un cliente **antes de que decida comprarte**, mostrando los productos/servicios y precios. Una vez el cliente la acepta, puedes convertirla en factura con un solo clic.

### 8.1 Crear una Cotización

1. En el menú lateral, haz clic en **"Cotizaciones"**
2. Haz clic en **"Nueva Cotización"** o **"+"**
3. Completa el formulario (es similar al de facturas):

| Campo | ¿Qué Hacer? |
|---|---|
| **Cliente** | Selecciona el cliente |
| **Moneda** | Selecciona la moneda |
| **Fecha de Emisión** | La fecha de hoy (por defecto) |
| **Fecha de Vencimiento** | La fecha en que vence la cotización (después de esta fecha ya no es válida) |
| **Ítems** | Agrega los productos/servicios cotizados |
| **Descuento** | Si aplica algún descuento |
| **ITBIS** | Si aplica impuesto |
| **Notas** | Condiciones especiales de la propuesta |
| **Términos** | Garantías, plazos de entrega, forma de pago propuesta |

4. Haz clic en **"Crear Cotización"**

> **Nota:** Las cotizaciones **no se envían automáticamente** al crearse. Debes enviarlas manualmente.

### 8.2 Enviar una Cotización al Cliente

1. Abre la cotización
2. Haz clic en **"Enviar"** o el ícono de correo ()
3. El sistema enviará la cotización por correo electrónico y/o WhatsApp
4. El estado cambiará a **"Enviada"**

### 8.3 Convertir una Cotización en Factura

Cuando el cliente acepta la cotización:

1. Abre la cotización
2. Haz clic en **"Convertir a Factura"**
3. El sistema creará automáticamente una nueva factura con:
 - Todos los ítems de la cotización
 - El mismo cliente
 - El mismo monto
 - Fecha de hoy como fecha de emisión
4. La cotización cambiará a estado **"Convertida"**
5. La factura se enviará automáticamente al cliente

> **Consejo:** Una vez convertida, la cotización queda vinculada a la factura generada. Puedes ver esa relación en el perfil de la cotización.

### 8.4 Duplicar una Cotización

Similar a las facturas, puedes duplicar una cotización existente para crear una nueva con los mismos productos/servicios.

### 8.5 Exportar Cotizaciones a CSV

Igual que con las facturas, puedes exportar toda la lista de cotizaciones a un archivo Excel/CSV.

### 8.6 Estados de una Cotización

| Estado | ¿Qué Significa? |
|---|---|
| **Borrador** | Creada pero no enviada |
| **Enviada** | Enviada al cliente |
| **Vista** | El cliente la abrió |
| **Aceptada** | El cliente indicó que la acepta (registro manual) |
| **Rechazada** | El cliente la rechazó (registro manual) |
| **Vencida** | La fecha de vencimiento pasó sin conversión |
| **Convertida** | Fue convertida en factura |

### 8.7 Preguntas Frecuentes — Cotizaciones

**¿Puedo modificar los precios al convertir la cotización en factura?**
Al convertir, se crea la factura con los precios de la cotización. Puedes editar la factura inmediatamente después si necesitas ajustar algún precio.

**¿Qué pasa si la cotización vence?**
El estado cambia a "Vencida" automáticamente. Aún puedes convertirla en factura si el cliente acepta después de la fecha de vencimiento.

---

## 9. Facturas Recurrentes / Suscripciones

### ¿Qué son las Facturas Recurrentes?

Las facturas recurrentes te permiten configurar una **factura que se genera automáticamente** de forma periódica. Son ideales para:
- Servicios mensuales de mantenimiento
- Suscripciones de software o hosting
- Pagos de renta o alquiler
- Retainerios de consultoría

### 9.1 Crear una Suscripción

1. En el menú lateral, haz clic en **"Recurrentes"** o **"Suscripciones"**
2. Haz clic en **"Nueva Suscripción"** o **"+"**
3. Completa el formulario:

#### Información de la Suscripción

| Campo | ¿Qué Hacer? |
|---|---|
| **Cliente** | El cliente que recibirá las facturas periódicas |
| **Frecuencia** | Con qué frecuencia se genera la factura |
| **Fecha de Inicio** | Cuándo comienza la primera factura |
| **Fecha de Fin** | Cuándo termina la suscripción (opcional; déjala vacía si es indefinida) |
| **Límite de Ocurrencias** | Máxima cantidad de facturas a generar (opcional) |
| **Moneda** | Moneda de facturación |
| **ITBIS (%)** | Porcentaje de impuesto a aplicar |
| **Ítems** | Productos/servicios que se facturarán cada período |

#### Frecuencias Disponibles

| Frecuencia | ¿Con Qué Frecuencia Genera? |
|---|---|
| **Semanal** | Cada 7 días |
| **Bisemanal** | Cada 14 días |
| **Mensual** | Cada mes |
| **Trimestral** | Cada 3 meses |
| **Semestral** | Cada 6 meses |
| **Anual** | Una vez al año |

#### Opciones de Envío Automático

| Campo | ¿Qué Hacer? |
|---|---|
| **Envío Automático** | Activa esta opción para que el sistema envíe la factura automáticamente cuando se genera |
| **Enviar Por** | Elige: Email, WhatsApp, o Ambos |

#### Factura Electrónica en Recurrentes

Si quieres que las facturas generadas sean electrónicas (e-CF):
- Activa la opción de e-CF
- Selecciona el tipo de comprobante
- Configura el tipo de ingresos

4. Haz clic en **"Crear Suscripción"**

**Al crear la suscripción, el sistema genera automáticamente la primera factura** y la envía al cliente (si el envío automático está activado).

### 9.2 Generar una Factura Manualmente

Si necesitas generar una factura de una suscripción antes del próximo período programado:

1. Abre la suscripción
2. Haz clic en **"Generar Factura Ahora"**
3. El sistema crea la factura inmediatamente y avanza la próxima fecha de generación

### 9.3 Pausar una Suscripción

Para detener temporalmente la generación de facturas:

1. Abre la suscripción
2. Haz clic en **"Pausar"** o cambia el estado a **"Pausada"**
3. Mientras está pausada, no se generarán nuevas facturas

### 9.4 Reactivar una Suscripción

1. Abre la suscripción pausada
2. Cambia el estado a **"Activa"**
3. El sistema reiniciará la generación desde la próxima fecha programada

### 9.5 Cancelar una Suscripción

1. Abre la suscripción
2. Cambia el estado a **"Cancelada"**
3. No se generarán más facturas

> **Nota:** Cancelar una suscripción **no elimina** las facturas ya generadas anteriormente. Solo detiene la generación futura.

### 9.6 Ver el Historial de Facturas de una Suscripción

En el detalle de cada suscripción, puedes ver las últimas 20 facturas generadas automáticamente.

### 9.7 Editar una Suscripción

Puedes modificar los ítems, la frecuencia, el cliente, el ITBIS y otras opciones en cualquier momento. Los cambios aplican a las **próximas** facturas generadas; las anteriores no se modifican.

### 9.8 Preguntas Frecuentes — Recurrentes

**¿A qué hora del día se generan las facturas automáticas?**
El sistema las genera una vez al día, típicamente en las primeras horas de la mañana (según la zona horaria del servidor).

**¿Qué pasa si la suscripción tiene un límite de ocurrencias y lo alcanza?**
Cuando se llega al límite, la suscripción cambia automáticamente a estado "Completada" y no genera más facturas.

**¿Las facturas de suscripciones se pueden generar como e-CF?**
Sí. Si configuras el tipo de e-CF en la suscripción, todas las facturas generadas serán electrónicas.

---

## 10. Gastos / Control de Egresos

### ¿Para qué sirve el Módulo de Gastos?

El módulo de Gastos te permite registrar todos los pagos y compras que hace tu empresa. Esto sirve para:
- Llevar un control interno de tus egresos
- Generar el **Reporte 606** de la DGII (Compras y Gastos)
- Ver en el dashboard la diferencia entre ingresos y gastos

> **Nota:** Este módulo es solo para **Administradores y Contadores**. Los usuarios con rol de Visualizador no pueden acceder.

### 10.1 Ver los Gastos Registrados

1. En el menú lateral, haz clic en **"Gastos"**
2. Verás la lista de gastos con filtros por:
 - **Período** (año y mes)
 - **Búsqueda** por nombre de proveedor, RNC o NCF

### 10.2 Registrar un Gasto

1. Ve a **Gastos**
2. Haz clic en **"Nuevo Gasto"** o **"+"**
3. Completa el formulario:

#### Información del Proveedor

| Campo | ¿Qué Escribir? |
|---|---|
| **Nombre del Proveedor** | El nombre de la empresa o persona a quien pagaste |
| **RNC / Cédula del Proveedor** | El número fiscal del proveedor (para el Reporte 606) |

#### Datos del Comprobante

| Campo | ¿Qué Escribir? |
|---|---|
| **NCF** | El número del comprobante fiscal que te dio el proveedor (hasta 13 caracteres) |
| **Fecha del Gasto** | La fecha en que realizaste el pago o recibiste la factura |

#### Montos

| Campo | ¿Qué Escribir? |
|---|---|
| **Subtotal** | El monto del gasto sin ITBIS |
| **ITBIS (Monto)** | El ITBIS que aparece en la factura del proveedor |
| **Total** | Subtotal + ITBIS = Total pagado |

#### Clasificación DGII (para el Reporte 606)

**Tipo de Bien o Servicio:**

| Código | Descripción |
|---|---|
| `01` | Gastos de Personal |
| `02` | Gastos por Trabajos, Suministros y Servicios |
| `03` | Arrendamientos |
| `04` | Gastos de Activos Fijos |
| `05` | Representación y Publicidad |
| `06` | Seguros |
| `07` | Investigación y Desarrollo |
| `08` | Gastos en Zonas Francas |
| `09` | Gastos de Representación |
| `10` | Otras Deducciones Admitidas |
| `11` | Gastos Contabilizados y No Deducibles |

**Forma de Pago:**

| Código | Descripción |
|---|---|
| `01` | Efectivo |
| `02` | Cheques / Transferencias / Depósitos |
| `03` | Tarjeta de Crédito / Débito |
| `04` | A Crédito |
| `05` | Permuta |
| `06` | Nota de Crédito |
| `07` | Mixto |

#### Notas

Campo libre para agregar cualquier información adicional sobre el gasto.

4. Haz clic en **"Guardar Gasto"**

### 10.3 Editar un Gasto

1. En la lista de gastos, encuentra el gasto a modificar
2. Haz clic en el ícono de editar ()
3. Modifica los campos necesarios
4. Haz clic en **"Guardar"**

### 10.4 Eliminar un Gasto

1. En la lista, haz clic en el ícono de eliminar ()
2. Confirma la eliminación

> **Advertencia:** Eliminar un gasto lo quitará también del **Reporte 606**. Esto podría crear discrepancias si ya presentaste el reporte a la DGII.

### 10.5 Preguntas Frecuentes — Gastos

**¿Debo registrar todos los gastos para el Reporte 606?**
Sí. El Reporte 606 incluye: facturas recibidas de proveedores (a través del módulo de Facturas Recibidas), facturas de compras informales (tipo ECF 41/43) y los gastos registrados manualmente en este módulo.

**¿Qué pasa si un proveedor no me da NCF?**
Puedes dejar el campo NCF vacío. Sin embargo, los comprobantes sin NCF tienen implicaciones fiscales — consulta con tu contador.
**¿El ITBIS de los gastos es recuperable?**
Dependiendo del tipo de gasto y de tu situación fiscal. Esta es una pregunta contable que debes consultar con tu contador.

---

## 11. Facturación Electrónica DGII (e-CF)

### 11.1 ¿Qué es la Facturación Electrónica?

La **facturación electrónica** en República Dominicana es el sistema oficial de la **DGII (Dirección General de Impuestos Internos)** para la emisión de comprobantes fiscales en formato digital (XML) firmados digitalmente. Se conoce como **e-CF (Comprobante Fiscal Electrónico)**.

**¿Por qué usar e-CF?**
- Es un **requisito legal** para empresas obligadas por la DGII
- Garantiza la **validez fiscal** de tus facturas
- Elimina la necesidad de comprobantes físicos NCF pre-impresos
- Facilita los reportes 606 y 607
- Los clientes pueden verificar la autenticidad de tu factura en el portal de la DGII

### 11.2 Requisitos para Usar la Facturación Electrónica

Antes de emitir e-CF, necesitas:

1. **Autorización de la DGII**: Tu empresa debe estar autorizada por la DGII para emitir e-CF
2. **Certificado Digital (.p12 o .pfx)**: Un archivo de certificado emitido por la DGII
3. **Contraseña del Certificado**: La clave del archivo de certificado
4. **Entorno configurado**: Pruebas o Producción

> **Importante:** Si aún no tienes autorización de la DGII para emitir e-CF, contacta a tu contador o directamente a la DGII en dgii.gov.do para iniciar el proceso. Hasta obtener la autorización, puedes usar el sistema con facturas regulares.

### 11.3 Configurar el Certificado Digital

El administrador debe subir el certificado al sistema (ver [Capítulo 15 — Configuración](#15-configuración-del-sistema)).

### 11.4 Tipos de Comprobantes Fiscales Electrónicos

| Tipo | Código e-NCF | Nombre |
|---|---|---|
| Factura de Crédito Fiscal | E31 | Para empresas con RNC (B2B) |
| Factura de Consumo | E32 | Para personas sin RNC (hasta RD$250,000) |
| Nota de Débito | E33 | Para aumentar monto de factura anterior |
| Nota de Crédito | E34 | Para reducir monto de factura anterior |
| Compras | E41 | Comprobante de compra informal |
| Gastos Menores | E43 | Gastos de bajo valor |
| Regímenes Especiales | E44 | Sectores con régimen especial |
| Gubernamental | E45 | Ventas al sector público |

Ver el [Apéndice 22](#22-apéndice-tipos-de-comprobantes-fiscales-electrónicos-e-cf) para una descripción detallada de cada tipo.

### 11.5 Cómo Emitir una Factura Electrónica

1. Ve a **Facturas** → **Nueva Factura**
2. Selecciona el cliente (debe tener RNC o Cédula si el tipo es E31)
3. Activa la opción **"¿Es factura electrónica (e-CF)?"**
4. Selecciona el **Tipo de e-CF** según el cliente:
 - **E31** — Si el cliente es una empresa con RNC
 - **E32** — Si el cliente es una persona sin RNC
 - **E44** — Si el cliente tiene régimen especial
 - **E45** — Si el cliente es del sector público
5. Selecciona el **Tipo de Ingresos** (para el reporte 607):

| Código | Categoría |
|---|---|
| `01` | Ingresos por Operaciones (Ventas) |
| `02` | Ingresos Financieros |
| `03` | Ingresos Extraordinarios |
| `04` | Ingresos por Arrendamientos |
| `05` | Ingresos por Venta de Activos Depreciables |
| `06` | Otros Ingresos |

6. Agrega los ítems, descuentos e ITBIS normalmente
7. Haz clic en **"Crear Factura"**

El sistema automáticamente:
- Genera el XML según el esquema de la DGII
- Firma el XML digitalmente con tu certificado
- Envía el XML a la DGII para su validación
- Obtiene el número e-NCF oficial (ej: `E3100000001`)
- Guarda el XML firmado en el sistema

### 11.6 Estados del Proceso e-CF

| Estado DGII | ¿Qué Significa? |
|---|---|
| `draft` | Factura creada, aún no procesada como e-CF |
| `signed` | XML generado y firmado digitalmente |
| `pending` | Enviado a la DGII, esperando respuesta |
| `approved` | La DGII aprobó el comprobante |
| `rejected` | La DGII rechazó el comprobante (ver errores) |
| `contingency` | Error temporal de comunicación con la DGII |

### 11.7 Qué Hacer si la Factura fue Rechazada

Si la DGII rechaza un e-CF:

1. Abre la factura rechazada
2. Lee el mensaje de error (debería decir el motivo del rechazo)
3. Corrige el problema indicado
4. Haz clic en **"Procesar e-CF"** o **"Reintentar"** para volver a enviar

**Errores comunes y sus soluciones:**

| Error Común | Solución |
|---|---|
| RNC del cliente incorrecto | Edita el cliente y corrige el RNC |
| Certificado vencido o inválido | Contacta al administrador para renovar el certificado |
| Monto fuera de rango para E32 | Si supera RD$250,000, usa E31 (requiere RNC del cliente) |
| Tipo de e-CF incorrecto para el comprador | Selecciona el tipo correcto según la categoría del cliente |

> **Consejo:** Si el estado es `contingency`, significa que hubo un problema de comunicación temporal con los servidores de la DGII. Espera unos minutos y usa el botón **"Verificar Estado"** para consultar si fue procesado.

### 11.8 Verificar el Estado de un e-CF

1. Abre la factura electrónica
2. Haz clic en **"Verificar Estado"** o **"Consultar DGII"**
3. El sistema consultará a la DGII y actualizará el estado

### 11.9 Ver los Logs de Comunicación con la DGII

Para usuarios Admin y Contador, existe un registro completo de todas las comunicaciones con la DGII:

1. Ve al menú **"DGII"** → **"Logs"**
2. Verás un historial cronológico de envíos, respuestas y errores
3. Puedes ver el detalle de cada transacción haciendo clic en ella

### 11.10 Preguntas Frecuentes — e-CF

**¿Qué pasa si no tengo internet cuando creo la factura?**
El sistema firmará el XML pero no podrá enviarlo a la DGII. La factura quedará en estado `signed` o `contingency`. Cuando recuperes la conexión, usa el botón "Reintentar" para enviarla.

**¿Cuánto tiempo tiene la DGII para aprobar un e-CF?**
Generalmente la respuesta es inmediata (segundos). En horarios de alto tráfico puede tardar algunos minutos.

**¿Puedo emitir e-CF si estoy en modo pruebas (sandbox)?**
Sí. En el entorno de pruebas puedes emitir e-CF sin que tengan validez fiscal. Es ideal para aprender y verificar la configuración antes de producción.

**¿Dónde puedo verificar la autenticidad de un e-CF?**
En el portal de la DGII: dgii.gov.do → "Consultas" → "Comprobantes Electrónicos"

---

## 12. Facturas Recibidas (Aprobaciones Comerciales)

### ¿Qué son las Facturas Recibidas?

Cuando tus **proveedores también usan facturación electrónica**, la DGII envía una copia del e-CF que el proveedor te emitió. Estas facturas recibidas (ACECF) requieren que tu empresa las **apruebe o rechace** formalmente.

> **Nota:** Este módulo es para **Administradores y Contadores** únicamente.

### 12.1 Ver las Facturas Recibidas

1. Ve al menú **"Facturas Recibidas"** o **"Aprobaciones Comerciales"**
2. Verás la lista con RNC del proveedor, nombre, e-NCF, fecha, monto y estado de aprobación

### 12.2 Aprobar una Factura Recibida

1. Haz clic en la factura recibida para ver el detalle
2. Revisa el monto, fecha y datos del proveedor
3. Si todo es correcto, haz clic en **"Aprobar"**
4. El sistema enviará la aprobación a la DGII y al proveedor

> **Importante:** Al aprobar una factura recibida, confirmas que el servicio o bien fue efectivamente recibido. Esta aprobación alimenta el Reporte 606.

### 12.3 Rechazar una Factura Recibida

1. Haz clic en la factura recibida
2. Haz clic en **"Rechazar"**
3. Escribe el **motivo del rechazo** (obligatorio)
4. Confirma el rechazo

El sistema notificará a la DGII y al proveedor.

### 12.4 Preguntas Frecuentes — Facturas Recibidas

**¿Qué pasa si no apruebo ni rechazo una factura recibida?**
Queda en estado "Pendiente". Consulta con tu contador sobre los plazos de aprobación que establece la DGII.

**¿Puedo ver el XML de la factura que me enviaron?**
Sí. En el detalle de la factura recibida, puedes ver el XML original del proveedor.

---

## 13. Reportes DGII — 606 y 607

### ¿Para qué Sirven los Reportes 606 y 607?

Cada mes, las empresas dominicanas deben presentar a la DGII un informe de sus **ventas (607)** y **compras/gastos (606)**. Gridbase Bills genera estos reportes automáticamente.

> **Nota:** Este módulo es para **Administradores y Contadores** únicamente.

### 13.1 Reporte 607 — Ventas del Período

El Reporte 607 incluye todas las facturas que emitiste durante el período.

#### Acceder al Reporte 607

1. Ve al menú **"Reportes"** → **"DGII 607"**
2. Selecciona el **Año** y el **Mes**
3. Haz clic en **"Generar Reporte"**

#### ¿Qué Incluye el Reporte 607?

Por cada factura emitida (excepto borradores):
- RNC o Cédula del cliente y tipo de identificación
- Número de comprobante (NCF o e-NCF) y NCF modificado si aplica
- Tipo de ingreso, fechas (comprobante y pago)
- Monto facturado y ITBIS
- Formas de pago desglosadas (efectivo, banco, tarjeta, crédito, otros)

> **Importante:** Las facturas E32 (Consumo) mayores de RD$250,000 requieren el RNC del cliente en el 607. Asegúrate de tenerlo registrado.

#### Exportar el Reporte 607

1. Revisa los datos en pantalla
2. Haz clic en **"Exportar .TXT"**
3. Descarga el archivo `DGII_607_[RNC]_[PERIODO].txt`
4. Cárgalo en el portal de la DGII en la sección de Anexos del IT-1

---

### 13.2 Reporte 606 — Compras y Gastos del Período

El Reporte 606 se compila de **tres fuentes**:

| Fuente | Descripción |
|---|---|
| **Facturas Recibidas** | e-CF recibidos de proveedores (módulo de Aprobaciones Comerciales) |
| **Facturas de Compras (E41/E43)** | Facturas que emitiste para compras informales |
| **Gastos Manuales** | Registros del módulo de Gastos |

#### Acceder y Exportar el Reporte 606

1. Ve al menú **"Reportes"** → **"DGII 606"**
2. Selecciona el **Año** y el **Mes**
3. Haz clic en **"Generar Reporte"**
4. Revisa y haz clic en **"Exportar .TXT"**
5. El archivo descargado: `DGII_606_[RNC]_[PERIODO].txt`

### 13.3 Buenas Prácticas para los Reportes DGII

> **Consejo:** Antes de generar los reportes:
> - Verifica que todos los gastos del mes estén registrados
> - Confirma que las facturas recibidas estén aprobadas o rechazadas
> - Revisa que los RNC de clientes y proveedores sean correctos
> - No dejes facturas importantes en estado "Borrador"

> **Advertencia:** Genera los reportes **ANTES** de la fecha límite mensual de la DGII. La presentación tardía conlleva multas.

### 13.4 Preguntas Frecuentes — Reportes DGII

**¿El sistema presenta automáticamente los reportes a la DGII?**
No. El sistema genera el archivo en el formato correcto, pero debes cargarlo manualmente en el portal de la DGII.

**¿Puedo generar el reporte de meses anteriores?**
Sí. Solo selecciona el año y mes del período deseado.

**¿El reporte incluye facturas en otras monedas?**
Sí. Usa el tipo de cambio registrado al momento de crear la factura para convertir a DOP.

---

## 14. Links de Pago y Cobro en Línea

### ¿Qué es un Link de Pago?

Un **link de pago** es un enlace único y seguro que le envías a tu cliente para que pueda pagar su factura en línea con tarjeta de crédito/débito o **PayPal**, sin necesidad de transferencia bancaria.

**Beneficios:**
- Cobras más rápido
- El pago se registra automáticamente en el sistema
- El cliente paga desde cualquier dispositivo y en cualquier momento
- Soporta pagos parciales

### 14.1 Generar un Link de Pago

**Automático:** Al crear la factura, el sistema genera y envía el link automáticamente.

**Manual:**
1. Abre la factura
2. Haz clic en **"Generar Link de Pago"**
3. Opcionalmente especifica los días de validez (por defecto: 30 días)
4. Haz clic en **"Generar"**

### 14.2 Enviar el Link de Pago

- **Por correo:** Haz clic en **"Enviar Link por Email"**
- **Por WhatsApp:** Haz clic en **"Enviar Link por WhatsApp"**
- **Por ambos:** Haz clic en **"Enviar por Email y WhatsApp"**
- **Copiar manualmente:** Haz clic en **"Copiar"** y pégalo donde necesites

### 14.3 Experiencia del Cliente al Pagar

El cliente:
1. Abre el link → ve una página segura con los detalles de la factura
2. Hace clic en **"Pagar con PayPal"**
3. Paga con su cuenta PayPal o con tarjeta de crédito/débito (sin necesitar cuenta PayPal)
4. Ve una confirmación del pago

Al completarse:
- El pago se registra automáticamente
- La factura cambia a estado "Pagada" o "Pago Parcial"
- El cliente recibe comprobante de PayPal

### 14.4 Verificar si el Link es Válido

Los links expiran cuando: la factura se paga, vence el período de validez, o la factura se cancela.

Para verificar: abre la factura → haz clic en **"Verificar Link"**.

### 14.5 Regenerar un Link Expirado

1. Abre la factura
2. Haz clic en **"Regenerar Link"**
3. Especifica el nuevo período de validez
4. El link anterior queda inválido automáticamente

### 14.6 Preguntas Frecuentes — Links de Pago

**¿Los pagos por PayPal tienen comisión?**
Sí, PayPal cobra por transacción (~2.9% + tarifa fija). Consulta tarifas actuales en paypal.com.

**¿Mi cliente necesita cuenta de PayPal?**
No. PayPal también permite pagar con tarjeta sin cuenta.

**¿El cliente pagó pero el sistema no lo registró?**
Verifica en tu cuenta PayPal directamente. Si el pago está ahí, regístralo manualmente con método "PayPal" y la referencia de transacción.

---

## 15. Configuración del Sistema

> **Nota:** La Configuración solo está disponible para usuarios **Administrador**.

### 15.1 Datos de la Empresa

1. Ve a **Configuración** → **"Datos de la Empresa"**
2. Completa:

| Campo | ¿Qué Escribir? |
|---|---|
| **Nombre de la Empresa** | Razón social oficial |
| **RNC de la Empresa** | Tu número de RNC |
| **Correo Electrónico** | Email de la empresa |
| **Teléfono** | Número de contacto |
| **Sitio Web** | URL de tu página web |
| **Dirección** | Dirección física |
| **Logo** | Imagen del logo (aparecerá en facturas PDF y correos) |

### 15.2 Configurar el Correo Saliente (SMTP)

1. Ve a **Configuración** → **"Correo Electrónico"**
2. Completa los datos del servidor:

| Campo | ¿Qué Escribir? |
|---|---|
| **Servidor SMTP** | `mail.tudominio.com`, `smtp.gmail.com`, etc. |
| **Puerto** | 25, 465 o 587 |
| **Cifrado** | TLS, SSL o ninguno |
| **Usuario SMTP** | Tu email de autenticación |
| **Contraseña SMTP** | Contraseña del servidor de correo |
| **Email del Remitente** | La dirección que verán los destinatarios |
| **Nombre del Remitente** | El nombre que acompañará el correo |

**Configuraciones Comunes:**

| Proveedor | Servidor | Puerto | Cifrado |
|---|---|---|---|
| cPanel / Hosting | `mail.tudominio.com` | 25 o 587 | ninguno o TLS |
| Gmail / G Suite | `smtp.gmail.com` | 587 | TLS |
| Microsoft 365 | `smtp.office365.com` | 587 | TLS |

**Probar la Configuración:**
1. Ingresa los datos
2. Escribe tu email en **"Correo de Prueba"**
3. Haz clic en **"Enviar Prueba"**
4. Si recibes el correo, guarda la configuración

**Diagnóstico Automático:**
Haz clic en **"Diagnosticar SMTP"** para que el sistema detecte automáticamente los puertos disponibles y te recomiende la mejor configuración.

### 15.3 Configurar DGII (Facturación Electrónica)

#### Subir el Certificado Digital

1. Ve a **Configuración** → **"DGII"**
2. Haz clic en **"Subir Certificado"**
3. Selecciona tu archivo `.p12` o `.pfx`

> **Advertencia:** El certificado digital es extremadamente sensible. No lo compartas con nadie fuera de tu organización.

#### Datos DGII a Configurar

| Campo | ¿Qué Escribir? |
|---|---|
| **Contraseña del Certificado** | Contraseña de tu archivo .p12 |
| **Entorno** | `pruebas` para testing, `produccion` para facturas reales |

> **Importante:** Comienza siempre con entorno de **pruebas** antes de pasar a producción.

### 15.4 Numeración de Documentos

| Campo | Ejemplo |
|---|---|
| **Prefijo de Facturas** | `FAC-` |
| **Siguiente Número de Factura** | `1` → genera FAC-0001 |
| **Prefijo de Cotizaciones** | `COT-` |
| **Siguiente Número de Cotización** | `1` |
| **Días de Vencimiento por Defecto** | `30` |

> **Advertencia:** Si cambias el número siguiente, no pongas un número menor a facturas ya emitidas para evitar duplicados.

### 15.5 Plantilla PDF

| Plantilla | Descripción |
|---|---|
| **Normal** | Diseño de página carta. Más información, mejor para impresoras de papel estándar |
| **Térmica** | Diseño estrecho optimizado para impresoras de tickets/térmica |

### 15.6 Configurar WhatsApp Business API

1. Ve a **Configuración** → **"WhatsApp"**
2. Ingresa las credenciales de Meta Business API:

| Campo | Descripción |
|---|---|
| **Phone Number ID** | ID del número en Meta |
| **Access Token** | Token de acceso de tu app Meta |
| **Business Account ID** | ID de tu cuenta de negocio |

> **Nota:** Necesitas una cuenta aprobada de WhatsApp Business API en Meta. Consulta: business.facebook.com

### 15.7 Configurar PayPal

1. Ve a **Configuración** → **"PayPal"**
2. Ingresa:

| Campo | ¿Qué Escribir? |
|---|---|
| **Modo** | `sandbox` para pruebas, `live` para real |
| **Client ID** | De tu app en developer.paypal.com |
| **Client Secret** | De tu app en developer.paypal.com |

### 15.8 Restablecer Base de Datos

> **Precaucion:** **Esta acción es IRREVERSIBLE.** Se eliminarán TODOS los datos de facturas, clientes, cotizaciones, gastos y pagos. Solo se conservan usuarios y configuración.

1. Ve a **Configuración** → **"Avanzado"**
2. Busca **"Restablecer Base de Datos"**
3. Escribe tu correo electrónico como confirmación
4. Confirma la acción

Úsala solo al terminar el período de pruebas para comenzar con datos reales.

---

## 16. Gestión de Usuarios

> **Nota:** Solo los **Administradores** pueden gestionar usuarios.

### 16.1 Roles del Sistema

| Rol | Acceso |
|---|---|
| **Administrador** | Acceso completo: facturas, clientes, gastos, reportes, configuración, usuarios, API keys |
| **Editor / Contador** | Facturas, cotizaciones, clientes, ítems, gastos, reportes, API keys. Sin gestión de usuarios ni reset de DB |
| **Visualizador** | Solo lectura. No puede crear, editar ni eliminar |

### 16.2 Crear un Nuevo Usuario

1. Ve a **Usuarios** → **"Nuevo Usuario"**
2. Completa: nombre, email, contraseña y rol
3. Haz clic en **"Crear Usuario"**

> **Importante:** Comparte la contraseña inicial con el usuario de forma segura. El sistema no envía correo de bienvenida automáticamente.

### 16.3 Editar, Activar/Desactivar y Eliminar

- **Editar:** Haz clic en → modifica nombre, email, contraseña o rol → Guardar
- **Desactivar:** Edita el usuario → desmarca "Activo" → Guardar (el usuario no podrá acceder)
- **Eliminar:** Haz clic en → confirma (el historial de sus documentos se conserva)

### 16.4 Restablecer el 2FA de un Usuario

Si un usuario perdió su teléfono:

1. Edita el usuario en **Usuarios**
2. Busca la opción **"Limpiar 2FA"** o **"Restablecer Autenticación"**
3. Confirma

La próxima vez que inicie sesión, configurará un nuevo 2FA desde cero.

### 16.5 Preguntas Frecuentes — Usuarios

**¿Cuántos usuarios puedo tener?**
No hay un límite técnico. Depende de tu plan con Gridbase.

**¿Un usuario puede cambiar su propio rol?**
No. Solo los administradores pueden cambiar roles.

---

## 17. API Pública — Llaves de Acceso

### ¿Qué es la API Externa?

La **API Externa** permite que otras aplicaciones o sistemas de tu empresa se conecten a Gridbase Bills para crear facturas, consultar clientes, etc., sin entrar al panel web.

**Casos de uso:** POS, tienda en línea, ERP, sistemas internos.

> **Nota:** Para **Administradores y Contadores**. El uso técnico de la API requiere conocimientos de programación.

### 17.1 Crear una Nueva API Key

1. Ve a **API** o **Integraciones** → **"API Keys"**
2. Haz clic en **"Nueva API Key"**
3. Completa:

| Campo | ¿Qué Escribir? |
|---|---|
| **Nombre** | Descriptivo: ej. "Sistema POS Tienda", "Integración WooCommerce" |
| **Permisos** | Los que necesita esta integración |
| **Límite de Velocidad** | Requests por minuto (default: 60, máx: 1000) |
| **Fecha de Expiración** | Opcional; vacío = nunca expira |

#### Permisos Disponibles

| Permiso | ¿Qué Permite? |
|---|---|
| `invoices.read` | Consultar y listar facturas |
| `invoices.create` | Crear y actualizar facturas |
| `quotes.read` | Consultar cotizaciones |
| `quotes.create` | Crear cotizaciones |
| `quotes.convert` | Convertir cotizaciones en facturas |
| `clients.read` | Consultar clientes |
| `clients.create` | Crear clientes |

> **Importante:** El **token completo** se muestra **UNA SOLA VEZ** al crearlo. Cópialo y guárdalo inmediatamente. Si lo pierdes, deberás regenerar la llave.

### 17.2 Usar la API

Todas las llamadas van a:
```
https://bills.tudominio.com/api/v1/...
```

With the header:
```
Authorization: Bearer tu_api_key
```

**Endpoints principales:**

| Método | Endpoint | Permiso |
|---|---|---|
| GET | `/api/v1/invoices` | `invoices.read` |
| POST | `/api/v1/invoices` | `invoices.create` |
| GET | `/api/v1/invoices/{id}` | `invoices.read` |
| GET | `/api/v1/quotes` | `quotes.read` |
| POST | `/api/v1/quotes` | `quotes.create` |
| POST | `/api/v1/quotes/{id}/convert` | `quotes.convert` |
| GET | `/api/v1/clients` | `clients.read` |
| POST | `/api/v1/clients` | `clients.create` |

### 17.3 Ver Logs de Uso

En la lista de API Keys, haz clic en el ícono junto a la llave para ver las últimas 50 llamadas: endpoint, IP, código de respuesta, tiempo de respuesta y fecha.

### 17.4 Regenerar o Revocar una API Key

- **Regenerar:** Haz clic en "Regenerar" → el token antiguo deja de funcionar inmediatamente → actualiza todas las apps que lo usen
- **Revocar:** Haz clic en → confirma → cualquier app con esa key recibirá error 401

---

## 18. Monedas y Tipos de Cambio

### Monedas Soportadas

Gridbase Bills soporta múltiples monedas: **DOP**, **USD**, **EUR** y otras monedas principales.

### Tipo de Cambio Automático

Al crear una factura en moneda extranjera:
1. El sistema consulta automáticamente el **tipo de cambio actual**
2. Lo registra en la factura para referencia futura
3. Lo usa en los reportes DGII (que siempre se presentan en DOP)

### Tipo de Cambio Manual

Si necesitas un tipo de cambio diferente al automático:
1. Al crear la factura, busca el campo **"Tipo de Cambio"**
2. Escribe el valor manualmente

> **Consejo:** Para los reportes DGII se usa el tipo de cambio registrado al momento de crear la factura, no el del día de presentación del reporte.

---

## 19. Solución de Problemas Frecuentes

### 19.1 El Correo Electrónico No Llega al Cliente

**Pasos para diagnosticar:**

1. **Verifica el email del cliente:** Sin espacios, formato correcto
2. **Revisa la carpeta de spam del cliente:** El correo puede estar ahí la primera vez
3. **Prueba el SMTP:** Ve a Configuración → Correo → escribe tu propio email en "Correo de Prueba" → Enviar Prueba
4. **Usa el diagnóstico:** Haz clic en "Diagnosticar SMTP" para detectar la configuración correcta
5. **Reenvía la factura:** Ábrela → haz clic en "Enviar"

> **Consejo:** En hosting cPanel, prueba: Servidor = `localhost` o `mail.tudominio.com`, Puerto = `25`, sin cifrado.

---

### 19.2 El Link de Pago No Funciona

| Causa | Solución |
|---|---|
| El link expiró | Regenera el link desde la factura |
| La factura ya fue pagada | No se necesita link; está saldada |
| La factura fue cancelada | No se puede pagar facturas canceladas |
| Credenciales PayPal incorrectas | Ve a Configuración → PayPal y verifica Client ID y Secret |
| Modo PayPal incorrecto | Usa "live" en producción, "sandbox" solo para pruebas |

---

### 19.3 La Factura Electrónica Fue Rechazada por la DGII

1. Abre la factura rechazada y lee el mensaje de error

**Errores comunes:**

| Error | Causa | Solución |
|---|---|---|
| "RNC del comprador inválido" | RNC incorrecto del cliente | Corrige el RNC en el perfil del cliente |
| "Certificado inválido o vencido" | Certificado expirado | Renueva el certificado con la DGII |
| "Monto excede límite para FC Consumo" | E32 supera RD$250,000 | Usa E31 con RNC del cliente |
| "Error de conexión / Timeout" | DGII temporalmente no disponible | Espera 15 min y usa "Reintentar" |

2. Corrige el problema
3. Haz clic en **"Procesar e-CF"** o **"Reintentar DGII"**

---

### 19.4 El RNC del Cliente No se Encuentra en la Búsqueda Automática

- Si el RNC es correcto, escribe el nombre manualmente
- Verifica en dgii.gov.do → "Consultas de RNC" que el número exista

---

### 19.5 El Sistema Dice que el Certificado DGII es Inválido

1. Ve a Configuración → DGII
2. Verifica que el archivo esté subido y la contraseña sea correcta
3. Confirma que el certificado no esté vencido
4. Si persiste: descarga de nuevo el .p12 de la fuente original, súbelo de nuevo y contacta al soporte

---

### 19.6 Una Factura Recurrente No se Generó

1. Ve a **Recurrentes** → abre la suscripción
2. Verifica que el estado sea **"Activa"**
3. Revisa la **"Próxima Fecha de Emisión"** — si ya pasó, usa **"Generar Ahora"**

> **Nota:** Las facturas recurrentes se generan una vez al día. Si estaba pausada, puede que aún no sea el momento.

---

### 19.7 Error al Iniciar Sesión

| Mensaje | Causa | Solución |
|---|---|---|
| "Credenciales inválidas" | Email o contraseña incorrecta | Verifica mayúsculas/minúsculas; pide reset al admin |
| "Código 2FA incorrecto" | Código expirado o reloj desincronizado | Sincroniza el reloj del teléfono; espera el siguiente código |
| "Sesión de pre-autenticación expirada" | Cerró el navegador entre pasos | Vuelve al login e inicia desde el principio |

---

## 20. Glosario de Términos

| Término | Definición |
|---|---|
| **2FA** | Autenticación en Dos Factores. Sistema de seguridad que requiere un segundo código además de la contraseña |
| **ACECF** | Aprobación Comercial de e-CF. Respuesta formal de aprobación o rechazo de un e-CF recibido de un proveedor |
| **API** | Interfaz de Programación de Aplicaciones. Permite que otros sistemas se conecten a Gridbase Bills |
| **Borrador** | Estado inicial de un documento que no ha sido enviado ni procesado |
| **CSV** | Formato de archivo de texto compatible con Excel (valores separados por coma) |
| **DGII** | Dirección General de Impuestos Internos. Entidad del gobierno dominicano que regula los impuestos |
| **e-CF** | Comprobante Fiscal Electrónico. Factura en formato XML firmado digitalmente y enviado a la DGII |
| **e-NCF** | Número de Comprobante Fiscal Electrónico. El código único asignado por la DGII a cada e-CF (ej: `E3100000001`) |
| **ITBIS** | Impuesto a las Transferencias de Bienes Industrializados y Servicios. El IVA dominicano (normalmente 18%) |
| **KPI** | Indicador Clave de Rendimiento. Métricas de negocio mostradas en el dashboard |
| **NCF** | Número de Comprobante Fiscal. Sistema anterior de comprobantes físicos (reemplazado por e-NCF) |
| **PDF** | Formato de Documento Portátil. Formato estándar no editable para compartir documentos |
| **PIN** | Número de Identificación Personal. Código de 6 dígitos para acceso rápido al sistema |
| **RNC** | Registro Nacional de Contribuyentes. Número fiscal de empresas en República Dominicana (9 dígitos) |
| **Cédula** | Documento de identidad dominicano (11 dígitos). Equivalente al RNC para personas físicas |
| **SMTP** | Protocolo estándar para enviar correos electrónicos |
| **TOTP** | Contraseña de Un Solo Uso Basada en Tiempo. Algoritmo que usa el 2FA para generar códigos que cambian cada 30 segundos |
| **XML** | Lenguaje de Marcado Extensible. Formato técnico en que se crean los e-CF para la DGII |

---

## 21. Referencia Rápida de Estados

### Estados de Facturas

| Estado | ¿Puede Editarse? | ¿Va al Reporte DGII? |
|---|---|---|
| **Borrador** | Sí | No |
| **Enviada** | Sí | Sí |
| **Vista** | Sí | Sí |
| **Pago Parcial** | Parcial | Sí |
| **Pagada** | No recomendado | Sí |
| **Vencida** | Sí | Sí |
| **Cancelada** | No | No |

### Estados de Cotizaciones

| Estado | ¿Puede Convertirse a Factura? |
|---|---|
| **Borrador** | Sí |
| **Enviada** | Sí |
| **Vista** | Sí |
| **Aceptada** | Sí |
| **Rechazada** | Sí (técnicamente) |
| **Vencida** | Sí (técnicamente) |
| **Convertida** | No (ya fue convertida) |

### Estados de e-CF (DGII)

| Estado | Significado | Acción |
|---|---|---|
| `draft` | No procesado | Usar "Procesar e-CF" |
| `signed` | XML firmado, pendiente de enviar | El sistema enviará automáticamente |
| `pending` | Esperando respuesta DGII | Esperar o "Verificar Estado" |
| `approved` | Aprobado | Ninguna |
| `rejected` | Rechazado | Corregir error y reintentar |
| `contingency` | Error temporal | Reintentar cuando haya conexión |

### Estados de Suscripciones Recurrentes

| Estado | ¿Genera Facturas? |
|---|---|
| **Activa** | Sí |
| **Pausada** | No |
| **Cancelada** | No |
| **Completada** | No (alcanzó el límite) |

### Estados de Facturas Recibidas

| Estado | Significado |
|---|---|
| **Pendiente** | Recibida, esperando tu decisión |
| **Aprobada** | Confirmaste la recepción del bien/servicio |
| **Rechazada** | Rechazaste el e-CF con motivo registrado |

---

## 22. Apéndice: Tipos de Comprobantes Fiscales Electrónicos (e-CF)

### E31 — Factura de Crédito Fiscal

**¿Para qué sirve?** Para vender a empresas o personas que van a deducir el ITBIS.

**¿Cuándo usarlo?**
- Cuando tu cliente es una empresa con RNC
- Cuando la transacción supera RD$250,000 (obligatorio)
- Servicios B2B (empresa a empresa)

**Requiere:** RNC del cliente (obligatorio). Sin límite de monto.

---

### E32 — Factura de Consumo

**¿Para qué sirve?** Para ventas al consumidor final que no va a deducir el ITBIS.

**¿Cuándo usarlo?**
- Ventas a personas naturales
- Ventas donde el cliente no necesita deducir ITBIS
- Montos menores a RD$250,000 sin identificación del comprador

> **Importante:** Una Factura de Consumo E32 **NO puede superar RD$250,000** sin el RNC o cédula del comprador. La DGII rechazará el comprobante.

---

### E33 — Nota de Débito

**¿Para qué sirve?** Para **aumentar** el monto de una factura ya emitida.

**¿Cuándo usarlo?**
- Olvidaste incluir un cargo adicional
- Ajustar errores de precio a favor de tu empresa
- Cobrar penalidades sobre una factura existente

**Requiere:** Número e-NCF de la factura original + código de modificación.

---

### E34 — Nota de Crédito

**¿Para qué sirve?** Para **reducir** el monto de una factura ya emitida.

**¿Cuándo usarlo?**
- Devolución de productos
- Descuentos otorgados después de facturar
- Anulación parcial de una factura
- Corrección de errores en precio a favor del cliente

**Requiere:** Número e-NCF de la factura original + código de modificación:

| Código | Motivo |
|---|---|
| `01` | Anulación de comprobante fiscal |
| `02` | Corrección en montos o datos |
| `03` | Descuento o bonificación |
| `04` | Devolución de mercancías |
| `05` | Permuta |
| `06` | Crédito incobrable |
| `07` | Errores en el tipo de comprobante |

---

### E41 — Comprobante de Compras

**¿Para qué sirve?** Para registrar compras a proveedores que **no emiten comprobantes fiscales** (sector informal).

**¿Cuándo usarlo?**
- Compras a agricultores o ganaderos
- Compras a pequeños productores sin RNC
- Servicios de personas sin factura fiscal

Aparece en el **Reporte 606**.

---

### E43 — Gastos Menores

**¿Para qué sirve?** Para documentar gastos pequeños donde no se obtiene comprobante fiscal.

**¿Cuándo usarlo?**
- Compras de oficina en efectivo de bajo valor
- Gastos de transporte menores
- Consumibles menores

Aparece en el **Reporte 606**.

---

### E44 — Regímenes Especiales de Tributación

**¿Para qué sirve?** Para facturar a entidades que operan bajo regímenes fiscales especiales de la DGII.

**¿Cuándo usarlo?**
- Ventas a empresas en Zona Franca
- Ventas a entidades con exenciones de ITBIS por régimen especial

---

### E45 — Gubernamental

**¿Para qué sirve?** Para facturar al **gobierno dominicano** o entidades del sector público.

**¿Cuándo usarlo?**
- Ventas a ministerios, ayuntamientos, hospitales públicos
- Cualquier entidad del Estado dominicano

Nota: El gobierno puede estar exento de ITBIS en muchas transacciones.

---

### ¿Qué Tipo de e-CF Usar? — Árbol de Decisión

```
¿A quién le vendes?
│
├─→ Empresa con RNC ──────────────────────→ E31 (Crédito Fiscal)
│
├─→ Persona sin RNC
│ ├─→ Monto < RD$250,000 ──────────────→ E32 (Consumo)
│ └─→ Monto ≥ RD$250,000 ─────────────→ E32 CON cédula obligatoria
│
├─→ Sector Público / Gobierno ────────────→ E45 (Gubernamental)
│
├─→ Empresa de Régimen Especial ──────────→ E44 (Régimen Especial)
│
├─→ Proveedor sin comprobante fiscal ─────→ E41 (Compras)
│
└─→ Gasto pequeño sin factura ────────────→ E43 (Gastos Menores)

¿Modificar una factura ya emitida?
├─→ Aumentar monto → E33 (Nota de Débito)
└─→ Reducir monto → E34 (Nota de Crédito)
```

---

## Soporte y Contacto

Si tienes alguna pregunta no cubierta en este manual, comunícate con el equipo de Gridbase Digital Solutions:

- **Sitio web:** gridbase.com.do
- **Email:** soporte@gridbase.com.do
- **WhatsApp:** Disponible en el sitio web

---

*Gridbase Bills — Documentación para Clientes v1.0 | Junio 2026*
*© 2026 Gridbase Digital Solutions. Todos los derechos reservados.*
