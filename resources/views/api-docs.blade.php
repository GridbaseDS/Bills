<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gridbase Bills — Documentación de API</title>
    <meta name="description" content="Documentación completa de la API de Gridbase Bills para integrar facturación y cotizaciones automáticas desde sistemas externos.">
    
    <!-- Favicon Gridbase Bills -->
    <link rel="icon" type="image/png" href="https://gridbase.com.do/wp-content/uploads/2026/03/cropped-imagen_2026-03-18_101800374-180x180.png">
    <link rel="apple-touch-icon" href="https://gridbase.com.do/wp-content/uploads/2026/03/cropped-imagen_2026-03-18_101800374-180x180.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <script>
        // Pre-carga nativa del tema para evitar parpadeos (FOUC)
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <style>
        :root {
            --bg-app: #F4F5F7;
            --bg-sidebar: #FFFFFF;
            --bg-card: #FFFFFF;
            --bg-hover: #F3F4F6;
            --bg-active: rgba(11, 72, 76, 0.06);
            --bg-code: #0B0F19;
            --bg-inline-code: #E5E7EB;
            
            --color-text-primary: #111827;
            --color-text-secondary: #4B5563;
            --color-text-muted: #9CA3AF;
            --color-border: #E5E7EB;
            --color-border-hover: #D1D5DB;
            
            --primary: #0B484C;         /* Deep Teal Gridbase */
            --primary-hover: #073538;
            --primary-soft: rgba(11, 72, 76, 0.08);
            
            --color-accent: #00DF83;    /* Neon Accent Green */
            --color-accent-soft: rgba(0, 223, 131, 0.12);
            
            --green: #10B981;
            --green-soft: rgba(16, 185, 129, 0.1);
            --orange: #D97706;
            --orange-soft: rgba(217, 119, 6, 0.1);
            --red: #EF4444;
            --red-soft: rgba(239, 68, 68, 0.1);
            --purple: #8B5CF6;
            --purple-soft: rgba(139, 92, 246, 0.1);
            --cyan: #06B6D4;
            --cyan-soft: rgba(6, 182, 212, 0.1);
            
            --radius-xs: 4px;
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            
            --shadow-sm: 0px 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0px 4px 6px -1px rgba(0,0,0,0.03), 0px 2px 4px -1px rgba(0,0,0,0.02);
            --shadow-lg: 0px 10px 15px -3px rgba(0,0,0,0.05), 0px 4px 6px -2px rgba(0,0,0,0.02);
            
            --transition-fast: 0.15s cubic-bezier(0.4,0,0.2,1);
            --transition-normal: 0.25s cubic-bezier(0.4,0,0.2,1);
            
            --sidebar-w: 280px;
        }

        [data-theme="dark"] {
            --bg-app: #0B0F19;          /* Slate Premium Dark de Gridbase Bills */
            --bg-sidebar: #111827;
            --bg-card: #111827;
            --bg-hover: #1F2937;
            --bg-active: rgba(0, 223, 131, 0.08);
            --bg-code: #070A13;
            --bg-inline-code: #1F2937;
            
            --color-text-primary: #F9FAFB;
            --color-text-secondary: #D1D5DB;
            --color-text-muted: #6B7280;
            --color-border: #1F2937;
            --color-border-hover: #374151;
            
            --primary: #00DF83;         /* Neon Accent Green como principal en Dark Mode */
            --primary-hover: #00f08e;
            --primary-soft: rgba(0, 223, 131, 0.12);
            
            --green-soft: rgba(16, 185, 129, 0.15);
            --orange-soft: rgba(245, 158, 11, 0.15);
            --red-soft: rgba(239, 68, 68, 0.15);
            --purple-soft: rgba(139, 92, 246, 0.15);
            --cyan-soft: rgba(6, 182, 212, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-app);
            color: var(--color-text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition: background var(--transition-normal), color var(--transition-normal);
        }

        code, pre {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
        }

        /* ── TOPBAR HEADER ── */
        .topbar-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(135deg, #0B484C 0%, #064347 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .topbar-logo {
            height: 28px;
            object-fit: contain;
            transition: transform var(--transition-fast);
        }
        
        .topbar-logo:hover {
            transform: scale(1.02);
        }
        
        .topbar-divider {
            width: 1px;
            height: 24px;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .topbar-badge {
            color: #FFFFFF;
            font-size: 13.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .version-label {
            font-size: 10.5px;
            font-weight: 600;
            background: rgba(0, 223, 131, 0.15);
            color: #00DF83;
            padding: 2px 8px;
            border-radius: 99px;
            border: 1px solid rgba(0, 223, 131, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .theme-toggle-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.85);
            width: 36px;
            height: 36px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .theme-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #FFFFFF;
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }
        
        .back-dashboard-btn {
            background: #00DF83;
            color: #064347;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all var(--transition-fast);
            box-shadow: 0 2px 4px rgba(0, 223, 131, 0.1);
        }
        
        .back-dashboard-btn:hover {
            background: #00f08e;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 223, 131, 0.2);
        }

        .download-md-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.85);
            font-size: 13px;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all var(--transition-fast);
        }
        
        .download-md-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #FFFFFF;
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        /* Layout */
        .layout {
            display: flex;
            min-height: 100vh;
            padding-top: 60px; /* Space for Topbar */
        }
        
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg-sidebar);
            border-right: 1px solid var(--color-border);
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 100;
            padding-top: 8px;
            scrollbar-width: none;
            transition: background var(--transition-normal), border var(--transition-normal);
        }
        .sidebar::-webkit-scrollbar { display: none; }
        
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 48px 56px 80px;
            max-width: 1024px;
            background: var(--bg-app);
            transition: background var(--transition-normal);
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            padding: 12px 0 32px;
        }
        .sidebar-nav .group-title {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--color-text-muted);
            padding: 16px 24px 6px;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 24px;
            font-size: 13px;
            color: var(--color-text-secondary);
            text-decoration: none;
            transition: all var(--transition-fast);
            border-left: 3px solid transparent;
            font-weight: 500;
        }
        .sidebar-nav a:hover {
            background: var(--bg-hover);
            color: var(--color-text-primary);
        }
        .sidebar-nav a.active {
            color: var(--primary);
            background: var(--primary-soft);
            font-weight: 600;
            border-left-color: var(--primary);
        }
        
        .sidebar-nav a .method {
            font-size: 9.5px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'JetBrains Mono', monospace;
            min-width: 38px;
            text-align: center;
        }
        .sidebar-nav a .method.get { background: var(--green-soft); color: var(--green); }
        .sidebar-nav a .method.post { background: var(--primary-soft); color: var(--primary); }

        /* Content Headers */
        h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px;
            padding-top: 40px;
            letter-spacing: -0.02em;
            color: var(--color-text-primary);
            border-bottom: 1px solid transparent;
        }
        h2:first-child { padding-top: 0; }
        h3 {
            font-size: 18px;
            font-weight: 650;
            margin: 36px 0 12px;
            color: var(--color-text-primary);
            letter-spacing: -0.01em;
        }
        h4 {
            font-size: 12px;
            font-weight: 700;
            margin: 24px 0 8px;
            color: var(--color-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        p {
            margin: 0 0 16px;
            color: var(--color-text-secondary);
            font-size: 14.5px;
            line-height: 1.6;
        }
        .subtitle {
            color: var(--color-text-muted);
            font-size: 14px;
            margin: 0 0 32px;
        }

        /* Endpoint blocks */
        .endpoint {
            background: var(--bg-card);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            margin: 20px 0 28px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: border var(--transition-normal), background var(--transition-normal), box-shadow var(--transition-normal);
        }
        .endpoint:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--color-border-hover);
        }
        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--color-border);
            background: rgba(255, 255, 255, 0.01);
        }
        .endpoint-header .method {
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.04em;
        }
        .method.get { background: var(--green-soft); color: var(--green); }
        .method.post { background: var(--primary-soft); color: var(--primary); }
        .method.put { background: var(--orange-soft); color: var(--orange); }
        .method.delete { background: var(--red-soft); color: var(--red); }
        
        .endpoint-header .path {
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            font-weight: 550;
            color: var(--color-text-primary);
        }
        .endpoint-header .path .param {
            color: #E28743;
            font-weight: 600;
        }
        .endpoint-body {
            padding: 20px;
        }

        /* Code blocks */
        pre {
            background: var(--bg-code);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.7;
            margin: 12px 0 16px;
            position: relative; /* Para botón de copiado */
        }
        pre code {
            color: #E2E8F0;
        }
        .code-tabs {
            display: flex;
            gap: 4px;
            background: var(--bg-hover);
            padding: 4px 8px 0;
            border-bottom: 1px solid var(--color-border);
            margin-bottom: 0;
            transition: background var(--transition-normal), border var(--transition-normal);
        }
        .code-tab {
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            color: var(--color-text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all var(--transition-fast);
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
            font-family: 'Inter', sans-serif;
            border-radius: var(--radius-sm) var(--radius-sm) 0 0;
        }
        .code-tab:hover {
            color: var(--color-text-secondary);
            background: rgba(255, 255, 255, 0.03);
        }
        .code-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: var(--bg-card);
        }
        .code-panel {
            display: none;
        }
        .code-panel.active {
            display: block;
        }
        code.inline {
            background: var(--bg-inline-code);
            padding: 2px 7px;
            border-radius: var(--radius-xs);
            font-size: 13px;
            color: var(--primary);
            font-weight: 500;
            transition: background var(--transition-normal), color var(--transition-normal);
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 20px;
            font-size: 13.5px;
        }
        th {
            text-align: left;
            padding: 10px 14px;
            font-weight: 600;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-text-muted);
            border-bottom: 2px solid var(--color-border);
        }
        td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--color-border);
            color: var(--color-text-secondary);
            vertical-align: top;
            transition: border var(--transition-normal), color var(--transition-normal);
        }
        td code {
            background: var(--bg-inline-code);
            padding: 2px 6px;
            border-radius: var(--radius-xs);
            font-size: 12px;
            color: var(--color-text-primary);
            transition: background var(--transition-normal), color var(--transition-normal);
        }
        tr:last-child td {
            border-bottom: none;
        }

        /* Alerts */
        .alert {
            border-radius: var(--radius-md);
            padding: 14px 18px;
            margin: 16px 0;
            font-size: 13.5px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            line-height: 1.5;
            transition: background var(--transition-normal), border var(--transition-normal);
        }
        .alert-info {
            background: var(--primary-soft);
            border: 1px solid rgba(11, 72, 76, 0.15);
            color: var(--color-text-secondary);
        }
        [data-theme="dark"] .alert-info {
            background: rgba(0, 223, 131, 0.08);
            border-color: rgba(0, 223, 131, 0.15);
        }
        .alert-info strong {
            color: var(--color-text-primary);
        }
        .alert-warning {
            background: var(--orange-soft);
            border: 1px solid rgba(217, 119, 6, 0.15);
            color: var(--color-text-secondary);
        }
        .alert-warning strong {
            color: var(--color-text-primary);
        }
        .alert-success {
            background: var(--green-soft);
            border: 1px solid rgba(16, 185, 129, 0.15);
            color: var(--color-text-secondary);
        }
        .alert-success strong {
            color: var(--color-text-primary);
        }
        .alert-danger {
            background: var(--red-soft);
            border: 1px solid rgba(239, 68, 68, 0.15);
            color: var(--color-text-secondary);
        }
        .alert-danger strong {
            color: var(--color-text-primary);
        }

        /* Badges & Tags */
        .tag {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: var(--radius-xs);
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .tag-required { background: var(--red-soft); color: var(--red); }
        .tag-optional { background: var(--bg-hover); color: var(--color-text-muted); }
        .tag-string { background: var(--green-soft); color: var(--green); }
        .tag-number { background: var(--purple-soft); color: var(--purple); }
        .tag-array { background: var(--orange-soft); color: var(--orange); }
        .tag-object { background: var(--cyan-soft); color: var(--cyan); }
        .tag-boolean { background: rgba(6, 182, 212, 0.12); color: var(--cyan); }

        /* Dynamic Copy Button styling */
        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #94A3B8;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 11px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all var(--transition-fast);
            opacity: 0;
        }
        pre:hover .copy-btn {
            opacity: 1;
        }
        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #FFFFFF;
            border-color: rgba(255, 255, 255, 0.25);
        }
        .copy-btn.copied {
            background: rgba(16, 185, 129, 0.12);
            border-color: rgba(16, 185, 129, 0.3);
            color: #10B981;
            opacity: 1 !important;
        }

        /* Section divider */
        .divider { height: 1px; background: var(--color-border); margin: 40px 0; transition: background var(--transition-normal); }
        section { scroll-margin-top: 80px; }

        /* Status codes list */
        .status-list { list-style: none; padding: 0; }
        .status-list li { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--color-border); transition: border var(--transition-normal); }
        .status-list li:last-child { border-bottom: none; }
        .status-code { font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 14px; min-width: 38px; }

        .hero-banner {
            background: linear-gradient(135deg, #0B484C 0%, #064347 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-lg);
            padding: 36px 32px;
            margin-bottom: 36px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        .hero-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0, 223, 131, 0.12) 0%, transparent 70%);
        }
        .hero-banner h2 { padding: 0; margin: 0 0 10px; font-size: 28px; font-weight: 700; color: #FFFFFF; }
        .hero-banner p { color: rgba(255, 255, 255, 0.85); margin: 0; font-size: 15px; }
        .hero-banner .version { position: absolute; top: 20px; right: 24px; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--color-border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--color-text-muted); }

        /* Mobile */
        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: 4px 0 20px rgba(0,0,0,0.1);
                transition: transform var(--transition-normal);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main {
                margin-left: 0;
                padding: 24px 20px 60px;
            }
            .topbar-header {
                padding: 0 16px;
            }
            .hamburger-btn {
                display: flex !important;
            }
            .topbar-logo {
                height: 24px;
            }
            .topbar-badge {
                display: none;
            }
            .topbar-divider {
                display: none;
            }
            .download-md-btn span {
                display: none;
            }
            .download-md-btn {
                padding: 8px;
            }
        }
    </style>
