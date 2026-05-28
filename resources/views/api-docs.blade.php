<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gridbase Bills — API Documentation</title>
    <meta name="description" content="Documentación completa de la API de Gridbase Bills para integrar facturación y cotizaciones automáticas desde sistemas externos.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0f1117;
            --bg-sidebar: #161822;
            --bg-card: #1a1d2e;
            --bg-code: #12141f;
            --bg-inline-code: #262a3d;
            --border: #2a2d3e;
            --border-active: #4f7df9;
            --text: #e2e8f0;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --primary: #4f7df9;
            --primary-soft: rgba(79,125,249,0.12);
            --green: #22c55e;
            --green-soft: rgba(34,197,94,0.12);
            --orange: #f59e0b;
            --orange-soft: rgba(245,158,11,0.12);
            --red: #ef4444;
            --red-soft: rgba(239,68,68,0.12);
            --purple: #a855f7;
            --purple-soft: rgba(168,85,247,0.12);
            --cyan: #06b6d4;
            --radius: 8px;
            --radius-lg: 12px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',system-ui,sans-serif; background:var(--bg-body); color:var(--text); line-height:1.6; }
        code, pre { font-family:'JetBrains Mono','Fira Code',monospace; }

        /* Layout */
        .layout { display:flex; min-height:100vh; }
        .sidebar { width:280px; background:var(--bg-sidebar); border-right:1px solid var(--border); position:fixed; top:0; left:0; bottom:0; overflow-y:auto; z-index:100; }
        .main { margin-left:280px; flex:1; padding:48px 56px 80px; max-width:960px; }

        /* Sidebar */
        .sidebar-header { padding:24px 20px 16px; border-bottom:1px solid var(--border); }
        .sidebar-header h1 { font-size:16px; font-weight:800; display:flex; align-items:center; gap:8px; }
        .sidebar-header .badge { font-size:10px; background:var(--primary-soft); color:var(--primary); padding:2px 8px; border-radius:99px; font-weight:600; }
        .sidebar-nav { padding:12px 0; }
        .sidebar-nav .group-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); padding:16px 20px 6px; }
        .sidebar-nav a { display:flex; align-items:center; gap:10px; padding:7px 20px; font-size:13px; color:var(--text-secondary); text-decoration:none; transition:all .15s; border-left:2px solid transparent; }
        .sidebar-nav a:hover { background:rgba(255,255,255,0.03); color:var(--text); }
        .sidebar-nav a.active { color:var(--primary); border-left-color:var(--primary); background:var(--primary-soft); font-weight:600; }
        .sidebar-nav a .method { font-size:10px; font-weight:700; padding:1px 5px; border-radius:3px; font-family:'JetBrains Mono',monospace; min-width:32px; text-align:center; }
        .sidebar-nav a .method.get { background:var(--green-soft); color:var(--green); }
        .sidebar-nav a .method.post { background:var(--primary-soft); color:var(--primary); }

        /* Content */
        h2 { font-size:26px; font-weight:800; margin:0 0 8px; padding-top:32px; }
        h2:first-child { padding-top:0; }
        h3 { font-size:18px; font-weight:700; margin:32px 0 12px; color:var(--text); }
        h4 { font-size:14px; font-weight:700; margin:24px 0 8px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.04em; }
        p { margin:0 0 16px; color:var(--text-secondary); font-size:14.5px; }
        .subtitle { color:var(--text-muted); font-size:14px; margin:0 0 32px; }

        /* Endpoint blocks */
        .endpoint { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); margin:20px 0 28px; overflow:hidden; }
        .endpoint-header { display:flex; align-items:center; gap:12px; padding:16px 20px; border-bottom:1px solid var(--border); background:rgba(255,255,255,0.02); }
        .endpoint-header .method { font-size:12px; font-weight:700; padding:4px 10px; border-radius:4px; font-family:'JetBrains Mono',monospace; letter-spacing:0.02em; }
        .method.get { background:var(--green-soft); color:var(--green); }
        .method.post { background:var(--primary-soft); color:var(--primary); }
        .method.put { background:var(--orange-soft); color:var(--orange); }
        .method.delete { background:var(--red-soft); color:var(--red); }
        .endpoint-header .path { font-family:'JetBrains Mono',monospace; font-size:14px; font-weight:500; color:var(--text); }
        .endpoint-header .path .param { color:var(--orange); }
        .endpoint-body { padding:20px; }

        /* Code blocks */
        pre { background:var(--bg-code); border:1px solid var(--border); border-radius:var(--radius); padding:16px 20px; overflow-x:auto; font-size:13px; line-height:1.7; margin:12px 0 16px; }
        pre code { color:#cdd6f4; }
        .code-tabs { display:flex; gap:0; border-bottom:1px solid var(--border); margin-bottom:0; }
        .code-tab { padding:8px 16px; font-size:12px; font-weight:600; color:var(--text-muted); cursor:pointer; border-bottom:2px solid transparent; transition:all .15s; background:none; border-top:none; border-left:none; border-right:none; font-family:'Inter',sans-serif; }
        .code-tab:hover { color:var(--text-secondary); }
        .code-tab.active { color:var(--primary); border-bottom-color:var(--primary); }
        .code-panel { display:none; }
        .code-panel.active { display:block; }
        code.inline { background:var(--bg-inline-code); padding:2px 7px; border-radius:4px; font-size:13px; color:var(--cyan); }

        /* Tables */
        table { width:100%; border-collapse:collapse; margin:12px 0 20px; font-size:13.5px; }
        th { text-align:left; padding:10px 14px; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); border-bottom:2px solid var(--border); }
        td { padding:10px 14px; border-bottom:1px solid var(--border); color:var(--text-secondary); vertical-align:top; }
        td code { background:var(--bg-inline-code); padding:1px 6px; border-radius:3px; font-size:12px; }
        tr:last-child td { border-bottom:none; }

        /* Alerts */
        .alert { border-radius:var(--radius); padding:14px 18px; margin:16px 0; font-size:13.5px; display:flex; gap:10px; align-items:flex-start; }
        .alert-info { background:var(--primary-soft); border:1px solid rgba(79,125,249,0.25); color:var(--primary); }
        .alert-warning { background:var(--orange-soft); border:1px solid rgba(245,158,11,0.25); color:var(--orange); }
        .alert-success { background:var(--green-soft); border:1px solid rgba(34,197,94,0.25); color:var(--green); }
        .alert-danger { background:var(--red-soft); border:1px solid rgba(239,68,68,0.25); color:var(--red); }

        /* Badges & Tags */
        .tag { display:inline-block; font-size:11px; font-weight:600; padding:2px 8px; border-radius:4px; }
        .tag-required { background:var(--red-soft); color:var(--red); }
        .tag-optional { background:rgba(255,255,255,0.05); color:var(--text-muted); }
        .tag-string { background:var(--green-soft); color:var(--green); }
        .tag-number { background:var(--purple-soft); color:var(--purple); }
        .tag-array { background:var(--orange-soft); color:var(--orange); }
        .tag-object { background:var(--primary-soft); color:var(--primary); }
        .tag-boolean { background:rgba(6,182,212,0.12); color:var(--cyan); }

        /* Copy button */
        .copy-btn { position:absolute; top:8px; right:8px; background:rgba(255,255,255,0.08); border:none; color:var(--text-muted); padding:4px 10px; border-radius:4px; cursor:pointer; font-size:11px; font-family:'Inter',sans-serif; transition:all .15s; }
        .copy-btn:hover { background:rgba(255,255,255,0.15); color:var(--text); }
        .pre-wrap { position:relative; }

        /* Section divider */
        .divider { height:1px; background:var(--border); margin:40px 0; }
        section { scroll-margin-top:20px; }

        /* Status codes list */
        .status-list { list-style:none; padding:0; }
        .status-list li { display:flex; align-items:flex-start; gap:12px; padding:10px 0; border-bottom:1px solid var(--border); }
        .status-list li:last-child { border-bottom:none; }
        .status-code { font-family:'JetBrains Mono',monospace; font-weight:700; font-size:14px; min-width:38px; }

        /* Mobile */
        @media (max-width:900px) {
            .sidebar { display:none; }
            .main { margin-left:0; padding:24px 20px; }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width:6px; height:6px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:var(--border); border-radius:3px; }
        ::-webkit-scrollbar-thumb:hover { background:var(--text-muted); }

        .hero-banner { background:linear-gradient(135deg, #1e293b 0%, #0f172a 50%, #1a1040 100%); border:1px solid var(--border); border-radius:var(--radius-lg); padding:36px 32px; margin-bottom:36px; position:relative; overflow:hidden; }
        .hero-banner::before { content:''; position:absolute; top:-50%; right:-20%; width:400px; height:400px; background:radial-gradient(circle, rgba(79,125,249,0.08) 0%, transparent 70%); }
        .hero-banner h2 { padding:0; margin:0 0 10px; font-size:28px; }
        .hero-banner p { color:var(--text-secondary); margin:0; font-size:15px; }
        .hero-banner .version { position:absolute; top:20px; right:24px; }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h1>🧾 Gridbase Bills <span class="badge">API v1</span></h1>
        </div>
        <nav class="sidebar-nav">
            <div class="group-title">Inicio</div>
            <a href="#introduction">Introducción</a>
            <a href="#authentication">Autenticación</a>
            <a href="#base-url">URL Base</a>
            <a href="#rate-limiting">Rate Limiting</a>
            <a href="#errors">Manejo de Errores</a>

            <div class="group-title">Facturas</div>
            <a href="#create-invoice"><span class="method post">POST</span> Crear Factura</a>
            <a href="#list-invoices"><span class="method get">GET</span> Listar Facturas</a>
            <a href="#get-invoice"><span class="method get">GET</span> Ver Factura</a>
            <a href="#get-invoice-pdf"><span class="method get">GET</span> Descargar PDF</a>

            <div class="group-title">Cotizaciones</div>
            <a href="#create-quote"><span class="method post">POST</span> Crear Cotización</a>
            <a href="#list-quotes"><span class="method get">GET</span> Listar Cotizaciones</a>
            <a href="#get-quote"><span class="method get">GET</span> Ver Cotización</a>
            <a href="#convert-quote"><span class="method post">POST</span> Convertir a Factura</a>

            <div class="group-title">Clientes</div>
            <a href="#create-client"><span class="method post">POST</span> Crear Cliente</a>
            <a href="#list-clients"><span class="method get">GET</span> Listar Clientes</a>
            <a href="#get-client"><span class="method get">GET</span> Ver Cliente</a>

            <div class="group-title">Referencia</div>
            <a href="#client-upsert">Upsert de Clientes</a>
            <a href="#permissions">Permisos</a>
            <a href="#status-codes">Códigos de Estado</a>
            <a href="#examples">Ejemplos por Lenguaje</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main">

        <!-- ════════════════════════════════════════════ -->
        <!-- INTRODUCTION -->
        <!-- ════════════════════════════════════════════ -->
        <section id="introduction">
            <div class="hero-banner">
                <span class="version"><span class="badge">v1.0</span></span>
                <h2>API de Gridbase Bills</h2>
                <p>Integra facturación y cotizaciones automáticas desde cualquier sistema externo — páginas web, e-commerce, ERP, CRM o aplicaciones móviles.</p>
            </div>

            <p>La API REST de Gridbase Bills te permite crear facturas, cotizaciones y gestionar clientes de forma programática. Todos los endpoints retornan JSON y usan autenticación via API Key.</p>

            <div class="alert alert-info">
                <span>💡</span>
                <span>Para obtener una API Key, ingresa a <strong>Configuración → 🔑 API Keys</strong> en tu panel de Gridbase Bills y crea una nueva.</span>
            </div>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════ -->
        <!-- AUTHENTICATION -->
        <!-- ════════════════════════════════════════════ -->
        <section id="authentication">
            <h2>Autenticación</h2>
            <p>Todas las peticiones a la API deben incluir tu API Key en el header <code class="inline">Authorization</code> usando el esquema Bearer Token.</p>

            <div class="pre-wrap">
                <pre><code>Authorization: Bearer gb_xxxxxxxxxxxxxxxxxxxxxxxxxxxx</code></pre>
            </div>

            <div class="alert alert-warning">
                <span>⚠️</span>
                <span><strong>Nunca expongas tu API Key</strong> en código del lado del cliente (JavaScript del navegador). Siempre usa la API desde tu servidor backend.</span>
            </div>

            <h4>Obtener tu API Key</h4>
            <ol style="color:var(--text-secondary);font-size:14px;padding-left:20px;margin-bottom:20px;line-height:2;">
                <li>Inicia sesión como administrador en Gridbase Bills</li>
                <li>Ve a <strong>Configuración → 🔑 API Keys</strong></li>
                <li>Haz clic en <strong>"Nueva API Key"</strong></li>
                <li>Asigna un nombre descriptivo (ej: "Mi Tienda Online")</li>
                <li>Selecciona los permisos necesarios</li>
                <li>Copia el token — <strong>solo se muestra una vez</strong></li>
            </ol>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════ -->
        <!-- BASE URL -->
        <!-- ════════════════════════════════════════════ -->
        <section id="base-url">
            <h2>URL Base</h2>
            <p>Todas las rutas de la API están bajo el prefijo:</p>
            <div class="pre-wrap">
                <pre><code>{{ url('/api/v1') }}</code></pre>
            </div>
            <p>Por ejemplo, para crear una factura la URL completa sería:</p>
            <div class="pre-wrap">
                <pre><code>{{ url('/api/v1/invoices') }}</code></pre>
            </div>

            <h4>Headers Requeridos</h4>
            <table>
                <thead><tr><th>Header</th><th>Valor</th><th>Requerido</th></tr></thead>
                <tbody>
                    <tr><td><code>Authorization</code></td><td><code>Bearer gb_tu_token...</code></td><td><span class="tag tag-required">Requerido</span></td></tr>
                    <tr><td><code>Content-Type</code></td><td><code>application/json</code></td><td><span class="tag tag-required">Requerido (POST/PUT)</span></td></tr>
                    <tr><td><code>Accept</code></td><td><code>application/json</code></td><td><span class="tag tag-optional">Recomendado</span></td></tr>
                </tbody>
            </table>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════ -->
        <!-- RATE LIMITING -->
        <!-- ════════════════════════════════════════════ -->
        <section id="rate-limiting">
            <h2>Rate Limiting</h2>
            <p>Cada API Key tiene un límite de peticiones por minuto (configurable por el admin, por defecto 60/min). Los headers de respuesta incluyen:</p>

            <table>
                <thead><tr><th>Header</th><th>Descripción</th></tr></thead>
                <tbody>
                    <tr><td><code>X-RateLimit-Limit</code></td><td>Máximo de peticiones permitidas por minuto</td></tr>
                    <tr><td><code>X-RateLimit-Remaining</code></td><td>Peticiones restantes en la ventana actual</td></tr>
                    <tr><td><code>Retry-After</code></td><td>Segundos hasta que se renueve el límite (solo en 429)</td></tr>
                </tbody>
            </table>

            <p>Si excedes el límite recibirás un <code class="inline">429 Too Many Requests</code>:</p>
            <pre><code>{
    "success": false,
    "error": "Límite de requests excedido.",
    "message": "Máximo 60 requests por minuto. Intenta de nuevo en 45 segundos.",
    "retry_after": 45
}</code></pre>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════ -->
        <!-- ERROR HANDLING -->
        <!-- ════════════════════════════════════════════ -->
        <section id="errors">
            <h2>Manejo de Errores</h2>
            <p>La API siempre retorna JSON con el campo <code class="inline">success</code> como indicador. Los errores incluyen un mensaje descriptivo en español.</p>

            <h4>Formato de Error</h4>
            <pre><code>{
    "success": false,
    "error": "Descripción corta del error.",
    "message": "Mensaje detallado con contexto adicional.",
    "errors": {                  // Solo en errores de validación (422)
        "campo": ["mensaje"]
    }
}</code></pre>

            <h4>Formato de Éxito</h4>
            <pre><code>{
    "success": true,
    "message": "Factura creada exitosamente.",
    "data": { ... }
}</code></pre>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- INVOICES -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <h2 style="padding-top:0;">📝 Facturas</h2>
        <p class="subtitle">Crea, consulta y descarga facturas electrónicas.</p>

        <!-- CREATE INVOICE -->
        <section id="create-invoice">
            <h3>Crear Factura</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="path">/api/v1/invoices</span>
                </div>
                <div class="endpoint-body">
                    <p style="margin:0 0 16px;">Crea una nueva factura con cálculos automáticos de subtotal, descuento, impuesto y total. El número de factura se genera automáticamente.</p>

                    <p style="margin:0 0 4px;font-weight:600;color:var(--text);">Permiso requerido: <code class="inline">invoices.create</code></p>

                    <h4>Parámetros del Body</h4>
                    <table>
                        <thead><tr><th>Campo</th><th>Tipo</th><th></th><th>Descripción</th></tr></thead>
                        <tbody>
                            <tr><td><code>client_id</code></td><td><span class="tag tag-number">integer</span></td><td><span class="tag tag-optional">*</span></td><td>ID de un cliente existente</td></tr>
                            <tr><td><code>client</code></td><td><span class="tag tag-object">object</span></td><td><span class="tag tag-optional">*</span></td><td>Datos del cliente (upsert automático). Ver <a href="#client-upsert" style="color:var(--primary);">Upsert de Clientes</a></td></tr>
                            <tr><td><code>client.tax_id</code></td><td><span class="tag tag-string">string</span></td><td></td><td>RNC o Cédula del cliente</td></tr>
                            <tr><td><code>client.company_name</code></td><td><span class="tag tag-string">string</span></td><td></td><td>Nombre de la empresa</td></tr>
                            <tr><td><code>client.contact_name</code></td><td><span class="tag tag-string">string</span></td><td></td><td>Nombre de contacto</td></tr>
                            <tr><td><code>client.email</code></td><td><span class="tag tag-string">string</span></td><td></td><td>Correo electrónico</td></tr>
                            <tr><td><code>client.phone</code></td><td><span class="tag tag-string">string</span></td><td></td><td>Teléfono</td></tr>
                            <tr><td><code>client.whatsapp</code></td><td><span class="tag tag-string">string</span></td><td></td><td>Número WhatsApp</td></tr>
                            <tr><td><code>items</code></td><td><span class="tag tag-array">array</span></td><td><span class="tag tag-required">Requerido</span></td><td>Lista de ítems/servicios</td></tr>
                            <tr><td><code>items[].description</code></td><td><span class="tag tag-string">string</span></td><td><span class="tag tag-required">Requerido</span></td><td>Descripción del ítem</td></tr>
                            <tr><td><code>items[].quantity</code></td><td><span class="tag tag-number">number</span></td><td><span class="tag tag-required">Requerido</span></td><td>Cantidad (mín: 0.01)</td></tr>
                            <tr><td><code>items[].unit_price</code></td><td><span class="tag tag-number">number</span></td><td><span class="tag tag-required">Requerido</span></td><td>Precio unitario</td></tr>
                            <tr><td><code>currency</code></td><td><span class="tag tag-string">string</span></td><td><span class="tag tag-optional">Opcional</span></td><td><code>DOP</code>, <code>USD</code>, <code>EUR</code> (default: DOP)</td></tr>
                            <tr><td><code>tax_rate</code></td><td><span class="tag tag-number">number</span></td><td><span class="tag tag-optional">Opcional</span></td><td>Tasa de impuesto en % (ej: 18)</td></tr>
                            <tr><td><code>discount_type</code></td><td><span class="tag tag-string">string</span></td><td><span class="tag tag-optional">Opcional</span></td><td><code>percentage</code> o <code>fixed</code></td></tr>
                            <tr><td><code>discount_value</code></td><td><span class="tag tag-number">number</span></td><td><span class="tag tag-optional">Opcional</span></td><td>Valor del descuento</td></tr>
                            <tr><td><code>notes</code></td><td><span class="tag tag-string">string</span></td><td><span class="tag tag-optional">Opcional</span></td><td>Notas internas</td></tr>
                            <tr><td><code>issue_date</code></td><td><span class="tag tag-string">date</span></td><td><span class="tag tag-optional">Opcional</span></td><td>Fecha de emisión (default: hoy)</td></tr>
                            <tr><td><code>due_date</code></td><td><span class="tag tag-string">date</span></td><td><span class="tag tag-optional">Opcional</span></td><td>Fecha de vencimiento (default: +30 días)</td></tr>
                            <tr><td><code>is_ecf</code></td><td><span class="tag tag-boolean">boolean</span></td><td><span class="tag tag-optional">Opcional</span></td><td>Procesar como e-CF (DGII)</td></tr>
                        </tbody>
                    </table>

                    <div class="alert alert-info">
                        <span>💡</span>
                        <span>Debes proporcionar <code>client_id</code> <strong>o</strong> un objeto <code>client</code>, no ambos. Si envías <code>client</code>, el sistema buscará un cliente existente por <code>tax_id</code> o <code>email</code>, y lo creará si no existe.</span>
                    </div>

                    <h4>Ejemplo de Request</h4>
                    <pre><code>POST /api/v1/invoices
Content-Type: application/json
Authorization: Bearer gb_xxxxxxxxxxxx...

{
    "client": {
        "tax_id": "131-456789-1",
        "company_name": "Acme Solutions SRL",
        "contact_name": "Juan Pérez",
        "email": "juan@acme.com.do",
        "phone": "809-555-1234"
    },
    "items": [
        {
            "description": "Diseño de página web",
            "quantity": 1,
            "unit_price": 25000.00
        },
        {
            "description": "Hosting anual",
            "quantity": 12,
            "unit_price": 500.00
        }
    ],
    "currency": "DOP",
    "tax_rate": 18,
    "discount_type": "percentage",
    "discount_value": 5,
    "notes": "Generada automáticamente desde cotizador web"
}</code></pre>

                    <h4>Respuesta Exitosa — <span style="color:var(--green);">201 Created</span></h4>
                    <pre><code>{
    "success": true,
    "message": "Factura creada exitosamente.",
    "data": {
        "id": 42,
        "invoice_number": "FAC-0042",
        "client_id": 15,
        "status": "draft",
        "currency": "DOP",
        "subtotal": 31000.00,
        "discount_amount": 1550.00,
        "tax_amount": 5301.00,
        "total": 34751.00,
        "issue_date": "2026-05-28",
        "due_date": "2026-06-27",
        "items": [...],
        "client": {
            "id": 15,
            "company_name": "Acme Solutions SRL",
            "contact_name": "Juan Pérez",
            ...
        },
        "created_at": "2026-05-28T16:30:00+00:00"
    }
}</code></pre>
                </div>
            </div>
        </section>

        <!-- LIST INVOICES -->
        <section id="list-invoices">
            <h3>Listar Facturas</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="path">/api/v1/invoices</span>
                </div>
                <div class="endpoint-body">
                    <p style="margin:0 0 16px;">Retorna una lista paginada de facturas con filtros opcionales.</p>
                    <p style="margin:0 0 4px;font-weight:600;color:var(--text);">Permiso requerido: <code class="inline">invoices.read</code></p>

                    <h4>Query Parameters</h4>
                    <table>
                        <thead><tr><th>Parámetro</th><th>Tipo</th><th>Descripción</th></tr></thead>
                        <tbody>
                            <tr><td><code>page</code></td><td><span class="tag tag-number">integer</span></td><td>Número de página (default: 1)</td></tr>
                            <tr><td><code>per_page</code></td><td><span class="tag tag-number">integer</span></td><td>Resultados por página (default: 20, máx: 100)</td></tr>
                            <tr><td><code>status</code></td><td><span class="tag tag-string">string</span></td><td>Filtrar por estado: <code>draft</code>, <code>sent</code>, <code>paid</code>, <code>partial</code>, <code>cancelled</code></td></tr>
                            <tr><td><code>client_id</code></td><td><span class="tag tag-number">integer</span></td><td>Filtrar por ID de cliente</td></tr>
                            <tr><td><code>date_from</code></td><td><span class="tag tag-string">date</span></td><td>Fecha de emisión desde (YYYY-MM-DD)</td></tr>
                            <tr><td><code>date_to</code></td><td><span class="tag tag-string">date</span></td><td>Fecha de emisión hasta (YYYY-MM-DD)</td></tr>
                        </tbody>
                    </table>

                    <h4>Ejemplo</h4>
                    <pre><code>GET /api/v1/invoices?status=paid&date_from=2026-05-01&per_page=10</code></pre>

                    <h4>Respuesta</h4>
                    <pre><code>{
    "success": true,
    "data": [ { "id": 42, "invoice_number": "FAC-0042", ... }, ... ],
    "pagination": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 10,
        "total": 27
    }
}</code></pre>
                </div>
            </div>
        </section>

        <!-- GET INVOICE -->
        <section id="get-invoice">
            <h3>Ver Factura</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="path">/api/v1/invoices/<span class="param">{id}</span></span>
                </div>
                <div class="endpoint-body">
                    <p style="margin:0 0 16px;">Retorna todos los detalles de una factura específica incluyendo cliente, ítems y pagos.</p>
                    <p style="margin:0 0 4px;font-weight:600;color:var(--text);">Permiso requerido: <code class="inline">invoices.read</code></p>
                </div>
            </div>
        </section>

        <!-- GET INVOICE PDF -->
        <section id="get-invoice-pdf">
            <h3>Descargar PDF</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="path">/api/v1/invoices/<span class="param">{id}</span>/pdf</span>
                </div>
                <div class="endpoint-body">
                    <p style="margin:0 0 16px;">Descarga el PDF de la factura con el diseño personalizado de la empresa.</p>
                    <p style="margin:0 0 4px;font-weight:600;color:var(--text);">Permiso requerido: <code class="inline">invoices.read</code></p>
                    <p>La respuesta es un archivo binario con <code class="inline">Content-Type: application/pdf</code>.</p>
                </div>
            </div>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- QUOTES -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <h2>📋 Cotizaciones</h2>
        <p class="subtitle">Crea, consulta y convierte cotizaciones a facturas.</p>

        <!-- CREATE QUOTE -->
        <section id="create-quote">
            <h3>Crear Cotización</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="path">/api/v1/quotes</span>
                </div>
                <div class="endpoint-body">
                    <p style="margin:0 0 16px;">Crea una nueva cotización. Los parámetros son idénticos a <a href="#create-invoice" style="color:var(--primary);">Crear Factura</a>, con la diferencia de <code class="inline">expiry_date</code> en lugar de <code class="inline">due_date</code>.</p>
                    <p style="margin:0 0 4px;font-weight:600;color:var(--text);">Permiso requerido: <code class="inline">quotes.create</code></p>

                    <h4>Diferencias con Factura</h4>
                    <table>
                        <thead><tr><th>Campo</th><th>Descripción</th></tr></thead>
                        <tbody>
                            <tr><td><code>expiry_date</code></td><td>Fecha de vencimiento de la cotización (default: +30 días)</td></tr>
                        </tbody>
                    </table>

                    <h4>Ejemplo</h4>
                    <pre><code>POST /api/v1/quotes
{
    "client": {
        "tax_id": "101-234567-8",
        "company_name": "Tech Corp SRL"
    },
    "items": [
        {"description": "Consultoría IT (40 horas)", "quantity": 40, "unit_price": 2500}
    ],
    "currency": "DOP",
    "tax_rate": 18,
    "expiry_date": "2026-06-30"
}</code></pre>
                </div>
            </div>
        </section>

        <!-- LIST QUOTES -->
        <section id="list-quotes">
            <h3>Listar Cotizaciones</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="path">/api/v1/quotes</span>
                </div>
                <div class="endpoint-body">
                    <p style="margin:0 0 16px;">Retorna una lista paginada de cotizaciones. Mismos filtros que <a href="#list-invoices" style="color:var(--primary);">Listar Facturas</a>.</p>
                    <p style="margin:0 0 4px;font-weight:600;color:var(--text);">Permiso requerido: <code class="inline">quotes.read</code></p>
                </div>
            </div>
        </section>

        <!-- GET QUOTE -->
        <section id="get-quote">
            <h3>Ver Cotización</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="path">/api/v1/quotes/<span class="param">{id}</span></span>
                </div>
                <div class="endpoint-body">
                    <p style="margin:0 0 16px;">Retorna los detalles completos de una cotización.</p>
                    <p style="margin:0 0 4px;font-weight:600;color:var(--text);">Permiso requerido: <code class="inline">quotes.read</code></p>
                </div>
            </div>
        </section>

        <!-- CONVERT QUOTE -->
        <section id="convert-quote">
            <h3>Convertir a Factura</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="path">/api/v1/quotes/<span class="param">{id}</span>/convert</span>
                </div>
                <div class="endpoint-body">
                    <p style="margin:0 0 16px;">Convierte una cotización aprobada en factura. Se genera un nuevo número de factura y se copian los ítems. La cotización queda marcada como <code class="inline">converted</code>.</p>
                    <p style="margin:0 0 4px;font-weight:600;color:var(--text);">Permiso requerido: <code class="inline">quotes.convert</code></p>

                    <h4>Respuesta</h4>
                    <pre><code>{
    "success": true,
    "message": "Cotización convertida a factura exitosamente.",
    "data": {
        "quote_id": 10,
        "invoice_id": 43,
        "invoice_number": "FAC-0043"
    }
}</code></pre>

                    <div class="alert alert-warning">
                        <span>⚠️</span>
                        <span>Una cotización solo puede convertirse una vez. Si ya fue convertida, recibirás un <code>409 Conflict</code>.</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- CLIENTS -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <h2>👤 Clientes</h2>
        <p class="subtitle">Gestiona la base de clientes para asociar a facturas y cotizaciones.</p>

        <!-- CREATE CLIENT -->
        <section id="create-client">
            <h3>Crear Cliente</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="path">/api/v1/clients</span>
                </div>
                <div class="endpoint-body">
                    <p style="margin:0 0 16px;">Crea un nuevo cliente o retorna uno existente si coincide por <code class="inline">tax_id</code> o <code class="inline">email</code>.</p>
                    <p style="margin:0 0 4px;font-weight:600;color:var(--text);">Permiso requerido: <code class="inline">clients.create</code></p>

                    <h4>Parámetros</h4>
                    <table>
                        <thead><tr><th>Campo</th><th>Tipo</th><th></th><th>Descripción</th></tr></thead>
                        <tbody>
                            <tr><td><code>company_name</code></td><td><span class="tag tag-string">string</span></td><td><span class="tag tag-optional">*</span></td><td>Nombre de la empresa</td></tr>
                            <tr><td><code>contact_name</code></td><td><span class="tag tag-string">string</span></td><td><span class="tag tag-optional">*</span></td><td>Nombre del contacto</td></tr>
                            <tr><td><code>email</code></td><td><span class="tag tag-string">email</span></td><td></td><td>Correo electrónico</td></tr>
                            <tr><td><code>phone</code></td><td><span class="tag tag-string">string</span></td><td></td><td>Teléfono</td></tr>
                            <tr><td><code>whatsapp</code></td><td><span class="tag tag-string">string</span></td><td></td><td>Número WhatsApp</td></tr>
                            <tr><td><code>tax_id</code></td><td><span class="tag tag-string">string</span></td><td></td><td>RNC o Cédula</td></tr>
                            <tr><td><code>address_line1</code></td><td><span class="tag tag-string">string</span></td><td></td><td>Dirección línea 1</td></tr>
                            <tr><td><code>city</code></td><td><span class="tag tag-string">string</span></td><td></td><td>Ciudad</td></tr>
                            <tr><td><code>country</code></td><td><span class="tag tag-string">string</span></td><td></td><td>Código de país (default: DO)</td></tr>
                        </tbody>
                    </table>

                    <p><small style="color:var(--text-muted);">* Se requiere al menos <code>company_name</code> o <code>contact_name</code>.</small></p>

                    <h4>Respuesta — Cliente Nuevo (<span style="color:var(--green);">201</span>)</h4>
                    <pre><code>{ "success": true, "message": "Cliente creado exitosamente.", "is_new": true, "data": { "id": 16, ... } }</code></pre>

                    <h4>Respuesta — Cliente Existente (<span style="color:var(--green);">200</span>)</h4>
                    <pre><code>{ "success": true, "message": "Cliente existente encontrado.", "is_new": false, "data": { "id": 8, ... } }</code></pre>
                </div>
            </div>
        </section>

        <!-- LIST CLIENTS -->
        <section id="list-clients">
            <h3>Listar Clientes</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="path">/api/v1/clients</span>
                </div>
                <div class="endpoint-body">
                    <p style="margin:0 0 4px;font-weight:600;color:var(--text);">Permiso requerido: <code class="inline">clients.read</code></p>
                    <h4>Query Parameters</h4>
                    <table>
                        <thead><tr><th>Parámetro</th><th>Descripción</th></tr></thead>
                        <tbody>
                            <tr><td><code>search</code></td><td>Buscar por nombre, email o RNC</td></tr>
                            <tr><td><code>tax_id</code></td><td>Filtrar por RNC/Cédula exacto</td></tr>
                            <tr><td><code>page</code></td><td>Número de página</td></tr>
                            <tr><td><code>per_page</code></td><td>Resultados por página (máx: 100)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- GET CLIENT -->
        <section id="get-client">
            <h3>Ver Cliente</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="path">/api/v1/clients/<span class="param">{id}</span></span>
                </div>
                <div class="endpoint-body">
                    <p style="margin:0 0 4px;font-weight:600;color:var(--text);">Permiso requerido: <code class="inline">clients.read</code></p>
                </div>
            </div>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- CLIENT UPSERT -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <section id="client-upsert">
            <h2>🔄 Upsert de Clientes</h2>
            <p>Cuando creas una factura o cotización con el objeto <code class="inline">client</code> (en lugar de <code class="inline">client_id</code>), el sistema usa un patrón <strong>upsert</strong>:</p>

            <ol style="color:var(--text-secondary);font-size:14px;padding-left:20px;margin-bottom:20px;line-height:2.2;">
                <li>Busca un cliente existente con el mismo <code class="inline">tax_id</code> (RNC/Cédula)</li>
                <li>Si no lo encuentra, busca por <code class="inline">email</code></li>
                <li>Si encuentra coincidencia, <strong>reutiliza ese cliente</strong> (no lo duplica)</li>
                <li>Si no encuentra ninguno, <strong>crea un nuevo cliente</strong></li>
            </ol>

            <div class="alert alert-success">
                <span>✅</span>
                <span>Esto significa que puedes enviar los mismos datos de cliente repetidamente sin generar duplicados. Ideal para integraciones automatizadas.</span>
            </div>

            <pre><code>// Primera llamada: crea el cliente y la factura
POST /api/v1/invoices { "client": {"tax_id": "131456789"}, ... }
→ client_id: 15 (nuevo)

// Segunda llamada con el mismo RNC: reutiliza el cliente
POST /api/v1/invoices { "client": {"tax_id": "131456789"}, ... }
→ client_id: 15 (existente, no duplicado)</code></pre>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- PERMISSIONS -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <section id="permissions">
            <h2>🔐 Permisos</h2>
            <p>Cada API Key tiene permisos granulares. Si intentas usar un endpoint sin el permiso correspondiente, recibirás un <code class="inline">403 Forbidden</code>.</p>

            <table>
                <thead><tr><th>Permiso</th><th>Descripción</th><th>Endpoints</th></tr></thead>
                <tbody>
                    <tr><td><code>invoices.create</code></td><td>Crear facturas</td><td><code>POST /invoices</code></td></tr>
                    <tr><td><code>invoices.read</code></td><td>Ver y listar facturas, descargar PDF</td><td><code>GET /invoices</code>, <code>GET /invoices/{id}</code>, <code>GET /invoices/{id}/pdf</code></td></tr>
                    <tr><td><code>quotes.create</code></td><td>Crear cotizaciones</td><td><code>POST /quotes</code></td></tr>
                    <tr><td><code>quotes.read</code></td><td>Ver y listar cotizaciones</td><td><code>GET /quotes</code>, <code>GET /quotes/{id}</code></td></tr>
                    <tr><td><code>quotes.convert</code></td><td>Convertir cotización a factura</td><td><code>POST /quotes/{id}/convert</code></td></tr>
                    <tr><td><code>clients.create</code></td><td>Crear clientes</td><td><code>POST /clients</code></td></tr>
                    <tr><td><code>clients.read</code></td><td>Ver y listar clientes</td><td><code>GET /clients</code>, <code>GET /clients/{id}</code></td></tr>
                </tbody>
            </table>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- STATUS CODES -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <section id="status-codes">
            <h2>📊 Códigos de Estado HTTP</h2>

            <ul class="status-list">
                <li><span class="status-code" style="color:var(--green);">200</span><div><strong>OK</strong> — La petición fue exitosa.</div></li>
                <li><span class="status-code" style="color:var(--green);">201</span><div><strong>Created</strong> — El recurso fue creado exitosamente.</div></li>
                <li><span class="status-code" style="color:var(--red);">401</span><div><strong>Unauthorized</strong> — API Key faltante, inválida, expirada o revocada.</div></li>
                <li><span class="status-code" style="color:var(--red);">403</span><div><strong>Forbidden</strong> — La API Key no tiene el permiso requerido para este endpoint.</div></li>
                <li><span class="status-code" style="color:var(--red);">404</span><div><strong>Not Found</strong> — El recurso solicitado no existe.</div></li>
                <li><span class="status-code" style="color:var(--orange);">409</span><div><strong>Conflict</strong> — La operación no se puede completar (ej: cotización ya convertida).</div></li>
                <li><span class="status-code" style="color:var(--orange);">422</span><div><strong>Unprocessable Entity</strong> — Datos de validación inválidos. Revisa el campo <code>errors</code>.</div></li>
                <li><span class="status-code" style="color:var(--red);">429</span><div><strong>Too Many Requests</strong> — Rate limit excedido. Espera <code>Retry-After</code> segundos.</div></li>
                <li><span class="status-code" style="color:var(--red);">500</span><div><strong>Internal Server Error</strong> — Error interno. Contacta al administrador.</div></li>
            </ul>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- CODE EXAMPLES -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <section id="examples">
            <h2>💻 Ejemplos por Lenguaje</h2>
            <p>Ejemplos completos de cómo crear una factura desde distintas tecnologías.</p>

            <!-- Tab navigation -->
            <div style="border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin:20px 0;">
                <div class="code-tabs" id="lang-tabs">
                    <button class="code-tab active" data-lang="curl">cURL</button>
                    <button class="code-tab" data-lang="javascript">JavaScript</button>
                    <button class="code-tab" data-lang="php">PHP</button>
                    <button class="code-tab" data-lang="python">Python</button>
                    <button class="code-tab" data-lang="csharp">C# / .NET</button>
                    <button class="code-tab" data-lang="wordpress">WordPress</button>
                </div>

                <!-- cURL -->
                <div class="code-panel active" id="panel-curl">
                    <pre style="margin:0;border:none;border-radius:0;"><code>curl -X POST {{ url('/api/v1/invoices') }} \
  -H "Authorization: Bearer gb_tu_api_key_aqui" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "client": {
      "tax_id": "131456789",
      "company_name": "Mi Empresa SRL",
      "email": "facturacion@miempresa.com"
    },
    "items": [
      {
        "description": "Servicio de consultoría",
        "quantity": 1,
        "unit_price": 15000
      }
    ],
    "currency": "DOP",
    "tax_rate": 18,
    "notes": "Factura generada automáticamente"
  }'</code></pre>
                </div>

                <!-- JavaScript -->
                <div class="code-panel" id="panel-javascript">
                    <pre style="margin:0;border:none;border-radius:0;"><code>// ═══ JavaScript (Node.js / Fetch API) ═══

