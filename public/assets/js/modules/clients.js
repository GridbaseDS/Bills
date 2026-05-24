const ClientsModule = {
    async render(container, id) {
        if (id === 'new' || id === 'nuevo') { this.renderForm(container); return; }
        if (id && (id.startsWith('profile/') || id.startsWith('perfil/'))) { this.renderProfile(container, id.replace(/^(profile|perfil)\//, '')); return; }
        if (id) { this.renderForm(container, id); return; }
        this.renderList(container);
    },

    async renderList(container) {
        try {
            const data = await window.App.api('clients');
            const allClients = data.data || [];

            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Clientes</h1>
                        <p class="page-subtitle">Administra tu base de datos de clientes</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.App.navigate('clientes/nuevo')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Nuevo Cliente
                    </button>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-lg);flex-wrap:wrap;gap:12px;">
                    <div class="search-wrapper">
                        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input class="search-input" type="text" id="cl-search" placeholder="Buscar cliente...">
                    </div>
                    <span style="font-size:13px;color:var(--color-text-muted);"><span id="cl-count">${allClients.length}</span> cliente(s)</span>
                </div>

                <div class="table-outer">
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead><tr>
                                <th>Cliente</th><th>Correo</th><th>Facturas</th><th>Facturado</th><th>Pendiente</th><th>Estado</th><th></th>
                            </tr></thead>
                            <tbody id="cl-tbody"></tbody>
                        </table>
                    </div>
                </div>
            `;

            this._allClients = allClients;
            this.filterClients();
            document.getElementById('cl-search').addEventListener('input', () => this.filterClients());
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar clientes</div>`;
        }
    },

    filterClients() {
        const search = (document.getElementById('cl-search')?.value || '').toLowerCase();
        let filtered = this._allClients || [];
        if (search) {
            filtered = filtered.filter(c =>
                (c.company_name||'').toLowerCase().includes(search) ||
                (c.contact_name||'').toLowerCase().includes(search) ||
                (c.email||'').toLowerCase().includes(search)
            );
        }
        document.getElementById('cl-count').textContent = filtered.length;

        document.getElementById('cl-tbody').innerHTML = filtered.length > 0 ? filtered.map(c => `
            <tr>
                <td>
                    <div class="user-cell">
                        <div class="user-avatar-sm">${(c.company_name || c.contact_name || '?').charAt(0).toUpperCase()}</div>
                        <div class="user-details">
                            <a href="#clientes/profile/${c.id}" class="user-name" style="text-decoration:none;">${c.company_name || c.contact_name}</a>
                            <span class="user-email">${c.contact_name}</span>
                        </div>
                    </div>
                </td>
                <td>${c.email}</td>
                <td style="text-align:center;">${c.invoice_count || 0}</td>
                <td style="font-weight:600">${window.App.formatCurrency(c.total_invoiced || 0)}</td>
                <td style="color:${(c.total_pending||0) > 0 ? 'var(--color-danger-icon)' : 'var(--color-success-icon)'};font-weight:500;">${window.App.formatCurrency(c.total_pending || 0)}</td>
                <td><span class="badge badge-${c.is_active ? 'active' : 'inactive'}">${c.is_active ? 'Activo' : 'Inactivo'}</span></td>
                <td>
                    <div class="row-actions">
                        <a href="#clientes/profile/${c.id}" class="btn-icon" style="width:28px;height:28px;" title="Perfil"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></a>
                        <a href="#clientes/${c.id}" class="btn-icon" style="width:28px;height:28px;" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a>
                        <button class="btn-icon" style="width:28px;height:28px;" onclick="ClientsModule.deleteClient(${c.id})" title="Eliminar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger-icon)" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                    </div>
                </td>
            </tr>
        `).join('') : '<tr><td colspan="7" class="text-center text-muted" style="padding:48px;">No hay clientes registrados</td></tr>';
    },

    async renderProfile(container, id) {
        const statusLabel = (s) => ({draft:'Borrador',sent:'Pendiente',paid:'Pagada',overdue:'Vencida',partial:'Parcial',converted:'Convertida'}[s]||s);
        try {
            const data = await window.App.api(`clients/${id}/profile`);
            const c = data.client;
            const s = data.stats;

            container.innerHTML = `
                <div style="margin-bottom:12px;">
                    <a href="#clientes" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Clientes</a>
                    <span style="color:var(--color-text-muted);font-size:13px;"> / </span>
                    <span style="font-size:13px;">${c.company_name || c.contact_name}</span>
                </div>
                <div class="page-header">
                    <div>
                        <h1 class="page-title">${c.company_name || c.contact_name}</h1>
                        <p class="page-subtitle">${c.email} ${c.phone ? '· '+c.phone : ''}</p>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <a href="#clientes/${id}" class="btn btn-secondary">Editar</a>
                        <button class="btn btn-primary" onclick="window.App.navigate('facturas/nueva')">+ Nueva Factura</button>
                    </div>
                </div>

                <div class="grid-metrics">
                    <div class="metric-card">
                        <div class="metric-header"><div class="metric-icon-box"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div></div>
                        <div class="metric-body"><span class="metric-value">${window.App.formatCurrency(s.total_invoiced)}</span></div>
                        <div class="metric-title">Total Facturado</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header"><div class="metric-icon-box green"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div></div>
                        <div class="metric-body"><span class="metric-value">${window.App.formatCurrency(s.total_paid)}</span></div>
                        <div class="metric-title">Total Pagado</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header"><div class="metric-icon-box ${s.total_pending > 0 ? 'red' : 'green'}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div></div>
                        <div class="metric-body"><span class="metric-value">${window.App.formatCurrency(s.total_pending)}</span></div>
                        <div class="metric-title">Pendiente</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header"><div class="metric-icon-box purple"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg></div></div>
                        <div class="metric-body"><span class="metric-value">${s.invoice_count + s.quote_count}</span></div>
                        <div class="metric-title">Documentos</div>
                    </div>
                </div>

                <div class="table-outer mb-24">
                    <div class="table-toolbar">
                        <span style="font-size:14px;font-weight:600;">Información del Cliente</span>
                    </div>
                    <div style="padding:var(--spacing-xl);">
                        <div class="grid-2">
                            <div style="font-size:13px;display:flex;flex-direction:column;gap:8px;">
                                <p><strong>Contacto:</strong> ${c.contact_name}</p>
                                <p><strong>Email:</strong> ${c.email}</p>
                                <p><strong>Teléfono:</strong> ${c.phone || '—'}</p>
                                <p><strong>WhatsApp:</strong> ${c.whatsapp || '—'}</p>
                            </div>
                            <div style="font-size:13px;display:flex;flex-direction:column;gap:8px;">
                                <p><strong>RNC/Cédula:</strong> ${c.tax_id || '—'}</p>
                                <p><strong>Dirección:</strong> ${c.address_line1 || '—'}</p>
                                <p><strong>Ciudad:</strong> ${c.city || '—'}</p>
                                <p><strong>País:</strong> ${c.country || '—'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="table-outer">
                        <div class="table-toolbar">
                            <span style="font-size:14px;font-weight:600;">Facturas (${data.invoices.length})</span>
                        </div>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead><tr><th>Número</th><th>Monto</th><th>Estado</th></tr></thead>
                                <tbody>
                                    ${data.invoices.map(i => `
                                        <tr style="cursor:pointer" onclick="window.App.navigate('facturas/${i.id}')">
                                            <td><span class="link-id">${i.invoice_number}</span></td>
                                            <td style="font-weight:600">${window.App.formatCurrency(i.total, i.currency)}</td>
                                            <td><span class="badge badge-${i.status}">${statusLabel(i.status)}</span></td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="3" class="text-center text-muted" style="padding:32px;">Sin facturas</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="table-outer">
                        <div class="table-toolbar">
                            <span style="font-size:14px;font-weight:600;">Cotizaciones (${data.quotes.length})</span>
                        </div>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead><tr><th>Número</th><th>Monto</th><th>Estado</th></tr></thead>
                                <tbody>
                                    ${data.quotes.map(q => `
                                        <tr style="cursor:pointer" onclick="window.App.navigate('cotizaciones/${q.id}')">
                                            <td><span class="link-id">${q.quote_number}</span></td>
                                            <td style="font-weight:600">${window.App.formatCurrency(q.total, q.currency)}</td>
                                            <td><span class="badge badge-${q.status}">${statusLabel(q.status)}</span></td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="3" class="text-center text-muted" style="padding:32px;">Sin cotizaciones</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar perfil del cliente</div>`;
        }
    },

    async renderForm(container, id = null) {
        let client = { company_name:'', contact_name:'', email:'', phone:'', whatsapp:'', tax_id:'', address_line1:'', city:'', state:'', postal_code:'', country:'Republica Dominicana', is_active:1 };
        if (id) { try { client = await window.App.api(`clients/${id}`); } catch(e) { container.innerHTML = `<div class="text-red">Error</div>`; return; } }

        container.innerHTML = `
            <div style="margin-bottom:12px;"><a href="#clientes" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Clientes</a></div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">${id ? 'Editar Cliente' : 'Nuevo Cliente'}</h1>
                    <p class="page-subtitle">Completa la información del cliente</p>
                </div>
                <button class="btn btn-secondary" onclick="window.App.navigate('clientes')">Cancelar</button>
            </div>
            <form id="client-form" class="form-card">
                <div style="padding:var(--spacing-xl);">
                    <div class="grid-2">
                        <div class="form-group"><label class="form-label">Nombre de Empresa</label><input type="text" id="c_company_name" class="form-control" value="${client.company_name || ''}"></div>
                        <div class="form-group"><label class="form-label">Nombre de Contacto *</label><input type="text" id="c_contact_name" class="form-control" required value="${client.contact_name || ''}"></div>
                        <div class="form-group"><label class="form-label">Correo Electrónico *</label><input type="email" id="c_email" class="form-control" required value="${client.email || ''}"></div>
                        <div class="form-group"><label class="form-label">RNC / Cédula</label><input type="text" id="c_tax_id" class="form-control" value="${client.tax_id || ''}"></div>
                        <div class="form-group"><label class="form-label">Teléfono</label><input type="text" id="c_phone" class="form-control" value="${client.phone || ''}"></div>
                        <div class="form-group"><label class="form-label">WhatsApp</label><input type="text" id="c_whatsapp" class="form-control" value="${client.whatsapp || ''}" placeholder="+18091234567"></div>
                    </div>
                    <h3 class="mt-24 mb-16" style="font-size:15px;font-weight:600;border-bottom:1px solid var(--color-border);padding-bottom:8px;">Dirección</h3>
                    <div class="grid-2">
                        <div class="form-group" style="grid-column:span 2"><label class="form-label">Dirección</label><input type="text" id="c_address" class="form-control" value="${client.address_line1 || ''}"></div>
                        <div class="form-group"><label class="form-label">Ciudad</label><input type="text" id="c_city" class="form-control" value="${client.city || ''}"></div>
                        <div class="form-group"><label class="form-label">País</label><input type="text" id="c_country" class="form-control" value="${client.country || 'Republica Dominicana'}"></div>
                    </div>
                    <div class="form-group mt-16"><label class="form-label"><input type="checkbox" id="c_active" ${client.is_active ? 'checked' : ''}> Cliente Activo</label></div>
                    <div class="mt-24"><button type="submit" class="btn btn-primary">${id ? 'Actualizar Cliente' : 'Guardar Cliente'}</button></div>
                </div>
            </form>
        `;

        document.getElementById('c_tax_id')?.addEventListener('input', async (e) => {
            const val = e.target.value.replace(/[^0-9]/g, '');
            if (val.length === 9 || val.length === 11) {
                if (e.target.dataset.lastFetch === val) return;
                e.target.dataset.lastFetch = val;
                const isRnc = val.length === 9;
                const endpoint = isRnc ? 'rnc' : 'cedula';
                try {
                    window.App.showToast('Buscando identificación...', 'info');
                    const res = await window.App.api(`lookup/${endpoint}/${val}`);
                    if (res.found && res.data) {
                        const d = res.data;
                        if (isRnc) { if (d.nombre) document.getElementById('c_company_name').value = d.nombre; }
                        else {
                            const fullName = `${d.nombres} ${d.apellido1} ${d.apellido2}`.trim();
                            document.getElementById('c_contact_name').value = fullName;
                            if (!document.getElementById('c_company_name').value) document.getElementById('c_company_name').value = fullName;
                        }
                        window.App.showToast('Información autocompletada', 'success');
                    }
                } catch (err) { window.App.showToast('RNC o Cédula no encontrada', 'error'); }
            }
        });

        document.getElementById('client-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                company_name: document.getElementById('c_company_name').value,
                contact_name: document.getElementById('c_contact_name').value,
                email: document.getElementById('c_email').value,
                phone: document.getElementById('c_phone').value,
                whatsapp: document.getElementById('c_whatsapp').value,
                tax_id: document.getElementById('c_tax_id').value,
                address_line1: document.getElementById('c_address').value,
                city: document.getElementById('c_city').value,
                country: document.getElementById('c_country').value,
                is_active: document.getElementById('c_active').checked ? 1 : 0
            };
            try {
                if (id) { await App.api(`clients/${id}`, { method: 'PUT', body: payload }); App.showToast('Cliente actualizado'); }
                else { await App.api('clients', { method: 'POST', body: payload }); App.showToast('Cliente creado'); }
                window.App.navigate('clientes');
            } catch (err) {}
        });
    },

    async deleteClient(id) {
        this._showConfirm('¿Eliminar este cliente?', async () => {
            try { await window.App.api(`clients/${id}`, { method: 'DELETE' }); window.App.showToast('Cliente eliminado'); window.App.navigate('clientes'); }
            catch(e) {}
        });
    },

    _showConfirm(message, onConfirm) {
        document.getElementById('confirm-modal')?.remove();
        const modal = document.createElement('div');
        modal.id = 'confirm-modal';
        modal.className = 'modal-overlay open';
        modal.innerHTML = `
            <div class="modal" style="max-width:400px;">
                <div class="modal-body" style="text-align:center;padding:32px;">
                    <p style="font-size:15px;margin:0 0 24px 0;line-height:1.5;">${message}</p>
                    <div style="display:flex;gap:12px;justify-content:center;">
                        <button class="btn btn-secondary" id="confirm-no">Cancelar</button>
                        <button class="btn btn-primary" id="confirm-yes">Confirmar</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
        document.getElementById('confirm-no').addEventListener('click', () => modal.remove());
        document.getElementById('confirm-yes').addEventListener('click', () => { modal.remove(); onConfirm(); });
    }
};

window.ClientsModule = ClientsModule;
export default ClientsModule;
