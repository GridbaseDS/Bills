export default {
    async render(container, id) {
        if (id === 'new') {
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
            const data = await window.App.api('clients');
            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Clientes</h1>
                        <p class="page-subtitle">Administra tu base de datos de clientes</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.location.hash='clients/new'">+ Nuevo Cliente</button>
                </div>
                <div class="card">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre / Empresa</th>
                                    <th>Contacto</th>
                                    <th>Correo</th>
                                    <th>Teléfono</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.data.map(c => `
                                    <tr>
                                        <td class="font-semibold"><a href="#clients/${c.id}" style="color:inherit;text-decoration:none">${c.company_name || c.contact_name}</a></td>
                                        <td>${c.contact_name}</td>
                                        <td>${c.email}</td>
                                        <td>${c.phone || '-'}</td>
                                        <td><span class="badge badge-${c.is_active ? 'paid' : 'draft'}">${c.is_active ? 'Activo' : 'Inactivo'}</span></td>
                                        <td>
                                            <a href="#clients/${c.id}" class="btn btn-ghost btn-sm">Editar</a>
                                        </td>
                                    </tr>
                                `).join('') || `<tr><td colspan="6" class="text-center py-8 text-muted">No hay clientes registrados</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar clientes</div>`;
        }
    },

    async renderForm(container, id = null) {
        let client = {
            company_name: '', contact_name: '', email: '', phone: '', whatsapp: '',
            tax_id: '', address_line1: '', city: '', state: '', postal_code: '', country: 'Republica Dominicana',
            is_active: 1
        };

        if (id) {
            try {
                client = await window.App.api(`clients/${id}`);
            } catch (e) {
                container.innerHTML = `<div class="text-red">Error al cargar el cliente</div>`;
                return;
            }
        }

        container.innerHTML = `
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
                        <div class="form-group">
                            <label class="form-label">Nombre de Empresa</label>
                            <input type="text" id="c_company_name" class="form-control" value="${client.company_name || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nombre de Contacto *</label>
                            <input type="text" id="c_contact_name" class="form-control" required value="${client.contact_name || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Correo Electrónico *</label>
                            <input type="email" id="c_email" class="form-control" required value="${client.email || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">RNC / Cédula</label>
                            <input type="text" id="c_tax_id" class="form-control" value="${client.tax_id || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input type="text" id="c_phone" class="form-control" value="${client.phone || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" id="c_whatsapp" class="form-control" value="${client.whatsapp || ''}" placeholder="Ej: +18091234567">
                        </div>
                    </div>
                    
                    <h3 class="mt-24 mb-16" style="font-size: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Dirección</h3>
                    <div class="grid-2">
                        <div class="form-group" style="grid-column: span 2">
                            <label class="form-label">Dirección</label>
                            <input type="text" id="c_address" class="form-control" value="${client.address_line1 || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ciudad</label>
                            <input type="text" id="c_city" class="form-control" value="${client.city || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">País</label>
                            <input type="text" id="c_country" class="form-control" value="${client.country || 'Republica Dominicana'}">
                        </div>
                    </div>

                    <div class="form-group mt-16">
                        <label class="form-label">
                            <input type="checkbox" id="c_active" ${client.is_active ? 'checked' : ''}> Cliente Activo
                        </label>
                    </div>

                    <div class="mt-24">
                        <button type="submit" class="btn btn-primary">${id ? 'Actualizar Cliente' : 'Guardar Cliente'}</button>
                    </div>
                </div>
            </form>
        `;

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
                if (id) {
                    await App.api(`clients/${id}`, { method: 'PUT', body: payload });
                    App.showToast('Cliente actualizado correctamente');
                } else {
                    await App.api('clients', { method: 'POST', body: payload });
                    App.showToast('Cliente creado correctamente');
                }
                window.location.hash = 'clients';
            } catch (err) {
                // error already handled by App.api wrapper toast
            }
        });
    }
};