const API_KEY = 'gb_tu_api_key_aqui';
const BASE_URL = '{{ url('/api/v1') }}';

async function crearFactura(datosCliente, items, opciones = {}) {
  const response = await fetch(`${BASE_URL}/invoices`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${API_KEY}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      client: datosCliente,
      items: items,
      currency: opciones.currency || 'DOP',
      tax_rate: opciones.tax_rate ?? 18,
      discount_type: opciones.discount_type,
      discount_value: opciones.discount_value,
      notes: opciones.notes,
    }),
  });

  const data = await response.json();

  if (!data.success) {
    throw new Error(data.error || 'Error al crear factura');
  }

  return data.data; // { id, invoice_number, total, ... }
}

// ── Uso ──
try {
  const factura = await crearFactura(
    {
      tax_id: '131456789',
      company_name: 'Mi Empresa SRL',
      email: 'facturacion@miempresa.com',
    },
    [
      { description: 'Diseño Web', quantity: 1, unit_price: 25000 },
      { description: 'Hosting 1 año', quantity: 1, unit_price: 6000 },
    ],
    { tax_rate: 18, notes: 'Desde cotizador web' }
  );

  console.log(`✅ Factura ${factura.invoice_number} creada por DOP ${factura.total}`);
} catch (error) {
  console.error('❌ Error:', error.message);
}</code></pre>
                </div>

                <!-- PHP -->
                <div class="code-panel" id="panel-php">
                    <pre style="margin:0;border:none;border-radius:0;"><code>&lt;?php
