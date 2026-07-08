# Historial de Versiones (Changelog) - Gridbase Bills

A continuación se presenta el registro de evolución y mejoras de la plataforma Gridbase Bills. Cada versión detalla los avances e incorporaciones del sistema, orientados a brindar el servicio de facturación y cumplimiento fiscal de mayor robustez, estabilidad y facilidad de uso del mercado.

## Gridbase Bills v3.2.0 (Gestión Multi-Dispositivo para Acceso PIN) (2026-07-08)

* Seguridad: Implementación de la gestión multi-dispositivo para el inicio de sesión rápido por PIN. Ahora el usuario puede autorizar y mantener hasta 3 dispositivos simultáneos (como su computadora, celular y tablet) sin que se invaliden entre sí al ingresar.
* Seguridad: Detección inteligente del tipo de dispositivo (Android, iOS, Windows, Mac, Linux) a partir del navegador de uso en el momento del registro.
* Seguridad: Depuración automática del dispositivo más antiguo en desuso al intentar registrar un cuarto dispositivo, asegurando el límite máximo de 3 autorizaciones.

## Gridbase Bills v3.1.0 (Integración de Tasas de Cambio BPD y RNC en Facturas de Consumo) (2026-07-08)

* Facturas: Implementación de campos dinámicos para introducir Nombre/Razón Social y RNC de forma directa en las Facturas de Consumo (Tipo 32) sin necesidad de registrar previamente un cliente permanente.
* Moneda: Integración directa del API Sandbox del Banco Popular Dominicano (BPD) como proveedor principal de tasas de cambio (venta) para conversiones precisas de DOP, USD y EUR en cobros y pasarelas de pago.
* Moneda: Sistema de redundancia (fallback) automático que cambia al API internacional si el servidor de BPD está fuera de servicio.
* Moneda: Incorporación de un nuevo widget visual en el Dashboard administrativo que muestra las tasas de venta oficiales para el Dólar (USD) y el Euro (EUR) de BPD actualizadas diariamente.


## Gridbase Bills v3.0.0 (Personalización de Marca, Notificaciones y Consumo Dinámico) (2026-07-07)

* Facturas: Requerir cliente obligatorio para todos los comprobantes, excepto para Facturas de Consumo (Tipo 32) que gozan de mayor flexibilidad.
* Facturas: Habilitar la creación simplificada de facturas rápidas asociándolas por defecto a 'Consumidor Final' si no se asigna un cliente.
* Notificaciones: Implementación del menú dinámico de notificaciones rápidas en la cabecera para visualizar facturas pendientes y estados oficiales.
* PDF de Factura: Incorporación de opciones de personalización avanzadas para alinear los colores del PDF con la identidad de marca del negocio.
* Panel de Control: Sincronización de etiquetas financieras del ITBIS acumulado para el reporte 607.
* Diseño: Optimización de la visibilidad y legibilidad de las etiquetas e-CF para el modo oscuro.
* Diseño: Refinamiento de la paleta de colores del modo oscuro utilizando tonos grises y carbón para una estética más ejecutiva.
* Facturas: Añadido soporte para cancelación rápida de facturas en la plataforma, manteniéndolas fuera de las estadísticas financieras.
* Facturación Electrónica: Validación avanzada de RNC en códigos QR para asegurar el correcto direccionamiento fiscal.

## Gridbase Bills v2.8.1 (Corrección de Desplazamiento en Android y WhatsApp) (2026-06-29)

* Reporte 607: Optimización matemática para restar descuentos aplicados y reflejar el monto neto gravado real.
* Soporte Móvil: Estabilización del desplazamiento de página en dispositivos Android para una navegación más suave.
* WhatsApp: Soporte mejorado para emparejamiento interactivo de cuentas de WhatsApp con Evolution API.
* WhatsApp: Integración del controlador de comunicación Evolution API con soporte de múltiples servidores.

## Gridbase Bills v2.8.0 (Generación de Reportes de Impuestos 606 y 607) (2026-06-27)

* Diseño Móvil: Optimización de márgenes y espacios de página en celulares para evitar recortes con la barra de navegación.
* Diseño Móvil: Habilitación de desplazamiento nativo para dispositivos iOS, logrando fluidez de lectura.
* Diseño Móvil: Ajuste de espaciados inferiores en el área principal para mejorar la interacción con los botones inferiores.
* Diseño Móvil: Tablas de totales y conceptos adaptadas al formato responsivo para pantallas táctiles.
* Sistema: Limpieza y optimización del Service Worker (sw.js) para recargas inmediatas del sistema en iOS PWA.
* Diseño Móvil: Refinamiento visual en vistas móviles de facturas, cotizaciones, suscripciones y perfiles de clientes.
* Reportes DGII: Inclusión de banner explicativo en el reporte 607 y validación exitosa del formato 606.
* Reportes DGII: Exclusión inteligente de facturas de consumo (tipo 32) del reporte 607 para mantener la consistencia fiscal.
* Reportes DGII: Ajuste de estructura del archivo de texto según especificaciones del reporte 607.
* Reportes DGII: Estandarización de archivos de texto 606 y 607 con la incorporación de la columna de Bonos.
* Facturación Electrónica: Procesamiento automático de facturas electrónicas e-CF originadas a través de contratos recurrentes.
* Soporte Móvil: Actualización de metadatos del navegador para garantizar compatibilidad completa en pantalla completa de iPhone.
* Configuración: Ajuste de rendimiento en el almacenamiento de parámetros en el navegador cliente.
* Sistema: Optimización del gestor de actualizaciones del navegador para cargas limpias y veloces de la aplicación.
* Sistema: Carga estática de módulos optimizada para garantizar la máxima velocidad de ejecución en todo tipo de navegadores.
* Sistema: Control automático de caché basado en la versión física de los archivos del servidor para actualizaciones transparentes.
* Suscripciones: Facturación e-CF manual y automática integrada dentro del módulo de suscripciones recurrentes.
* Sistema: Sincronización e invalidación de caché para mostrar los paneles de control siempre actualizados.
* Panel de Control: Consolidación del contador de facturas e incorporación del widget analítico de ITBIS acumulado.
* Panel de Control: Refinamiento en los cálculos para reportar ganancias netas excluyendo impuestos acumulados.
* Facturación Electrónica: Auto-renovación inteligente de vigencia de certificados DGII expirados.

## Gridbase Bills v2.7.1 (Mensajes de Error Reales de la DGII) (2026-06-26)

* Facturación Electrónica: Visor interactivo de retroalimentación de la DGII en pantalla para agilizar cualquier aclaratoria.

## Gridbase Bills v2.7.0 (Soporte para Impresoras de Tickets Térmicos) (2026-06-14)

