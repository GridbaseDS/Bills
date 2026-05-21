const RecurringModule = {
    items: [],
    freqLabels: { weekly:'Semanal', biweekly:'Quincenal', monthly:'Mensual', quarterly:'Trimestral', semiannual:'Semestral', annual:'Anual' },
    statusLabels: { active:'Activa', paused:'Pausada', completed:'Completada', cancelled:'Cancelada' },
    statusBadge: { active:'active', paused:'onboarding', completed:'draft', cancelled:'cancelled' },

    async render(container, id) {
        if (id === 'new') return this.renderForm(container);
        if (id && id.startsWith('edit/')) return this.renderForm(container, id.replace('edit/',''));
        if (id) return this.renderDetails(container, id);
        this.renderList(container);
    },

    async renderList(container) {
        try {
            const data = await App.api('recurring');
            const items = data.data || [];

            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Suscripciones Recurrentes</h1>
                        <p class="page-subtitle">Facturación automática periódica</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.App.navigate('recurring/new')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Nueva Suscripción
                    </button>
                </div>
                <div class="table-outer">
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead><tr><th>Cliente</th><th>Frecuencia</th><th>Próxima Emisión</th><th>Monto</th><th>Estado</th><th></th></tr></thead>
                            <tbody>
                                ${items.length > 0 ? items.map(i => `
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar-sm">${(i.company_name||i.contact_name||'?').charAt(0).toUpperCase()}</div>
                                                <div class="user-details">
                                                    <a href="#recurring/${i.id}" class="user-name" style="text-decoration:none;">${i.company_name||i.contact_name||'—'}</a>
                                                    <span class="user-email">#${i.id}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>${this.freqLabels[i.frequency]||i.frequency}</td>
                                        <td style="font-weight:600">${App.formatDate(i.next_issue_date)}</td>
                                        <td style="font-weight:600">${App.formatCurrency(i.calculated_total||0, i.currency)}</td>
                                        <td><span class="badge badge-${this.statusBadge[i.status]||'draft'}">${this.statusLabels[i.status]||i.status}</span></td>
                                        <td>
                                            <div class="row-actions">
                                                <a href="#recurring/${i.id}" class="btn-icon" style="width:28px;height:28px;" title="Ver"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('') : '<tr><td colspan="6" class="text-center text-muted" style="padding:48px;">No hay suscripciones recurrentes</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>`;
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar suscripciones</div>`;
        }
    },

    async renderDetails(container, id) {
        try {
            const r = await App.api(`recurring/${id}`);
            const invoiceRows = (r.invoices||[]).map(inv => `
                <tr style="cursor:pointer" onclick="window.App.navigate('invoices/${inv.id}')">
                    <td><span class="link-id">${inv.invoice_number}</span></td>
                    <td>${App.formatDate(inv.issue_date)}</td>
                    <td style="font-weight:600">${App.formatCurrency(inv.total, inv.currency)}</td>
                    <td><span class="badge badge-${inv.status==='paid'?'paid':(inv.status==='overdue'?'overdue':'sent')}">${inv.status}</span></td>
                </tr>`).join('') || '<tr><td colspan="4" class="text-center text-muted" style="padding:32px;">Sin facturas generadas</td></tr>';

            container.innerHTML = `
                <div style="margin-bottom:12px;">
                    <a href="#recurring" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Suscripciones</a>
                    <span style="color:var(--color-text-muted);font-size:13px;"> / </span>
                    <span style="font-size:13px;">#${r.id}</span>
                </div>
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Suscripción #${r.id}</h1>
                        <p class="page-subtitle">${r.company_name||r.contact_name||''}</p>
                    </div>
                    <div style="display:flex;gap:8px">
                        <button class="btn btn-secondary" onclick="window.App.navigate('recurring/edit/${id}')">Editar</button>
                        <button class="btn btn-secondary" onclick="window.RecurringModule.toggleStatus(${id},'${r.status==='active'?'paused':'active'}')">
                            ${r.status==='active'?'⏸️ Pausar':'▶️ Reactivar'}
                        </button>
                        <button class="btn-icon" style="width:36px;height:36px;color:var(--color-danger-icon)" onclick="window.RecurringModule.deleteRecurring(${id})"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                    </div>
                </div>

                <div class="table-outer mb-24">
                    <div style="padding:var(--spacing-xl);">
                        <div class="grid-2">
                            <div>
                                <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px">Configuración</div>
                                <p style="margin:0;font-size:13px;"><strong>Frecuencia:</strong> ${this.freqLabels[r.frequency]||r.frequency}</p>
                                <p style="margin:4px 0 0;font-size:13px;"><strong>Auto-envío:</strong> ${r.auto_send?'Sí ('+r.send_via+')':'No (Borrador)'}</p>
                                <p style="margin:4px 0 0;font-size:13px;"><strong>Estado:</strong> <span class="badge badge-${this.statusBadge[r.status]||'draft'}">${this.statusLabels[r.status]||r.status}</span></p>
                                <p style="margin:4px 0 0;font-size:13px;"><strong>Moneda:</strong> ${r.currency||'USD'}</p>
                            </div>
                            <div class="text-right">
                                <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px">Fechas</div>
                                <p style="margin:0;font-size:13px;"><strong>Inicio:</strong> ${App.formatDate(r.start_date)}</p>
                                <p style="margin:4px 0 0;font-size:13px;"><strong>Próxima:</strong> ${App.formatDate(r.next_issue_date)}</p>
                                <p style="margin:4px 0 0;font-size:13px;"><strong>Generadas:</strong> ${r.occurrences_count}${r.occurrences_limit?' / '+r.occurrences_limit:' (ilimitado)'}</p>
                            </div>
                        </div>
                        <div class="mt-24">
                            <table class="data-table" style="border:1px solid var(--color-border);border-radius:var(--radius-lg);overflow:hidden;">
                                <thead><tr><th>Descripción</th><th class="text-right">Cant.</th><th class="text-right">Precio</th><th class="text-right">Total</th></tr></thead>
                                <tbody>${(r.items||[]).map(it=>`<tr><td style="color:var(--color-text-primary);font-weight:500">${it.description}</td><td class="text-right">${it.quantity}</td><td class="text-right">${App.formatCurrency(it.unit_price,r.currency)}</td><td class="text-right font-semibold">${App.formatCurrency(it.amount,r.currency)}</td></tr>`).join('')}</tbody>
                            </table>
                        </div>
                        <div style="text-align:right;margin-top:16px">
                            <div style="display:inline-block;background:var(--bg-hover);padding:16px 24px;border-radius:var(--radius-lg);min-width:250px">
                                <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px"><span style="color:var(--color-text-muted)">Subtotal:</span><span class="font-semibold">${App.formatCurrency(r.calculated_subtotal||0,r.currency)}</span></div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px"><span style="color:var(--color-text-muted)">ITBIS (${r.tax_rate||0}%):</span><span class="font-semibold">${App.formatCurrency(r.calculated_tax||0,r.currency)}</span></div>
                                <div style="display:flex;justify-content:space-between;border-top:2px solid var(--color-border);padding-top:8px;margin-top:8px"><span style="font-weight:700;font-size:16px">Total:</span><span style="font-weight:700;font-size:16px;color:var(--color-primary)">${App.formatCurrency(r.calculated_total||0,r.currency)}</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-outer">
                    <div class="table-toolbar"><span style="font-size:14px;font-weight:600;">Facturas Generadas</span></div>
                    <div class="table-wrapper">
                        <table class="data-table"><thead><tr><th>Número</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead><tbody>${invoiceRows}</tbody></table>
                    </div>
                </div>`;
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar detalles</div>`;
        }
    },

    async renderForm(container, editId) {
        let clients = [], existing = null;
        try { const res = await App.api('clients'); clients = res.data||[]; } catch(e){}
        if (editId) { try { existing = await App.api(`recurring/${editId}`); } catch(e){ container.innerHTML='<div class="text-red">No encontrado</div>'; return; } }
        const today = new Date().toISOString().split('T')[0];
        const isEdit = !!existing;
        const autoVal = existing ? (existing.auto_send ? existing.send_via : 'draft') : 'draft';

        container.innerHTML = `
            <div style="margin-bottom:12px;"><a href="#recurring" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Suscripciones</a></div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">${isEdit?'Editar Suscripción #'+editId:'Nueva Suscripción'}</h1>
                    <p class="page-subtitle">${isEdit?'Modifica la configuración':'Configura una factura periódica'}</p>
                </div>
                <button class="btn btn-secondary" onclick="window.App.navigate('recurring${isEdit?'/'+editId:''}')">Cancelar</button>
            </div>
            <form id="recurring-form" class="table-outer"><div style="padding:var(--spacing-xl);">
                <div class="grid-2">
                    <div class="form-group"><label class="form-label">Cliente *</label>
                        <select id="r_client_id" class="form-control" required>
                            <option value="">Seleccione un cliente</option>
                            ${clients.map(c=>`<option value="${c.id}" ${existing&&existing.client_id==c.id?'selected':''}>${c.company_name||c.contact_name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">Moneda</label>
                        <select id="r_currency" class="form-control">
                            ${['USD','DOP','EUR'].map(c=>`<option value="${c}" ${existing&&existing.currency===c?'selected':''}>${c}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">Frecuencia *</label>
                        <select id="r_frequency" class="form-control" required>
                            ${Object.entries(this.freqLabels).map(([k,v])=>`<option value="${k}" ${existing&&existing.frequency===k?'selected':''}>${v}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">Fecha de Inicio *</label><input type="date" id="r_start_date" class="form-control" required value="${existing?existing.start_date?.split('T')[0]:today}"></div>
                    <div class="form-group"><label class="form-label">Límite de Ocurrencias</label><input type="number" id="r_limit" class="form-control" placeholder="Ilimitado" value="${existing&&existing.occurrences_limit?existing.occurrences_limit:''}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Deja vacío para indefinido</div></div>
                    <div class="form-group"><label class="form-label">Acción Automática</label>
                        <select id="r_auto_action" class="form-control">
                            <option value="draft" ${autoVal==='draft'?'selected':''}>Solo crear borrador</option>
                            <option value="email" ${autoVal==='email'?'selected':''}>Enviar por Email</option>
                            <option value="whatsapp" ${autoVal==='whatsapp'?'selected':''}>Enviar por WhatsApp</option>
                            <option value="both" ${autoVal==='both'?'selected':''}>Email + WhatsApp</option>
                        </select>
                    </div>
                </div>
                <h3 class="mt-24 mb-16" style="font-size:15px;font-weight:600;border-bottom:1px solid var(--color-border);padding-bottom:8px">Conceptos</h3>
                <div id="recurring-items-container"></div>
                <button type="button" class="btn btn-ghost mt-16" onclick="window.RecurringModule.addItem()">+ Agregar Concepto</button>
                <div class="grid-2 mt-24">
                    <div class="form-group"><label class="form-label">Notas</label><textarea id="r_notes" class="form-control" rows="3">${existing&&existing.notes?existing.notes:''}</textarea></div>
                    <div>
                        <div class="form-group"><label class="form-label">ITBIS (%)</label><input type="number" id="r_tax" class="form-control" value="${existing?existing.tax_rate:18}" min="0" max="100" step="0.01" oninput="window.RecurringModule.calculateTotals()"></div>
                        <div style="background:var(--bg-hover);padding:16px;border-radius:var(--radius-lg);margin-top:16px">
                            <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px"><span style="color:var(--color-text-muted)">Subtotal:</span><span id="r_calc_subtotal" class="font-semibold">0.00</span></div>
                            <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px"><span style="color:var(--color-text-muted)">Impuesto:</span><span id="r_calc_tax" class="font-semibold">0.00</span></div>
                            <div style="display:flex;justify-content:space-between;border-top:1px solid var(--color-border);padding-top:8px;margin-top:8px"><span style="font-weight:700;font-size:18px">Total:</span><span id="r_calc_total" style="font-weight:700;font-size:18px;color:var(--color-primary)">0.00</span></div>
                        </div>
                    </div>
                </div>
                <div class="mt-24"><button type="submit" class="btn btn-primary">${isEdit?'Actualizar':'Guardar'} Suscripción</button></div>
            </div></form>`;

        this.items = [];
        if (existing && existing.items && existing.items.length > 0) {
            existing.items.forEach((it, idx) => { this.items.push({ id: idx, desc: it.description, qty: it.quantity, price: it.unit_price }); });
        } else {
            this.items.push({ id: 0, desc: '', qty: 1, price: '' });
        }
        this.renderItems();
        this.calculateTotals();

        document.getElementById('recurring-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const itemsToSave = this.items.map(item => ({
                description: document.getElementById(`ritem_desc_${item.id}`)?.value || '',
                quantity: parseFloat(document.getElementById(`ritem_qty_${item.id}`)?.value) || 0,
                unit_price: parseFloat(document.getElementById(`ritem_price_${item.id}`)?.value) || 0
            })).filter(i => i.description.trim() !== '');
            if (itemsToSave.length === 0) { App.showToast('Agrega al menos un concepto','error'); return; }

            const action = document.getElementById('r_auto_action').value;
            const payload = {
                client_id: document.getElementById('r_client_id').value,
                currency: document.getElementById('r_currency').value,
                frequency: document.getElementById('r_frequency').value,
                start_date: document.getElementById('r_start_date').value,
                next_issue_date: document.getElementById('r_start_date').value,
                occurrences_limit: document.getElementById('r_limit').value || null,
                tax_rate: document.getElementById('r_tax').value || 0,
                auto_send: action !== 'draft' ? 1 : 0,
                send_via: action !== 'draft' ? action : 'email',
                notes: document.getElementById('r_notes').value,
                items: itemsToSave
            };
            try {
                if (isEdit) { await App.api(`recurring/${editId}`, { method: 'PUT', body: payload }); App.showToast('Suscripción actualizada'); window.App.navigate(`recurring/${editId}`); }
                else { await App.api('recurring', { method: 'POST', body: payload }); App.showToast('Suscripción creada'); window.App.navigate('recurring'); }
            } catch (err) {}
        });
    },

    addItem() {
        const maxId = this.items.length > 0 ? Math.max(...this.items.map(i=>i.id)) + 1 : 0;
        this.items.push({ id: maxId, desc:'', qty:1, price:'' });
        this.renderItems();
    },

    removeItem(idx) {
        if (this.items.length <= 1) return;
        this.saveItemValues();
        this.items = this.items.filter(i => i.id !== idx);
        this.renderItems();
        this.calculateTotals();
    },

    saveItemValues() {
        this.items.forEach(item => {
            const d = document.getElementById(`ritem_desc_${item.id}`);
            if (d) { item.desc = d.value; item.qty = document.getElementById(`ritem_qty_${item.id}`).value; item.price = document.getElementById(`ritem_price_${item.id}`).value; }
        });
    },

    renderItems() {
        const container = document.getElementById('recurring-items-container');
        if (!container) return;
        this.items.forEach(item => { const d = document.getElementById(`ritem_desc_${item.id}`); if (d) { item.desc = d.value; item.qty = document.getElementById(`ritem_qty_${item.id}`).value; item.price = document.getElementById(`ritem_price_${item.id}`).value; } });
        container.innerHTML = this.items.map(item => `
            <div style="display:flex;gap:12px;margin-bottom:12px;align-items:flex-start">
                <div style="flex:1"><input type="text" id="ritem_desc_${item.id}" class="form-control" placeholder="Descripción..." required></div>
                <div style="width:100px"><input type="number" id="ritem_qty_${item.id}" class="form-control" placeholder="Cant." min="0.01" step="0.01" value="1" required oninput="window.RecurringModule.calculateTotals()"></div>
                <div style="width:150px"><input type="number" id="ritem_price_${item.id}" class="form-control" placeholder="Precio" min="0" step="0.01" required oninput="window.RecurringModule.calculateTotals()"></div>
                <button type="button" class="btn-icon" style="color:var(--color-danger-icon);width:38px;height:38px;" onclick="window.RecurringModule.removeItem(${item.id})"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
            </div>`).join('');
        this.items.forEach(item => {
            if (item.desc !== undefined) document.getElementById(`ritem_desc_${item.id}`).value = item.desc;
            if (item.qty !== undefined) document.getElementById(`ritem_qty_${item.id}`).value = item.qty;
            if (item.price !== undefined && item.price !== '') document.getElementById(`ritem_price_${item.id}`).value = item.price;
        });
    },

    calculateTotals() {
        let subtotal = 0;
        if (this.items) { this.items.forEach(item => { subtotal += (parseFloat(document.getElementById(`ritem_qty_${item.id}`)?.value)||0) * (parseFloat(document.getElementById(`ritem_price_${item.id}`)?.value)||0); }); }
        const taxRate = parseFloat(document.getElementById('r_tax')?.value) || 0;
        const tax = subtotal * (taxRate / 100);
        const el = document.getElementById('r_calc_subtotal');
        if (el) { el.textContent = subtotal.toFixed(2); document.getElementById('r_calc_tax').textContent = tax.toFixed(2); document.getElementById('r_calc_total').textContent = (subtotal + tax).toFixed(2); }
    },

    async toggleStatus(id, status) {
        try { await App.api(`recurring/${id}/toggle`, { method: 'POST', body: { status } }); App.showToast('Estado actualizado'); App.navigate(`recurring/${id}`); } catch(e) {}
    },

    async deleteRecurring(id) {
        if (!confirm('¿Eliminar esta suscripción?')) return;
        try { await App.api(`recurring/${id}`, { method: 'DELETE' }); App.showToast('Suscripción eliminada'); window.App.navigate('recurring'); } catch(e) {}
    }
};

window.RecurringModule = RecurringModule;
export default RecurringModule;