</head>
<body>

<!-- Header Superior de Marca -->
<header class="topbar-header">
    <div class="topbar-left">
        <!-- Botón Hamburguesa Móvil -->
        <button onclick="toggleSidebar()" class="hamburger-btn" aria-label="Abrir menú" style="display:none;background:none;border:none;color:#FFFFFF;cursor:pointer;align-items:center;justify-content:center;width:36px;height:36px;border-radius:var(--radius-md);margin-right:4px;transition:background var(--transition-fast);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="22" height="22">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <a href="/" class="topbar-logo-link">
            <img src="https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png" alt="GridBase Bills Logo" class="topbar-logo">
        </a>
        <span class="topbar-divider"></span>
        <span class="topbar-badge">Documentación API <span class="version-label">v1.0</span></span>
    </div>
    <div class="topbar-right">
        <!-- Botón de Descarga Markdown -->
        <a href="/docs/api-docs.md" download class="download-md-btn" title="Descargar documentación en Markdown">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>Descargar MD</span>
        </a>
        
        <!-- Alternador de Tema -->
        <button onclick="toggleTheme()" class="theme-toggle-btn" title="Cambiar tema" aria-label="Cambiar tema">
            <svg id="theme-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
                <!-- SVG Dinámico -->
            </svg>
        </button>
        
        <a href="/" class="back-dashboard-btn">
            <span>Ir al Panel</span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </a>
    </div>