// ═══ PHP (cURL nativo) ═══

define('BILLS_API_KEY', 'gb_tu_api_key_aqui');
define('BILLS_API_URL', '{{ url('/api/v1') }}');

function crearFacturaBills(array $cliente, array $items, array $opciones = []): array
{
    $payload = [
        'client'        => $cliente,
        'items'         => $items,
        'currency'      => $opciones['currency'] ?? 'DOP',
        'tax_rate'      => $opciones['tax_rate'] ?? 18,
        'discount_type' => $opciones['discount_type'] ?? null,
        'discount_value'=> $opciones['discount_value'] ?? null,
        'notes'         => $opciones['notes'] ?? null,
    ];

    $ch = curl_init(BILLS_API_URL . '/invoices');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . BILLS_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT    => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode !== 201 || !($data['success'] ?? false)) {
        throw new \Exception($data['error'] ?? "HTTP $httpCode");
    }

    return $data['data'];
}

// ── Uso ──
try {
    $factura = crearFacturaBills(
        [
            'tax_id'       => '131456789',
            'company_name' => 'Mi Empresa SRL',
            'email'        => 'facturacion@miempresa.com',
        ],
        [
            ['description' => 'Servicio Web', 'quantity' => 1, 'unit_price' => 25000],
        ],
        ['tax_rate' => 18, 'notes' => 'Desde PHP']
    );

    echo "Factura {$factura['invoice_number']} creada - Total: {$factura['total']}\n";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}</code></pre>
                </div>

                <!-- Python -->
                <div class="code-panel" id="panel-python">
                    <pre style="margin:0;border:none;border-radius:0;"><code># ═══ Python (requests) ═══