* Ticket Térmico: Inclusión elegante del nombre comercial debajo del logotipo en impresiones de recibos térmicos.
* Ticket Térmico: Ajuste de dimensiones máximas para logotipos impresos en tickets térmicos.
* Ticket Térmico: Conversión monocromática automática de logotipos a color para una impresión de alta nitidez.
* Ticket Térmico: Integración del logotipo principal del sistema en el diseño del ticket térmico.
* Ticket Térmico: Cálculo de altura de papel dinámico y adaptable a la cantidad de conceptos para optimizar insumos.
* Ticket Térmico: Márgenes de ticket configurados a la medida para aprovechar el ancho del papel.
* Ticket Térmico: Rediseño visual del ticket térmico con código QR centrado y texto perfectamente alineado.
* Facturas: Selección intuitiva de plantilla de impresión (Factura A4 estándar vs Ticket Térmico).

## Gridbase Bills v2.6.2 (Auto-reintento con Token Refrescado) (2026-06-09)

* Facturación Electrónica: Re-autenticación y obtención de token de seguridad DGII automatizada en caso de expiración de sesión.

## Gridbase Bills v2.6.1 (Rediseño de Pantalla de Acceso para Celulares) (2026-06-08)

* Permisos: Acceso al módulo de facturación electrónica y estados de la DGII para usuarios con rol de Contador.
* Clientes: Interfaz de perfil de clientes optimizada para búsquedas y cargas rápidas de datos.
* Acceso: Incorporación de logotipo de la empresa en la pantalla de inicio de sesión.
* Diseño Móvil: Tarjetas compactas modernas diseñadas para ver listados de facturas cómodamente en celulares.
* Diseño Móvil: Pantalla de detalles de facturas adaptada con diseño limpio y optimizado para pantallas pequeñas.
* Acceso: Garantizar la compatibilidad de bases de datos para el inicio de sesión rápido mediante PIN.
* Acceso: Centrado perfecto de elementos y optimización de espacios en el formulario de login móvil.
* Acceso: Alineación estética y responsiva para el acceso móvil.
* Acceso: Rediseño premium de la pantalla de login con banner de bienvenida y formulario flotante.
* Revert "revert: undo móvil / celular bitácorain redesign changes"
* Revert: undo móvil / celular bitácorain redesign changes
* Optimización de: móvil / celular bitácorain hero desborde visual and visibility
* Asa

## Gridbase Bills v2.6.0 (Consola de Simulación de Certificación de la DGII) (2026-06-06)

* Facturación Electrónica: Cálculo de montos de comprobantes exentos (Tipo 44) optimizado mediante lectura directa del XML firmado.
* Facturas: Sincronización horaria para el registro exacto de notas de crédito en la interfaz.
* Facturas: Notificación interactiva de procesamiento al guardar facturas para una mejor experiencia de usuario.
* Facturación Electrónica: Validación avanzada de consistencia de documentos antes de la emisión de notas de crédito referenciadas.
* Facturación Electrónica: Estandarización de códigos de modificación en notas de crédito para coincidir con normas oficiales.
* Facturación Electrónica: Estandarización del formato telefónico del emisor para un cumplimiento perfecto ante la DGII.
* Facturación Electrónica: Depuración de caracteres especiales en teléfonos de emisor para una validación impecable.
* Simulaciones: Carga secuencial optimizada para la consola de simulación de facturación electrónica.
* Sistema: Estabilización del servicio de verificación de conexión en tiempo real.
* Facturación Electrónica: Inclusión del simulador de emisión e-CF para el Paso 4 de homologación en el panel administrativo.
* Facturación Electrónica: Incorporación del entorno de Certificación en el asistente de configuraciones generales.

## Gridbase Bills v2.5.4 (Alineación de Plantillas PDF según Normativas) (2026-06-03)

* PDF: Cabecera de PDF rediseñada con datos de emisor y comprobante distribuidos estéticamente.
* PDF: Cabecera con fondo de color corporativo conservando la organización recomendada por la DGII.
* PDF: Distribución balanceada de datos del emisor y tipo de comprobante en la cabecera.
* PDF: Gestión optimizada de nombres y títulos de comprobantes electrónicos en plantillas imprimibles.
* PDF: Organización del diseño de factura PDF adaptado minuciosamente a la guía visual oficial de la DGII.
* PDF: Código QR optimizado y reposicionado para mejor escaneo en facturas.
* PDF: Código QR alineado a la derecha conservando la legibilidad de la información descriptiva del emisor.
* PDF: Código QR posicionado según el modelo de referencia oficial de la DGII.

## Gridbase Bills v2.5.3 (Bitácoras de Auditoría DGII en Administración) (2026-06-02)

* Facturación Electrónica: Dirección de consulta de código QR configurada dinámicamente según el entorno.
* Facturación Electrónica: Zona horaria predeterminada ajustada a Santo Domingo para todos los comprobantes electrónicos.
* Facturación Electrónica: Conexión optimizada al entorno de pruebas de la DGII para agilizar la pre-certificación de comprobantes.
* Facturación Electrónica: Optimización de endpoints y lectura de estados de respuesta de recepción de la DGII.
* Auditoría: Panel de control visual para consultar el historial de comunicaciones con la DGII.
* Auditoría: Bitácora detallada de transacciones DGII para un registro íntegro de solicitudes firmadas.

## Gridbase Bills v2.5.2 (Ajustes de Modelos de QR para la DGII) (2026-06-01)

* PDF: Ajuste de proporciones físicas del código QR de acuerdo con los estándares oficiales.

## Gridbase Bills v2.5.1 (Acceso Rápido con PIN de 6 dígitos) (2026-05-30)

* Acceso: Limpieza de estilos CSS de inicio de sesión para un comportamiento responsivo libre de conflictos.
* Acceso: Implementación del inicio de sesión ultra rápido por PIN de 6 dígitos para dispositivos táctiles.
* Facturas: Botón de descarga rápida de archivo PDF directo desde la tabla de facturas.

## Gridbase Bills v2.5.0 (Aprobaciones Comerciales y Descargas de Consumo) (2026-05-29)