</header>

<!-- Contenedor Principal con Layout Adaptativo -->
<div class="layout">
    <aside class="sidebar">
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
                <span>Para obtener una API Key, ingresa a <strong>Configuración → API Keys</strong> en tu panel de Gridbase Bills y crea una nueva.</span>
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
                <pre><code>Authorization: Bearer {{ $api_key ?? 'gb_tu_api_key_aqui' }}</code></pre>
            </div>

            <div class="alert alert-warning">
                <span><strong>Nunca expongas tu API Key</strong> en código del lado del cliente (JavaScript del navegador). Siempre usa la API desde tu servidor backend.</span>
            </div>

            <h4>Obtener tu API Key</h4>
            <ol style="color:var(--text-secondary);font-size:14px;padding-left:20px;margin-bottom:20px;line-height:2;">
                <li>Inicia sesión como administrador en Gridbase Bills</li>
                <li>Ve a <strong>Configuración → API Keys</strong></li>
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
                    <tr><td><code>Authorization</code></td><td><code>Bearer {{ $api_key ?? 'gb_tu_api_key_aqui' }}</code></td><td><span class="tag tag-required">Requerido</span></td></tr>
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
        <h2 style="padding-top:0;">Facturas</h2>
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
                        <span>Debes proporcionar <code>client_id</code> <strong>o</strong> un objeto <code>client</code>, no ambos. Si envías <code>client</code>, el sistema buscará un cliente existente por <code>tax_id</code> o <code>email</code>, y lo creará si no existe.</span>
                    </div>

                    <h4>Ejemplo de Request</h4>
                    <pre><code>POST /api/v1/invoices