# pip install requests

import requests

API_KEY = "gb_tu_api_key_aqui"
BASE_URL = "{{ url('/api/v1') }}"

headers = {
    "Authorization": f"Bearer {API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
}

def crear_factura(cliente: dict, items: list, **opciones) -> dict:
    payload = {
        "client": cliente,
        "items": items,
        "currency": opciones.get("currency", "DOP"),
        "tax_rate": opciones.get("tax_rate", 18),
        "notes": opciones.get("notes"),
    }

    response = requests.post(
        f"{BASE_URL}/invoices",
        json=payload,
        headers=headers,
        timeout=30,
    )

    data = response.json()
    if not data.get("success"):
        raise Exception(data.get("error", f"HTTP {response.status_code}"))

    return data["data"]


# ── Uso ──
try:
    factura = crear_factura(
        cliente={
            "tax_id": "131456789",
            "company_name": "Mi Empresa SRL",
            "email": "facturacion@miempresa.com",
        },
        items=[
            {"description": "Servicio Web", "quantity": 1, "unit_price": 25000},
        ],
        tax_rate=18,
        notes="Desde script Python",
    )

    print(f"✅ Factura {factura['invoice_number']} creada - Total: {factura['total']}")
except Exception as e:
    print(f"❌ Error: {e}")</code></pre>
                </div>

                <!-- C# -->
                <div class="code-panel" id="panel-csharp">
                    <pre style="margin:0;border:none;border-radius:0;"><code>// ═══ C# / .NET (HttpClient) ═══