* Facturación Electrónica: Lectura de fecha de firma electrónica directamente desde el XML para mayor integridad en el código QR.
* Aprobaciones Comerciales: Sincronización de marcas de tiempo de aprobación comercial conforme a los lineamientos oficiales de la DGII.
* Aprobaciones Comerciales: Filtro inteligente para sincronizar únicamente los comprobantes requeridos en las pruebas vigentes de la DGII.
* Aprobaciones Comerciales: Ajuste de horas de aprobación para la consistencia técnica de las firmas.
* Aprobaciones Comerciales: Asignación automática de marcas de tiempo actuales para evitar rechazos por fechas desactualizadas.
* Aprobaciones Comerciales: Ajuste de dirección web del endpoint oficial de aprobación comercial.
* Aprobaciones Comerciales: Dirección del servidor de la DGII configurada con las mayúsculas oficiales para compatibilidad total.
* Aprobaciones Comerciales: Implementación del constructor de XML y firma para el envío de aprobaciones comerciales de compras (ACECF).
* Optimización de(dgii): CodigoSeguridadeCF from firmaValue + reuse same FC file - Extract from firmaValue (not DigestValue) per DGII válidoation - Phase 4 reuses exact same signed FC from Phase 3 to ensure firma coincidir - añadired warning bitácora when FC is generard without Phase 3 RFCE context
* Optimización de(dgii-phase4): FC<250k must be uploaded to portal, not sent via API - Phase 4 generars signed XML files for manual upload to DGII portal - añadired download dirección de red: /api/dgii/certificadoification/download-fc250k/{encf} - interfaz gráfica shows orange 'Generar XML' button for Phase 4 instead of 'Ejecutar' - Phase 4 exclinterfaz gráficard from 'Ejecutar Todos' since it reqinterfaz gráficares manual portal upload - respuesta includes download_enlace for easy acceso to generard files
* Optimización de(dgii-rfce): optimización de RFCE XML estructura per RFCE 32 v.1.0.xsd - eliminar FechaHoraFirma (not in RFCE XSD, caused inválido child element error) - añadir CodigoSeguridadeCF (reqinterfaz gráficared, first 6 chars of FC firma DigestValue) - Binterfaz gráficald and sign FC<250k e-CF first to extract security code - guardar signed FC<250k XMLs for portal file upload in certificadoification_fc250k/ - XSD estructura: RFCE > Encabezado > [IdDoc,Emisor,Comprador,Totales,CodigoSeguridadeCF] + firma

## Gridbase Bills v2.4.0 (API Pública Externa y Documentación Técnica) (2026-05-28)

