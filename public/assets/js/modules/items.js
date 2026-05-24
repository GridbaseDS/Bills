const ItemsModule = {
    async render(container, id) {
        if (id === 'nuevo') { this.renderForm(container); return; }
        if (id) { this.renderForm(container, id); return; }
        this.renderList(container);
    },

    async renderList(container) {
        try {
            const items = await window.App.api('items');

            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Artículos y Servicios</h1>
                        <p class="page-subtitle">Define tus conceptos preestablecidos y precios</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.App.navigate('articulos/nuevo')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Nuevo Artículo
                    </button>
                </div>

                <div class="table-outer">
                    <div class="table-toolbar">
                        <div class="search-wrapper" style="max-width: 300px; display: flex; width: 100%;">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="items-search" class="form-control" placeholder="Buscar artículos..." style="width: 100%; padding-left: 36px;">
                        </div>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Precio Base</th>
                                    <th>Estado</th>
                                    <th style="width: 100px; text-align:right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="items-table-body">
                                ${this.generateTableRows(items)}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            // Setup search filtering
            const searchInput = document.getElementById('items-search');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const query = e.target.value.toLowerCase();
                    const filtered = items.filter(i => 
                        (i.name && i.name.toLowerCase().includes(query)) ||
                        (i.description && i.description.toLowerCase().includes(query))
                    );
                    document.getElementById('items-table-body').innerHTML = this.generateTableRows(filtered);
                });
            }

        } catch (error) {
            container.innerHTML = `<div class="text-red">Error al cargar artículos.</div>`;
        }
    },

    generateTableRows(items) {
        if (!items || items.length === 0) {
            return `<tr><td colspan="5" style="text-align:center;padding:32px;color:var(--color-text-muted);">No hay artículos registrados</td></tr>`;
        }
        
        return items.map(item => `
            <tr>
                <td style="font-weight:500;color:var(--color-text-primary);">${item.name}</td>
                <td><span class="truncate" style="max-width: 250px; display: inline-block;" title="${item.description || ''}">${item.description || '<span style="color:var(--color-text-muted)">Sin descripción</span>'}</span></td>
                <td style="font-weight:600;">${window.App.formatCurrency(item.price, 'DOP')}</td>
                <td><span class="badge badge-${item.is_active ? 'active' : 'inactive'}">${item.is_active ? 'Activo' : 'Inactivo'}</span></td>
                <td style="text-align:right;">
                    <div class="row-actions" style="justify-content:flex-end;">
                        <a href="#articulos/${item.id}" class="btn-icon" style="width:28px;height:28px;" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a>
                        <button class="btn-icon" style="width:28px;height:28px;" onclick="ItemsModule.deleteItem(${item.id})" title="Eliminar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger-icon)" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    async renderForm(container, id) {
        let item = { is_active: true, price: 0 };
        if (id) {
            try { 
                item = await window.App.api(`items/${id}`); 
            } catch(e) { 
                container.innerHTML = `<div class="text-red">Error al cargar el artículo</div>`; 
                return; 
            }
        }

        container.innerHTML = `
            <div style="margin-bottom:12px;">
                <a href="#articulos" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Artículos</a>
            </div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">${id ? 'Editar Artículo' : 'Nuevo Artículo'}</h1>
                    <p class="page-subtitle">${id ? 'Modifica los datos del artículo' : 'Registra un nuevo concepto para facturar'}</p>
                </div>
                <button class="btn btn-secondary" onclick="window.App.navigate('articulos')">Cancelar</button>
            </div>

            <form id="item-form" class="form-card">
                <div style="padding:var(--spacing-xl);">
                    <div class="grid-2">
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Nombre del Artículo / Servicio *</label>
                            <input type="text" id="i_name" class="form-control" value="${item.name || ''}" placeholder="Ej: Mensualidad Academic+" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Precio por Defecto</label>
                            <input type="number" id="i_price" class="form-control" value="${item.price}" min="0" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Estado</label>
                            <select id="i_status" class="form-select">
                                <option value="1" ${item.is_active ? 'selected' : ''}>Activo</option>
                                <option value="0" ${!item.is_active ? 'selected' : ''}>Inactivo</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Descripción Detallada (Opcional)</label>
                            <textarea id="i_description" class="form-control" rows="3" placeholder="Descripción que aparecerá en la factura...">${item.description || ''}</textarea>
                        </div>
                    </div>

                    <div class="mt-24">
                        <button type="submit" class="btn btn-primary">${id ? 'Guardar Cambios' : 'Crear Artículo'}</button>
                    </div>
                </div>
            </form>
        `;

        document.getElementById('item-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                name: document.getElementById('i_name').value,
                price: parseFloat(document.getElementById('i_price').value) || 0,
                is_active: document.getElementById('i_status').value === '1',
                description: document.getElementById('i_description').value
            };

            try {
                if (id) {
                    await App.api(`items/${id}`, { method: 'PUT', body: payload });
                    App.showToast('Artículo actualizado');
                } else {
                    await App.api('items', { method: 'POST', body: payload });
                    App.showToast('Artículo creado');
                }
                window.App.navigate('articulos');
            } catch (err) {}
        });
    },

    async deleteItem(id) {
        if (confirm('¿Estás seguro de eliminar este artículo?')) {
            try {
                await window.App.api(`items/${id}`, { method: 'DELETE' });
                window.App.showToast('Artículo eliminado');
                window.App.navigate('articulos');
            } catch (e) {
                // Ignore error if handled by API
            }
        }
    }
};

export default ItemsModule;