using System.Net.Http.Json;

const string API_KEY = "gb_tu_api_key_aqui";
const string BASE_URL = "{{ url('/api/v1') }}";

var client = new HttpClient();
client.DefaultRequestHeaders.Add("Authorization", $"Bearer {API_KEY}");
client.DefaultRequestHeaders.Add("Accept", "application/json");

var payload = new {
    client = new {
        tax_id = "131456789",
        company_name = "Mi Empresa SRL",
        email = "facturacion@miempresa.com"
    },
    items = new[] {
        new { description = "Servicio Web", quantity = 1, unit_price = 25000 }
    },
    currency = "DOP",
    tax_rate = 18,
    notes = "Desde .NET"
};

var response = await client.PostAsJsonAsync($"{BASE_URL}/invoices", payload);
var result = await response.Content.ReadFromJsonAsync&lt;dynamic&gt;();

Console.WriteLine($"Factura creada: {result.data.invoice_number}");</code></pre>
                </div>

                <!-- WordPress -->
                <div class="code-panel" id="panel-wordpress">
                    <pre style="margin:0;border:none;border-radius:0;"><code>&lt;?php
// ═══ WordPress — Enviar factura a Gridbase Bills desde WooCommerce ═══
// Agregar en functions.php o en un plugin custom

define('BILLS_API_KEY', 'gb_tu_api_key_aqui');
define('BILLS_API_URL', '{{ url('/api/v1') }}');

