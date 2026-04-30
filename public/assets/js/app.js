/**
 * GridBase Digital Solutions — Bills System
 * Main Frontend Application Logic
 */

import DashboardModule from './modules/dashboard.js?v=11';
import InvoicesModule from './modules/invoices.js?v=11';
import QuotesModule from './modules/quotes.js?v=11';
import ClientsModule from './modules/clients.js?v=11';
import SettingsModule from './modules/settings.js?v=11';
import RecurringModule from './modules/recurring.js?v=11';

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
            const response = await fetch(url, { ...options, headers, credentials: 'same-origin' });

            // Handle empty responses safely
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
            try { await this.api('auth/logout', { method: 'POST' }); } catch (e) { }
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

        // Close mobile sidebar
        document.getElementById('sidebar')?.classList.remove('open');
        document.getElementById('sidebar-overlay')?.classList.remove('open');

        // Load view
        const appContent = document.getElementById('app-content');
        if (!appContent) return;

        appContent.innerHTML = `<div class="text-center mt-24"><div class="spinner mx-auto"></div></div>`;

        // Dynamic loading based on route
        const parts = route.split('/');
        const view = parts[0];
        const id = parts[1];

        setTimeout(() => {
            // Build sub-id: everything after the first segment
            const subId = parts.length > 1 ? parts.slice(1).join('/') : undefined;
            switch (view) {
                case 'dashboard': DashboardModule.render(appContent); break;
                case 'invoices': InvoicesModule.render(appContent, subId); break;
                case 'quotes': QuotesModule.render(appContent, subId); break;
                case 'clients': ClientsModule.render(appContent, subId); break;
                case 'recurring': RecurringModule.render(appContent, subId); break;
                case 'settings': SettingsModule.render(appContent); break;
                default: appContent.innerHTML = '<h2>404 No Encontrado</h2>';
            }
        }, 50);
    },

    renderLogin() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="login-page">
                <div class="login-card">
                    <div class="login-logo">
                        <img src="https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png" alt="GridBase Digital Solutions" style="max-height: 56px; max-width: 100%; object-fit: contain;">
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
                    <p style="margin-top: 20px; font-size: 11px; color: var(--text-muted);">
                        Powered by <span style="color: var(--accent); font-weight: 600;">GridBase</span> Digital Solutions
                    </p>
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
        const userInitial = this.state.user.name ? this.state.user.name.charAt(0).toUpperCase() : '?';

        app.innerHTML = `
            <div class="app-layout">
                <div class="sidebar-overlay" id="sidebar-overlay"></div>
                <aside class="sidebar" id="sidebar">
                    <div class="sidebar-brand">
                        <div class="sidebar-brand-logo">
                            <img src="https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png" alt="GridBase Digital Solutions" style="max-height: 36px; max-width: 100%; object-fit: contain;">
                        </div>
                    </div>
                    <nav class="sidebar-nav">
                        <div class="sidebar-section">
                            <div class="sidebar-section-title">Menú</div>
                            <a href="#dashboard" class="sidebar-link active"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg> Panel</a>
                            <a href="#invoices" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Facturas <span id="overdue-badge" style="display:none;margin-left:auto;background:var(--red);color:#fff;font-size:11px;padding:2px 8px;border-radius:10px;font-weight:600;"></span></a>
                            <a href="#recurring" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg> Recurrentes</a>
                            <a href="#quotes" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg> Cotizaciones</a>
                            <a href="#clients" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg> Clientes</a>
                        </div>
                        <div class="sidebar-section">
                            <div class="sidebar-section-title">Sistema</div>
                            <a href="#settings" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg> Configuración</a>
                        </div>
                    </nav>
                    <div class="sidebar-footer">
                        <div class="sidebar-avatar">${userInitial}</div>
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
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <button class="btn btn-icon btn-ghost sidebar-toggle" id="sidebar-toggle" onclick="App.toggleSidebar()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                            </button>
                            <div class="topbar-search">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" placeholder="Buscar facturas, clientes...">
                            </div>
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

        // Sidebar overlay click
        document.getElementById('sidebar-overlay')?.addEventListener('click', () => {
            this.toggleSidebar();
        });

        // Load overdue badge
        this.loadOverdueBadge();
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
        const searchInput = document.querySelector('.topbar-search input');
        if (searchInput) {
            searchInput.addEventListener('input', async (e) => {
                const q = e.target.value.trim().toLowerCase();
                
                // Remove existing dropdown
                document.getElementById('global-search-results')?.remove();
                
                if (q.length < 2) return;
                
                // We'll create a simple dropdown
                const searchContainer = document.querySelector('.topbar-search');
                searchContainer.style.position = 'relative';
                
                const dropdown = document.createElement('div');
                dropdown.id = 'global-search-results';
                dropdown.style.cssText = 'position:absolute;top:100%;left:0;right:0;background:var(--bg-card);border:1px solid var(--border-color);border-radius:6px;box-shadow:0 10px 30px rgba(0,0,0,0.2);margin-top:8px;z-index:9999;max-height:400px;overflow-y:auto;padding:8px 0;';
                dropdown.innerHTML = '<div style="padding:12px 16px;color:var(--text-muted);font-size:13px;text-align:center;">Buscando...</div>';
                searchContainer.appendChild(dropdown);
                
                try {
                    // Fetch data to search
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
                        html += '<div style="padding:4px 16px;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:700;">Facturas</div>';
                        invoices.slice(0, 5).forEach(i => {
                            html += `<a href="#invoices/${i.id}" style="display:block;padding:8px 16px;text-decoration:none;color:inherit;border-bottom:1px solid var(--border-color);font-size:13px;" onclick="document.getElementById('global-search-results').remove();document.querySelector('.topbar-search input').value=''">
                                <div style="display:flex;justify-content:space-between;">
                                    <strong>${i.invoice_number}</strong>
                                    <span style="color:var(--primary);font-weight:600;">${this.formatCurrency(i.total, i.currency)}</span>
                                </div>
                                <div style="color:var(--text-muted);font-size:12px;margin-top:2px;">${i.company_name || i.contact_name}</div>
                            </a>`;
                        });
                    }
                    
                    if (clients.length > 0) {
                        html += '<div style="padding:4px 16px;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:700;margin-top:8px;">Clientes</div>';
                        clients.slice(0, 5).forEach(c => {
                            html += `<a href="#clients/profile/${c.id}" style="display:block;padding:8px 16px;text-decoration:none;color:inherit;border-bottom:1px solid var(--border-color);font-size:13px;" onclick="document.getElementById('global-search-results').remove();document.querySelector('.topbar-search input').value=''">
                                <div><strong>${c.company_name || c.contact_name}</strong></div>
                                <div style="color:var(--text-muted);font-size:12px;margin-top:2px;">${c.email}</div>
                            </a>`;
                        });
                    }
                    
                    if (html === '') {
                        html = '<div style="padding:12px 16px;color:var(--text-muted);font-size:13px;text-align:center;">No se encontraron resultados</div>';
                    }
                    
                    dropdown.innerHTML = html;
                    
                } catch(e) {
                    dropdown.innerHTML = '<div style="padding:12px 16px;color:var(--red);font-size:13px;text-align:center;">Error al buscar</div>';
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.topbar-search')) {
                    document.getElementById('global-search-results')?.remove();
                }
            });
        }
    }
};

// Boot application
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