Content-Type: application/json
Authorization: Bearer {{ $api_key ?? 'gb_tu_api_key_aqui' }}

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
        <h2>Cotizaciones</h2>
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
                        <span>Una cotización solo puede convertirse una vez. Si ya fue convertida, recibirás un <code>409 Conflict</code>.</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="divider"></div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- CLIENTS -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <h2>Clientes</h2>
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
            <h2>Upsert de Clientes</h2>
            <p>Cuando creas una factura o cotización con el objeto <code class="inline">client</code> (en lugar de <code class="inline">client_id</code>), el sistema usa un patrón <strong>upsert</strong>:</p>

            <ol style="color:var(--text-secondary);font-size:14px;padding-left:20px;margin-bottom:20px;line-height:2.2;">
                <li>Busca un cliente existente con el mismo <code class="inline">tax_id</code> (RNC/Cédula)</li>
                <li>Si no lo encuentra, busca por <code class="inline">email</code></li>
                <li>Si encuentra coincidencia, <strong>reutiliza ese cliente</strong> (no lo duplica)</li>
                <li>Si no encuentra ninguno, <strong>crea un nuevo cliente</strong></li>
            </ol>

            <div class="alert alert-success">
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
            <h2>Permisos</h2>
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
            <h2>Códigos de Estado HTTP</h2>

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
            <h2>Ejemplos por Lenguaje</h2>
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
  -H "Authorization: Bearer {{ $api_key ?? 'gb_tu_api_key_aqui' }}" \
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

