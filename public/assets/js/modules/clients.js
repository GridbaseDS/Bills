const ClientsModule = {
    async render(container, id) {
        if (id === 'new') { this.renderForm(container); return; }
        if (id && id.startsWith('profile/')) { this.renderProfile(container, id.replace('profile/', '')); return; }
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
                    <button class="btn btn-primary" onclick="window.location.hash='clients/new'">+ Nuevo Cliente</button>
                </div>

                <div class="card mb-24" style="padding:16px 20px;">
                    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                        <input type="text" id="cl-search" class="form-control" placeholder="🔍 Buscar por nombre, empresa, email..." style="flex:1;min-width:200px;max-width:400px;">
                        <div style="margin-left:auto;font-size:13px;color:var(--text-muted);">
                            <span id="cl-count">${allClients.length}</span> cliente(s)
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre / Empresa</th>
                                    <th>Contacto</th>
                                    <th>Correo</th>
                                    <th>Facturas</th>
                                    <th>Total Facturado</th>
                                    <th>Pendiente</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
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

        document.getElementById('cl-tbody').innerHTML = filtered.map(c => `
            <tr>
                <td class="font-semibold"><a href="#clients/profile/${c.id}" style="color:inherit;text-decoration:none">${c.company_name || c.contact_name}</a></td>
                <td>${c.contact_name}</td>
                <td>${c.email}</td>
                <td class="text-center">${c.invoice_count || 0}</td>
                <td class="font-semibold">${window.App.formatCurrency(c.total_invoiced || 0)}</td>
                <td style="color:${(c.total_pending||0) > 0 ? 'var(--red)' : 'var(--green)'};font-weight:500;">${window.App.formatCurrency(c.total_pending || 0)}</td>
                <td><span class="badge badge-${c.is_active ? 'paid' : 'draft'}">${c.is_active ? 'Activo' : 'Inactivo'}</span></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <a href="#clients/profile/${c.id}" class="btn btn-ghost btn-sm" title="Perfil">👤</a>
                        <a href="#clients/${c.id}" class="btn btn-ghost btn-sm" title="Editar">✏️</a>
                        <button class="btn btn-ghost btn-sm" onclick="ClientsModule.deleteClient(${c.id})" title="Eliminar" style="color:var(--red)">🗑️</button>
                    </div>
                </td>
            </tr>
        `).join('') || `<tr><td colspan="8" class="text-center py-8 text-muted">No hay clientes registrados</td></tr>`;
    },

    async renderProfile(container, id) {
        const statusLabel = (s) => ({draft:'Borrador',sent:'Pendiente de Pago',paid:'Pagada',overdue:'Vencida',partial:'Pago Parcial',converted:'Convertida'}[s]||s);
        try {
            const data = await window.App.api(`clients/${id}/profile`);
            const c = data.client;
            const s = data.stats;

            container.innerHTML = `
                <div style="margin-bottom:12px;">
                    <a href="#clients" style="color:var(--text-muted);text-decoration:none;font-size:13px;">← Clientes</a>
                    <span style="color:var(--text-muted);font-size:13px;"> / </span>
                    <span style="font-size:13px;">${c.company_name || c.contact_name}</span>
                </div>
                <div class="page-header">
                    <div>
                        <h1 class="page-title">${c.company_name || c.contact_name}</h1>
                        <p class="page-subtitle">${c.email} ${c.phone ? '· '+c.phone : ''}</p>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <a href="#clients/${id}" class="btn btn-ghost">✏️ Editar</a>
                        <button class="btn btn-primary" onclick="window.location.hash='invoices/new'">+ Nueva Factura</button>
                    </div>
                </div>

                <!-- Stats -->
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
                    <div class="card" style="padding:20px;text-align:center;">
                        <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">Total Facturado</div>
                        <div style="font-size:22px;font-weight:700;">${window.App.formatCurrency(s.total_invoiced)}</div>
                    </div>
                    <div class="card" style="padding:20px;text-align:center;">
                        <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">Total Pagado</div>
                        <div style="font-size:22px;font-weight:700;color:var(--green);">${window.App.formatCurrency(s.total_paid)}</div>
                    </div>
                    <div class="card" style="padding:20px;text-align:center;">
                        <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">Pendiente</div>
                        <div style="font-size:22px;font-weight:700;color:${s.total_pending > 0 ? 'var(--red)' : 'var(--green)'};">${window.App.formatCurrency(s.total_pending)}</div>
                    </div>
                    <div class="card" style="padding:20px;text-align:center;">
                        <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">Documentos</div>
                        <div style="font-size:22px;font-weight:700;">${s.invoice_count + s.quote_count}</div>
                    </div>
                </div>

                <!-- Client info card -->
                <div class="card mb-24">
                    <div class="card-header"><div class="card-title">Información del Cliente</div></div>
                    <div class="card-body">
                        <div class="grid-2">
                            <div>
                                <p><strong>Contacto:</strong> ${c.contact_name}</p>
                                <p><strong>Email:</strong> ${c.email}</p>
                                <p><strong>Teléfono:</strong> ${c.phone || '—'}</p>
                                <p><strong>WhatsApp:</strong> ${c.whatsapp || '—'}</p>
                            </div>
                            <div>
                                <p><strong>RNC/Cédula:</strong> ${c.tax_id || '—'}</p>
                                <p><strong>Dirección:</strong> ${c.address_line1 || '—'}</p>
                                <p><strong>Ciudad:</strong> ${c.city || '—'}</p>
                                <p><strong>País:</strong> ${c.country || '—'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Invoices -->
                    <div class="card">
                        <div class="card-header"><div class="card-title">Facturas (${data.invoices.length})</div></div>
                        <div class="card-body p-0">
                            <table class="table">
                                <thead><tr><th>Número</th><th>Monto</th><th>Estado</th></tr></thead>
                                <tbody>
                                    ${data.invoices.map(i => `
                                        <tr style="cursor:pointer" onclick="window.location.hash='invoices/${i.id}'">
                                            <td class="font-semibold text-mono">${i.invoice_number}</td>
                                            <td class="font-semibold">${window.App.formatCurrency(i.total, i.currency)}</td>
                                            <td><span class="badge badge-${i.status}">${statusLabel(i.status)}</span></td>
                                        </tr>
                                    `).join('') || `<tr><td colspan="3" class="text-center text-muted py-4">Sin facturas</td></tr>`}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Quotes -->
                    <div class="card">
                        <div class="card-header"><div class="card-title">Cotizaciones (${data.quotes.length})</div></div>
                        <div class="card-body p-0">
                            <table class="table">
                                <thead><tr><th>Número</th><th>Monto</th><th>Estado</th></tr></thead>
                                <tbody>
                                    ${data.quotes.map(q => `
                                        <tr style="cursor:pointer" onclick="window.location.hash='quotes/${q.id}'">
                                            <td class="font-semibold text-mono">${q.quote_number}</td>
                                            <td class="font-semibold">${window.App.formatCurrency(q.total, q.currency)}</td>
                                            <td><span class="badge badge-${q.status}">${statusLabel(q.status)}</span></td>
                                        </tr>
                                    `).join('') || `<tr><td colspan="3" class="text-center text-muted py-4">Sin cotizaciones</td></tr>`}
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
            <div style="margin-bottom:12px;"><a href="#clients" style="color:var(--text-muted);text-decoration:none;font-size:13px;">← Clientes</a></div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">${id ? 'Editar Cliente' : 'Nuevo Cliente'}</h1>
                    <p class="page-subtitle">Completa la información del cliente</p>
                </div>
                <button class="btn btn-ghost" onclick="window.location.hash='clients'">Cancelar</button>
            </div>
            <form id="client-form" class="card">
                <div class="card-body">
                    <div class="grid-2">
                        <div class="form-group"><label class="form-label">Nombre de Empresa</label><input type="text" id="c_company_name" class="form-control" value="${client.company_name || ''}"></div>
                        <div class="form-group"><label class="form-label">Nombre de Contacto *</label><input type="text" id="c_contact_name" class="form-control" required value="${client.contact_name || ''}"></div>
                        <div class="form-group"><label class="form-label">Correo Electrónico *</label><input type="email" id="c_email" class="form-control" required value="${client.email || ''}"></div>
                        <div class="form-group"><label class="form-label">RNC / Cédula</label><input type="text" id="c_tax_id" class="form-control" value="${client.tax_id || ''}"></div>
                        <div class="form-group"><label class="form-label">Teléfono</label><input type="text" id="c_phone" class="form-control" value="${client.phone || ''}"></div>
                        <div class="form-group"><label class="form-label">WhatsApp</label><input type="text" id="c_whatsapp" class="form-control" value="${client.whatsapp || ''}" placeholder="+18091234567"></div>
                    </div>
                    <h3 class="mt-24 mb-16" style="font-size:16px;border-bottom:1px solid var(--border-color);padding-bottom:8px;">Dirección</h3>
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
                        if (isRnc) {
                            if (d.nombre) document.getElementById('c_company_name').value = d.nombre;
                        } else {
                            const fullName = `${d.nombres} ${d.apellido1} ${d.apellido2}`.trim();
                            document.getElementById('c_contact_name').value = fullName;
                            if (!document.getElementById('c_company_name').value) {
                                document.getElementById('c_company_name').value = fullName;
                            }
                        }
                        window.App.showToast('Información autocompletada', 'success');
                    }
                } catch (err) {
                    window.App.showToast('RNC o Cédula no encontrada', 'error');
                }
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
                window.location.hash = 'clients';
            } catch (err) {}
        });
    },

    async deleteClient(id) {
        this._showConfirm('⚠️ ¿Eliminar este cliente? Esta acción no se puede deshacer.', async () => {
            try { await window.App.api(`clients/${id}`, { method: 'DELETE' }); window.App.showToast('Cliente eliminado'); window.location.hash = 'clients'; }
            catch(e) {}
        });
    },

    _showConfirm(message, onConfirm) {
        document.getElementById('confirm-modal')?.remove();
        const modal = document.createElement('div');
        modal.id = 'confirm-modal';
        modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;';
        modal.innerHTML = `
            <div style="background:var(--bg-card);border-radius:12px;padding:32px;width:400px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,0.3);text-align:center;">
                <p style="font-size:16px;margin:0 0 24px 0;line-height:1.5;">${message}</p>
                <div style="display:flex;gap:12px;justify-content:center;">
                    <button class="btn btn-ghost" id="confirm-no">Cancelar</button>
                    <button class="btn btn-primary" id="confirm-yes">Confirmar</button>
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
