const RecurringModule = {
    async render(container, id) {
        if (id === 'new') {
            this.renderForm(container);
            return;
        }
        if (id) {
            this.renderDetails(container, id);
            return;
        }
        this.renderList(container);
    },

    async renderList(container) {
        try {
            const data = await App.api('recurring');
            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Suscripciones / Recurrentes</h1>
                        <p class="page-subtitle">Generación automática de facturas periódicas</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.location.hash='recurring/new'">+ Nuevo Recurrente</button>
                </div>
                <div class="card">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID / Cliente</th>
                                    <th>Frecuencia</th>
                                    <th>Próxima Emisión</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.data && data.data.length > 0 ? data.data.map(i => `
                                    <tr>
                                        <td>
                                            <a href="#recurring/${i.id}" class="font-semibold text-mono" style="color:inherit;text-decoration:none">#${i.id}</a>
                                            <div style="font-size: 12px; color: var(--text-muted)">${i.company_name || i.contact_name}</div>
                                        </td>
                                        <td style="text-transform: capitalize">${i.frequency}</td>
                                        <td class="font-semibold">${App.formatDate(i.next_issue_date)}</td>
                                        <td class="font-semibold">${App.formatCurrency(i.subtotal + (i.subtotal * i.tax_rate / 100), i.currency)}</td>
                                        <td>
                                            <span class="badge badge-${i.status === 'active' ? 'paid' : (i.status === 'paused' ? 'draft' : 'cancelled')}">${i.status}</span>
                                        </td>
                                        <td>
                                            <a href="#recurring/${i.id}" class="btn btn-ghost btn-sm" title="Ver Detalles">Ver / Editar</a>
                                        </td>
                                    </tr>
                                `).join('') : `<tr><td colspan="6" class="text-center py-8 text-muted">No se encontraron cobros recurrentes</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar facturas recurrentes</div>`;
        }
    },

    async renderDetails(container, id) {
        try {
            const rec = await App.api(`recurring/${id}`);
            
            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Suscripción #${rec.id}</h1>
                        <p class="page-subtitle">Cliente: ${rec.company_name || rec.contact_name}</p>
                    </div>
                    <div>
                        <button class="btn btn-ghost" onclick="window.RecurringModule.toggleStatus(${id}, '${rec.status === 'active' ? 'paused' : 'active'}')">
                            ${rec.status === 'active' ? '⏸️ Pausar' : '▶️ Reactivar'}
                        </button>
                        <button class="btn btn-ghost" style="color:var(--red)" onclick="window.RecurringModule.deleteRecurring(${id})">🗑️ Eliminar</button>
                    </div>
                </div>

                <div class="card mb-24">
                    <div class="card-body">
                        <div class="grid-2">
                            <div>
                                <h3 style="font-size: 14px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Configuración</h3>
                                <p style="margin: 0;"><strong>Frecuencia:</strong> <span style="text-transform: capitalize">${rec.frequency}</span></p>
                                <p style="margin: 4px 0 0 0;"><strong>Envió Auto:</strong> ${rec.auto_send ? 'Sí (' + rec.send_via + ')' : 'No (Solo Generar Borrador)'}</p>
                                <p style="margin: 4px 0 0 0;"><strong>Estado:</strong> <span class="badge badge-${rec.status === 'active' ? 'paid' : 'draft'}">${rec.status}</span></p>
                            </div>
                            <div class="text-right">
                                <h3 style="font-size: 14px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Fechas</h3>
                                <p style="margin: 0;"><strong>Inicio:</strong> ${App.formatDate(rec.start_date)}</p>
                                <p style="margin: 4px 0 0 0;"><strong>Próxima Factura:</strong> ${App.formatDate(rec.next_issue_date)}</p>
                                <p style="margin: 4px 0 0 0;"><strong>Veces Generado:</strong> ${rec.occurrences_count} ${rec.occurrences_limit ? ' / ' + rec.occurrences_limit : ''}</p>
                            </div>
                        </div>

                        <div class="mt-24">
                            <table class="table" style="border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;">
                                <thead style="background: var(--bg-hover);">
                                    <tr>
                                        <th>Descripción</th>
                                        <th class="text-right">Cantidad</th>
                                        <th class="text-right">Precio</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rec.items.map(item => `
                                        <tr>
                                            <td>${item.description}</td>
                                            <td class="text-right">${item.quantity}</td>
                                            <td class="text-right">${App.formatCurrency(item.unit_price, rec.currency)}</td>
                                            <td class="text-right font-semibold">${App.formatCurrency(item.amount, rec.currency)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar detalles de la suscripción</div>`;
        }
    },

    async renderForm(container) {
        let clients = [];
        try {
            const res = await App.api('clients');
            clients = res.data || [];
        } catch(e) {}

        const today = new Date().toISOString().split('T')[0];

        container.innerHTML = `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Nueva Suscripción</h1>
                    <p class="page-subtitle">Configura una factura para que se genere periódicamente</p>
                </div>
                <button class="btn btn-ghost" onclick="window.location.hash='recurring'">Cancelar</button>
            </div>
            
            <form id="recurring-form" class="card">
                <div class="card-body">
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Cliente *</label>
                            <select id="r_client_id" class="form-control" required>
                                <option value="">Seleccione un cliente</option>
                                ${clients.map(c => `<option value="${c.id}">${c.company_name || c.contact_name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Moneda</label>
                            <select id="r_currency" class="form-control">
                                <option value="USD">USD - Dólares</option>
                                <option value="DOP">DOP - Pesos Dominicanos</option>
                                <option value="EUR">EUR - Euros</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Frecuencia *</label>
                            <select id="r_frequency" class="form-control" required>
                                <option value="weekly">Semanal</option>
                                <option value="biweekly">Quincenal</option>
                                <option value="monthly" selected>Mensual</option>
                                <option value="quarterly">Trimestral</option>
                                <option value="semiannual">Semestral</option>
                                <option value="annual">Anual</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha de Inicio * (Próxima emisión)</label>
                            <input type="date" id="r_start_date" class="form-control" required value="${today}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Límite de Ocurrencias</label>
                            <input type="number" id="r_limit" class="form-control" placeholder="Infinito si está vacío">
                            <div class="form-hint">¿Cuántas veces cobrar? Deja vacío para cobrar siempre.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Acción Automática</label>
                            <select id="r_auto_action" class="form-control">
                                <option value="draft">Solo crear borrador de factura</option>
                                <option value="email">Auto-aprobar y Enviar por Email</option>
                                <option value="whatsapp">Auto-aprobar y Enviar por WhatsApp</option>
                                <option value="both">Auto-aprobar, Email y WhatsApp</option>
                            </select>
                        </div>
                    </div>
                    
                    <h3 class="mt-24 mb-16" style="font-size: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Conceptos Fijos</h3>
                    
                    <div id="recurring-items-container"></div>
                    
                    <button type="button" class="btn btn-ghost mt-16" onclick="window.RecurringModule.addItem()">+ Agregar Concepto</button>

                    <div class="grid-2 mt-24">
                        <div>
                            <div class="form-group">
                                <label class="form-label">Notas (Opcional)</label>
                                <textarea id="r_notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label class="form-label">Impuesto (%)</label>
                                <input type="number" id="r_tax" class="form-control" value="18" min="0" max="100" step="0.01" onchange="window.RecurringModule.calculateTotals()">
                            </div>
                            <div style="background: var(--bg-hover); padding: 16px; border-radius: 6px; margin-top: 16px;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="color:var(--text-muted)">Subtotal:</span>
                                    <span id="r_calc_subtotal" class="font-semibold">0.00</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="color:var(--text-muted)">Impuesto:</span>
                                    <span id="r_calc_tax" class="font-semibold">0.00</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-top:1px solid var(--border-color); padding-top:8px; margin-top:8px;">
                                    <span style="font-weight:bold; font-size:18px;">Total por Cobro:</span>
                                    <span id="r_calc_total" style="font-weight:bold; font-size:18px; color:var(--primary)">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-24">
                        <button type="submit" class="btn btn-primary">Guardar Suscripción</button>
                    </div>
                </div>
            </form>
        `;

        window.RecurringModule.items = [];
        window.RecurringModule.addItem();

        document.getElementById('recurring-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const itemsToSave = window.RecurringModule.items.map((item, idx) => {
                return {
                    description: document.getElementById(`ritem_desc_${idx}`).value,
                    quantity: parseFloat(document.getElementById(`ritem_qty_${idx}`).value) || 0,
                    unit_price: parseFloat(document.getElementById(`ritem_price_${idx}`).value) || 0
                };
            }).filter(i => i.description.trim() !== '');

            if (itemsToSave.length === 0) {
                App.showToast('Debes agregar al menos un concepto', 'error');
                return;
            }

            const action = document.getElementById('r_auto_action').value;
            const auto_send = action !== 'draft' ? 1 : 0;
            const send_via = action !== 'draft' ? action : 'email';

            const payload = {
                client_id: document.getElementById('r_client_id').value,
                currency: document.getElementById('r_currency').value,
                frequency: document.getElementById('r_frequency').value,
                start_date: document.getElementById('r_start_date').value,
                next_issue_date: document.getElementById('r_start_date').value,
                occurrences_limit: document.getElementById('r_limit').value || null,
                tax_rate: document.getElementById('r_tax').value,
                auto_send: auto_send,
                send_via: send_via,
                notes: document.getElementById('r_notes').value,
                items: itemsToSave
            };

            try {
                await App.api('recurring', { method: 'POST', body: payload });
                App.showToast('Suscripción recurrente creada');
                window.location.hash = 'recurring';
            } catch (err) {}
        });
    },

    addItem() {
        const idx = this.items ? this.items.length : 0;
        if(!this.items) this.items = [];
        this.items.push({ id: idx });
        this.renderItems();
    },

    removeItem(idx) {
        if(this.items.length <= 1) return;
        this.items = this.items.filter(i => i.id !== idx);
        this.renderItems();
        this.calculateTotals();
    },

    renderItems() {
        const container = document.getElementById('recurring-items-container');
        if(!container) return;
        
        const values = {};
        this.items.forEach(item => {
            const descEl = document.getElementById(`ritem_desc_${item.id}`);
            if(descEl) {
                values[item.id] = {
                    desc: descEl.value,
                    qty: document.getElementById(`ritem_qty_${item.id}`).value,
                    price: document.getElementById(`ritem_price_${item.id}`).value
                };
            }
        });

        container.innerHTML = this.items.map((item) => `
            <div style="display:flex; gap:12px; margin-bottom:12px; align-items:flex-start;">
                <div style="flex:1">
                    <input type="text" id="ritem_desc_${item.id}" class="form-control" placeholder="Descripción del concepto..." required>
                </div>
                <div style="width: 100px;">
                    <input type="number" id="ritem_qty_${item.id}" class="form-control" placeholder="Cant." min="0.01" step="0.01" value="1" required onchange="window.RecurringModule.calculateTotals()" onkeyup="window.RecurringModule.calculateTotals()">
                </div>
                <div style="width: 150px;">
                    <input type="number" id="ritem_price_${item.id}" class="form-control" placeholder="Precio" min="0" step="0.01" required onchange="window.RecurringModule.calculateTotals()" onkeyup="window.RecurringModule.calculateTotals()">
                </div>
                <button type="button" class="btn btn-icon" style="color:var(--red); padding:10px" onclick="window.RecurringModule.removeItem(${item.id})">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>
        `).join('');

        this.items.forEach(item => {
            if(values[item.id]) {
                document.getElementById(`ritem_desc_${item.id}`).value = values[item.id].desc;
                document.getElementById(`ritem_qty_${item.id}`).value = values[item.id].qty;
                document.getElementById(`ritem_price_${item.id}`).value = values[item.id].price;
            }
        });
    },

    calculateTotals() {
        let subtotal = 0;
        if(this.items) {
            this.items.forEach(item => {
                const qty = parseFloat(document.getElementById(`ritem_qty_${item.id}`)?.value) || 0;
                const price = parseFloat(document.getElementById(`ritem_price_${item.id}`)?.value) || 0;
                subtotal += (qty * price);
            });
        }
        
        const taxRate = parseFloat(document.getElementById('r_tax')?.value) || 0;
        const taxAmt = subtotal * (taxRate / 100);
        
        const total = subtotal + taxAmt;

        const subtotalEl = document.getElementById('r_calc_subtotal');
        if(subtotalEl) {
            subtotalEl.textContent = App.formatCurrency(subtotal, '');
            document.getElementById('r_calc_tax').textContent = App.formatCurrency(taxAmt, '');
            document.getElementById('r_calc_total').textContent = App.formatCurrency(total, '');
        }
    },

    async toggleStatus(id, status) {
        try {
            await App.api(`recurring/${id}/toggle`, {
                method: 'POST',
                body: { status: status }
            });
            App.showToast('Estado de la suscripción actualizado');
            App.navigate(`recurring/${id}`);
        } catch(e) {}
    },

    async deleteRecurring(id) {
        if(!confirm('¿Estás seguro de que deseas eliminar esta suscripción? Esto no eliminará las facturas ya generadas, pero detendrá cobros futuros.')) return;
        try {
            await App.api(`recurring/${id}`, { method: 'DELETE' });
            App.showToast('Suscripción eliminada');
            window.location.hash = 'recurring';
        } catch(e) {}
    }
};

window.RecurringModule = RecurringModule;
export default RecurringModule;