const API_KEY = '{{ $api_key ?? 'gb_tu_api_key_aqui' }}';
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

  console.log(`Factura ${factura.invoice_number} creada por DOP ${factura.total}`);
} catch (error) {
  console.error('Error:', error.message);
}</code></pre>
                </div>

                <!-- PHP -->
                <div class="code-panel" id="panel-php">
                    <pre style="margin:0;border:none;border-radius:0;"><code>&lt;?php
// ═══ PHP (cURL nativo) ═══

define('BILLS_API_KEY', '{{ $api_key ?? 'gb_tu_api_key_aqui' }}');
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

API_KEY = "{{ $api_key ?? 'gb_tu_api_key_aqui' }}"
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

    print(f"Factura {factura['invoice_number']} creada - Total: {factura['total']}")
except Exception as e:
    print(f"Error: {e}")</code></pre>
                </div>

                <!-- C# -->
                <div class="code-panel" id="panel-csharp">
                    <pre style="margin:0;border:none;border-radius:0;"><code>// ═══ C# / .NET (HttpClient) ═══

using System.Net.Http.Json;

const string API_KEY = "{{ $api_key ?? 'gb_tu_api_key_aqui' }}";
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

define('BILLS_API_KEY', '{{ $api_key ?? 'gb_tu_api_key_aqui' }}');
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
            "Factura Bills: {$body['data']['invoice_number']} (Total: {$body['data']['total']})"
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