* Optimización de(dgii): move NumeroCuentaPago/BancoPago to IdDoc per XSD - Per e-CF 47 v1.0.xsd lines 33-35, these are IdDoc children, NOT formularioaDePago children - Same estructura confirmed in e-CF 31 v1.0.xsd lines 37-39 - Also añadired FechaDesde/FechaHasta support in IdDoc - Cleaned up binterfaz gráficaldTablaformularioasPago to only handle formularioaPago/MontoPago - documentación at: Documentacion Facturacion Electronica/*.xsd
* Facturación Electrónica (DGII): strict 4-phase ordering for certificadoification tests - Phase 1: 31, 32>=250k, 41, 43, 44, 45, 46, 47 (sorted by type) - Phase 2: 33, 34 (Notes - depend on Phase 1 facturas) - Phase 3: RFCE summaries (fc.dgii.gov.do) - Phase 4: FC<250k individual (fc.dgii.gov.do) - Phase separator rows with colored cabeceras in the interfaz gráfica tabla - Phase transition bitácoraging in console during Ejecutar Todos - Type names optimizared (Regimenes Especiales, pagos al Exterior)
* Optimización de(dgii): rewrite DescuentosORecargos to use indexed fields from XLSX - Was looking for non-existent TipoAjusteGlobal field - Now uses TipoAjuste[n], MontoDescuentooRecargo[n], etc. - Supports múltiples discount/surcharge lines - E310000000011 now includes the 1500.00 discount that makes MontoGravadoI1 válido
* Optimización de(dgii): optimización de RFCE consulta in runSingle - strip (RFCE) sufoptimización de antes de searching - Handle RFCE sufoptimización de from interfaz listCases properly - Cache was flushed on server to optimización de HTTP 401
* Optimización de(dgii): regenerar JSON from XLSX with exact string valores - todos los valores now are exact strings from the DGII XLSX cells - eliminard todos los number formularioatoting (fmtPrice/fmtQty/fmtMoney/fmtDecimal now pass-through) - eliminard regex quoting hack from data loaders - There is NO consistent rule per type: each cell has its own precision - Verified: 25/25 test cases produce XML coincidiring XLSX valores exactly - New eNCFs from fresh XLSX download (28/05/2026)
* Optimización de(dgii): T33/T34 use 2-decimal prices and quantities, not 4dec/integer - T31/T32: PrecioUnitarioItem=4dec, CantidadItem=integer - T33/T34: PrecioUnitarioItem=2dec, CantidadItem=2dec - T41/T43: PrecioUnitarioItem=2dec, CantidadItem=2dec - T44-47: PrecioUnitarioItem=4dec, CantidadItem=2dec
* Facturación Electrónica (DGII): añadir individual test case execution buttons + type-aware formularioatoting + FC<250k optimización de - Each test case row now has a '▶ Ejecutar' button for individual runs - Shows phase info (Phase 1-4) per test case - FC<250k ECFs Phase 4 now routes to fc.dgii.gov.do - Type-aware formularioatoting: T41/43=2dec prices, T31-34=4dec+int qty, T44-47=4dec+2dec qty
* Optimización de(dgii): type-aware formularioatoting and FC<250k dirección de red - PrecioUnitarioItem: 4dec for T31-34/T44-47, 2dec for T41/T43 - CantidadItem: integer for T31-34, 2dec for T41-47 - FC<250k Phase 4 now sends to fc.dgii.gov.do (B2C channel) - optimiza cascade reinicio errors caused by formularioato miscoincidires
* Facturación Electrónica (DGII): implementar 4-phase certificadoification flow with RFCE support Phase 1: Base ECF types (31,32>=250k,41,43,44,45,46,47) -> ecf.dgii.gov.do Phase 2: Notes (33,34) -> ecf.dgii.gov.do Phase 3: RFCE summaries -> fc.dgii.gov.do (new RFCE XML constructor) Phase 4: FC<250k ECFs (32<250k) -> ecf.dgii.gov.do - añadired inline RFCE XML constructor with optimizar formularioato (no Dettodos losesconceptos) - Zero-pad TipoIngresos (01 not 1) for RFCE - Total: 29 test cases (25 ECF + 4 RFCE)
* Optimización de(dgii): optimización de decimal formularioatoting and test case ordering for certificadoification - PrecioUnitarioItem: always 4 decimals (40.0000 not 40.00) - CantidadItem: integer-safe (1 not 1.00, 23 not 23.00) - MontoTotal/MontoItem: always 2 decimals (95597.70 not 95597.7) - Pre-process JSON to preserve numérico strings antes de json_decode - Sort test cases: base types first, then 33/34 (referencia other eNCFs) - todos los ECF test cases go to ecf dirección de red (not RFCE dirección de red)
* Optimización de(dgii): optimización de 3 Crítico XSD issues in certificadoificationXmlconstructor - optimización de eNCF: JSON key 'ENCF' mapped to XML element 'eNCF' - añadir FechaHoraFirma at root level (reqinterfaz gráficared by todos los types) - añadir InformularioacionReferencia for types 33/34 (NCFModificado, CodigoModificacion) - Conditional field emission per type (FechaVencSecuencia, IndicadorMontoGravado, TipoIngresos) - reemplazar broken XSD válidoation with structural válidoation (DGII XSDs have internal errors) todos los 25 test cases pass structural válidoation
* Facturación Electrónica (DGII): añadir XSD válidoation for both production and certificadoification flows - Include todos los DGII XSD files in xsd/ directory - certificadoificationController válidoars XML against XSD antes de sending - DgiiTestinterfaz gráficaController diagnóstico válidoars against XSD + separate firma check - optimización de certificadoificationXmlconstructor: eliminar inoptimizar namespace (XSDs have no targetNamespace)
* Facturación Electrónica (DGII): añadir dedicated certificadoification XML generator and test ejecutor - certificadoificationXmlconstructor binterfaz gráficalds XML directly from test JSON data - certificadoificationController with list/run-single/run-todos los dirección de reds - certificadoification panel interfaz gráfica in dgii-tests.js with test case tabla - Bypass factura model entirely for certificadoification tests
* Optimización de(dgii): re-intentar always regenerars XML from scratch instead of reusing antiguo cache
* Optimización de(dgii): wrap TelefonoEmisor in TablaTelefonoEmisor per XSD schema
* Mantenimiento: bump cache version to v=59 for configuración.js and app.js
* Facturación Electrónica (DGII): añadir test data preinicio button to auto-fill DGII certificadoification fields
* Facturación Electrónica (DGII): expand e-CF configuración with full emitter data, 10 NCF types, and certificado upload
* Optimización de: return factura_number in external convertTofactura 409 respuesta for self-healing state alinearment
* Optimización de: añadir nullable válidoation rules for optional external API fields
* Deajuste: añadir switcher bitácoraging
* Nueva función: añadir dynamic multi-tenant dominio mapping support in AppServiceProvider
* Mantenimiento: actualizar caché welcome view with app.js version 58
* Mantenimiento: clear browser cache for configuración and api-keys JS modules
* Nueva función: completar emoji limpieza and implementar interactive API bitácoras viewer
* Nueva función: store plain api key in DB, display in configuración panel and dynamic placeholders in api-docs
* Nueva función: añadir markdown documentación download button on api-docs page
* Diseño: api-docs visual redesign to coincidir Gridbase Bills brand line
* Nueva función: API privada externa + documentacion completa - API Keys, middleware auth/permisos/throttle, controladores externos, docs en /api-docs
* Nueva función: móvil / celular bitácorain with gradient banner, floating tarjeta formulario, iteléfono SE support
* Nueva función: 2FA redesigned with split bitácorain diseño de panttodos losa — left formulario panel + right security hero panel
* Optimización de: perfil tarjeta and avatar now use barra lateral hover/bg colores to blend with estilo visual
* Nueva función: customizable barra lateral hover color with injected style tag for:hover/:active states
* Nueva función: editabla bitácoraotipo and favicon enlaces in configuración with live preview and instant apply
* Nueva función: customizable barra lateral colores (bg + text) from configuración, eliminar bitácoraotipo capsule
* Optimización de: bump cache versions to v56 for app.js and panel de control.js
* Nueva función: dynamic page title 'CompanyName - Bills' from configuración
* Optimización de(panel de control): hide panel de control cabecera on móvil / celular — topbar already shows greeting
* Optimización de(panel de control): eliminar pie de página, full responsive for iteléfono 14 Pro Max + iteléfono SE
* Feat(panel de control): v5 redesign — mimics referencia interfaz gráfica with 3 KPI tarjetas, activity tabla, cashflow chart, overdue grid
* Feat(panel de control): completar premium redesign v4 — hero banner, KPI strip, chart tarjeta, two-column diseño de panttodos losa
* Convert brand pie de página to a optimización deed white bar alineared with the desktop barra lateral and móvil / celular tab bar, and añadir page bottom márgenes
* Optimize panel de control pañadirings and móvil / celular diseño de panttodos losa rendering, añadir overdue list móvil / celular tarjeta conversion, añadir clean branding pie de página linked to gridbase.com.do
* Optimize panel de control diseño de panttodos losa, implementar dynamic planotening of workspace-panel on panel de control navegación / rutas, adjust bottom tablas side-by-side columns alinearment using panel de control-grid, añadir dynamic greeting, strip wave emojis, and añadir tarjeta micro-animations
* Optimización de panel de control trendBadge referenciaError by declaring it antes de template evaluation, bump script versions to v54 for actualizar cachéing
* Implementar gorgeous ApexCharts area chart coincidiring dynamic trend tarjeta mockup and clean old SVG interactive methods, bump script versions to v53 for actualizar cachéing
* Lock SVG chart height to 220px in CSS and render flinterfaz gráficad dynamictodos losy based on container.offsetWidth, and bump version parameter to v52 for immediate actualizar cachéing
* Optimización de panel de control diseño de panttodos losa tags, enlarge interactive SVG chart height to 280px, and bump script versions to v51 for immediate actualizar cachéing
* Implementar premium interactive SVG analytics chart for monthly revenue and expenses on panel de control
* Nueva función: implementar secure mandatory 2FA TOTP authentication flow with QR configuración wizard for users

## Gridbase Bills v2.3.0 (Asistente de Configuración Inicial y Modo Oscuro) (2026-05-27)

* Mantenimiento: increment cache-busting version parameter to force reload configuración Support module
* Configuración: añadir Support tab and secure sistema reinicio database functionality
* Optimización de: asegurar compatibilidad de ERR_TOO_MANY_REDIRECTS on cPanel reverse proxy (check X-Forwarded-Proto)
* Nueva función: añadir PDF branding customization (colores, bitácoraotipo, pie de página toggle) in configuración > diseño
* Nueva función: añadir dynamic multi-currency support (USD, EUR, DOP) to facturas and cotizacións with cached rates
* Implementarada Gestion de UsuariOS para cuentas de Administrador y Soporte
* Implementarado rols y Permisos, Gastos y Exportacion Excel
* Customize billing correo electrónicos for Credit and Debit Notes, hiding pago button for Credit Notes
* Bypass SPA link click interceptor on report downloads by setting target _blank
* Optimización de export txt formularioato by using direct fetch instead of JSON-interpretación App.api
* Optimización de STR_PAD_LEFT referencia error in reports module export using native padStart
* Implementar interactive DGII Reports 606 (compras) and 607 (ventas) with oficial pipe-separated TXT downloads
* Optimización de credit note formulario valores, guardar details on actualizar, and añadir issueCreditNote shortcut
* Nueva función: DGII connection estado pill in topbar + dual deploy (VPS + cPanel)
* Nueva función: añadir dynamic greeting next to search bar
* Nueva función: configuración wizard, bitácoraotipo capsule backdrop, LONGTEXT migration, CI/CD GitHub Actions deploy
* Refactor panel de control Diseño visual: eliminar emojis, optimize watermark background icons, vertical canvas linear gradients, and view-todos los micro-interactions
* Nueva función: implementar completar responsive dark mode with persistence and topbar toggle switcher
* Diseño: eliminar emojis and reemplazar with clean SVGs in bulk actions
* Nueva función: implementar bulk actions for facturas (edicion masiva)
* Nueva función: añadir capturar todo /fe/* route to diagnóstico and handle any DGII enlace variation
* Optimización de: use Route:any for todos los DGII conexión automática (webhook) dirección de reds to asegurar compatibilidad de MethodNottodos losowed on any HTTP method
* Optimización de: válidoacioncertificadoificado now returns proper auth token per DGII spec (token/expira/expedido)
* Optimización de: añadir case-variant routes and GET/POST support for válidoacioncertificadoificado dirección de red
* Nueva función: rewrite ACECF respuesta to coincidir ACECF v1.0.xsd with proper Dettodos loseAprobacionComercial, interpretación, and signing
* Optimización de: use case-insensitive regex to optimizarly coincidir RNCEmisor from DGII XML

## Gridbase Bills v2.2.3 (Adjuntos de PDF en Mensajes de WhatsApp) (2026-05-26)

* Optimización de: añadir XML deajuste bitácoraging, envío en bloque support, and regex interpretación for DGII conexión automática (webhook) reception
* Optimización de: rewrite ARECF XML to coincidir oficial DGII XSD schema (Dettodos loseAcusedeRecibo, Version, RNCEmisor, Estado, FechaHoraAcuseRecibo)
* Nueva función: adjuntar PDF de factura/cotizacion en WhatsApp - Upload PDF a Media API de WhatsApp - entornoiar como documento con caption - Ftodos losback a texto si ftodos losa el upload - Aplica a facturas, cotizaciones y reactuales
* Nueva función: entornoiar facturas tambien por WhatsApp - Confirmaciones de pago por WhatsApp - Facturas reactuales por WhatsApp - Soporte WhatsApp-only para clientees sin correo electrónico - interfaz gráfica actualizada con icono WhatsApp y toasts

## Gridbase Bills v2.2.2 (Enlaces de QR Dinámicos y Columnas de Estado) (2026-05-25)

* Nueva función: añadir DGII estado column to factura list with color-coded badges
* Optimización de: mark e-CF as 'aceptadoed' immediately when DGII returns trackId instead of leaving as 'pending'
* Optimización de: estándarize todos los DGII certificadoification enlaces to lowercase 'certificadoecf'
* Optimización de: enlace-encode CodigoSeguridad in QR enlace to handle +/= chars from base64 firmaValue
* Optimización de: omit RncComprador from QR enlace for types 43 (Gastos Menores) and 47 (pagos al Exterior) which have no buyer
* Optimización de: QR enlace ruta now dynamic based on dgii_entorno setting (testing=certificadoecf, production=ecf)
* Diseño: centrar código QR and text below it in PDF
* Optimización de: QR enlace uses certificadoecf not testecf - confirmed working with DGII portal
* Optimización de: Read FechaHoraFirma from signed XML for QR enlace - optimiza QR not válidoating
* Optimización de: DGII Step 5 optimizarions - QR enlaces PascalCase, expiry 2028, NCF mod in RI, security code below QR

## Gridbase Bills v2.2.1 (Catálogo de Conceptos y Autocompletado) (2026-05-24)

* Nueva función: añadir conceptos catabitácora and auto-completar in facturas/cotizacións
* Optimización de: separate formulario-tarjeta from tabla-outer to optimización de desborde visual clipping on formularios
* Optimización de: desborde visual issues - workspace-panel scroll and tabla-outer content clipping
* Nueva función: Spanish routes (facturas, cotizaciones, clientees, inicio, configuracion)
* Optimización de: use absolute asset rutas for SPA deep links
* Optimización de: bump cache version to v42
* Nueva función: auto-set ITBIS by ecf type, default e-CF on, default DOP currency

## Gridbase Bills v2.2.0 (Soporte Completo e-CF Tipos 31 a 47) (2026-05-23)

* Optimización de: type-specific titles in PDF per DGII illustrative modelos
* Nueva función: DGII Paso 5 - optimización de QR enlaces, añadir signed_at, PDF generator script
* Optimización de: exclinterfaz gráficar FechaLimitePago for type 43 - not in XSD
* Optimización de: handle null cliente in factura list + limit to 200
* Optimización de: use is_ecf condition for XML download button visibility
* Nueva función: añadir XML download button and dirección de red for e-CF facturas
* Optimización de: TipoIngresos default '01' for types 33/34 - DGII reqinterfaz gráficares it despite XSD optional
* Optimización de: optimizar modification code labels to coincidir DGII spec + añadir 33/34 to IndicadorMontoGravado
* Optimización de: NC mod_code=3 (corrige montos) instead of 1 (anula totalmente)
* Optimización de: añadir types 33,34 to IndicadorMontoGravado - XSD RAW confirms both have it
* Optimización de: IndicadorMontoGravado=0 for types 31,32,41,45 (todos los with XSD support)
* Optimización de: IndicadorMontoGravado only for type 31 - todos los others rechazado by DGII
* Optimización de: eliminar type 45 from IndicadorMontoGravado - DGII rejects
* Optimización de: añadir TotalITBISRetenido and TotalISRRetencion for type 41 Totales
* Optimización de: IndicadorMontoGravado=0 for types 31,32,34,41,45 only (33 doesnt have it)
* Optimización de: re-añadir IndicadorMontoGravado=1 for types with ITBIS conceptos (31,32,33,34,41,45)
* Optimización de: eliminar IndicadorMontoGravado entirely - DGII rejects with no es válidoo
* Optimización de: eliminar IndicadorMontoGravado from type45, optimización de type46 Totales with MontoGravadoI3, optimización de type47 TotalISRRetencion, dynamic NCF ref
* Optimización de: actualizar NCF ref to 1000 series
* Optimización de: type46 IndicadorFacturacion=3, IndicadorAgenteRetencionoPercepcion=1, mod_code=3
* Optimización de: IndicadorNotaCredito=0, Retencion for 41/47, IndicadorFacturacion=4 exento, type46 totales, test ejecutor tax=0 for exento types
* Optimización de: Rewrite Xmlconstructor with per-type XSD compliance - IdDoc, Comprador, Totales, conceptos
* Añadir: comprehensive DGII test ejecutor for Paso 4
* Nueva función: añadir NCF Modificado and Codigo Modificacion fields to factura formulario for types 33/34
* Optimización de: añadir IndicadorMontoGravado=0 to IdDoc per DGII reqinterfaz gráficarement
* Optimización de: optimizar DGII enlace and envío en bloque upload in script de prueba
* Optimización de: auth ctodos los in script de prueba
* Optimización de: optimizar certificado ruta in script de prueba
* Optimización de: script de prueba certificado params
* Añadir: direct DGII date script de prueba
* Optimización de: FechaVencimientoSecuencia exclinterfaz gráficard for types 32,34 per DGII obligatoriedad tabla
* Optimización de: todos los dates to DD-MM-YYYY per XSD FechaválidoationType
* Optimización de: eliminar xmlns namespace from ECF root + optimización de Totales element order per XSD
* Optimización de: bump JS versions to v=41 for cache inválidoation
* Nueva función: Paso 4 - Soporte completo para todos los tipos e-CF (31-47) + RFCE automatizado
* Diseño: eliminar todos los emojis de la interfaz
* Nueva función: Módulo completo de Aprobaciones Comerciales en producción
* Optimización de: ACECF - qinterfaz gráficatar namespace (XSD no define ninguno) y corregir dirección de red a minúsculas
* Mantenimiento: Inclinterfaz gráficar set de datos de aprobaciones comerciales DGII
* Nueva función: Paso 3 DGII - Aprobaciones Comerciales (ACECF)

## Gridbase Bills v2.1.0 (Diagnóstico de Certificado e-CF y Códigos QR) (2026-05-21)

* Optimización de: Scroll bloqueado en secciones con contenido largo
* Optimización de: código QR en PDF - generar localmente con php-qrcode v6
* Nueva función: diagnósticoo e-CF en vivo - verifica certificado, auth, XML, firma paso a paso
* Optimización de: Reparar sistema e-CF produccion completo
* Nueva función: Ruta publica /dgii-fc250k para descargar archivos FC<250k directo del browser
* Optimización de: eliminarr ruta download-fc250k (500) + aumentar delay a 60s para Notas
* Optimización de: Descargar FC<250k inline del respuesta JSON (elimina dirección de red separado 404)
* Optimización de: Auto-descarga FC<250k en bloque fintodos losy + boton manual de descarga
* Optimización de: Aumentar delay a 30s antes de entornoiar Notas (10s no era suficiente)
* Nueva función: Auto-descarga FC<250k + optimización de CodigoSeguridadeCF = primeros 6 chars de firmaValue
* Optimización de: CodigoSeguridadeCF = primeros 6 chars del firmaValue base64 (NO MD5)
* Optimización de: CodigoSeguridadeCF del RFCE debe venir de la firma del e-CF COMPLETO
* Optimización de: entornoiar facturas base PRIMERO, luego Notas (Type 33/34) con delay de 10s
* Optimización de: Forzar token fresco en test runs (evitar 401 por token cacheado expirado)
* Optimización de: omitir Type 32 <250k del dirección de red e-CF + script para generar archivos FC<250k para upload portal
* Optimización de: Usar formularioatoo {RNCEmisor}{eNCF}.xml para TODOS los archivos, no solo RFCE
* Optimización de: Detectar optimizaramente respuestas exitosas de RFCE (codigo=1/estado=Aceptado)
* Optimización de: RFCE filename debe ser {RNCEmisor}{eNCF}.xml segun norma DGII
* Optimización de: RFCE - mover CodigoSeguridadeCF dentro de Encabezado y eliminar FechaHoraFirma
* Optimización de: ELIMINAR xmlns del XML raiz - XSD no tiene targetNamespace
* Optimización de: Corregir FechaHoraFirma (dd-MM-yyyy HH:mm:ss) y agregar IndicadorNotaCredito
* Nueva función: Regenerar XMLs de prueba desde Excel oficial DGII con estructura XSD optimizara
* Optimización de: entornoiar XML como envío en bloque/formulario-data segun DGII docs
* Optimización de: Corregir enlaces de dirección de reds DGII segun documentacion oficial
* Optimización de: Corregir RunDgiiTestscomando - Setting:getSetting no existe, usar gettodos los/get, optimización de enlaces y certificado ruta
* Optimización de: Rewrite XmlfirmaService siginterfaz gráficaendo ginterfaz gráficaa oficial DGII 'Firmado de e-CF'
* Optimización de: Qinterfaz gráficatar texto del bitácoraotipo barra lateral (solo bitácoraotipo) y eliminar todos los emojis
* Nueva función: Integración completa del Gridbase kit de diseño en todos los módulos
* Nueva función: migrar interfaz to Gridbase kit de diseño v2 - reemplazar app.css with Gridbase kit de diseño tokens (Inter font, gris carbón paleta de colores, premium sombras) - actualizar panel de control chart colores from teal to gris carbón - optimización de e-CF badge and DGII estado colores in facturas module - actualizar estilo visual-color meta and actualizar caché versión de estilos - añadir design-sistema.css referencia file

## Gridbase Bills v2.0.1 (Firmas XML y Depuración de Rutas) (2026-05-20)

* Nueva función: binterfaz gráficald DGII test consola interfaz gráfica console
* Set tipo de contenido text/xml for DGII válidoarSemilla
* Optimización de XML firma DSig preoptimización de and referencia transformulario
* Añadir generard DGII test XMLs
* Optimización de gitignorar codificación and añadir faltante DGII comandos and test generators
* Optimización de DGII Auth dirección de reds, Semilla envío en bloque válidoation, and XML firma canonicalización
* Mantenimiento: ignorar .p12 certificadoificates for security
* Bump cache version to v31 to force browser to load navegación / rutas optimiza
* Optimización de SPA navegación / rutas: change window.location.hash to window.App.navegar to asegurar compatibilidad de silenciOSo drops
* Optimización de múltiples issues across controladores, modelos, and DGII serviciOS
* Rediseño: completar plano design sistema renovación completa - new CSS tokens, panel de control rewrite, Outfit tipografía, no sombras, v30 actualizar caché
* Diseño visual: completar plano, estructurad, mínimo redesign of Gridbase Bills interfaz gráfica according to global design tokens
* Optimización de: eliminar heredado configuración rutas web to let SPA capturar todo handle direct visitas directas on clean rutas
* Optimización de: actualizar module imports to v22 to bust browser cache for configuración and electronic invoicing
* Nueva función: añadir clean-git.php herramienta for server deployment limpieza
* Nueva función: implementar HTML5 History API navegación / rutas (pushState) to reemplazar hash-based enlaces
* Optimización de: bump assets version query for actualizar cachéing

## Gridbase Bills v2.0.0 (Módulo de Facturación Electrónica DGII) (2026-05-19)

* Nueva función: modulo de facturacion electronica e-CF de la DGII integrado

## Gridbase Bills v1.5.2 (Mejoras en Despliegue de Servidor y Cotizaciones) (2026-05-12)

* Optimización de: simplify .cpanel.yml for válido despliegue en cPanelment
* Optimización de: actualizar .cpanel.yml for proper Laravel deployment with gestor de paquetes and comandos del sistema
* Optimización de: añadir actualizar method to cotizaciónController - garantiza la estabilidad del sistema when editing cotizacións

## Gridbase Bills v1.5.1 (Centro de Pruebas de WhatsApp) (2026-05-03)

* Feature: WhatsApp API Test centrar - Herramienta de pruebas

## Gridbase Bills v1.5.0 (Notificaciones de Correo y WhatsApp) (2026-05-02)

* Feature: entornoío automático por correo electrónico y WhatsApp de facturas y cotizaciones
* Optimización de: Corregir dominio en documentación de conexión automática (webhook) (bills.gridbase.com.do)
* Feature: Agregar dirección de red de conexión automática (webhook) para WhatsApp Cloud API
* Optimización de: Eliminar mensaje de error 401 al cargar página de bitácorain

## Gridbase Bills v1.4.0 (Integración de Pasarela de Pagos PayPal) (2026-05-01)

* Nueva función: entornoiar correo electrónico de confirmación al clientee después de pago por PayPal
* Diseño: Usar bitácoraotipo real de GridBase en lugar de texto en páginas de pago
* Nueva función: Mejorar experiencia de usuario de páginas de pago - distinginterfaz gráficar entre pagada y expirada con branding GridBase
* Optimización de: Corregir ubicación de custom_id en respuesta de PayPal (captura de pago vs unidad de compra)
* Deajuste: Agregar bitácoras dettodos losados para rastrear problema de conversión de moneda
* Optimización de: analizarar valores numéricos en tabla de pagos problemáticos
* Nueva función: Herramienta para corregir pagos con conversión inoptimizara
* Optimización de: Registrar pago con monto ORIGINAL después de conversión
* Optimización de: Sincronizar moneda del SDK (código integrado) PayPal con moneda convertida
* Nueva función: Conversión automática de moneda DOP a USD para PayPal
* Nueva función: Herramienta de diagnóstico PayPal y bitácoraging mejorado
* Nueva función: Sistema de configuración PayPal en admin y mejoras de válidoación
* Implementarar PayPal estándar Checkout oficial - Añadir soporte para múltiples métodos de pago (PayPal, tarjetas, Venmo) - Configurar estilo de botones según guías oficiales - Enable funding sources - Añadir ctodos losback onShippingChange - Aumentar altura mínima del contenedor
* Corregir campo clientee en blanco - Añadir accesoor 'name' a modelo cliente - Retorna company_name o contact_name - válidoar existencia de clientee en vistas
* Corregir error de campos null - Cambiar factura_date a issue_date, tax a tax_amount - Añadir válidoación de campos nulos - Corregir mapeo de conceptos con unit_price y amount
* Convertir página de pago en checkout profesional - 2 pasos: Revisar y Pagar - Diseño limpio tipo e-commerce - Estados visuales en pasos PayPal - Box de pago destacado
* Rediseñar portal de búsqueda como checkout profesional - 3 pasos: Buscar, Revisar, Pagar - Vista previa completa de factura con dettodos loses - Diseño limpio tipo e-commerce
* Mejorar interfaz y funciones del portal de búsqueda de facturas - Animaciones flinterfaz gráficadas, historial de búsquedas, mejor experiencia de usuario
* Corregir estilos y añadir enlaces de pago en correo electrónicos - implementarar línea gráfica GridBase en páginas públicas
* Agregar link de pago automático en correo electrónicos y buscador público
* Implementarar sistema de pagos con PayPal

## Gridbase Bills v1.3.0 (Rediseño Móvil Estilo iOS y PWA) (2026-04-30)

* Nueva función: reemplazar topbar text with gridbase bitácoraotipo on móvil / celular
* Optimización de: detail tabla expanding width and pushing content off screen
* Optimización de: tabla cells not wrapping on móvil / celular due to tabla-cell display
* Optimización de: iOS horizontal scrolling issue
* Optimización de: móvil / celular tab bar hidden by css cascade
* Nueva función: completar iOS-style native móvil / celular redesign
* Mantenimiento: actualizar app name and icons
* Nueva función: añadir aplicación PWA manifiesto de aplicación and iOS aplicación web etiquetas meta
* Nueva función: responsive móvil / celular conceptos formulario and panel de control bitácoraotipo actualizar
* Optimización de: optimizar unique constraint and type error on factura creation
* Optimización de: pad factura and cotización números with ceros
* Nueva función: implementar tiempo real buscador global
* Nueva función: send correo electrónico on partial pagos

## Gridbase Bills v1.2.0 (Facturas Recurrentes e Interfaz en Español) (2026-04-29)

* Optimización de: detector de cambios to entrada for immediate válidoation
* Mantenimiento: bump version to v10 to bust cache
* Nueva función: autocompletar cliente and nombres de empresas based on RNC o Cédula
* Nueva función: añadir RNC and Cedula consulta API serviciOS
* Optimización de pago method valores to coincidir valores de base de datos (bank_transfer, credit_tarjeta, etc)
* Force actualizar caché v9 for sincronización de despliegue
* Reemplazar todos los prompt/confirm with diálogos HTML personalizados
* Optimización de pago modal: handle null monto pagado que causaba errores
* Major upgrade: panel de control chart, factura/cotización edit/delete/duplicado/search, cliente perfils, overdue badge, Spanish interfaz gráfica
* Envío automático factura correo electrónico on create & cotización convert, correo electrónico estado indicator in interfaz gráfica, Spanish estado labels
* Optimización de MySQL key length para hosting cPanel compartido
* Crítico: exclinterfaz gráficar .entorno, storage, database from despliegue rsync
* Mínimo deploy: rsync only, exclinterfaz gráficar vendor dir
* Eliminar gestor de paquetes insttodos los from deploy - por errores de tiempo límite en hosting
* Optimización de cpanel.yml: restaurar configuración de despliegue original, eliminar duplicado line
* Restaurar cpanel.yml with view:clear only
* Optimización de: mínimo deploy, eliminar comandos del sistema cache comandos causing 500
* Optimize despliegue en cPanel: omitir gestor de paquetes, exclinterfaz gráficar vendor, ocultar errores
* Use cropped bitácoraotipo for barra lateral, code formularioatoting limpieza
* Restaurar cropped bitácoraotipo for bitácorain page
* Optimización de bitácoraotipo: use remote enlace directly, eliminar brightness filter, actualizar caché v6
* Unificar bitácoraotipo across todos los plantillas - use oficial Gridbase bitácoraotipo enlace everywhere
* Auto-generar first factura on reactuale creation + send correo electrónico, pago confirmation correo electrónicos, estado labels Pendiente de Pago

## Gridbase Bills v1.1.0 (Migración a Laravel 12 y Plantillas PDF) (2026-04-28)

* Completar reactuale facturas: controlador CRUD completo + Mejorard interfaz with edit, details, estado labels
* Actualizar caché v5 + actualizar favicon to colores corporativos verde azulado
* Switch interfaz gráfica from dark mode to light estilo visual with corporate colores corporativos verde azulado
* Pie de página fijo al fondo, fondo teal full-width, sin mensaje de agradecimiento
* Rediseno pie de página: sin position:optimización deed, flujo normal, centrado con línea verde azulada
* Pie de página: contenedor secundario con pañadiring 45px, texto aumentado
* Pie de página fijo full-width, etiquetas en verde oscuro, texto aumentado
* Qinterfaz gráficatar borde del caja de datos en cabecera
* Optimización de pie de página: qinterfaz gráficatar position:optimización deed, mover dentro del body-content
* Qinterfaz gráficatar nombre empresa bajo bitácoraotipo, agrandar bitácoraotipo, optimización de pie de página desborde visual
* Rediseno estilo clásico corporativo: cabecera verde, tabla con filas de color, pie de página bar
* Optimización de A4 (hoja normal): margin:0, width:595px, sin fórmulas complejas, tamaños fijos
* Rediseño total: cabecera band teal, diseño de panttodos losa 3 columnas, mejor distribución del espacio
* Rediseno completo factura.blade.php con estilo corporativo Gridbase
* Rediseno factura en PDF con estilo corporativo Gridbase (teal, accent verde, tarjeta beige)
* Rediseno completo: diseño de panttodos losa barra lateral + pantalla principal, jerarqinterfaz gráficaa visual mejorada
* Reestructurar diseño de panttodos losa a tablas para Mejorar generación en PDF y aumentar espacio
* Mejorar diseño y distribución de la plantilla PDF
* Optimización de: añadir dompdf.php config with habilitar imágenes remotas, fuente DejaVu Sans, new bitácoraotipo enlace, ultra compacto single page diseño de panttodos losa
* Optimización de: comprimir PDF márgenes to ajustar en una hoja - reduce pañadiring, font tamaños, barra lateral width
* Optimización de: switch PDF and correo electrónico to LIGHT estilo visual coincidiring gridbase.com.do - fondo blanco, teal #0B484C, verde #00DF83, beige #F6F5F2
* Optimización de: personalizar marca de PDF and correo electrónico to Gridbase colores - dark estilo visual, lime verde #B4E717, mint #00D690
* Nueva función: redesign factura en PDF with dark barra lateral + detalles en naranja, añadir styled HTML correo electrónico template with factura summary
* Optimización de: test with admin correo electrónico
* Optimización de: añadir limpieza de archivos temporales to deploy, añadir deajuste mail-test dirección de red
* Optimización de: completar correo electrónico delivery renovación completa - centralizar configuración SMTP, optimización de encryption, añadir bitácoraging
* Optimización de(correo electrónico): configurar directamente opciones SSL de correo on motor de envío to bypass CN miscoincidir with web-hosting.com certificado
* Correos: reemplazar todos los custom motor de envío with Laravel Mail facade + config/mail.php with SSL verify desactivard
* Optimización de(correo electrónico): desactivar SSL peer verification for servidor de correo local - optimiza CN miscoincidir with web-hosting.com certificado
* Optimización de(correo electrónico): use authenticated SMTP to localhost:25 without SSL for confiable cPanel correo electrónico delivery
* Optimización de(correo electrónico): use sendmail://default DSN transport instead of SendmailTransport(-bs) for cPanel compatibility
* Optimización de(correo electrónico): use transporte sendmail local for entrega local en cPanel - bypasses todos los firewtodos los/port blocks on shared hosting
* Feat(smtp): añadir server-side SMTP port diagnóstico dirección de red to discover working host/port combos on cPanel
* Optimización de(configuración): use actualizarOrCreate instead of actualizar to optimización de silenciOSo guardar failures, bump cache to v4 for SMTP test button
* Feat(interfaz gráfica): añadir cargando toasts to correo electrónico sending functions to provide user notificación during long smtp timeouts
* Feat(mailer): asegurar compatibilidad de cryptic connection errors by válidoating smtp host and credentials antes de transport creation
* Optimización de(mailer): optimizar empty string correo electrónico error by replacing null coalescing with empty check in from_correo electrónico assignments
* Optimización de(pdf): optimizar undefined array key error by using the cliente relation array instead of factura for cliente details
* Feat(api): implementar faltante PDF routes, sendcorreo electrónico methods, and full cotizaciónController functionality
* Optimización de(modelos): optimizar php syntax error on faltante fecha y horas declaration across todos los item pivot tablas
* Configuración: añadir interactive smtp connection probador
* Optimización de(interfaz gráfica): aggressively bypass browser cache for módulos de JavaScript with número de versión
* Optimización de(configuración): optimización de php syntax error in configuraciones and javascript analizar error in configuración view
* Optimización de(panel de control): optimizar faltante keys and faltante overdue facturas que generaba error 500 in SPA; optimización de(interfaz gráfica): añadir límites de tamaño to bitácoraotipos and forzar recarga de estilos
* Optimización de(auth): añadir production dominio to lista de seguridad de sesiones to asegurar compatibilidad de 401
* Optimización de(database): añadir laravel sistema tablas to migración inicial unificada
* Optimización de(deploy): asegurar compatibilidad de bucle de instalación infinito by añadiring bandera sin interacción
* Optimización de(deploy): actualizar canal de despliegue cPanel for gestor de paquetes and cache
* Feat(laravel): fully migrar estándar servidor y base de datos to Laravel 12 with Eloquent, seguridad de sesiones, serviciOS and comandos del sistema Schedulers
* Nueva función: completar configuración interfaz gráfica, reactuale facturas interfaz, and recordatorios automáticos
* Config: actualizar database credentials for production
* Optimización de: usar rsync para despliegue for confiable deployment
* Optimización de: actualizar cpanel.yml with explícito file/dir copy rutas
* Optimización de: configuración cargando, personalizar marca de plantillas to verde, barra lateral bitácoraotipo-only
* Optimización de: manejar respuestas vacias del API + verificar archivo de librerías
* Optimización de deploy: actualizar .gitignorar y .cpanel.yml para evitar cambios pendientes en servidor
* Personalizar marca deing completo + correcciones de seguridad y ajustes

## Gridbase Bills v1.0.0 (Lanzamiento Inicial) (2026-04-27)

* Añadir .cpanel.yml for automatizado instalación de Git en cPanel
* Optimización de: añadir redireccionamiento de rutas for cPanel navegación / rutas
* Sistema Gridbase facturas Versión 1 completada con interfaz de usuario y servidor y base de datos

