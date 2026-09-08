const UsersModule = {
    roles: {
        'admin': {
            name: 'Administrador / Soporte',
            badgeClass: 'role-badge-admin',
            color: '#4F46E5',
            bg: 'rgba(99, 102, 241, 0.12)',
            border: 'rgba(99, 102, 241, 0.25)',
            description: 'Acceso total y configuración del sistema',
            permissions: {
                invoicing: 'Total (Emitir, Anular, NC/ND, Certificación)',
                commercial: 'Total (Cotizaciones, Pagos, Clientes, Productos)',
                reports: 'Total (607, 608, IT-1, IR-2, Auditorías)',
                settings: 'Total (Certificados DGII, SMTP, Usuarios, Respaldos)'
            }
        },
        'gerente': {
            name: 'Gerente Operativo',
            badgeClass: 'role-badge-gerente',
            color: '#00A460',
            bg: 'rgba(0, 164, 96, 0.12)',
            border: 'rgba(0, 164, 96, 0.25)',
            description: 'Supervisión operativa y comercial sin configuración crítica',
            permissions: {
                invoicing: 'Emisión, Consulta y Notas de Crédito',
                commercial: 'Total (Cotizaciones, Cobros, Descuentos, Catálogo)',
                reports: 'Visualización de Ventas, Ingresos y Cierres',
                settings: 'Gestión Básica de Usuarios (Sin Certificados Fiscales)'
            }
        },
        'contador': {
            name: 'Contador / Auditor',
            badgeClass: 'role-badge-contador',
            color: '#2563EB',
            bg: 'rgba(37, 99, 235, 0.12)',
            border: 'rgba(37, 99, 235, 0.25)',
            description: 'Gestión contable, fiscal y auditoría de comprobantes',
            permissions: {
                invoicing: 'Consulta de Facturas y Trazabilidad DGII',
                commercial: 'Consulta de Pagos, Cuentas por Cobrar y Clientes',
                reports: 'Total (Formatos 607, 608, IT-1, IR-2 y Declaraciones)',
                settings: 'Solo Lectura Informativa'
            }
        },
        'vendedor': {
            name: 'Vendedor / Facturación',
            badgeClass: 'role-badge-vendedor',
            color: '#D97706',
            bg: 'rgba(217, 119, 6, 0.12)',
            border: 'rgba(217, 119, 6, 0.25)',
            description: 'Emisión comercial y atención a clientes en punto de venta',
            permissions: {
                invoicing: 'Emisión de Facturas y Comprobantes de Consumo',
                commercial: 'Cotizaciones, Registro de Clientes y Catálogo',
                reports: 'Sin Acceso',
                settings: 'Sin Acceso'
            }
        }
    },

    state: {
        users: [],
        stats: {},
        filterQuery: '',
        filterRole: 'all',
        filterStatus: 'all'
    },

    async render(container, id) {
        if (id === 'nuevo') { 
            this.renderForm(container); 
            return; 
        }
        if (id) { 
            this.renderForm(container, id); 
            return; 
        }
        this.renderList(container);
    },

    async renderList(container) {
        try {
            const response = await window.App.api('users');
            this.state.users = Array.isArray(response) ? response : (response.data || []);
            this.state.stats = response.stats || this.computeLocalStats(this.state.users);
            this.renderListView(container);
        } catch (error) {
            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Gestión de Usuarios</h1>
                        <p class="page-subtitle">Administra cuentas de acceso, roles y estados del sistema</p>
                    </div>
                </div>
                <div class="alert alert-error" style="margin-top:20px;">
                    Error al cargar los usuarios del sistema. Por favor intente nuevamente.
                </div>
            `;
        }
    },

    computeLocalStats(users) {
        return {
            total: users.length,
            active: users.filter(u => u.is_active).length,
            inactive: users.filter(u => !u.is_active).length,
            with_2fa: users.filter(u => u.has_2fa).length,
            by_role: {
                admin: users.filter(u => u.role === 'admin').length,
                gerente: users.filter(u => u.role === 'gerente').length,
                contador: users.filter(u => u.role === 'contador').length,
                vendedor: users.filter(u => u.role === 'vendedor').length
            }
        };
    },

    renderListView(container) {
        const stats = this.state.stats || {};
        const currentUser = window.App.state.user || {};

        container.innerHTML = `
            <div class="page-header" style="margin-bottom:20px;">
                <div>
                    <h1 class="page-title">Gestión de Usuarios</h1>
                    <p class="page-subtitle">Administración integral de accesos, roles operativos, seguridad y trazabilidad</p>
                </div>
                <button class="btn btn-primary" onclick="window.App.navigate('usuarios/nuevo')" style="display:inline-flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Nuevo Usuario
                </button>
            </div>

            <!-- KPI Summary Cards -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:14px;margin-bottom:24px;">
                
                <!-- Card 1: Total -->
                <div style="background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:16px 18px;box-shadow:var(--shadow-sm);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-size:11.5px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.5px;">Total Usuarios</span>
                        <div style="width:30px;height:30px;border-radius:8px;background:var(--bg-hover);color:var(--color-text-secondary);display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>
                    <div style="font-size:24px;font-weight:800;color:var(--color-text-primary);font-family:'JetBrains Mono',monospace;" id="kpi-total-users">${stats.total || 0}</div>
                    <div style="font-size:11.5px;color:var(--color-text-muted);margin-top:4px;">Cuentas registradas</div>
                </div>

                <!-- Card 2: Activos -->
                <div style="background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:16px 18px;box-shadow:var(--shadow-sm);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-size:11.5px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.5px;">Usuarios Activos</span>
                        <div style="width:30px;height:30px;border-radius:8px;background:rgba(0,164,96,0.1);color:#00a460;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                    </div>
                    <div style="font-size:24px;font-weight:800;color:#00a460;font-family:'JetBrains Mono',monospace;" id="kpi-active-users">${stats.active || 0}</div>
                    <div style="font-size:11.5px;color:var(--color-text-muted);margin-top:4px;">Acceso autorizado</div>
                </div>

                <!-- Card 3: Inactivos -->
                <div style="background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:16px 18px;box-shadow:var(--shadow-sm);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-size:11.5px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.5px;">Suspendidos</span>
                        <div style="width:30px;height:30px;border-radius:8px;background:rgba(239,68,68,0.1);color:#ef4444;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                        </div>
                    </div>
                    <div style="font-size:24px;font-weight:800;color:var(--color-text-primary);font-family:'JetBrains Mono',monospace;" id="kpi-inactive-users">${stats.inactive || 0}</div>
                    <div style="font-size:11.5px;color:var(--color-text-muted);margin-top:4px;">Acceso bloqueado</div>
                </div>

                <!-- Card 4: Seguridad 2FA -->
                <div style="background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:16px 18px;box-shadow:var(--shadow-sm);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-size:11.5px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.5px;">Seguridad 2FA</span>
                        <div style="width:30px;height:30px;border-radius:8px;background:rgba(79,70,229,0.1);color:#4f46e5;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </div>
                    </div>
                    <div style="font-size:24px;font-weight:800;color:#4f46e5;font-family:'JetBrains Mono',monospace;" id="kpi-2fa-users">${stats.with_2fa || 0}</div>
                    <div style="font-size:11.5px;color:var(--color-text-muted);margin-top:4px;">Doble factor activo</div>
                </div>

            </div>

            <!-- Table Outer & Filters -->
            <div class="table-outer">
                <div class="table-toolbar" style="padding:14px 16px;border-bottom:1px solid var(--color-border);display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;">
                    
                    <!-- Search Input -->
                    <div class="search-wrapper" style="flex:1 1 240px;max-width:320px;position:relative;">
                        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:0.5;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" id="users-search" class="form-control" placeholder="Buscar por nombre o correo..." value="${this.state.filterQuery}" style="width:100%;padding-left:36px;font-size:13px;height:38px;">
                    </div>

                    <!-- Dropdown Filters -->
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <!-- Role Filter -->
                        <select id="users-filter-role" class="form-select" style="font-size:13px;height:38px;padding:4px 30px 4px 12px;min-width:160px;">
                            <option value="all" ${this.state.filterRole === 'all' ? 'selected' : ''}>Todos los Roles</option>
                            <option value="admin" ${this.state.filterRole === 'admin' ? 'selected' : ''}>Administradores</option>
                            <option value="gerente" ${this.state.filterRole === 'gerente' ? 'selected' : ''}>Gerentes</option>
                            <option value="contador" ${this.state.filterRole === 'contador' ? 'selected' : ''}>Contadores</option>
                            <option value="vendedor" ${this.state.filterRole === 'vendedor' ? 'selected' : ''}>Vendedores</option>
                        </select>

                        <!-- Status Filter -->
                        <select id="users-filter-status" class="form-select" style="font-size:13px;height:38px;padding:4px 30px 4px 12px;min-width:140px;">
                            <option value="all" ${this.state.filterStatus === 'all' ? 'selected' : ''}>Todos los Estados</option>
                            <option value="active" ${this.state.filterStatus === 'active' ? 'selected' : ''}>Activos</option>
                            <option value="inactive" ${this.state.filterStatus === 'inactive' ? 'selected' : ''}>Inactivos</option>
                        </select>

                        <!-- Clear Filters Button -->
                        <button id="users-reset-filters" class="btn btn-secondary" style="height:38px;padding:0 12px;font-size:12px;display:${(this.state.filterQuery || this.state.filterRole !== 'all' || this.state.filterStatus !== 'all') ? 'inline-flex' : 'none'};align-items:center;gap:6px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            Limpiar
                        </button>
                    </div>

                </div>

                <!-- Desktop Table View -->
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Contacto</th>
                                <th>Rol en Bills</th>
                                <th>Estado</th>
                                <th>Seguridad & Acceso</th>
                                <th>Actividad</th>
                                <th style="width:130px;text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            ${this.generateTableRows(this.getFilteredUsers(), currentUser)}
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card List View -->
                <div id="users-mobile-list" class="mobile-card-list">
                    ${this.generateMobileCards(this.getFilteredUsers(), currentUser)}
                </div>
            </div>
        `;

        this.bindFilterEvents(container, currentUser);
    },

    bindFilterEvents(container, currentUser) {
        const searchInput = container.querySelector('#users-search');
        const roleSelect = container.querySelector('#users-filter-role');
        const statusSelect = container.querySelector('#users-filter-status');
        const resetBtn = container.querySelector('#users-reset-filters');

        const applyFilters = () => {
            const filtered = this.getFilteredUsers();
            const tbody = container.querySelector('#users-table-body');
            const mobileList = container.querySelector('#users-mobile-list');
            
            if (tbody) tbody.innerHTML = this.generateTableRows(filtered, currentUser);
            if (mobileList) mobileList.innerHTML = this.generateMobileCards(filtered, currentUser);

            const hasActiveFilters = Boolean(this.state.filterQuery || this.state.filterRole !== 'all' || this.state.filterStatus !== 'all');
            if (resetBtn) resetBtn.style.display = hasActiveFilters ? 'inline-flex' : 'none';
        };

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.state.filterQuery = e.target.value.trim().toLowerCase();
                applyFilters();
            });
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', (e) => {
                this.state.filterRole = e.target.value;
                applyFilters();
            });
        }

        if (statusSelect) {
            statusSelect.addEventListener('change', (e) => {
                this.state.filterStatus = e.target.value;
                applyFilters();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                this.state.filterQuery = '';
                this.state.filterRole = 'all';
                this.state.filterStatus = 'all';
                if (searchInput) searchInput.value = '';
                if (roleSelect) roleSelect.value = 'all';
                if (statusSelect) statusSelect.value = 'all';
                applyFilters();
            });
        }
    },

    getFilteredUsers() {
        return this.state.users.filter(user => {
            // Search query filter
            if (this.state.filterQuery) {
                const q = this.state.filterQuery;
                const nameMatch = user.name && user.name.toLowerCase().includes(q);
                const emailMatch = user.email && user.email.toLowerCase().includes(q);
                if (!nameMatch && !emailMatch) return false;
            }

            // Role filter
            if (this.state.filterRole !== 'all') {
                if (user.role !== this.state.filterRole) return false;
            }

            // Status filter
            if (this.state.filterStatus !== 'all') {
                const isActive = Boolean(user.is_active);
                if (this.state.filterStatus === 'active' && !isActive) return false;
                if (this.state.filterStatus === 'inactive' && isActive) return false;
            }

            return true;
        });
    },

    getInitials(name) {
        if (!name) return 'U';
        const parts = name.trim().split(' ');
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[1][0]).toUpperCase();
    },

    generateTableRows(users, currentUser) {
        if (!users || users.length === 0) {
            return `<tr><td colspan="7" style="text-align:center;padding:48px 16px;color:var(--color-text-muted);">No se encontraron usuarios coincidentes con los filtros aplicados.</td></tr>`;
        }

        return users.map(user => {
            const isSelf = currentUser && currentUser.id === user.id;
            const roleMeta = this.roles[user.role] || { name: user.role, color: '#64748B', bg: '#F1F5F9', border: '#E2E8F0' };
            const initials = this.getInitials(user.name);
            const invoicesCount = user.invoices_count || 0;
            const quotesCount = user.quotes_count || 0;

            return `
                <tr>
                    <!-- Usuario / Avatar -->
                    <td style="vertical-align:middle;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:50%;background:${roleMeta.color};color:#FFFFFF;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;">
                                ${initials}
                            </div>
                            <div>
                                <div style="font-weight:600;color:var(--color-text-primary);display:flex;align-items:center;gap:6px;">
                                    ${user.name}
                                    ${isSelf ? `<span style="font-size:10px;font-weight:700;padding:2px 6px;background:var(--bg-hover);color:var(--color-primary);border-radius:var(--radius-sm);border:1px solid var(--color-border);">Tú</span>` : ''}
                                </div>
                                <div style="font-size:11.5px;color:var(--color-text-muted);margin-top:2px;">
                                    ID: #${user.id}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Contacto / Email -->
                    <td style="vertical-align:middle;">
                        <span style="font-family:'JetBrains Mono',monospace;font-size:12.5px;color:var(--color-text-primary);">
                            ${user.email}
                        </span>
                    </td>

                    <!-- Rol -->
                    <td style="vertical-align:middle;">
                        <span style="background:${roleMeta.bg};color:${roleMeta.color};border:1px solid ${roleMeta.border};font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:var(--radius-sm);display:inline-block;white-space:nowrap;">
                            ${roleMeta.name}
                        </span>
                    </td>

                    <!-- Estado (Toggle) -->
                    <td style="vertical-align:middle;">
                        ${isSelf ? `
                            <span class="badge ${user.is_active ? 'badge-paid' : 'badge-draft'}" style="font-size:11px;font-weight:600;">
                                ${user.is_active ? 'Activo' : 'Inactivo'}
                            </span>
                        ` : `
                            <button type="button" class="btn btn-sm ${user.is_active ? 'btn-secondary' : 'btn-ghost'}" 
                                style="padding:3px 10px;font-size:11px;font-weight:700;border-radius:var(--radius-full);color:${user.is_active ? '#00a460' : 'var(--color-danger-text, #ef4444)'};border:1px solid ${user.is_active ? 'rgba(0,164,96,0.3)' : 'rgba(239,68,68,0.3)'};background:${user.is_active ? 'rgba(0,164,96,0.08)' : 'rgba(239,68,68,0.08)'};"
                                onclick="UsersModule.toggleUserStatus(${user.id}, ${Boolean(user.is_active)})"
                                title="Haga clic para ${user.is_active ? 'desactivar' : 'activar'} usuario">
                                ${user.is_active ? '● Activo' : '○ Inactivo'}
                            </button>
                        `}
                    </td>

                    <!-- Seguridad & Ultimo Acceso -->
                    <td style="vertical-align:middle;">
                        <div style="display:flex;flex-direction:column;gap:3px;">
                            <div>
                                ${user.has_2fa ? `
                                    <span style="background:rgba(16,185,129,0.1);color:#10b981;padding:1px 6px;border-radius:4px;font-size:10.5px;font-weight:700;display:inline-block;">2FA Activo</span>
                                ` : `
                                    <span style="background:var(--bg-hover);color:var(--color-text-muted);padding:1px 6px;border-radius:4px;font-size:10.5px;font-weight:600;display:inline-block;">Sin 2FA</span>
                                `}
                            </div>
                            <div style="font-size:11px;color:var(--color-text-muted);" title="${user.last_login_formatted || 'Sin accesos'}">
                                ${user.last_login_human || 'Sin accesos'}
                            </div>
                        </div>
                    </td>

                    <!-- Actividad -->
                    <td style="vertical-align:middle;">
                        <div style="font-size:12px;color:var(--color-text-primary);font-weight:600;">
                            ${invoicesCount} facturas
                        </div>
                        <div style="font-size:11px;color:var(--color-text-muted);">
                            ${quotesCount} cotizaciones
                        </div>
                    </td>

                    <!-- Acciones -->
                    <td style="vertical-align:middle;text-align:right;">
                        <div class="row-actions" style="justify-content:flex-end;gap:4px;">
                            
                            <!-- Reenviar Credenciales -->
                            <button type="button" class="btn-icon" style="width:30px;height:30px;" onclick="UsersModule.resendCredentials(${user.id}, '${user.name}', '${user.email}')" title="Reenviar Credenciales por Correo">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            </button>

                            <!-- Restablecer Contrasena -->
                            <button type="button" class="btn-icon" style="width:30px;height:30px;" onclick="UsersModule.showResetPasswordModal(${user.id}, '${user.name}', '${user.email}')" title="Restablecer Contraseña">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </button>

                            <!-- Editar -->
                            <a href="#usuarios/${user.id}" class="btn-icon" style="width:30px;height:30px;" title="Editar Usuario">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </a>

                            <!-- Eliminar (Proteccion si es self) -->
                            ${!isSelf ? `
                                <button type="button" class="btn-icon" style="width:30px;height:30px;" onclick="UsersModule.deleteUser(${user.id}, '${user.name}')" title="Eliminar Usuario">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger-icon, #ef4444)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            ` : `<div style="width:30px;height:30px;display:inline-block;"></div>`}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    },

    generateMobileCards(users, currentUser) {
        if (!users || users.length === 0) {
            return '<div class="text-center text-muted" style="padding:32px;">No hay usuarios que coincidan con los filtros.</div>';
        }

        return users.map(user => {
            const isSelf = currentUser && currentUser.id === user.id;
            const roleMeta = this.roles[user.role] || { name: user.role, color: '#64748B', bg: '#F1F5F9', border: '#E2E8F0' };
            const initials = this.getInitials(user.name);

            return `
                <div class="mobile-card" style="padding:16px;margin-bottom:12px;border:1px solid var(--color-border);border-radius:var(--radius-lg);background:var(--bg-card);">
                    
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:50%;background:${roleMeta.color};color:#FFFFFF;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">
                                ${initials}
                            </div>
                            <div>
                                <div style="font-weight:700;color:var(--color-text-primary);font-size:14px;">
                                    ${user.name} ${isSelf ? '<span style="font-size:10px;font-weight:700;padding:1px 5px;background:var(--bg-hover);color:var(--color-primary);border-radius:3px;">Tú</span>' : ''}
                                </div>
                                <div style="font-size:12px;color:var(--color-text-muted);font-family:'JetBrains Mono',monospace;">
                                    ${user.email}
                                </div>
                            </div>
                        </div>

                        <span style="background:${roleMeta.bg};color:${roleMeta.color};border:1px solid ${roleMeta.border};font-size:11px;font-weight:700;padding:3px 8px;border-radius:var(--radius-sm);">
                            ${roleMeta.name}
                        </span>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-top:1px solid var(--color-border);border-bottom:1px solid var(--color-border);margin-bottom:12px;font-size:12px;">
                        <div style="color:var(--color-text-muted);">
                            Estado: <strong style="color:${user.is_active ? '#00a460' : 'var(--color-danger-text)'};">${user.is_active ? 'Activo' : 'Inactivo'}</strong>
                        </div>
                        <div style="color:var(--color-text-muted);">
                            Último login: <strong style="color:var(--color-text-primary);">${user.last_login_human || 'Nunca'}</strong>
                        </div>
                    </div>

                    <!-- Mobile Action Buttons -->
                    <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="UsersModule.resendCredentials(${user.id}, '${user.name}', '${user.email}')" style="font-size:12px;" title="Reenviar Credenciales">
                            Reenviar
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="UsersModule.showResetPasswordModal(${user.id}, '${user.name}', '${user.email}')" style="font-size:12px;">
                            Contraseña
                        </button>
                        <a href="#usuarios/${user.id}" class="btn btn-secondary btn-sm" style="font-size:12px;">
                            Editar
                        </a>
                        ${!isSelf ? `
                            <button type="button" class="btn btn-secondary btn-sm" onclick="UsersModule.toggleUserStatus(${user.id}, ${Boolean(user.is_active)})" style="font-size:12px;color:${user.is_active ? 'var(--color-danger-text)' : '#00a460'};">
                                ${user.is_active ? 'Suspender' : 'Activar'}
                            </button>
                        ` : ''}
                    </div>

                </div>
            `;
        }).join('');
    },

    async resendCredentials(id, name, email) {
        if (!confirm(`¿Deseas reenviar las credenciales de acceso a ${name} (${email})? Se generará una nueva contraseña temporal y se enviará por correo.`)) {
            return;
        }

        window.App.showToast('Enviando credenciales por correo...', 'info');

        try {
            const res = await window.App.api(`users/${id}/resend-credentials`, { method: 'POST' });
            if (res.success) {
                window.App.showToast(res.message || 'Credenciales enviadas exitosamente');
            } else {
                window.App.showToast(res.message || 'Error al enviar credenciales', 'error');
            }
        } catch (e) {}
    },

    async toggleUserStatus(id, currentStatus) {
        const currentUser = window.App.state.user || {};
        if (currentUser.id === id) {
            window.App.showToast('No puedes desactivar tu propia cuenta en sesión.', 'error');
            return;
        }

        const actionText = currentStatus ? 'desactivar' : 'activar';
        if (!confirm(`¿Estás seguro de que deseas ${actionText} el acceso de este usuario?`)) {
            return;
        }

        try {
            const res = await window.App.api(`users/${id}/toggle-status`, { method: 'PATCH' });
            window.App.showToast(res.message || 'Estado de usuario actualizado correctamente');
            
            // Update local state
            const targetUser = this.state.users.find(u => u.id === id);
            if (targetUser) {
                targetUser.is_active = res.is_active;
                this.state.stats = this.computeLocalStats(this.state.users);
            }

            // Refresh table view
            const container = document.querySelector('.main-content') || document.getElementById('app-content');
            if (container) this.renderListView(container);
        } catch (error) {
            window.App.showToast('Error al modificar estado del usuario', 'error');
        }
    },

    showResetPasswordModal(id, name, email) {
        document.getElementById('reset-password-modal')?.remove();

        const modal = document.createElement('div');
        modal.id = 'reset-password-modal';
        modal.className = 'modal-overlay open';
        modal.innerHTML = `
            <div class="modal" style="max-width:460px;">
                <div class="modal-header">
                    <div class="modal-title" style="display:flex;align-items:center;gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Restablecer Contraseña
                    </div>
                    <button type="button" class="btn-icon" id="modal-close-btn" style="width:28px;height:28px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <div class="modal-body">
                    <div style="background:var(--bg-hover);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:12px 14px;margin-bottom:18px;">
                        <div style="font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.5px;">Usuario Seleccionado</div>
                        <div style="font-size:13.5px;font-weight:700;color:var(--color-text-primary);margin-top:2px;">${name}</div>
                        <div style="font-size:12px;color:var(--color-text-secondary);font-family:'JetBrains Mono',monospace;">${email}</div>
                    </div>

                    <div class="form-group" style="margin-bottom:14px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <label class="form-label" style="margin:0;">Nueva Contraseña *</label>
                            <button type="button" id="btn-generate-pwd" class="btn btn-ghost" style="padding:2px 6px;font-size:11.5px;color:var(--color-primary);font-weight:600;">
                                Generar Contraseña Segura
                            </button>
                        </div>
                        <div style="position:relative;">
                            <input type="text" id="modal-new-pwd" class="form-control" placeholder="Mínimo 6 caracteres" style="font-family:'JetBrains Mono',monospace;padding-right:70px;" required>
                            <button type="button" id="btn-copy-pwd" class="btn btn-ghost" style="position:absolute;right:4px;top:50%;transform:translateY(-50%);padding:4px 8px;font-size:11px;color:var(--color-text-muted);" title="Copiar al portapapeles">
                                Copiar
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:8px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12.5px;color:var(--color-text-secondary);">
                            <input type="checkbox" id="modal-send-email" checked style="accent-color:var(--color-primary);width:16px;height:16px;">
                            <span>Enviar nueva contraseña por correo a <strong>${email}</strong></span>
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="modal-cancel-btn">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="modal-submit-btn">Guardar Contraseña</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const closeModal = () => modal.remove();
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
        document.getElementById('modal-close-btn').addEventListener('click', closeModal);
        document.getElementById('modal-cancel-btn').addEventListener('click', closeModal);

        const pwdInput = document.getElementById('modal-new-pwd');
        const copyBtn = document.getElementById('btn-copy-pwd');
        const generateBtn = document.getElementById('btn-generate-pwd');
        const submitBtn = document.getElementById('modal-submit-btn');

        generateBtn.addEventListener('click', () => {
            const generated = this.generateRandomPassword();
            pwdInput.value = generated;
            pwdInput.focus();
        });

        copyBtn.addEventListener('click', async () => {
            if (!pwdInput.value) return;
            try {
                await navigator.clipboard.writeText(pwdInput.value);
                copyBtn.textContent = 'Copiado!';
                setTimeout(() => { copyBtn.textContent = 'Copiar'; }, 2000);
            } catch (e) {}
        });

        submitBtn.addEventListener('click', async () => {
            const password = pwdInput.value.trim();
            if (!password || password.length < 6) {
                window.App.showToast('La contraseña debe tener al menos 6 caracteres', 'error');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';

            try {
                const res = await window.App.api(`users/${id}/reset-password`, {
                    method: 'POST',
                    body: {
                        password: password,
                        send_email: document.getElementById('modal-send-email').checked
                    }
                });

                closeModal();
                window.App.showToast(res.message || 'Contraseña restablecida con éxito');
            } catch (err) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Guardar Contraseña';
            }
        });
    },

    generateRandomPassword() {
        const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*';
        let pass = '';
        for (let i = 0; i < 12; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return pass;
    },

    async renderForm(container, id) {
        let user = { is_active: true, role: 'vendedor' };
        if (id) {
            try {
                user = await window.App.api(`users/${id}`);
            } catch (e) {
                container.innerHTML = `<div class="alert alert-error">Error al cargar datos del usuario solicitado.</div>`;
                return;
            }
        }

        const isEditing = Boolean(id);

        container.innerHTML = `
            <div style="margin-bottom:14px;">
                <a href="#usuarios" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Volver a Gestión de Usuarios
                </a>
            </div>

            <div class="page-header" style="margin-bottom:24px;">
                <div>
                    <h1 class="page-title">${isEditing ? 'Editar Perfil de Usuario' : 'Nuevo Usuario del Sistema'}</h1>
                    <p class="page-subtitle">${isEditing ? 'Modifica los privilegios de acceso, rol y credenciales de esta cuenta' : 'Registra una nueva cuenta de acceso comercial y operativa'}</p>
                </div>
                <button type="button" class="btn btn-secondary" onclick="window.App.navigate('usuarios')">Cancelar</button>
            </div>

            <div style="display:grid;grid-template-columns:1fr;gap:24px;max-width:880px;">
                
                <!-- Main Form Card -->
                <form id="user-form" class="form-card" style="background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);">
                    <div style="padding:24px 28px;">
                        
                        <div style="font-size:14px;font-weight:700;color:var(--color-text-primary);margin-bottom:18px;padding-bottom:10px;border-bottom:1px solid var(--color-border);">
                            Información General de la Cuenta
                        </div>

                        <div class="grid-2" style="gap:18px;">
                            
                            <!-- Nombre -->
                            <div class="form-group">
                                <label class="form-label">Nombre Completo *</label>
                                <input type="text" id="u_name" class="form-control" value="${user.name || ''}" placeholder="Ej: Juan Pérez" required style="font-size:13.5px;">
                            </div>

                            <!-- Correo -->
                            <div class="form-group">
                                <label class="form-label">Correo Electrónico *</label>
                                <input type="email" id="u_email" class="form-control" value="${user.email || ''}" placeholder="Ej: juan.perez@empresa.com.do" required autocomplete="email" style="font-size:13.5px;font-family:'JetBrains Mono',monospace;">
                            </div>

                            <!-- Rol -->
                            <div class="form-group">
                                <label class="form-label">Rol y Nivel de Privilegios *</label>
                                <select id="u_role" class="form-select" required style="font-size:13.5px;">
                                    <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Administrador / Soporte (Acceso Total)</option>
                                    <option value="gerente" ${user.role === 'gerente' ? 'selected' : ''}>Gerente Operativo (Supervisión Comercial)</option>
                                    <option value="contador" ${user.role === 'contador' ? 'selected' : ''}>Contador / Auditor (Reportes e Impuestos)</option>
                                    <option value="vendedor" ${user.role === 'vendedor' ? 'selected' : ''}>Vendedor / Facturación (Emisión en Caja)</option>
                                </select>
                            </div>

                            <!-- Estado -->
                            <div class="form-group">
                                <label class="form-label">Estado de la Cuenta</label>
                                <select id="u_status" class="form-select" style="font-size:13.5px;">
                                    <option value="1" ${user.is_active ? 'selected' : ''}>Activo (Permitir Acceso Inmediato)</option>
                                    <option value="0" ${!user.is_active ? 'selected' : ''}>Inactivo (Bloquear Sesión)</option>
                                </select>
                            </div>

                            <!-- Contrasena -->
                            <div class="form-group" style="grid-column: span 2;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                    <label class="form-label" style="margin:0;">
                                        ${isEditing ? 'Nueva Contraseña (Opcional - dejar en blanco para mantener actual)' : 'Contraseña de Acceso *'}
                                    </label>
                                    <button type="button" id="btn-form-gen-pwd" class="btn btn-ghost" style="padding:2px 8px;font-size:11.5px;color:var(--color-primary);font-weight:600;">
                                        Generar Aleatoria
                                    </button>
                                </div>
                                <input type="text" id="u_password" class="form-control" placeholder="${isEditing ? 'Mantener contraseña existente' : 'Mínimo 6 caracteres'}" ${isEditing ? '' : 'required'} style="font-family:'JetBrains Mono',monospace;font-size:13px;">
                            </div>

                            <!-- Opcion de envio de correo de bienvenida -->
                            ${!isEditing ? `
                                <div class="form-group" style="grid-column: span 2;margin-top:-6px;">
                                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--color-text-secondary);">
                                        <input type="checkbox" id="u_send_welcome" checked style="accent-color:var(--color-primary);width:16px;height:16px;">
                                        <span>Enviar credenciales de acceso por correo electrónico al usuario</span>
                                    </label>
                                </div>
                            ` : ''}

                        </div>

                        <!-- Matriz de Permisos por Rol -->
                        <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--color-border);" id="role-matrix-container">
                            ${this.renderRoleMatrix(user.role || 'vendedor')}
                        </div>

                        <!-- Submit Button -->
                        <div style="margin-top:24px;display:flex;gap:12px;align-items:center;">
                            <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                                ${isEditing ? 'Guardar Cambios' : 'Crear Cuenta de Acceso'}
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.App.navigate('usuarios')">
                                Cancelar
                            </button>
                        </div>

                    </div>
                </form>

            </div>
        `;

        // Bind interactive role matrix change
        const roleSelect = container.querySelector('#u_role');
        const matrixContainer = container.querySelector('#role-matrix-container');
        if (roleSelect && matrixContainer) {
            roleSelect.addEventListener('change', (e) => {
                matrixContainer.innerHTML = this.renderRoleMatrix(e.target.value);
            });
        }

        // Bind random password generator button in form
        const formGenPwdBtn = container.querySelector('#btn-form-gen-pwd');
        const formPwdInput = container.querySelector('#u_password');
        if (formGenPwdBtn && formPwdInput) {
            formGenPwdBtn.addEventListener('click', () => {
                formPwdInput.value = this.generateRandomPassword();
                formPwdInput.focus();
            });
        }

        // Submit handler
        container.querySelector('#user-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                name: document.getElementById('u_name').value.trim(),
                email: document.getElementById('u_email').value.trim(),
                role: document.getElementById('u_role').value,
                is_active: document.getElementById('u_status').value === '1'
            };

            const password = document.getElementById('u_password').value.trim();
            if (password) {
                if (password.length < 6) {
                    window.App.showToast('La contraseña debe tener al menos 6 caracteres', 'error');
                    return;
                }
                payload.password = password;
            }

            if (!isEditing) {
                const sendWelcome = document.getElementById('u_send_welcome');
                if (sendWelcome) payload.send_welcome_email = sendWelcome.checked;
            }

            try {
                if (isEditing) {
                    await window.App.api(`users/${id}`, { method: 'PUT', body: payload });
                    window.App.showToast('Usuario actualizado con éxito');
                } else {
                    const res = await window.App.api('users', { method: 'POST', body: payload });
                    if (res && res.email_sent === false && payload.send_welcome_email) {
                        window.App.showToast(res.message || 'Usuario creado, pero hubo un error al enviar el correo', 'warning');
                    } else {
                        window.App.showToast(res?.message || 'Usuario creado con éxito');
                    }
                }
                window.App.navigate('usuarios');
            } catch (err) {}
        });
    },

    renderRoleMatrix(roleKey) {
        const role = this.roles[roleKey] || this.roles['vendedor'];
        const perms = role.permissions;

        return `
            <div style="background:var(--bg-hover);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:16px 18px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <div>
                        <div style="font-size:11px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.5px;">Matriz de Capacidades Asignadas</div>
                        <div style="font-size:13.5px;font-weight:700;color:${role.color};margin-top:2px;">${role.name}</div>
                    </div>
                    <span style="font-size:11.5px;color:var(--color-text-muted);max-width:320px;text-align:right;">${role.description}</span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:10px;font-size:12px;">
                    <div style="background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-sm);padding:8px 12px;">
                        <div style="color:var(--color-text-muted);font-weight:600;font-size:10.5px;text-transform:uppercase;margin-bottom:3px;">Facturación & e-CF</div>
                        <div style="color:var(--color-text-primary);font-weight:600;">${perms.invoicing}</div>
                    </div>
                    <div style="background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-sm);padding:8px 12px;">
                        <div style="color:var(--color-text-muted);font-weight:600;font-size:10.5px;text-transform:uppercase;margin-bottom:3px;">Gestión Comercial</div>
                        <div style="color:var(--color-text-primary);font-weight:600;">${perms.commercial}</div>
                    </div>
                    <div style="background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-sm);padding:8px 12px;">
                        <div style="color:var(--color-text-muted);font-weight:600;font-size:10.5px;text-transform:uppercase;margin-bottom:3px;">Reportes Fiscales</div>
                        <div style="color:var(--color-text-primary);font-weight:600;">${perms.reports}</div>
                    </div>
                    <div style="background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-sm);padding:8px 12px;">
                        <div style="color:var(--color-text-muted);font-weight:600;font-size:10.5px;text-transform:uppercase;margin-bottom:3px;">Configuración</div>
                        <div style="color:var(--color-text-primary);font-weight:600;">${perms.settings}</div>
                    </div>
                </div>
            </div>
        `;
    },

    async deleteUser(id, name) {
        const currentUser = window.App.state.user || {};
        if (currentUser.id === id) {
            window.App.showToast('No puedes eliminar tu propio usuario en sesión.', 'error');
            return;
        }

        if (confirm(`¿Estás seguro de que deseas eliminar permanentemente al usuario ${name || ''}? Esta acción es irreversible.`)) {
            try {
                await window.App.api(`users/${id}`, { method: 'DELETE' });
                window.App.showToast('Usuario eliminado del sistema');
                
                // Remove from local state
                this.state.users = this.state.users.filter(u => u.id !== id);
                this.state.stats = this.computeLocalStats(this.state.users);

                const container = document.querySelector('.main-content') || document.getElementById('app-content');
                if (container) this.renderListView(container);
            } catch (e) {}
        }
    }
};

window.UsersModule = UsersModule;
export default UsersModule;