/**
 * Al completar un pedido en WooCommerce, crea una factura en Gridbase Bills.
 */
add_action('woocommerce_order_status_completed', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Construir datos del cliente
    $cliente = [
        'company_name' => $order->get_billing_company() ?: $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'contact_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'email'        => $order->get_billing_email(),
        'phone'        => $order->get_billing_phone(),
    ];

    // Construir ítems
    $items = [];
    foreach ($order->get_items() as $item) {
        $items[] = [
            'description' => $item->get_name(),
            'quantity'    => $item->get_quantity(),
            'unit_price'  => (float) ($item->get_total() / max($item->get_quantity(), 1)),
        ];
    }

    // Crear factura via API
    $response = wp_remote_post(BILLS_API_URL . '/invoices', [
        'headers' => [
            'Authorization' => 'Bearer ' . BILLS_API_KEY,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ],
        'body'    => json_encode([
            'client'   => $cliente,
            'items'    => $items,
            'currency' => $order->get_currency(),
            'tax_rate' => 18,
            'notes'    => "Pedido WooCommerce #{$order_id}",
        ]),
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        error_log('Bills API Error: ' . $response->get_error_message());
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($body['success'] ?? false) {
        $order->add_order_note(
            "✅ Factura Bills: {$body['data']['invoice_number']} (Total: {$body['data']['total']})"
        );
    }
});</code></pre>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <div style="margin-top:60px;padding-top:24px;border-top:1px solid var(--border);text-align:center;color:var(--text-muted);font-size:13px;">
            <p>Gridbase Bills API v1 — Documentación generada automáticamente</p>
            <p style="margin:4px 0 0;">¿Necesitas ayuda? Contacta a <a href="mailto:soporte@gridbase.com.do" style="color:var(--primary);">soporte@gridbase.com.do</a></p>
        </div>

    </main>
</div>

<script>
// Tab switching for code examples
document.querySelectorAll('.code-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const lang = tab.dataset.lang;
        // Update tabs
        tab.closest('.code-tabs').querySelectorAll('.code-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        // Update panels
        tab.closest('div[style]').querySelectorAll('.code-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('panel-' + lang).classList.add('active');
    });
});

// Sidebar active state on scroll
const sections = document.querySelectorAll('section[id]');
const navLinks = document.querySelectorAll('.sidebar-nav a');

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + entry.target.id) {
                    link.classList.add('active');
                }
            });
        }
    });
}, { rootMargin: '-20% 0px -70% 0px' });

sections.forEach(section => observer.observe(section));
</script>

</body>
</html>
