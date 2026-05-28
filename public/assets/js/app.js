/**
 * GridBase Digital Solutions — Bills System
 * Main Frontend Application Logic — Gridbase Design Kit v3
 */

import DashboardModule from './modules/dashboard.js?v=52';
import InvoicesModule from './modules/invoices.js?v=52';
import QuotesModule from './modules/quotes.js?v=52';
import ClientsModule from './modules/clients.js?v=52';
import ItemsModule from './modules/items.js?v=52';
import SettingsModule from './modules/settings.js?v=52';
import RecurringModule from './modules/recurring.js?v=52';
import DgiiTestsModule from './modules/dgii-tests.js?v=52';
import ReceivedInvoicesModule from './modules/received-invoices.js?v=52';
import ReportsModule from './modules/reports.js?v=52';
import SetupModule from './modules/setup.js?v=52';
import ExpensesModule from './modules/expenses.js?v=52';
import UsersModule from './modules/users.js?v=52';



window.App = {
    state: {
        user: null,
        token: null,
        currentRoute: 'dashboard'
    },

    isMobile() { return window.innerWidth <= 640; },

    init() {
        // Load cached favicon immediately for instant branding load
        const cachedFavicon = localStorage.getItem('company_favicon');
        if (cachedFavicon) {
            const link = document.querySelector("link[rel~='icon']");
            if (link) link.href = cachedFavicon;
            const appleLink = document.querySelector("link[rel='apple-touch-icon']");
            if (appleLink) appleLink.href = cachedFavicon;
        }

        this.checkAuth();
        this.setupRouter();
    },

    async api(endpoint, options = {}) {
        const url = `/api/${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        if (options.body && typeof options.body !== 'string') {
            options.body = JSON.stringify(options.body);
        }

        try {
            const response = await fetch(url, { ...options, headers, credentials: 'same-origin' });
            const text = await response.text();
            let data;
            try {
                data = text ? JSON.parse(text) : {};
            } catch (parseErr) {
                console.error('API response not JSON:', text.substring(0, 500));
                throw new Error('Error del servidor. Revisa la consola para detalles.');
            }

            if (!response.ok) {
                if (response.status === 401 && this.state.currentRoute !== 'login') {
                    this.logout(false);
                }
                throw new Error(data.error || `Error ${response.status}`);
            }
            return data;
        } catch (error) {
            if (!options.silent) {
                this.showToast(error.message, 'error');
            }
            throw error;
        }
    },

    async checkAuth() {
        try {
            const res = await this.api('auth/session', { silent: true });
            if (res.authenticated) {
                this.state.user = res.user;
                
                // Fetch settings to check if installed
                const settings = await this.api('settings');
                this.state.settings = settings;
                
                // Cache branding elements in localStorage
                if (settings.company_logo) localStorage.setItem('company_logo', settings.company_logo);
                if (settings.company_favicon) localStorage.setItem('company_favicon', settings.company_favicon);
                this.updateFavicon();
                
                if (settings.is_installed !== '1') {
                    this.renderSetupWizard();
                } else {
                    this.renderAppShell();
                    const currentRoute = window.location.pathname.substring(1) || 'inicio';
                    this.navigate(currentRoute);
                }
            }
        } catch (error) {
            this.renderLogin();
        }
    },

    async login(email, password) {
        try {
            const res = await this.api('auth/login', {
                method: 'POST',
                body: { email, password }
            });
            if (res.requires_2fa) {
                this.render2FA(res.setup_mode, res.temp_secret, res.qr_uri);
                return;
            }
            if (res.success) {
                this.state.user = res.user;
                
                // Fetch settings to check if installed
                const settings = await this.api('settings');
                this.state.settings = settings;
                
                // Cache branding elements in localStorage
                if (settings.company_logo) localStorage.setItem('company_logo', settings.company_logo);
                if (settings.company_favicon) localStorage.setItem('company_favicon', settings.company_favicon);
                this.updateFavicon();
                
                if (settings.is_installed !== '1') {
                    this.renderSetupWizard();
                } else {
                    this.renderAppShell();
                    this.navigate('inicio');
                }
            }
        } catch (error) {
            const errorEl = document.getElementById('login-error');
            if (errorEl) {
                errorEl.textContent = error.message;
                errorEl.style.display = 'block';
            }
        }
    },

    renderSetupWizard() {
        this.state.currentRoute = 'setup';
        history.pushState(null, '', '/configuracion-inicial');
        const app = document.getElementById('app');
        if (app) {
            SetupModule.render(app);
        }
    },

    updateFavicon() {
        const faviconUrl = this.state.settings?.company_favicon;
        if (faviconUrl) {
            let link = document.querySelector("link[rel~='icon']");
            if (!link) {
                link = document.createElement('link');
                link.rel = 'icon';
                document.getElementsByTagName('head')[0].appendChild(link);
            }
            link.href = faviconUrl;
            
            const appleLink = document.querySelector("link[rel='apple-touch-icon']");
            if (appleLink) {
                appleLink.href = faviconUrl;
            }
        }
    },

    async logout(callApi = true) {
        if (callApi) {
            try { await this.api('auth/logout', { method: 'POST' }); } catch (e) { }
        }
        this.state.user = null;
        this.state.currentRoute = 'login';
        history.pushState(null, '', '/acceso');
        this.renderLogin();
    },

    setupRouter() {
        window.addEventListener('popstate', () => {
            const route = window.location.pathname.substring(1) || 'inicio';
            if (this.state.user && route !== 'login' && !route.startsWith('fe/')) {
                this.navigate(route, false);
            }
        });

        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link) return;
            const href = link.getAttribute('href');
            const target = link.getAttribute('target');
            if (!href || href.startsWith('http') || href.startsWith('mailto:') || href.startsWith('tel:') || target === '_blank' || href.includes('/api/')) return;
            e.preventDefault();
            let route = href;
            if (route.startsWith('#')) route = route.substring(1);
            else if (route.startsWith('/')) route = route.substring(1);
            this.navigate(route || 'dashboard');
        });
    },

    navigate(route, pushToHistory = true) {
        if (!this.state.user) return this.renderLogin();
        
        // If not installed, force Setup Wizard
        if (this.state.settings && this.state.settings.is_installed !== '1') {
            return this.renderSetupWizard();
        }

        route = route.replace('#', '').replace(/^\//, '');
        const parts = route.split('/');
        const view = parts[0];

        // Roles & Permissions Redirection Control
        const role = this.state.user.role || 'admin';
        
        // Restricted views per role
        const restricted = {
            vendedor: ['recurrentes', 'recurring', 'gastos', 'expenses', 'usuarios', 'users', 'configuracion', 'settings', 'pruebas-dgii', 'dgii-tests', 'facturas-recibidas', 'received-invoices', 'reportes', 'reports'],
            contador: ['usuarios', 'users', 'configuracion', 'settings', 'pruebas-dgii', 'dgii-tests']
        };

        if (restricted[role] && restricted[role].includes(view)) {
            this.showToast('No tienes permiso para acceder a esta sección.', 'error');
            route = 'inicio';
            if (pushToHistory) history.pushState(null, '', '/inicio');
            this.state.currentRoute = 'inicio';
        } else {
            this.state.currentRoute = route;
            if (pushToHistory) history.pushState(null, '', '/' + route);
        }

        const activeRoute = route.split('/')[0];
        document.querySelectorAll('.sidebar-link, .tab-link').forEach(link => {
            link.classList.remove('active');
            if (link.classList.contains('tab-fab')) return;
            const linkHref = link.getAttribute('href') || '';
            const cleanHref = linkHref.replace('#', '').replace(/^\//, '').split('/')[0];
            if (cleanHref === activeRoute) link.classList.add('active');
        });

        document.getElementById('sidebar')?.classList.remove('open');
        document.getElementById('sidebar-overlay')?.classList.remove('open');

        const appContent = document.getElementById('app-content');
        if (!appContent) return;
        appContent.innerHTML = `<div class="text-center mt-24"><div class="spinner mx-auto"></div></div>`;

        setTimeout(() => {
            const subParts = route.split('/');
            const subView = subParts[0];
            const subId = subParts.length > 1 ? subParts.slice(1).join('/') : undefined;
            switch (subView) {
                case 'inicio': case 'dashboard': DashboardModule.render(appContent); break;
                case 'facturas': case 'invoices': InvoicesModule.render(appContent, subId); break;
                case 'cotizaciones': case 'quotes': QuotesModule.render(appContent, subId); break;
                case 'clientes': case 'clients': ClientsModule.render(appContent, subId); break;
                case 'articulos': case 'items': ItemsModule.render(appContent, subId); break;
                case 'recurrentes': case 'recurring': RecurringModule.render(appContent, subId); break;
                case 'gastos': case 'expenses': ExpensesModule.render(appContent, subId); break;
                case 'usuarios': case 'users': UsersModule.render(appContent, subId); break;
                case 'configuracion': case 'settings': SettingsModule.render(appContent); break;
                case 'pruebas-dgii': case 'dgii-tests': DgiiTestsModule.render(appContent); break;
                case 'facturas-recibidas': case 'received-invoices': ReceivedInvoicesModule.render(appContent); break;
                case 'reportes': case 'reports': ReportsModule.render(appContent); break;
                default: appContent.innerHTML = '<h2>404 No Encontrado</h2>';
            }
        }, 50);
    },

    /* ═══════════════════════════════════════════════
       LOGIN — Gridbase Design Kit
       ═══════════════════════════════════════════════ */
    renderLogin() {
        const cachedLogo = localStorage.getItem('company_logo') || 'https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png';
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="login-page">
                <div class="login-card">
                    <div class="login-logo" style="display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <div style="background: #111827; padding: 8px 24px; border-radius: var(--radius-xl); border: 1px solid rgba(255, 255, 255, 0.08); display: inline-flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md); max-height: 56px;">
                            <img src="${cachedLogo}" alt="Logo" style="max-height:40px;max-width:100%;object-fit:contain;">
                        </div>
                    </div>
                    <h1 class="login-title">GridBase Bills</h1>
                    <p class="login-subtitle">Inicia sesión en tu cuenta</p>
                    <div id="login-error" class="login-error"></div>
                    <form id="login-form">
                        <div class="form-group text-left">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" id="login-email" class="form-control" placeholder="tu@correo.com" required autocomplete="email">
                        </div>
                        <div class="form-group text-left">
                            <label class="form-label">Contraseña</label>
                            <input type="password" id="login-password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px;">Iniciar Sesión</button>
                    </form>
                    <p style="margin-top: 20px; font-size: 11px; color: var(--color-text-muted);">
                        Powered by <span style="color: var(--color-primary); font-weight: 600;">GridBase</span> Digital Solutions
                    </p>
                </div>
            </div>
        `;
        document.getElementById('login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.login(document.getElementById('login-email').value, document.getElementById('login-password').value);
        });
    },

    render2FA(setupMode, tempSecret, qrUri) {
        const cachedLogo = localStorage.getItem('company_logo') || 'https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png';
        const app = document.getElementById('app');
        
        let subHtml = '';
        if (setupMode) {
            subHtml = `
                <h1 class="login-title">Configurar Doble Factor (2FA)</h1>
                <p class="login-subtitle">Protege tu cuenta activando el segundo factor</p>
                <div id="login-error" class="login-error"></div>
                
                <div style="background:var(--color-danger-bg); color:var(--color-danger-text); border:1px solid rgba(239, 68, 68, 0.25); border-radius:var(--radius-md); padding:10px 12px; font-size:11px; margin-bottom:20px; text-align:center; font-weight:600; line-height:1.4; letter-spacing:0.1px;">
                    Por motivos de seguridad y resguardo de su información, la activación del segundo factor de autenticación (2FA) es obligatoria para acceder a la plataforma.
                </div>
                
                <p style="font-size:12px; color:var(--color-text-secondary); margin-bottom:20px; line-height:1.5; text-align:center;">
                    Escanea este código QR con tu aplicación autenticadora (Google Authenticator, Authy, etc.) e ingresa el código de 6 dígitos para activarlo.
                </p>
                
                <div style="display:flex; justify-content:center; margin-bottom:20px; background:#ffffff; padding:12px; border-radius:var(--radius-md); border:1px solid var(--color-border); width:fit-content; margin-left:auto; margin-right:auto; box-shadow:var(--shadow-sm);">
                    <canvas id="qr-canvas"></canvas>
                </div>
                
                <div style="background:var(--bg-page); border:1px solid var(--color-border); border-radius:var(--radius-md); padding:10px 12px; font-size:12px; margin-bottom:20px; text-align:left; word-break:break-all; font-family:monospace; display:flex; justify-content:space-between; align-items:center; gap:8px;">
                    <div>
                        <span style="color:var(--color-text-muted); font-size:9px; font-weight:600; text-transform:uppercase; display:block; letter-spacing:0.05em; margin-bottom:2px;">Clave manual</span>
                        <span style="color:var(--color-text-primary); font-size:13px; font-weight:700;">${tempSecret}</span>
                    </div>
                </div>
            `;
        } else {
            subHtml = `
                <h1 class="login-title">Verificación de Seguridad</h1>
                <p class="login-subtitle">Ingresa el código dinámico de 6 dígitos generado por tu aplicación</p>
                <div id="login-error" class="login-error"></div>
            `;
        }
        
        app.innerHTML = `
            <div class="login-page">
                <div class="login-card animate-fade-in">
                    <div class="login-logo" style="display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <div style="background: #111827; padding: 8px 24px; border-radius: var(--radius-xl); border: 1px solid rgba(255, 255, 255, 0.08); display: inline-flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md); max-height: 56px;">
                            <img src="${cachedLogo}" alt="Logo" style="max-height:40px;max-width:100%;object-fit:contain;">
                        </div>
                    </div>
                    ${subHtml}
                    <form id="2fa-form">
                        <div class="form-group text-left">
                            <label class="form-label" style="text-align:center;">Código de Seguridad (2FA)</label>
                            <input type="text" id="2fa-code" class="form-control" placeholder="000 000" pattern="[0-9]*" inputmode="numeric" maxlength="6" required autofocus autocomplete="one-time-code" style="text-align:center; font-size:24px; letter-spacing:0.1em; padding:8px 12px;">
                        </div>
                        <div style="display:flex; gap:12px; margin-top:16px;">
                            <button type="button" id="btn-cancel-2fa" class="btn btn-secondary" style="flex:1;">Regresar</button>
                            <button type="submit" class="btn btn-primary" style="flex:1;">Verificar</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        if (setupMode) {
            if (typeof QRious === 'undefined') {
                const script = document.createElement('script');
                script.src = "https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js";
                script.onload = () => {
                    new QRious({
                        element: document.getElementById('qr-canvas'),
                        value: qrUri,
                        size: 160,
                        background: '#ffffff',
                        foreground: '#111827',
                        level: 'H'
                    });
                };
                document.head.appendChild(script);
            } else {
                new QRious({
                    element: document.getElementById('qr-canvas'),
                    value: qrUri,
                    size: 160,
                    background: '#ffffff',
                    foreground: '#111827',
                    level: 'H'
                });
            }
        }
        
        document.getElementById('btn-cancel-2fa').addEventListener('click', () => {
            this.logout(false);
        });
        
        document.getElementById('2fa-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const code = document.getElementById('2fa-code').value.trim();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner"></span>`;
            
            try {
                const res = await this.api('auth/verify-2fa', {
                    method: 'POST',
                    body: { code }
                });
                
                if (res.success) {
                    this.state.user = res.user;
                    
                    const settings = await this.api('settings');
                    this.state.settings = settings;
                    
                    if (settings.is_installed !== '1') {
                        this.renderSetupWizard();
                    } else {
                        this.renderAppShell();
                        this.navigate('inicio');
                    }
                }
            } catch (error) {
                btn.disabled = false;
                btn.innerHTML = 'Verificar';
                const errorEl = document.getElementById('login-error');
                if (errorEl) {
                    errorEl.textContent = error.message;
                    errorEl.style.display = 'block';
                }
            }
        });
    },

    /* ═══════════════════════════════════════════════
       APP SHELL — Gridbase Design Kit
       White sidebar, vertical active line, profile-card,
       search-wrapper with keycap, workspace-panel
       ═══════════════════════════════════════════════ */
    renderAppShell() {
        const app = document.getElementById('app');
        const userInitial = this.state.user.name ? this.state.user.name.charAt(0).toUpperCase() : '?';
        const logoSrc = this.state.settings?.company_logo || 'https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png';

        app.innerHTML = `
            <div class="app-container">
                <div class="sidebar-overlay" id="sidebar-overlay"></div>
                <aside class="sidebar" id="sidebar">
                    <div class="sidebar-logo" style="padding: 16px 12px; display: flex; justify-content: center; align-items: center;">
                        <div class="logo-backdrop" style="background: #111827; padding: 6px 16px; border-radius: var(--radius-lg); border: 1px solid rgba(255, 255, 255, 0.08); display: inline-flex; align-items: center; justify-content: center; max-height: 44px; max-width: 100%; box-shadow: var(--shadow-sm);">
                            <img src="${logoSrc}" alt="Logo" style="max-height: 28px; max-width: 100%; object-fit: contain;">
                        </div>
                    </div>
                    <nav class="sidebar-nav">
                        <div class="sidebar-section-title">Menú</div>
                        <ul class="sidebar-menu">
                            <li><a href="/inicio" class="sidebar-link active"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>Panel</span></a></li>
                            <li><a href="/facturas" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>Facturas</span><span id="overdue-badge" style="display:none;background:var(--color-danger-bg);color:var(--color-danger-text);font-size:11px;padding:2px 8px;border-radius:var(--radius-full);font-weight:600;"></span></a></li>
                            ${this.state.user.role !== 'vendedor' ? `<li><a href="/recurrentes" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>Recurrentes</span></a></li>` : ''}
                            <li><a href="/cotizaciones" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>Cotizaciones</span></a></li>
                            <li><a href="/clientes" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>Clientes</span></a></li>
                            <li><a href="/articulos" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>Artículos</span></a></li>
                            ${this.state.user.role !== 'vendedor' ? `
                                <li><a href="/facturas-recibidas" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>Facturas Recibidas</span></a></li>
                                <li><a href="/gastos" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><line x1="12" y1="1" x2="12" y2="23"></line><line x1="17" y1="5" x2="9.5" y2="5"></line><path d="M9.5 5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>Gastos</span></a></li>
                                <li><a href="/reportes" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>Reportes DGII</span></a></li>
                            ` : ''}
                        </ul>
                        ${this.state.user.role === 'admin' ? `
                            <div class="sidebar-section-title" style="margin-top: var(--spacing-xl);">Sistema</div>
                            <ul class="sidebar-menu">
                                <li><a href="/usuarios" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>Usuarios</span></a></li>
                                <li><a href="/configuracion" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>Configuración</span></a></li>
                                <li><a href="/pruebas-dgii" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="2"></rect><path d="M9 14l2 2 4-4"></path></svg>Pruebas DGII</span></a></li>
                            </ul>
                        ` : ''}
                    </nav>
                    <div class="sidebar-footer">
                        <div class="profile-card" onclick="App.logout()" title="Cerrar Sesión">
                            <div class="profile-avatar">${userInitial}</div>
                            <div class="profile-info">
                                <div class="profile-name">${this.state.user.name}</div>
                                <div class="profile-role" style="text-transform: capitalize; font-weight: 600; font-size: 11px; color: var(--color-primary);">${this.state.user.role || 'admin'}</div>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        </div>
                    </div>
                </aside>
                <main class="main-content">
                    <div class="topbar">
                        <div style="display:flex;align-items:center;gap:12px">
                            <button class="btn-icon sidebar-toggle" id="sidebar-toggle" onclick="App.toggleSidebar()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                            </button>
                            <div id="greeting-text" style="font-size:14px;font-weight:600;color:var(--color-text);white-space:nowrap;">
                                ${this.getGreeting()}, <span style="color:var(--color-primary)">${this.state.user.name.split(' ')[0]}</span> 👋
                            </div>
                            <div class="search-wrapper" id="search-wrapper">
                                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input class="search-input" type="text" placeholder="Buscar facturas, clientes..." id="global-search-input">
                                <div class="search-shortcuts"><span class="keycap">⌘</span><span class="keycap">K</span></div>
                            </div>
                        </div>
                        <div class="topbar-actions">
                            <div id="dgii-status-pill" style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:var(--radius-full);font-size:11px;font-weight:600;letter-spacing:0.3px;cursor:pointer;transition:all .2s ease;background:var(--color-border);color:var(--color-text-muted);" onclick="App.navigate('pruebas-dgii')" title="Estado de conexión DGII">
                                <span id="dgii-status-dot" style="width:7px;height:7px;border-radius:50%;background:currentColor;flex-shrink:0;"></span>
                                <span id="dgii-status-label">DGII...</span>
                            </div>
                            <button class="btn-icon" id="theme-toggle" onclick="App.toggleTheme()" title="Cambiar Tema" style="display:inline-flex;align-items:center;justify-content:center;"></button>
                            <button class="btn-icon" title="Notificaciones">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                            </button>
                            <div class="vertical-divider"></div>
                            <button class="btn btn-primary" onclick="App.navigate('invoices/new')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                Nueva Factura
                            </button>
                        </div>
                    </div>
                    <div class="workspace-panel" id="app-content"></div>
                </main>
                <nav class="mobile-tab-bar" id="mobile-tab-bar">
                    <a href="/inicio" class="tab-link active"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg><span>Panel</span></a>
                    <a href="/facturas" class="tab-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg><span>Facturas</span></a>
                    <a href="/facturas/nueva" class="tab-link tab-fab"><div class="tab-fab-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></div><span>Nuevo</span></a>
                    <a href="/clientes" class="tab-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg><span>Clientes</span></a>
                    <button class="tab-link" onclick="App.openMoreMenu()" type="button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg><span>Más</span></button>
                </nav>
            </div>
            <div class="toast-container" id="toast-container"></div>
        `;

        document.getElementById('sidebar-overlay')?.addEventListener('click', () => this.toggleSidebar());
        this.loadOverdueBadge();
        this.loadDgiiStatus();
        this.bindSearch();
        this.updateThemeButton();
    },

    async loadOverdueBadge() {
        try {
            const data = await this.api('dashboard');
            const count = data.stats?.overdue_count || 0;
            const badge = document.getElementById('overdue-badge');
            if (badge) {
                if (count > 0) { badge.textContent = count; badge.style.display = 'inline'; }
                else { badge.style.display = 'none'; }
            }
        } catch(e) {}
    },

    async loadDgiiStatus() {
        const pill = document.getElementById('dgii-status-pill');
        const dot = document.getElementById('dgii-status-dot');
        const label = document.getElementById('dgii-status-label');
        if (!pill) return;

        try {
            const res = await this.api('dgii/status', { silent: true });
            const colors = {
                connected: { bg: 'rgba(16,185,129,0.12)', color: '#10b981', border: 'rgba(16,185,129,0.25)' },
                disconnected: { bg: 'rgba(239,68,68,0.12)', color: '#ef4444', border: 'rgba(239,68,68,0.25)' },
                not_configured: { bg: 'rgba(245,158,11,0.12)', color: '#f59e0b', border: 'rgba(245,158,11,0.25)' },
                error: { bg: 'rgba(239,68,68,0.12)', color: '#ef4444', border: 'rgba(239,68,68,0.25)' }
            };
            const c = colors[res.status] || colors.error;
            pill.style.background = c.bg;
            pill.style.color = c.color;
            pill.style.border = `1px solid ${c.border}`;
            dot.style.background = c.color;
            const envTag = res.env === 'production' ? '' : ' (Test)';
            label.textContent = res.label + envTag;
        } catch (e) {
            pill.style.background = 'rgba(107,114,128,0.12)';
            pill.style.color = '#6b7280';
            label.textContent = 'DGII N/D';
        }
    },

    toggleSidebar() {
        document.getElementById('sidebar')?.classList.toggle('open');
        document.getElementById('sidebar-overlay')?.classList.toggle('open');
    },

    openMoreMenu() {
        document.getElementById('more-menu-overlay')?.remove();
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const isDark = currentTheme === 'dark';
        const themeIcon = isDark ? 
            `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>` : 
            `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
        const themeText = isDark ? 'Modo Claro' : 'Modo Oscuro';

        const overlay = document.createElement('div');
        overlay.id = 'more-menu-overlay';
        overlay.className = 'action-sheet-overlay';
        overlay.innerHTML = `
            <div class="action-sheet">
                <div class="action-sheet-title">Navegación</div>
                <button class="action-sheet-item" onclick="App.closeMoreMenu();App.navigate('cotizaciones')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                    Cotizaciones
                </button>
                <button class="action-sheet-item" onclick="App.closeMoreMenu();App.navigate('recurrentes')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    Recurrentes
                </button>
                <button class="action-sheet-item" onclick="App.closeMoreMenu();App.navigate('articulos')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    Artículos
                </button>
                <button class="action-sheet-item" onclick="App.closeMoreMenu();App.navigate('facturas-recibidas')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>
                    Facturas Recibidas
                </button>
                <div class="action-sheet-divider"></div>
                <div class="action-sheet-title">Sistema</div>
                <button class="action-sheet-item" onclick="App.closeMoreMenu();App.navigate('configuracion')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    Configuración
                </button>
                <button class="action-sheet-item" onclick="App.closeMoreMenu();App.navigate('pruebas-dgii')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="2"></rect><path d="M9 14l2 2 4-4"></path></svg>
                    Pruebas DGII
                </button>
                <button class="action-sheet-item" onclick="App.closeMoreMenu();App.toggleTheme()">
                    ${themeIcon}
                    ${themeText}
                </button>
                <div class="action-sheet-divider"></div>
                <button class="action-sheet-item danger" onclick="App.closeMoreMenu();App.logout()">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Cerrar Sesión
                </button>
                <button class="action-sheet-cancel" onclick="App.closeMoreMenu()">Cancelar</button>
            </div>
        `;
        document.body.appendChild(overlay);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) this.closeMoreMenu(); });
    },

    closeMoreMenu() {
        const overlay = document.getElementById('more-menu-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), 200);
        }
    },

    formatCurrency(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: currency || 'USD' }).format(amount);
    },

    formatDate(dateStr) {
        if (!dateStr) return '';
        const cleanDate = dateStr.includes('T') ? dateStr : `${dateStr}T12:00:00`;
        return new Intl.DateTimeFormat('es-DO', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(cleanDate));
    },

    showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        let icon = '';
        if (type === 'success') icon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
        else if (type === 'error') icon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
        toast.innerHTML = `${icon} <span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
    },

    bindSearch() {
        const searchInput = document.getElementById('global-search-input');
        if (!searchInput) return;

        searchInput.addEventListener('input', async (e) => {
            const q = e.target.value.trim().toLowerCase();
            document.getElementById('global-search-results')?.remove();
            if (q.length < 2) return;

            const searchContainer = document.getElementById('search-wrapper');
            const dropdown = document.createElement('div');
            dropdown.id = 'global-search-results';
            dropdown.style.cssText = 'position:absolute;top:100%;left:0;right:0;background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-lg);margin-top:8px;z-index:9999;max-height:400px;overflow-y:auto;padding:8px 0;box-shadow:var(--shadow-lg);';
            dropdown.innerHTML = '<div style="padding:12px 16px;color:var(--color-text-muted);font-size:13px;text-align:center;">Buscando...</div>';
            searchContainer.appendChild(dropdown);

            try {
                const [invRes, cliRes] = await Promise.all([
                    this.api('invoices').catch(()=>({data:[]})),
                    this.api('clients').catch(()=>({data:[]}))
                ]);

                const invoices = (invRes.data || []).filter(i =>
                    (i.invoice_number||'').toLowerCase().includes(q) ||
                    (i.company_name||'').toLowerCase().includes(q) ||
                    (i.contact_name||'').toLowerCase().includes(q)
                );
                const clients = (cliRes.data || []).filter(c =>
                    (c.company_name||'').toLowerCase().includes(q) ||
                    (c.contact_name||'').toLowerCase().includes(q) ||
                    (c.email||'').toLowerCase().includes(q)
                );

                let html = '';
                if (invoices.length > 0) {
                    html += '<div style="padding:4px 16px;font-size:11px;text-transform:uppercase;color:var(--color-text-muted);font-weight:600;letter-spacing:1px;">Facturas</div>';
                    invoices.slice(0, 5).forEach(i => {
                        html += `<a href="#facturas/${i.id}" style="display:block;padding:10px 16px;text-decoration:none;color:inherit;border-bottom:1px solid var(--color-border);font-size:13px;transition:background .15s ease;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''" onclick="document.getElementById('global-search-results').remove();document.getElementById('global-search-input').value=''">
                            <div style="display:flex;justify-content:space-between;">
                                <strong>${i.invoice_number}</strong>
                                <span style="color:var(--color-primary);font-weight:700;">${this.formatCurrency(i.total, i.currency)}</span>
                            </div>
                            <div style="color:var(--color-text-secondary);font-size:12px;margin-top:2px;">${i.company_name || i.contact_name}</div>
                        </a>`;
                    });
                }
                if (clients.length > 0) {
                    html += '<div style="padding:4px 16px;font-size:11px;text-transform:uppercase;color:var(--color-text-muted);font-weight:600;letter-spacing:1px;margin-top:8px;">Clientes</div>';
                    clients.slice(0, 5).forEach(c => {
                        html += `<a href="#clientes/profile/${c.id}" style="display:block;padding:10px 16px;text-decoration:none;color:inherit;border-bottom:1px solid var(--color-border);font-size:13px;transition:background .15s ease;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''" onclick="document.getElementById('global-search-results').remove();document.getElementById('global-search-input').value=''">
                            <div><strong>${c.company_name || c.contact_name}</strong></div>
                            <div style="color:var(--color-text-secondary);font-size:12px;margin-top:2px;">${c.email}</div>
                        </a>`;
                    });
                }
                if (!html) html = '<div style="padding:12px 16px;color:var(--color-text-muted);font-size:13px;text-align:center;">No se encontraron resultados</div>';
                dropdown.innerHTML = html;
            } catch(e) {
                dropdown.innerHTML = '<div style="padding:12px 16px;color:var(--red);font-size:14px;text-align:center;">Error al buscar</div>';
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#search-wrapper')) {
                document.getElementById('global-search-results')?.remove();
            }
        });
    },

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        this.updateThemeButton();
    },

    updateThemeButton() {
        const themeBtn = document.getElementById('theme-toggle');
        if (!themeBtn) return;
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        if (currentTheme === 'dark') {
            themeBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`;
        } else {
            themeBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
        }
    },

    getGreeting() {
        const hour = new Date().getHours();
        if (hour < 12) return 'Buenos Días';
        if (hour < 18) return 'Buenas Tardes';
        return 'Buenas Noches';
    }
};

// Boot application
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
