/**
 * Gridbase Digital Solutions - Invoice System
 * Main Frontend Application Logic
 */

import DashboardModule from './modules/dashboard.js';
import InvoicesModule from './modules/invoices.js';
import QuotesModule from './modules/quotes.js';
import ClientsModule from './modules/clients.js';
import SettingsModule from './modules/settings.js';

window.App = {
    state: {
        user: null,
        token: null,
        currentRoute: 'dashboard'
    },
    
    init() {
        this.checkAuth();
        this.setupRouter();
        this.bindEvents();
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
            const response = await fetch(url, { ...options, headers });
            const data = await response.json();
            
            if (!response.ok) {
                if (response.status === 401 && this.state.currentRoute !== 'login') {
                    this.logout(false);
                }
                throw new Error(data.error || 'Error de la API');
            }
            return data;
        } catch (error) {
            this.showToast(error.message, 'error');
            throw error;
        }
    },

    async checkAuth() {
        try {
            const res = await this.api('auth/session');
            if (res.authenticated) {
                this.state.user = res.user;
                this.renderAppShell();
                this.navigate(window.location.hash.replace('#', '') || 'dashboard');
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
                this.navigate('dashboard');
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
            try { await this.api('auth/logout', { method: 'POST' }); } catch (e) {}
        }
        this.state.user = null;
        this.state.currentRoute = 'login';
        window.location.hash = '';
        this.renderLogin();
    },

    setupRouter() {
        window.addEventListener('hashchange', () => {
            const route = window.location.hash.replace('#', '') || 'dashboard';
            if (this.state.user && route !== 'login') {
                this.navigate(route);
            }
        });
    },

    navigate(route) {
        if (!this.state.user) {
            return this.renderLogin();
        }
        
        this.state.currentRoute = route;
        window.location.hash = route;
        
        // Update sidebar active state
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${route.split('/')[0]}`) {
                link.classList.add('active');
            }
        });

        // Load view
        const appContent = document.getElementById('app-content');
        if (!appContent) return;

        appContent.innerHTML = `<div class="text-center mt-24"><div class="spinner mx-auto"></div></div>`;
        
        // Dynamic loading based on route
        const parts = route.split('/');
        const view = parts[0];
        const id = parts[1];

        setTimeout(() => {
            switch(view) {
                case 'dashboard': DashboardModule.render(appContent); break;
                case 'invoices': InvoicesModule.render(appContent, id); break;
                case 'quotes': QuotesModule.render(appContent, id); break;
                case 'clients': ClientsModule.render(appContent, id); break;
                case 'settings': SettingsModule.render(appContent); break;
                default: appContent.innerHTML = '<h2>404 No Encontrado</h2>';
            }
        }, 50); // slight delay to show spinner and avoid stutter
    },

    renderLogin() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="login-page">
                <div class="login-card">
                    <div class="login-logo">
                        <svg width="48" height="48" viewBox="0 0 32 32"><rect width="32" height="32" rx="6" fill="#E63946"/><text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="white" font-family="sans-serif" font-weight="700" font-size="18">G</text></svg>
                    </div>
                    <h1 class="login-title">Gridbase Invoices</h1>
                    <p class="login-subtitle">Inicia sesión en tu cuenta</p>
                    <div id="login-error" class="login-error"></div>
                    <form id="login-form">
                        <div class="form-group text-left">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" id="login-email" class="form-control" value="samuel@gridbase.com.do" required>
                        </div>
                        <div class="form-group text-left">
                            <label class="form-label">Contraseña</label>
                            <input type="password" id="login-password" class="form-control" value="SamDP9903" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px;">Iniciar Sesión</button>
                    </form>
                </div>
            </div>
        `;

        document.getElementById('login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.login(document.getElementById('login-email').value, document.getElementById('login-password').value);
        });
    },

    renderAppShell() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="app-layout">
                <aside class="sidebar" id="sidebar">
                    <div class="sidebar-brand">
                        <div class="sidebar-brand-icon">G</div>
                        <div>
                            <div class="sidebar-brand-text">Gridbase</div>
                            <div class="sidebar-brand-sub">Invoices</div>
                        </div>
                    </div>
                    <nav class="sidebar-nav">
                        <div class="sidebar-section">
                            <div class="sidebar-section-title">Menú</div>
                            <a href="#dashboard" class="sidebar-link active"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg> Panel</a>
                            <a href="#invoices" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Facturas</a>
                            <a href="#quotes" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg> Cotizaciones</a>
                            <a href="#clients" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg> Clientes</a>
                        </div>
                        <div class="sidebar-section">
                            <div class="sidebar-section-title">Sistema</div>
                            <a href="#settings" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg> Configuración</a>
                        </div>
                    </nav>
                    <div class="sidebar-footer">
                        <div class="sidebar-avatar">${this.state.user.name.charAt(0)}</div>
                        <div style="flex:1; overflow:hidden;">
                            <div class="sidebar-user-name truncate">${this.state.user.name}</div>
                            <div class="sidebar-user-email truncate">${this.state.user.email}</div>
                        </div>
                        <button class="btn btn-icon btn-ghost" onclick="App.logout()" title="Cerrar Sesión">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        </button>
                    </div>
                </aside>
                <main class="main-content">
                    <header class="topbar">
                        <div class="topbar-search">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" placeholder="Buscar facturas, clientes...">
                        </div>
                        <div class="topbar-actions">
                            <button class="btn btn-primary" onclick="window.location.hash='invoices/new'">+ Nueva Factura</button>
                        </div>
                    </header>
                    <div class="page-content" id="app-content">
                        <!-- Content injected here -->
                    </div>
                </main>
            </div>
        `;
    },

    formatCurrency(amount, currency = 'USD') {
        const formatter = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency || 'USD',
        });
        return formatter.format(amount);
    },

    formatDate(dateStr) {
        if (!dateStr) return '';
        // Fix timezone issue by appending T12:00:00 if it's just a date
        const cleanDate = dateStr.includes('T') ? dateStr : `${dateStr}T12:00:00`;
        return new Intl.DateTimeFormat('es-DO', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(cleanDate));
    },

    showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let icon = '';
        if (type === 'success') icon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
        else if (type === 'error') icon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
        
        toast.innerHTML = `${icon} <span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
    },

    bindEvents() {
        // Global event delegation if needed
    }
};

// Boot application
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
