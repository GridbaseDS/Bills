const UsersModule = {
    roles: {
        'admin': 'Administrador / Soporte',
        'gerente': 'Gerente Operativo',
        'contador': 'Contador',
        'vendedor': 'Vendedor'
    },

    async render(container, id) {
        if (id === 'nuevo') { this.renderForm(container); return; }
        if (id) { this.renderForm(container, id); return; }
        this.renderList(container);
    },

    async renderList(container) {
        try {
            const users = await window.App.api('users');
            const currentUser = window.App.state.user;

            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Gestión de Usuarios</h1>
                        <p class="page-subtitle">Administra cuentas de acceso, roles y estados del sistema</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.App.navigate('usuarios/nuevo')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Nuevo Usuario
                    </button>
                </div>

                <div class="table-outer">
                    <div class="table-toolbar">
                        <div class="search-wrapper" style="max-width: 300px; display: flex; width: 100%;">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="users-search" class="form-control" placeholder="Buscar usuarios..." style="width: 100%; padding-left: 36px;">
                        </div>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo Electrónico</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th style="width: 100px; text-align:right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                ${this.generateTableRows(users, currentUser)}
                            </tbody>
                        </table>
                    </div>
                    <div id="users-mobile-list" class="mobile-card-list">${this.generateMobileCards(users)}</div>
                </div>
            `;

            // Setup search filtering
            const searchInput = document.getElementById('users-search');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const query = e.target.value.toLowerCase();
                    const filtered = users.filter(u => 
                        (u.name && u.name.toLowerCase().includes(query)) ||
                        (u.email && u.email.toLowerCase().includes(query))
                    );
                    document.getElementById('users-table-body').innerHTML = this.generateTableRows(filtered, currentUser);
                    const mobileList = document.getElementById('users-mobile-list');
                    if (mobileList) mobileList.innerHTML = this.generateMobileCards(filtered);
                });
            }

        } catch (error) {
            container.innerHTML = `<div class="alert alert-error" style="margin-top:20px;">Error al cargar usuarios de la plataforma.</div>`;
        }
    },

    generateTableRows(users, currentUser) {
        if (!users || users.length === 0) {
            return `<tr><td colspan="5" style="text-align:center;padding:32px;color:var(--color-text-muted);">No hay usuarios registrados</td></tr>`;
        }
        
        return users.map(user => {
            const isSelf = currentUser && currentUser.id === user.id;
            const roleBadge = user.role === 'admin' ? 'badge-paid' : (user.role === 'contador' ? 'badge-sent' : 'badge-draft');
            
            return `
                <tr>
                    <td style="font-weight:500;color:var(--color-text-primary);">${user.name} ${isSelf ? '<span style="font-size:10px;font-weight:600;padding:1px 6px;background:var(--color-border);color:var(--color-text-secondary);border-radius:var(--radius-sm);margin-left:6px;">Tú</span>' : ''}</td>
                    <td style="font-family:monospace;font-size:13px;color:var(--color-text-secondary);">${user.email}</td>
                    <td><span class="badge ${roleBadge}" style="font-size:11px;font-weight:600;">${this.roles[user.role] || user.role}</span></td>
                    <td><span class="badge badge-${user.is_active ? 'active' : 'inactive'}">${user.is_active ? 'Activo' : 'Inactivo'}</span></td>
                    <td style="text-align:right;">
                        <div class="row-actions" style="justify-content:flex-end;">
                            <a href="#usuarios/${user.id}" class="btn-icon" style="width:28px;height:28px;" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a>
                            ${!isSelf ? `<button class="btn-icon" style="width:28px;height:28px;" onclick="UsersModule.deleteUser(${user.id})" title="Eliminar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger-icon)" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>` : `<div style="width:28px;height:28px;display:inline-block;"></div>`}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    },

    generateMobileCards(users) {
        if (!users || users.length === 0) {
            return '<div class="text-center text-muted" style="padding:32px;">No hay usuarios registrados</div>';
        }
        return users.map(user => `
            <a href="#usuarios/${user.id}" class="mobile-card">
                <div class="mobile-card-top">
                    <div class="mobile-card-id">${user.name}</div>
                    <span class="badge badge-${user.is_active ? 'active' : 'inactive'}">${user.is_active ? 'Activo' : 'Inactivo'}</span>
                </div>
                <div class="mobile-card-bottom" style="margin-top:4px;">
                    <div style="font-size:12px;color:var(--color-text-secondary);">${user.email} • ${this.roles[user.role] || user.role}</div>
                    <svg class="mobile-card-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </div>
            </a>
        `).join('');
    },

    async renderForm(container, id) {
        let user = { is_active: true, role: 'vendedor' };
        if (id) {
            try { 
                user = await window.App.api(`users/${id}`); 
            } catch(e) { 
                container.innerHTML = `<div class="alert alert-error">Error al cargar el usuario</div>`; 
                return; 
            }
        }

        container.innerHTML = `
            <div style="margin-bottom:12px;">
                <a href="#usuarios" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Gestión de Usuarios</a>
            </div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">${id ? 'Editar Usuario' : 'Nuevo Usuario'}</h1>
                    <p class="page-subtitle">${id ? 'Modifica los permisos o contraseña de este usuario' : 'Registra una nueva cuenta de acceso al sistema'}</p>
                </div>
                <button class="btn btn-secondary" onclick="window.App.navigate('usuarios')">Cancelar</button>
            </div>

            <form id="user-form" class="form-card">
                <div style="padding:var(--spacing-xl);">
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" id="u_name" class="form-control" value="${user.name || ''}" placeholder="Ej: Juan Pérez" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Correo Electrónico *</label>
                            <input type="email" id="u_email" class="form-control" value="${user.email || ''}" placeholder="Ej: juan.perez@gridbase.com.do" required autocomplete="email">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Rol del Usuario *</label>
                            <select id="u_role" class="form-select" required>
                                <option value="vendedor" ${user.role === 'vendedor' ? 'selected' : ''}>Vendedor (Acceso Facturas/Cotizaciones)</option>
                                <option value="contador" ${user.role === 'contador' ? 'selected' : ''}>Contador (Acceso Reportes/Gastos/Facturas)</option>
                                <option value="gerente" ${user.role === 'gerente' ? 'selected' : ''}>Gerente Operativo (Acceso Operativo Sin Configuración Crítica)</option>
                                <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Administrador / Soporte (Acceso Total)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Estado de la Cuenta</label>
                            <select id="u_status" class="form-select">
                                <option value="1" ${user.is_active ? 'selected' : ''}>Activo (Permitir Acceso)</option>
                                <option value="0" ${!user.is_active ? 'selected' : ''}>Inactivo (Bloquear Acceso)</option>
                            </select>
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Contraseña ${id ? '(Opcional - dejar en blanco para mantener la actual)' : '*'}</label>
                            <input type="password" id="u_password" class="form-control" placeholder="${id ? '••••••••' : 'Mínimo 6 caracteres'}" ${id ? '' : 'required'} autocomplete="new-password">
                        </div>
                    </div>

                    <div class="mt-24">
                        <button type="submit" class="btn btn-primary">${id ? 'Guardar Cambios' : 'Crear Cuenta de Acceso'}</button>
                    </div>
                </div>
            </form>
        `;

        document.getElementById('user-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                name: document.getElementById('u_name').value,
                email: document.getElementById('u_email').value,
                role: document.getElementById('u_role').value,
                is_active: document.getElementById('u_status').value === '1'
            };

            const password = document.getElementById('u_password').value;
            if (password) {
                if (password.length < 6) {
                    window.App.showToast('La contraseña debe tener al menos 6 caracteres', 'error');
                    return;
                }
                payload.password = password;
            }

            try {
                if (id) {
                    await window.App.api(`users/${id}`, { method: 'PUT', body: payload });
                    window.App.showToast('Usuario actualizado con éxito');
                } else {
                    await window.App.api('users', { method: 'POST', body: payload });
                    window.App.showToast('Usuario creado con éxito');
                }
                window.App.navigate('usuarios');
            } catch (err) {}
        });
    },

    async deleteUser(id) {
        if (confirm('¿Estás seguro de que deseas eliminar permanentemente este usuario? Esta acción es irreversible.')) {
            try {
                await window.App.api(`users/${id}`, { method: 'DELETE' });
                window.App.showToast('Usuario eliminado del sistema');
                window.App.navigate('usuarios');
            } catch (e) {
                // Handled in API wrapper
            }
        }
    }
};

export default UsersModule;