<!-- Footer -->
        <div style="margin-top:60px;padding-top:24px;border-top:1px solid var(--color-border);text-align:center;color:var(--color-text-muted);font-size:13px;transition: border var(--transition-normal);">
            <p>Gridbase Bills API v1 — Documentación de API oficial</p>
            <p style="margin:4px 0 0;">¿Necesitas ayuda adicional? Contacta a <a href="mailto:soporte@gridbase.com.do" style="color:var(--primary);font-weight:600;transition:color var(--transition-fast);">soporte@gridbase.com.do</a></p>
        </div>

    </main>
</div>

<script>
// Manejo del cambio de tema Claro / Oscuro
function toggleTheme() {
    const html = document.documentElement;
    const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const icon = document.getElementById('theme-icon');
    if (!icon) return;
    if (theme === 'dark') {
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z"/>'; // Icono de Sol
    } else {
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>'; // Icono de Luna
    }
}

// Cargar icono inicial de tema
updateThemeIcon(document.documentElement.getAttribute('data-theme') || 'light');

// Alternar barra lateral en móvil
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

// Cerrar sidebar al hacer clic en un link en móvil
document.querySelectorAll('.sidebar-nav a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 900) {
            document.querySelector('.sidebar').classList.remove('active');
        }
    });
});

// Pestañas de código interactivo
document.querySelectorAll('.code-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const lang = tab.dataset.lang;
        const parent = tab.closest('.code-tabs').parentNode;
        
        // Desactivar pestañas anteriores del mismo contenedor
        tab.closest('.code-tabs').querySelectorAll('.code-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        
        // Ocultar paneles anteriores
        parent.querySelectorAll('.code-panel').forEach(p => p.classList.remove('active'));
        
        // Mostrar panel activo
        parent.querySelector('#panel-' + lang).classList.add('active');
    });
});

// Autogeneración del Botón de Copiar con Micro-animación en cada bloque pre
document.querySelectorAll('pre').forEach(preBlock => {
    preBlock.style.position = 'relative';
    
    const copyBtn = document.createElement('button');
    copyBtn.className = 'copy-btn';
    copyBtn.setAttribute('aria-label', 'Copiar código');
    copyBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
        </svg>
        <span>Copiar</span>
    `;
    
    copyBtn.addEventListener('click', async () => {
        const codeElement = preBlock.querySelector('code');
        const codeText = codeElement ? codeElement.innerText : preBlock.innerText;
        
        try {
            await navigator.clipboard.writeText(codeText.trim());
            copyBtn.classList.add('copied');
            copyBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#10B981" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span style="color:#10B981;font-weight:600;">¡Copiado!</span>
            `;
            setTimeout(() => {
                copyBtn.classList.remove('copied');
                copyBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                    </svg>
                    <span>Copiar</span>
                `;
            }, 2000);
        } catch (err) {
            console.error('No se pudo copiar el código: ', err);
        }
    });
    
    preBlock.appendChild(copyBtn);
});

// Barra lateral activa dinámica al hacer scroll
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
}, { rootMargin: '-20% 0px -60% 0px' });

sections.forEach(section => observer.observe(section));
</script>

</body>
</html>
