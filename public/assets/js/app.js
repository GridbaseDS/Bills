/**
 * GridBase Digital Solutions — Bills System
 * Main Frontend Application Logic — Gridbase Design Kit v3
 */

import DashboardModule from './modules/dashboard.js?v=47';
import InvoicesModule from './modules/invoices.js?v=47';
import QuotesModule from './modules/quotes.js?v=47';
import ClientsModule from './modules/clients.js?v=47';
import ItemsModule from './modules/items.js?v=47';
import SettingsModule from './modules/settings.js?v=47';
import RecurringModule from './modules/recurring.js?v=47';
import DgiiTestsModule from './modules/dgii-tests.js?v=47';
import ReceivedInvoicesModule from './modules/received-invoices.js?v=47';

window.App = {
    state: {
        user: null,
        token: null,
        currentRoute: 'dashboard'
    },

    isMobile() { return window.innerWidth <= 640; },

    init() {
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
                this.renderAppShell();
                const currentRoute = window.location.pathname.substring(1) || 'inicio';
                this.navigate(currentRoute);
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
            if (res.success) {
                this.state.user = res.user;
                this.renderAppShell();
                this.navigate('inicio');
            }
        } catch (error) {
            const errorEl = document.getElementById('login-error');
            if (errorEl) {
                errorEl.textContent = error.message;
                errorEl.style.display = 'block';
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
        route = route.replace('#', '').replace(/^\//, '');
        this.state.currentRoute = route;
        if (pushToHistory) history.pushState(null, '', '/' + route);

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

        const parts = route.split('/');
        const view = parts[0];
        setTimeout(() => {
            const subId = parts.length > 1 ? parts.slice(1).join('/') : undefined;
            switch (view) {
                case 'inicio': case 'dashboard': DashboardModule.render(appContent); break;
                case 'facturas': case 'invoices': InvoicesModule.render(appContent, subId); break;
                case 'cotizaciones': case 'quotes': QuotesModule.render(appContent, subId); break;
                case 'clientes': case 'clients': ClientsModule.render(appContent, subId); break;
                case 'articulos': case 'items': ItemsModule.render(appContent, subId); break;
                case 'recurrentes': case 'recurring': RecurringModule.render(appContent, subId); break;
                case 'configuracion': case 'settings': SettingsModule.render(appContent); break;
                case 'pruebas-dgii': case 'dgii-tests': DgiiTestsModule.render(appContent); break;
                case 'facturas-recibidas': case 'received-invoices': ReceivedInvoicesModule.render(appContent); break;
                default: appContent.innerHTML = '<h2>404 No Encontrado</h2>';
            }
        }, 50);
    },

    /* ═══════════════════════════════════════════════
       LOGIN — Gridbase Design Kit
       ═══════════════════════════════════════════════ */
    renderLogin() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="login-page">
                <div class="login-card">
                    <div class="login-logo">
                        <img src="https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png" alt="GridBase Digital Solutions">
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

    /* ═══════════════════════════════════════════════
       APP SHELL — Gridbase Design Kit
       White sidebar, vertical active line, profile-card,
       search-wrapper with keycap, workspace-panel
       ═══════════════════════════════════════════════ */
    renderAppShell() {
        const app = document.getElementById('app');
        const userInitial = this.state.user.name ? this.state.user.name.charAt(0).toUpperCase() : '?';

        app.innerHTML = `
            <div class="app-container">
                <div class="sidebar-overlay" id="sidebar-overlay"></div>
                <aside class="sidebar" id="sidebar">
                    <div class="sidebar-logo">
                        <img src="https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png" alt="GridBase" style="height: 28px;">
                    </div>
                    <nav class="sidebar-nav">
                        <div class="sidebar-section-title">Menú</div>
                        <ul class="sidebar-menu">
                            <li><a href="/inicio" class="sidebar-link active"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>Panel</span></a></li>
                            <li><a href="/facturas" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>Facturas</span><span id="overdue-badge" style="display:none;background:var(--color-danger-bg);color:var(--color-danger-text);font-size:11px;padding:2px 8px;border-radius:var(--radius-full);font-weight:600;"></span></a></li>
                            <li><a href="/recurrentes" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>Recurrentes</span></a></li>
                            <li><a href="/cotizaciones" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>Cotizaciones</span></a></li>
                            <li><a href="/clientes" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>Clientes</span></a></li>
                            <li><a href="/articulos" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>Artículos</span></a></li>
                            <li><a href="/facturas-recibidas" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>Facturas Recibidas</span></a></li>
                        </ul>
                        <div class="sidebar-section-title" style="margin-top: var(--spacing-xl);">Sistema</div>
                        <ul class="sidebar-menu">
                            <li><a href="/configuracion" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>Configuración</span></a></li>
                            <li><a href="/pruebas-dgii" class="sidebar-link"><span class="sidebar-link-content"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="2"></rect><path d="M9 14l2 2 4-4"></path></svg>Pruebas DGII</span></a></li>
                        </ul>
                    </nav>
                    <div class="sidebar-footer">
                        <div class="profile-card" onclick="App.logout()" title="Cerrar Sesión">
                            <div class="profile-avatar">${userInitial}</div>
                            <div class="profile-info">
                                <div class="profile-name">${this.state.user.name}</div>
                                <div class="profile-role">${this.state.user.email}</div>
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
                            <div class="search-wrapper" id="search-wrapper">
                                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input class="search-input" type="text" placeholder="Buscar facturas, clientes..." id="global-search-input">
                                <div class="search-shortcuts"><span class="keycap">⌘</span><span class="keycap">K</span></div>
                            </div>
                        </div>
                        <div class="topbar-actions">
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
    }
};

// Boot application
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
