/**
 * GridBase Digital Solutions — Bills System
 * Main Frontend Application Logic — Gridbase Design Kit v3
 */

import DashboardModule from './modules/dashboard.js?v=201';
import InvoicesModule from './modules/invoices.js?v=203';
import QuotesModule from './modules/quotes.js?v=201';
import ClientsModule from './modules/clients.js?v=201';
import ItemsModule from './modules/items.js?v=201';
import SettingsModule from './modules/settings.js?v=209';
import RecurringModule from './modules/recurring.js?v=201';
import DgiiTestsModule from './modules/dgii-tests.js?v=201';
import DgiiLogsModule from './modules/dgii-logs.js?v=201';
import ReceivedInvoicesModule from './modules/received-invoices.js?v=201';
import ReportsModule from './modules/reports.js?v=201';
import SetupModule from './modules/setup.js?v=201';
import ExpensesModule from './modules/expenses.js?v=201';
import UsersModule from './modules/users.js?v=204';
import { WebAuthnHelper } from './helpers/webauthn-helper.js?v=207';



window.App = {
    state: {
        user: null,
        token: null,
        currentRoute: 'dashboard'
    },

    isMobile() { return window.innerWidth <= 640; },

    init() {
        // Test CI pipeline deploy: stable verification checked successfully
        // Load cached favicon immediately for instant branding load
        const cachedFavicon = localStorage.getItem('company_favicon');
        if (cachedFavicon) {
            const link = document.querySelector("link[rel~='icon']");
            if (link) link.href = cachedFavicon;
            const appleLink = document.querySelector("link[rel='apple-touch-icon']");
            if (appleLink) appleLink.href = cachedFavicon;
        }
        this.applyLoginColors();

        // Proactive network speed detection (Network Information API)
        if (navigator.connection) {
            const checkConnectionSpeed = () => {
                const conn = navigator.connection;
                if (conn.effectiveType === '2g' || conn.effectiveType === '3g' || conn.rtt > 1500 || conn.downlink < 0.5) {
                    this.showToast('Conexión lenta detectada. El sistema podría tardar en responder.', 'warning');
                }
            };
            checkConnectionSpeed();
            navigator.connection.addEventListener('change', checkConnectionSpeed);
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

        // Set a timer to notify the user if connection is slow/sluggish
        let slowConnectionTimer = setTimeout(() => {
            if (!options.silent) {
                this.showToast('Tu conexión a internet parece lenta o inestable. Por favor espera...', 'warning');
            }
        }, 5000); // 5-second threshold

        try {
            const response = await fetch(url, { ...options, headers, credentials: 'same-origin' });
            clearTimeout(slowConnectionTimer);
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
            clearTimeout(slowConnectionTimer);
            let msg = error.message || 'Error de conexión';
            const isNetworkError = !navigator.onLine || 
                msg.toLowerCase().includes('failed to fetch') || 
                msg.toLowerCase().includes('load failed') || 
                msg.toLowerCase().includes('networkerror') ||
                msg.toLowerCase().includes('network error') ||
                msg.toLowerCase().includes('conexion fallida') ||
                msg.toLowerCase().includes('conexión fallida');

            if (isNetworkError) {
                msg = 'No hay internet';
            }

            if (!options.silent) {
                this.showToast(msg, 'error');
            }
            throw new Error(msg);
        }
    },

    async checkAuth() {
        // Load public settings for branding/logos/colors
        try {
            const publicSettings = await this.api('settings/public', { silent: true });
            this.state.settings = { ...this.state.settings, ...publicSettings };
            if (publicSettings.company_logo) localStorage.setItem('company_logo', publicSettings.company_logo);
            if (publicSettings.login_logo) localStorage.setItem('login_logo', publicSettings.login_logo);
            if (publicSettings.company_favicon) localStorage.setItem('company_favicon', publicSettings.company_favicon);
            if (publicSettings.company_name) localStorage.setItem('company_name', publicSettings.company_name);
            if (publicSettings.pdf_primary_color) localStorage.setItem('pdf_primary_color', publicSettings.pdf_primary_color);
            if (publicSettings.sidebar_logo_height) localStorage.setItem('sidebar_logo_height', publicSettings.sidebar_logo_height);
            if (publicSettings.login_logo_height) localStorage.setItem('login_logo_height', publicSettings.login_logo_height);
            this.updateFavicon();
            this.updateTitle();
            this.applyLoginColors();
        } catch (pubErr) {
            console.error('Failed to load public settings:', pubErr);
        }

        try {
            const res = await this.api('auth/session', { silent: true });
            if (res.authenticated) {
                this.state.user = res.user;
                
                // Fetch settings to check if installed
                const settings = await this.api('settings');
                this.state.settings = { ...this.state.settings, ...settings };
                
                // Cache branding elements in localStorage
                if (settings.company_logo) localStorage.setItem('company_logo', settings.company_logo);
                if (settings.login_logo) localStorage.setItem('login_logo', settings.login_logo);
                if (settings.company_favicon) localStorage.setItem('company_favicon', settings.company_favicon);
                if (settings.company_name) localStorage.setItem('company_name', settings.company_name);
                if (settings.pdf_primary_color) localStorage.setItem('pdf_primary_color', settings.pdf_primary_color);
                if (settings.sidebar_logo_height) localStorage.setItem('sidebar_logo_height', settings.sidebar_logo_height);
                if (settings.login_logo_height) localStorage.setItem('login_logo_height', settings.login_logo_height);
                this.updateFavicon();
                this.updateTitle();
                
                if (settings.is_installed !== '1') {
                    this.renderSetupWizard();
                } else {
                    this.renderAppShell();
                    const currentRoute = window.location.pathname.substring(1) || 'inicio';
                    this.navigate(currentRoute);
                }
            }
        } catch (error) {
            const deviceToken = localStorage.getItem('device_token');
            const savedEmail = localStorage.getItem('saved_email');
            if (deviceToken && savedEmail) {
                this.renderPinLogin(savedEmail);
            } else {
                this.renderLogin();
            }
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
                localStorage.setItem('saved_email', res.user.email);
                
                // Fetch settings to check if installed
                const settings = await this.api('settings');
                this.state.settings = { ...this.state.settings, ...settings };
                
                // Cache branding elements in localStorage
                if (settings.company_logo) localStorage.setItem('company_logo', settings.company_logo);
                if (settings.login_logo) localStorage.setItem('login_logo', settings.login_logo);
                if (settings.company_favicon) localStorage.setItem('company_favicon', settings.company_favicon);
                if (settings.sidebar_logo_height) localStorage.setItem('sidebar_logo_height', settings.sidebar_logo_height);
                if (settings.login_logo_height) localStorage.setItem('login_logo_height', settings.login_logo_height);
                this.updateFavicon();
                this.updateTitle();
                
                const hasDeviceToken = localStorage.getItem('device_token');
                
                if (settings.is_installed !== '1') {
                    this.renderSetupWizard();
                } else if (!hasDeviceToken) {
                    this.renderPinSetup();
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

    updateTitle() {
        const name = this.state.settings?.company_name;
        document.title = name ? `${name} - Bills` : 'Bills';
    },

    applySidebarColors() {
        const isDark = (document.documentElement.getAttribute('data-theme') || localStorage.getItem('theme')) === 'dark';
        
        let bgColor, textColor, hoverColor;
        if (isDark) {
            bgColor = this.state.settings?.sidebar_dark_bg_color || localStorage.getItem('sidebar_dark_bg_color');
            textColor = this.state.settings?.sidebar_dark_text_color || localStorage.getItem('sidebar_dark_text_color');
            hoverColor = this.state.settings?.sidebar_dark_hover_color || localStorage.getItem('sidebar_dark_hover_color');
        } else {
            bgColor = this.state.settings?.sidebar_bg_color || localStorage.getItem('sidebar_bg_color');
            textColor = this.state.settings?.sidebar_text_color || localStorage.getItem('sidebar_text_color');
            hoverColor = this.state.settings?.sidebar_hover_color || localStorage.getItem('sidebar_hover_color');
        }
        
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;
        
        if (bgColor) {
            sidebar.style.backgroundColor = bgColor;
            sidebar.style.borderRightColor = 'transparent';
        } else {
            sidebar.style.backgroundColor = '';
            sidebar.style.borderRightColor = '';
        }
        
        if (textColor) {
            sidebar.querySelectorAll('.sidebar-link, .sidebar-section-title, .sidebar-link svg, .profile-name, .profile-role').forEach(el => {
                el.style.color = textColor;
            });
        } else {
            sidebar.querySelectorAll('.sidebar-link, .sidebar-section-title, .sidebar-link svg, .profile-name, .profile-role').forEach(el => {
                el.style.color = '';
            });
        }
        
        // Hover + active styles via injected <style> (can't do :hover inline)
        let styleTag = document.getElementById('sidebar-custom-colors');
        if (!styleTag) {
            styleTag = document.createElement('style');
            styleTag.id = 'sidebar-custom-colors';
            document.head.appendChild(styleTag);
        }
        const hv = hoverColor || (isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)');
        const tc = textColor || '';
        const bg = bgColor || '';
        styleTag.textContent = `
            .sidebar-link:hover { background-color: ${hv} !important; }
            .sidebar-link.active { background-color: ${hv} !important; }
            ${tc ? `.sidebar-link:hover, .sidebar-link:hover svg { color: ${tc} !important; }` : ''}
            ${tc ? `.sidebar-footer { border-top-color: ${hv} !important; }` : ''}
            .profile-card { background-color: ${hv} !important; border-radius: 10px; }
            .profile-card:hover { opacity: 0.85; }
            ${tc ? `.profile-card, .profile-card svg { color: ${tc} !important; }` : ''}
            .profile-avatar { background-color: ${bg ? bg : (isDark ? '#111827' : '#FFFFFF')} !important; border: 2px solid ${hv} !important; ${tc ? `color: ${tc} !important;` : ''} }
        `;
    },

    applyLoginColors() {
        const primaryColor = this.state.settings?.pdf_primary_color || localStorage.getItem('pdf_primary_color') || '#0B484C';
        let styleTag = document.getElementById('login-custom-colors');
        if (!styleTag) {
            styleTag = document.createElement('style');
            styleTag.id = 'login-custom-colors';
            document.head.appendChild(styleTag);
        }
        
        const hexToRgba = (hex, alpha) => {
            hex = hex.replace('#', '');
            if (hex.length === 3) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }
            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        };
        
        let rgbaColor = 'rgba(11, 72, 76, 0.08)';
        let rgbaHover = 'rgba(11, 72, 76, 0.25)';
        try {
            if (primaryColor && primaryColor.startsWith('#')) {
                rgbaColor = hexToRgba(primaryColor, 0.08);
                rgbaHover = hexToRgba(primaryColor, 0.25);
            }
        } catch(e) {}
        
        styleTag.textContent = `
            .login-field input:focus { 
                border-color: ${primaryColor} !important; 
                box-shadow: 0 0 0 3px ${rgbaColor} !important; 
            }
            .login-submit { 
                background: linear-gradient(135deg, ${primaryColor} 0%, ${primaryColor} 100%) !important; 
            }
            .login-submit:hover {
                box-shadow: 0 6px 20px ${rgbaHover} !important;
            }
            .login-footer span { 
                color: ${primaryColor} !important; 
            }
            .login-right { 
                background: linear-gradient(160deg, ${primaryColor} 0%, ${primaryColor} 100%) !important; 
            }
            .login-float-sub { 
                color: ${primaryColor} !important; 
            }
            .login-float-dot { 
                background: ${primaryColor} !important; 
            }
            .login-float-mini-icon { 
                background: ${primaryColor} !important; 
            }
        `;
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
            contador: ['usuarios', 'users', 'configuracion', 'settings', 'pruebas-dgii', 'dgii-tests'],
            gerente: ['pruebas-dgii', 'dgii-tests', 'auditoria-dgii', 'dgii-logs']
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

            // Dynamically flatten workspace-panel styles for dashboard view to prevent card nesting
            const isDashboard = subView === 'inicio' || subView === 'dashboard';
            if (isDashboard) {
                appContent.style.background = 'transparent';
                appContent.style.border = 'none';
                appContent.style.boxShadow = 'none';
                appContent.style.padding = '0';
            } else {
                appContent.removeAttribute('style');
            }

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
                case 'auditoria-dgii': case 'dgii-logs': DgiiLogsModule.render(appContent); break;
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
        const cachedLogo = localStorage.getItem('login_logo') || localStorage.getItem('company_logo') || 'https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png';
        const loginLogoHeight = this.state.settings?.login_logo_height || localStorage.getItem('login_logo_height') || '79';
        const companyName = localStorage.getItem('company_name') || this.state.settings?.company_name || 'Bills';
        const primaryColor = this.state.settings?.pdf_primary_color || localStorage.getItem('pdf_primary_color') || '#0B484C';
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="login-page">
                <div class="login-left">
                    <div class="login-brand">
                        <img src="${cachedLogo}" alt="Logo" style="height: ${loginLogoHeight}px; object-fit: contain;">
                    </div>
                    <div class="login-form-wrap">
                        <h1 class="login-title">Bienvenido</h1>
                        <p class="login-subtitle">Inicia sesi\u00f3n para acceder a tu cuenta</p>
                        <div id="login-error" class="login-error"></div>

                        <form id="login-form">
                            <div class="login-field">
                                <label>Correo Electr\u00f3nico</label>
                                <input type="email" id="login-email" placeholder="tu@correo.com" required autocomplete="email">
                            </div>
                            <div class="login-field">
                                <label>Contrase\u00f1a</label>
                                <input type="password" id="login-password" placeholder="\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022" required autocomplete="current-password">
                            </div>
                            <button type="submit" class="login-submit">Iniciar Sesi\u00f3n</button>
                        </form>
                        <p class="login-footer">
                            Powered by <a href="https://gridbase.com.do" target="_blank" rel="noopener noreferrer"><span>GridBase</span> Digital Solutions</a>
                        </p>
                    </div>
                </div>
                <div class="login-right">
                    <div class="login-hero-text">
                        <h2>Simplifica<br>tu Facturaci\u00f3n,<br><span>hoy</span></h2>
                        <p class="login-hero-sub">Simplifica tu facturaci\u00f3n, hoy. Gestiona facturas y clientes desde una sola plataforma.</p>
                    </div>
                    <div class="login-float-card">
                        <div class="login-float-label">Cobrado Este Mes</div>
                        <div class="login-float-amount">RD$ 248,500</div>
                        <div class="login-float-sub">+12.4% vs mes anterior</div>
                        <div class="login-float-row">
                            <div class="login-float-row-item">
                                <div class="login-float-dot">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </div>
                                <div>
                                    <div class="login-float-row-label">Facturas</div>
                                    <div class="login-float-row-val">24 emitidas</div>
                                </div>
                            </div>
                            <div style="font-size:20px;font-weight:700;color:${primaryColor};">98%</div>
                        </div>
                    </div>
                    <div class="login-float-mini">
                        <div class="login-float-mini-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                        <div>
                            <div class="login-float-mini-text">Clientes activos</div>
                            <div class="login-float-mini-val">156 registrados</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.login(document.getElementById('login-email').value, document.getElementById('login-password').value);
        });
    },

    render2FA(setupMode, tempSecret, qrUri) {
        const cachedLogo = localStorage.getItem('login_logo') || localStorage.getItem('company_logo') || 'https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png';
        const app = document.getElementById('app');
        
        let formContent = '';
        if (setupMode) {
            formContent = `
                <h1 class="login-title">Configurar 2FA</h1>
                <p class="login-subtitle">Protege tu cuenta activando el segundo factor de autenticaci\u00f3n</p>
                <div id="login-error" class="login-error"></div>
                
                <div style="background:#E6F4F5; color:#0B484C; border:1px solid rgba(11,72,76,0.2); border-radius:10px; padding:10px 14px; font-size:11px; margin-bottom:20px; font-weight:600; line-height:1.4;">
                    La activación del 2FA es recomendada para proteger tu cuenta.
                </div>
                
                <p style="font-size:13px; color:#6B7280; margin-bottom:20px; line-height:1.5;">
                    Escanea este c\u00f3digo QR con tu app autenticadora (Google Authenticator, Authy, etc.)
                </p>
                
                <div style="display:flex; justify-content:center; margin-bottom:20px; background:#fff; padding:14px; border-radius:12px; border:1.5px solid #E5E7EB; width:fit-content; margin-left:auto; margin-right:auto;">
                    <canvas id="qr-canvas"></canvas>
                </div>
                
                <div style="background:#F9FAFB; border:1.5px solid #E5E7EB; border-radius:10px; padding:10px 14px; font-size:12px; margin-bottom:24px; word-break:break-all; font-family:monospace;">
                    <span style="color:#9CA3AF; font-size:10px; font-weight:600; text-transform:uppercase; display:block; letter-spacing:0.05em; margin-bottom:3px;">Clave manual</span>
                    <span style="color:#111827; font-size:14px; font-weight:700;">${tempSecret}</span>
                </div>
            `;
        } else {
            formContent = `
                <h1 class="login-title">Verificaci\u00f3n de Seguridad</h1>
                <p class="login-subtitle">Ingresa el c\u00f3digo din\u00e1mico de 6 d\u00edgitos generado por tu aplicaci\u00f3n</p>
                <div id="login-error" class="login-error"></div>
            `;
        }
        
        const loginLogoHeight = this.state.settings?.login_logo_height || localStorage.getItem('login_logo_height') || '79';
        app.innerHTML = `
            <div class="login-page">
                <div class="login-left">
                    <div class="login-brand">
                        <img src="${cachedLogo}" alt="Logo" style="height: ${loginLogoHeight}px; object-fit: contain;">
                    </div>
                    <div class="login-form-wrap">
                        ${formContent}
                        <form id="2fa-form">
                            <div class="login-field">
                                <label>C\u00f3digo de Seguridad (2FA)</label>
                                <input type="text" id="2fa-code" placeholder="000000" pattern="[0-9]*" inputmode="numeric" maxlength="6" required autofocus autocomplete="one-time-code" style="text-align:center; font-size:24px; letter-spacing:0.15em; font-weight:700;">
                            </div>
                            <div style="display:flex; gap:12px; margin-top:8px;">
                                <button type="button" id="btn-cancel-2fa" style="flex:1; padding:13px 24px; border:1.5px solid #E5E7EB; border-radius:10px; font-size:14px; font-weight:600; color:#374151; background:#fff; cursor:pointer; transition:all .15s;">Regresar</button>
                                <button type="submit" class="login-submit" style="flex:1;">Verificar</button>
                            </div>
                        </form>
                        <p class="login-footer">
                            Powered by <a href="https://gridbase.com.do" target="_blank" rel="noopener noreferrer"><span>GridBase</span> Digital Solutions</a>
                        </p>
                    </div>
                </div>
                <div class="login-right">
                    <div class="login-hero-text">
                        <h2>Tu cuenta<br>est\u00e1 protegida,<br><span>siempre</span></h2>
                        <p class="login-hero-sub">Verificaci\u00f3n en dos pasos para mantener tu informaci\u00f3n segura en todo momento.</p>
                    </div>
                    <div class="login-float-card">
                        <div class="login-float-label">Seguridad Activa</div>
                        <div class="login-float-amount" style="font-size:24px;">2FA Habilitado</div>
                        <div class="login-float-sub">Protecci\u00f3n de doble factor</div>
                        <div class="login-float-row">
                            <div class="login-float-row-item">
                                <div class="login-float-dot">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                </div>
                                <div>
                                    <div class="login-float-row-label">Encriptaci\u00f3n</div>
                                    <div class="login-float-row-val">AES-256</div>
                                </div>
                            </div>
                            <div style="font-size:16px;font-weight:700;color:#0B484C;">100%</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        if (setupMode) {
            const renderQR = () => {
                new QRious({
                    element: document.getElementById('qr-canvas'),
                    value: qrUri,
                    size: 160,
                    background: '#ffffff',
                    foreground: '#111827',
                    level: 'H'
                });
            };
            if (typeof QRious === 'undefined') {
                const script = document.createElement('script');
                script.src = "https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js";
                script.onload = renderQR;
                document.head.appendChild(script);
            } else {
                renderQR();
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
                    localStorage.setItem('saved_email', res.user.email);
                    
                    const settings = await this.api('settings');
                    this.state.settings = settings;
                    
                    const hasDeviceToken = localStorage.getItem('device_token');
                    if (settings.is_installed !== '1') {
                        this.renderSetupWizard();
                    } else if (!hasDeviceToken) {
                        this.renderPinSetup();
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

    renderPinSetup() {
        const cachedLogo = localStorage.getItem('login_logo') || localStorage.getItem('company_logo') || 'https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png';
        const loginLogoHeight = this.state.settings?.login_logo_height || localStorage.getItem('login_logo_height') || '79';
        const app = document.getElementById('app');
        
        app.innerHTML = `
            <div class="login-page">
                <div class="login-left">
                    <div class="login-brand">
                        <img src="${cachedLogo}" alt="Logo" style="height: ${loginLogoHeight}px; object-fit: contain;">
                    </div>
                    <div class="login-form-wrap">
                        <h1 class="login-title">Acceso Rápido</h1>
                        <p class="login-subtitle">Crea un PIN de 6 dígitos para ingresar más rápido desde este dispositivo en el futuro.</p>
                        <div id="login-error" class="login-error"></div>
                        <form id="pin-setup-form">
                            <div class="login-field">
                                <label>Ingresa un PIN de 6 dígitos</label>
                                <input type="password" id="setup-pin-code" placeholder="••••••" pattern="[0-9]*" inputmode="numeric" maxlength="6" required autofocus style="text-align:center; font-size:24px; letter-spacing:0.15em; font-weight:700;">
                            </div>
                            <div style="display:flex; gap:12px; margin-top:8px;">
                                <button type="button" id="btn-skip-pin" style="flex:1; padding:13px 24px; border:1.5px solid #E5E7EB; border-radius:10px; font-size:14px; font-weight:600; color:#374151; background:#fff; cursor:pointer; transition:all .15s;">Saltar por ahora</button>
                                <button type="submit" class="login-submit" style="flex:1;">Guardar PIN</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="login-right">
                    <div class="login-hero-text">
                        <h2>Seguro y<br><span>rápido</span></h2>
                        <p class="login-hero-sub">No vuelvas a teclear tu contraseña completa si estás en tu propio dispositivo.</p>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('btn-skip-pin').addEventListener('click', () => {
            this.renderAppShell();
            this.navigate('inicio');
        });
        
        document.getElementById('pin-setup-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const pin = document.getElementById('setup-pin-code').value;
            if (pin.length !== 6) {
                document.getElementById('login-error').textContent = 'El PIN debe tener exactamente 6 dígitos';
                document.getElementById('login-error').style.display = 'block';
                return;
            }
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span>';
            
            try {
                const res = await this.api('auth/setup-pin', { method: 'POST', body: { pin } });
                if (res.success) {
                    localStorage.setItem('device_token', res.device_token);
                    this.renderAppShell();
                    this.navigate('inicio');
                }
            } catch (error) {
                btn.disabled = false;
                btn.innerHTML = 'Guardar PIN';
                document.getElementById('login-error').textContent = error.message;
                document.getElementById('login-error').style.display = 'block';
            }
        });
    },

    renderPinLogin(email) {
        const cachedLogo = localStorage.getItem('login_logo') || localStorage.getItem('company_logo') || 'https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png';
        const loginLogoHeight = this.state.settings?.login_logo_height || localStorage.getItem('login_logo_height') || '79';
        const app = document.getElementById('app');
        
        app.innerHTML = `
            <div class="login-page">
                <div class="login-left">
                    <div class="login-brand">
                        <img src="${cachedLogo}" alt="Logo" style="height: ${loginLogoHeight}px; object-fit: contain;">
                    </div>
                    <div class="login-form-wrap">
                        <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">
                            <div style="width:48px;height:48px;border-radius:50%;background:var(--color-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;">
                                ${email.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <h1 class="login-title" style="margin-bottom:0;">Hola de nuevo</h1>
                                <p class="login-subtitle" style="margin-bottom:0;">${email}</p>
                            </div>
                        </div>
                        <div id="login-error" class="login-error"></div>

                        <div id="biometric-login-wrap" style="display:none; margin-bottom:16px;">
                            <button type="button" id="btn-webauthn-login" style="width:100%; padding:13px 20px; border:none; border-radius:10px; font-size:15px; font-weight:600; color:#ffffff; background:linear-gradient(135deg, #0B484C 0%, #16696e 100%); display:inline-flex; align-items:center; justify-content:center; gap:10px; cursor:pointer; box-shadow:0 4px 14px rgba(11,72,76,0.22); transition:all 0.2s ease;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                                    <path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/>
                                    <path d="M14 13.12c0 2.38 0 3.88-.26 5.88"/>
                                    <path d="M17.29 21.02c.12-.6.43-2.3.43-5.02 0-2.28-.56-4.17-1.73-5.67"/>
                                    <path d="M2 12C2 6.5 6.5 2 12 2a10 10 0 0 1 8 4"/>
                                    <path d="M4.27 17.58A8.98 8.98 0 0 1 3 12c0-2.2.8-4.2 2.1-5.7"/>
                                    <path d="M7 11c0-1.7 1.3-3 3-3s3 1.3 3 3c0 2.22 0 3.72-.26 5.72"/>
                                    <path d="M9 18a6 6 0 0 1-2-4.5"/>
                                </svg>
                                Ingresar con Face ID / Touch ID / Huella
                            </button>
                            <div style="display:flex; align-items:center; margin:16px 0; color:var(--color-text-muted); font-size:12px;">
                                <div style="flex:1; height:1px; background:var(--color-border);"></div>
                                <span style="padding:0 12px;">o ingresa con tu PIN</span>
                                <div style="flex:1; height:1px; background:var(--color-border);"></div>
                            </div>
                        </div>

                        <form id="pin-login-form">
                            <div class="login-field">
                                <label>Ingresa tu PIN</label>
                                <input type="password" id="login-pin-code" placeholder="••••••" pattern="[0-9]*" inputmode="numeric" maxlength="6" required autofocus autocomplete="off" style="text-align:center; font-size:24px; letter-spacing:0.15em; font-weight:700;">
                            </div>
                            <button type="submit" class="login-submit">Ingresar</button>
                        </form>
                        <div style="margin-top:24px; text-align:center;">
                            <button type="button" id="btn-fallback-login" style="background:none; border:none; color:var(--color-primary); font-size:14px; font-weight:600; cursor:pointer; text-decoration:underline;">Ingresar con contraseña u otra cuenta</button>
                        </div>
                    </div>
                </div>
                <div class="login-right">
                    <div class="login-hero-text">
                        <h2>Acceso<br><span>rápido</span></h2>
                    </div>
                </div>
            </div>
        `;

        // Check WebAuthn support
        WebAuthnHelper.isSupported().then(supported => {
            if (supported) {
                const wrap = document.getElementById('biometric-login-wrap');
                if (wrap) wrap.style.display = 'block';

                const btnWebAuthn = document.getElementById('btn-webauthn-login');
                btnWebAuthn?.addEventListener('click', async () => {
                    const errorEl = document.getElementById('login-error');
                    errorEl.style.display = 'none';
                    btnWebAuthn.disabled = true;
                    btnWebAuthn.innerHTML = '<span class="spinner"></span> Verificando biometría...';

                    try {
                        const res = await WebAuthnHelper.login(email);
                        if (res.success) {
                            this.state.user = res.user;
                            const settings = await this.api('settings');
                            this.state.settings = settings;
                            this.renderAppShell();
                            this.navigate('inicio');
                        }
                    } catch (err) {
                        btnWebAuthn.disabled = false;
                        btnWebAuthn.innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                                <path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/>
                                <path d="M14 13.12c0 2.38 0 3.88-.26 5.88"/>
                                <path d="M17.29 21.02c.12-.6.43-2.3.43-5.02 0-2.28-.56-4.17-1.73-5.67"/>
                                <path d="M2 12C2 6.5 6.5 2 12 2a10 10 0 0 1 8 4"/>
                                <path d="M4.27 17.58A8.98 8.98 0 0 1 3 12c0-2.2.8-4.2 2.1-5.7"/>
                                <path d="M7 11c0-1.7 1.3-3 3-3s3 1.3 3 3c0 2.22 0 3.72-.26 5.72"/>
                                <path d="M9 18a6 6 0 0 1-2-4.5"/>
                            </svg>
                            Ingresar con Face ID / Touch ID / Huella
                        `;
                        if (errorEl) {
                            errorEl.textContent = err.message || 'Error en la verificación biométrica';
                            errorEl.style.display = 'block';
                        }
                    }
                });
            }
        });
        
        document.getElementById('btn-fallback-login').addEventListener('click', () => {
            localStorage.removeItem('device_token');
            this.renderLogin();
        });
        
        document.getElementById('pin-login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const pin = document.getElementById('login-pin-code').value;
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span>';
            
            try {
                const res = await this.api('auth/pin-login', { 
                    method: 'POST', 
                    body: { email, pin, device_token: localStorage.getItem('device_token') } 
                });
                
                if (res.success) {
                    this.state.user = res.user;
                    const settings = await this.api('settings');
                    this.state.settings = settings;
                    this.renderAppShell();
                    this.navigate('inicio');
                }
            } catch (error) {
                btn.disabled = false;
                btn.innerHTML = 'Ingresar';
                document.getElementById('login-error').textContent = error.message;
                document.getElementById('login-error').style.display = 'block';
                document.getElementById('login-pin-code').value = '';
                document.getElementById('login-pin-code').focus();
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
        const logoHeight = this.state.settings?.sidebar_logo_height || localStorage.getItem('sidebar_logo_height') || '45';

        app.innerHTML = `
            <div class="app-container">
                <div class="sidebar-overlay" id="sidebar-overlay"></div>
                <aside class="sidebar" id="sidebar">
                    <div class="sidebar-logo" style="padding: 16px 12px; display: flex; justify-content: center; align-items: center;">
                        <img id="sidebar-logo-img" src="${logoSrc}" alt="Logo" style="max-width: 100%; height: ${logoHeight}px; object-fit: contain;">
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
                        ${['admin', 'contador', 'gerente'].includes(this.state.user.role) ? `
                            <div class="sidebar-section-title" style="margin-top: var(--spacing-xl);">Sistema</div>
                            <ul class="sidebar-menu">
                                ${['admin', 'gerente'].includes(this.state.user.role) ? `
                                <li><a href="/usuarios" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>Usuarios</span></a></li>
                                <li><a href="/configuracion" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>Configuración</span></a></li>
                                ` : ''}
                                ${this.state.user.role === 'admin' ? `
                                <li><a href="/pruebas-dgii" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="2"></rect><path d="M9 14l2 2 4-4"></path></svg>Pruebas DGII</span></a></li>
                                <li><a href="/auditoria-dgii" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>Auditoría DGII</span></a></li>
                                ` : ''}
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
                                ${this.getGreeting()}, <span style="color:var(--color-primary)">${this.state.user.name.split(' ')[0]}</span>
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
                            <div style="position:relative;">
                                 <button class="btn-icon" id="notification-toggle" onclick="App.toggleNotifications(event)" title="Notificaciones" style="position:relative; display:inline-flex;align-items:center;justify-content:center;">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                     <span id="notification-badge" style="display:none;position:absolute;top:2px;right:2px;width:7px;height:7px;border-radius:50%;background:#ef4444;border:1px solid var(--bg-card);"></span>
                                 </button>
                                 <div id="notification-dropdown" class="table-outer" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;z-index:300;background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);padding:0;max-height:400px;overflow-y:auto;animation:fadeIn 0.15s ease;">
                                     <div style="padding:12px 16px;border-bottom:1px solid var(--color-border);display:flex;justify-content:space-between;align-items:center;">
                                         <span style="font-weight:700;font-size:13px;color:var(--color-text-primary);">Notificaciones</span>
                                         <span id="notification-count" class="badge" style="background:var(--color-primary-light);color:var(--color-primary);font-size:10px;padding:2px 6px;font-weight:700;">0</span>
                                     </div>
                                     <div id="notification-list" style="padding:4px 0;">
                                         <div class="text-center text-muted" style="padding:20px;font-size:12px;"><span class="spinner" style="width:14px;height:14px;margin:0 auto 8px;"></span>Cargando...</div>
                                     </div>
                                 </div>
                             </div>
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
        this.applySidebarColors();
        this.loadOverdueBadge();
        this.loadDgiiStatus();
        this.bindSearch();
        this.updateThemeButton();
        this.loadNotifications();
        this.check2faReminder();
    },

    check2faReminder() {
        if (!this.state.user || this.state.user.two_factor_enabled) return;

        const dismissedAt = localStorage.getItem('2fa_reminder_dismissed');
        if (dismissedAt) {
            const diffDays = (Date.now() - parseInt(dismissedAt, 10)) / (1000 * 60 * 60 * 24);
            if (diffDays < 7) return;
        }

        if (document.getElementById('2fa-reminder-banner')) return;

        const appContent = document.getElementById('app-content');
        if (!appContent || !appContent.parentNode) return;

        const banner = document.createElement('div');
        banner.id = '2fa-reminder-banner';
        banner.className = 'security-reminder-card';
        banner.innerHTML = `
            <div style="display:flex; align-items:center; gap:14px;">
                <div class="security-reminder-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <div>
                    <div class="security-reminder-title">Recomendación de Seguridad</div>
                    <div class="security-reminder-sub">Protege tu cuenta activando la Autenticación en Dos Pasos (2FA).</div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:12px; flex-shrink:0;">
                <button id="btn-banner-enable-2fa" class="security-reminder-btn">Activar 2FA Ahora</button>
                <button id="btn-banner-dismiss-2fa" style="background:none; border:none; color:var(--color-text-muted); cursor:pointer; padding:6px; font-size:16px; display:flex; align-items:center; justify-content:center; transition:opacity .15s;" title="Recordar más tarde">✕</button>
            </div>
        `;

        appContent.parentNode.insertBefore(banner, appContent);

        document.getElementById('btn-banner-enable-2fa')?.addEventListener('click', () => {
            banner.remove();
            if (this.state.currentRoute !== 'configuracion' && this.state.currentRoute !== 'settings') {
                this.navigate('settings');
            }
            setTimeout(() => {
                const secTab = document.querySelector('#settings-tabs .segment-item[data-tab="security"]');
                if (secTab) secTab.click();
            }, 150);
        });

        document.getElementById('btn-banner-dismiss-2fa')?.addEventListener('click', () => {
            localStorage.setItem('2fa_reminder_dismissed', Date.now().toString());
            banner.remove();
            this.showToast('Recordatorio pospuesto por 7 días', 'info');
        });
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

    toggleNotifications(event) {
        if (event) event.stopPropagation();
        const dropdown = document.getElementById('notification-dropdown');
        if (!dropdown) return;
        const isOpen = dropdown.style.display === 'block';
        
        dropdown.style.display = isOpen ? 'none' : 'block';
        
        if (!isOpen) {
            this.loadNotifications();
            const closeHandler = () => {
                dropdown.style.display = 'none';
                document.removeEventListener('click', closeHandler);
            };
            setTimeout(() => document.addEventListener('click', closeHandler), 10);
        }
    },

    async loadNotifications() {
        const list = document.getElementById('notification-list');
        const badge = document.getElementById('notification-badge');
        const countBadge = document.getElementById('notification-count');
        if (!list) return;

        try {
            const [dashboardData, receivedData, dgiiRes] = await Promise.all([
                this.api('dashboard', { silent: true }).catch(() => null),
                this.api('received-invoices/summary', { silent: true }).catch(() => null),
                this.api('dgii/status', { silent: true }).catch(() => null)
            ]);

            const notifications = [];

            const currentVersion = this.state.settings?.system_version;
            const changelog = this.state.settings?.system_changelog;
            const lastSeenVersion = localStorage.getItem('gridbase_bills_last_seen_version');

            if (currentVersion && changelog && Array.isArray(changelog) && changelog.length > 0) {
                notifications.push({
                    type: 'update',
                    title: 'Sistema Actualizado',
                    description: `Nueva versión ${currentVersion} instalada. Haz clic para ver las novedades.`,
                    onclick: `App.showChangelogModal('${currentVersion}', App.state.settings.system_changelog); App.toggleNotifications(event); event.preventDefault();`,
                    icon: `<svg style="color:var(--color-primary)" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="8"/></svg>`
                });
            }

            if (receivedData && receivedData.pending > 0) {
                notifications.push({
                    type: 'approval',
                    title: 'Aprobación de e-CF',
                    description: `Tienes ${receivedData.pending} factura${receivedData.pending > 1 ? 's' : ''} de proveedor${receivedData.pending > 1 ? 'es' : ''} pendiente${receivedData.pending > 1 ? 's' : ''} de aprobación comercial.`,
                    link: 'facturas-recibidas',
                    icon: `<svg style="color:#f59e0b" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>`
                });
            }

            if (dashboardData && dashboardData.stats && dashboardData.stats.overdue_count > 0) {
                notifications.push({
                    type: 'overdue',
                    title: 'Facturas Vencidas',
                    description: `Tienes ${dashboardData.stats.overdue_count} factura${dashboardData.stats.overdue_count > 1 ? 's' : ''} vencida${dashboardData.stats.overdue_count > 1 ? 's' : ''} pendiente${dashboardData.stats.overdue_count > 1 ? 's' : ''} de cobro.`,
                    link: 'facturas',
                    icon: `<svg style="color:#ef4444" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`
                });
            }

            if (dgiiRes && dgiiRes.status !== 'connected') {
                const desc = dgiiRes.status === 'not_configured' 
                    ? 'Facturación electrónica no configurada' 
                    : 'Sin conexión con el servidor de la DGII';
                notifications.push({
                    type: 'dgii',
                    title: 'Conexión DGII',
                    description: desc,
                    link: 'pruebas-dgii',
                    icon: `<svg style="color:#ef4444" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`
                });
            }

            const unreadNotifications = notifications.filter(n => {
                if (n.type === 'update') return lastSeenVersion !== currentVersion;
                return true;
            });

            if (badge) {
                badge.style.display = unreadNotifications.length > 0 ? 'block' : 'none';
            }
            if (countBadge) {
                countBadge.textContent = notifications.length;
            }

            if (notifications.length === 0) {
                list.innerHTML = `
                    <div class="text-center" style="padding:32px 16px;">
                        <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" style="color:#10b981;margin-bottom:8px;opacity:0.8;">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <div style="font-weight:600;font-size:12px;color:var(--color-text-primary);">¡Todo al día!</div>
                        <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px;">No tienes alertas ni notificaciones pendientes.</div>
                    </div>
                `;
                return;
            }

            list.innerHTML = notifications.map(n => `
                <a href="${n.onclick ? '#' : '#' + n.link}" onclick="${n.onclick || 'App.toggleNotifications(event)'}" style="display:flex;gap:12px;padding:12px 16px;border-bottom:1.5px solid var(--color-border);text-decoration:none;color:inherit;transition:background 0.15s;" class="notification-item">
                    <div style="width:32px;height:32px;border-radius:var(--radius-md);background:var(--bg-hover);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        ${n.icon}
                    </div>
                    <div style="min-width:0;flex:1;">
                        <div style="font-weight:700;font-size:12px;color:var(--color-text-primary);margin-bottom:2px;">${n.title}</div>
                        <div style="font-size:11px;color:var(--color-text-secondary);line-height:1.4;">${n.description}</div>
                    </div>
                </a>
            `).join('') + `
                <style>
                    .notification-item:hover { background: var(--bg-hover) !important; }
                    .notification-item:last-child { border-bottom: none; }
                </style>
            `;

        } catch (e) {
            list.innerHTML = `<div class="text-center text-red" style="padding:20px;font-size:11px;">Error al cargar notificaciones</div>`;
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
        
        // Prevent duplicate warning/connection toasts from cluttering screen
        if (type === 'warning' && Array.from(container.children).some(t => t.innerText.includes(message))) {
            return;
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        let icon = '';
        if (type === 'success') icon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
        else if (type === 'error') icon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
        else if (type === 'warning') icon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;
        
        toast.innerHTML = `${icon} <span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, type === 'warning' ? 5000 : 3000);
    },

    checkChangelog() {
        const currentVersion = this.state.settings?.system_version;
        const changelog = this.state.settings?.system_changelog;
        
        if (!currentVersion || !changelog || !Array.isArray(changelog) || changelog.length === 0) {
            return;
        }

        const lastSeenVersion = localStorage.getItem('gridbase_bills_last_seen_version');
        
        // If we haven't seen this version, display the modal!
        if (lastSeenVersion !== currentVersion) {
            this.showChangelogModal(currentVersion, changelog);
        }
    },

    showChangelogModal(version, changelog) {
        const modal = document.createElement('div');
        modal.id = 'changelog-modal';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.backgroundColor = 'rgba(15, 23, 42, 0.6)';
        modal.style.backdropFilter = 'blur(12px)';
        modal.style.display = 'flex';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
        modal.style.zIndex = '99999';
        modal.style.animation = 'fadeIn 0.3s ease';

        const primaryColor = this.state.settings?.pdf_primary_color || '#0B484C';

        const listItems = changelog.map(item => `
            <li style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px; font-size: 14px; color: #475569; line-height: 1.5; text-align: left;">
                <span style="color: ${primaryColor}; margin-top: 4px; display: inline-flex; align-items: center; justify-content: center; min-width: 14px; height: 14px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </span>
                <span style="font-family: inherit; font-weight: 500;">${item}</span>
            </li>
        `).join('');

        modal.innerHTML = `
            <div style="background: #ffffff; width: 92%; max-width: 480px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.7); overflow: hidden; transform: scale(0.9); animation: scaleUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; padding: 32px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 20px;">
                    <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(11, 72, 76, 0.1); display: flex; align-items: center; justify-content: center; color: ${primaryColor}; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    </div>
                    <div style="text-align: left;">
                        <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #1e293b; letter-spacing: -0.01em;">Sistema Actualizado</h3>
                        <span style="font-size: 11px; font-weight: 700; color: ${primaryColor}; background: rgba(11, 72, 76, 0.15); padding: 3px 10px; border-radius: 99px; display: inline-block; margin-top: 4px;">Versión ${version}</span>
                    </div>
                </div>
                
                <p style="font-size: 14px; color: #64748b; margin-top: 0; margin-bottom: 24px; text-align: left; line-height: 1.5;">Hemos introducido mejoras de seguridad y optimizaciones de rendimiento en esta actualización:</p>
                
                <ul style="list-style: none; padding: 0; margin: 0 0 28px 0;">
                    ${listItems}
                </ul>
                
                <button id="dismiss-changelog-btn" style="width: 100%; padding: 14px; background: ${primaryColor}; color: #ffffff; border: none; border-radius: 14px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(11, 72, 76, 0.25); outline: none;">
                    Entendido, ¡continuar!
                </button>
            </div>
        `;

        document.body.appendChild(modal);

        document.getElementById('dismiss-changelog-btn').addEventListener('click', () => {
            localStorage.setItem('gridbase_bills_last_seen_version', version);
            modal.style.animation = 'fadeOut 0.2s ease';
            modal.addEventListener('animationend', () => {
                modal.remove();
                this.loadNotifications();
            });
        });
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
        this.applySidebarColors();
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
