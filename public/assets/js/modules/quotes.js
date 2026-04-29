const QuotesModule = {
    statusLabel: (s) => ({draft:'Borrador',sent:'Enviada',converted:'Convertida',expired:'Expirada',rejected:'Rechazada'}[s]||s),

    async render(container, id) {
        if (id === 'new') { this.renderForm(container); return; }
        if (id && id.startsWith('edit/')) { this.renderForm(container, id.replace('edit/', '')); return; }
        if (id) { this.renderDetails(container, id); return; }
        this.renderList(container);
    },

    async renderList(container) {
        try {
            const data = await window.App.api('quotes');
            const allQuotes = data.data || [];

            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Cotizaciones</h1>
                        <p class="page-subtitle">Administra presupuestos de clientes</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.location.hash='quotes/new'">+ Nueva Cotización</button>
                </div>

                <div class="card mb-24" style="padding:16px 20px;">
                    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                        <input type="text" id="qt-search" class="form-control" placeholder="🔍 Buscar por número, cliente..." style="flex:1;min-width:200px;max-width:350px;">
                        <select id="qt-filter-status" class="form-control" style="width:180px;">
                            <option value="">Todos los estados</option>
                            <option value="draft">Borrador</option>
                            <option value="sent">Enviada</option>
                            <option value="converted">Convertida</option>
                            <option value="expired">Expirada</option>
                        </select>
                        <div style="margin-left:auto;font-size:13px;color:var(--text-muted);">
                            <span id="qt-count">${allQuotes.length}</span> cotización(es)
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Número</th><th>Cliente</th><th>Emisión</th><th>Válida Hasta</th><th>Monto</th><th>Estado</th><th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="qt-tbody"></tbody>
                        </table>
                    </div>
                </div>
            `;

            this._allQuotes = allQuotes;
            this.filterQuotes();
            document.getElementById('qt-search').addEventListener('input', () => this.filterQuotes());
            document.getElementById('qt-filter-status').addEventListener('change', () => this.filterQuotes());

        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar cotizaciones</div>`;
        }
    },

    filterQuotes() {
        const search = (document.getElementById('qt-search')?.value || '').toLowerCase();
        const status = document.getElementById('qt-filter-status')?.value || '';
        let filtered = this._allQuotes || [];
        if (search) filtered = filtered.filter(q => (q.quote_number||'').toLowerCase().includes(search) || (q.company_name||'').toLowerCase().includes(search));
        if (status) filtered = filtered.filter(q => q.status === status);
        document.getElementById('qt-count').textContent = filtered.length;

        document.getElementById('qt-tbody').innerHTML = filtered.map(q => `
            <tr>
                <td class="font-semibold text-mono"><a href="#quotes/${q.id}" style="color:inherit;text-decoration:none">${q.quote_number}</a></td>
                <td>${q.company_name || q.contact_name}</td>
                <td>${window.App.formatDate(q.issue_date)}</td>
                <td>${window.App.formatDate(q.expiry_date)}</td>
                <td class="font-semibold">${window.App.formatCurrency(q.total, q.currency)}</td>
                <td><span class="badge badge-${q.status}">${this.statusLabel(q.status)}</span></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <a href="#quotes/${q.id}" class="btn btn-ghost btn-sm" title="Ver">👁️</a>
                        <a href="/api/quotes/${q.id}/pdf?download=1" target="_blank" class="btn btn-ghost btn-sm" title="PDF">📄</a>
                        <button class="btn btn-ghost btn-sm" onclick="QuotesModule.duplicateQuote(${q.id})" title="Duplicar">📋</button>
                        <button class="btn btn-ghost btn-sm" onclick="QuotesModule.deleteQuote(${q.id})" title="Eliminar" style="color:var(--red)">🗑️</button>
                    </div>
                </td>
            </tr>
        `).join('') || `<tr><td colspan="7" class="text-center py-8 text-muted">No se encontraron cotizaciones</td></tr>`;
    },

    async renderDetails(container, id) {
        try {
            const quote = await window.App.api(`quotes/${id}`);
            container.innerHTML = `
                <div style="margin-bottom:12px;">
                    <a href="#quotes" style="color:var(--text-muted);text-decoration:none;font-size:13px;">← Cotizaciones</a>
                    <span style="color:var(--text-muted);font-size:13px;"> / </span>
                    <span style="font-size:13px;">${quote.quote_number}</span>
                </div>
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Cotización ${quote.quote_number}</h1>
                        <p class="page-subtitle">Emitida el ${window.App.formatDate(quote.issue_date)}</p>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        ${quote.status !== 'converted' ? `<button class="btn btn-primary" onclick="QuotesModule.convertToInvoice(${id})">⚡ Convertir a Factura</button>` : '<span class="badge badge-converted" style="padding:8px 16px;">Ya Facturada</span>'}
                        <a href="/api/quotes/${id}/pdf" target="_blank" class="btn btn-ghost">📄 Ver PDF</a>
                        <button class="btn btn-ghost" onclick="QuotesModule.sendEmail(${id})">✉️ Enviar</button>
                        <button class="btn btn-ghost" onclick="QuotesModule.duplicateQuote(${id})">📋 Duplicar</button>
                        <a href="#quotes/edit/${id}" class="btn btn-ghost">✏️ Editar</a>
                    </div>
                </div>

                <div class="card mb-24">
                    <div class="card-body">
                        <div class="grid-2">
                            <div>
                                <h3 style="font-size:14px;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px;">Cotizado a</h3>
                                <p class="font-semibold" style="font-size:16px;margin:0;">${quote.company_name || quote.contact_name}</p>
                                <p style="margin:4px 0 0 0;">${quote.email || ''}</p>
                            </div>
                            <div class="text-right">
                                <h3 style="font-size:14px;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px;">Detalles</h3>
                                <p style="margin:0;"><strong>Válida Hasta:</strong> ${window.App.formatDate(quote.expiry_date)}</p>
                                <p style="margin:4px 0 0 0;"><strong>Estado:</strong> <span class="badge badge-${quote.status}">${this.statusLabel(quote.status)}</span></p>
                            </div>
                        </div>

                        <div class="mt-24">
                            <table class="table" style="border:1px solid var(--border-color);border-radius:6px;overflow:hidden;">
                                <thead style="background:var(--bg-hover);"><tr><th>Descripción</th><th class="text-right">Cant.</th><th class="text-right">Precio</th><th class="text-right">Total</th></tr></thead>
                                <tbody>
                                    ${quote.items.map(item => `
                                        <tr>
                                            <td>${item.description}</td>
                                            <td class="text-right">${item.quantity}</td>
                                            <td class="text-right">${window.App.formatCurrency(item.unit_price, quote.currency)}</td>
                                            <td class="text-right font-semibold">${window.App.formatCurrency(item.amount, quote.currency)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>

                        <div class="grid-2 mt-24">
                            <div>${quote.notes ? `<h3 style="font-size:14px;color:var(--text-muted);margin-bottom:8px;">Notas</h3><p style="white-space:pre-wrap;">${quote.notes}</p>` : ''}</div>
                            <div>
                                <table style="width:100%;border-collapse:collapse;">
                                    <tr><td style="padding:8px 0;text-align:right;color:var(--text-muted);">Subtotal</td><td style="padding:8px 0;text-align:right;font-weight:500;">${window.App.formatCurrency(quote.subtotal, quote.currency)}</td></tr>
                                    ${quote.discount_amount > 0 ? `<tr><td style="padding:8px 0;text-align:right;color:var(--text-muted);">Descuento</td><td style="padding:8px 0;text-align:right;font-weight:500;">-${window.App.formatCurrency(quote.discount_amount, quote.currency)}</td></tr>` : ''}
                                    ${quote.tax_amount > 0 ? `<tr><td style="padding:8px 0;text-align:right;color:var(--text-muted);">ITBIS (${quote.tax_rate}%)</td><td style="padding:8px 0;text-align:right;font-weight:500;">${window.App.formatCurrency(quote.tax_amount, quote.currency)}</td></tr>` : ''}
                                    <tr style="border-top:2px solid var(--border-color);"><td style="padding:12px 0;text-align:right;font-size:18px;font-weight:600;">Total</td><td style="padding:12px 0;text-align:right;font-size:18px;font-weight:700;color:var(--primary);">${window.App.formatCurrency(quote.total, quote.currency)}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar cotización</div>`;
        }
    },

    async renderForm(container, editId = null) {
        let clients = [];
        try { const res = await window.App.api('clients'); clients = res.data || []; } catch(e) {}

        let quote = null;
        if (editId) {
            try { quote = await window.App.api(`quotes/${editId}`); } catch(e) { container.innerHTML = `<div class="text-red">Error</div>`; return; }
        }

        const today = new Date().toISOString().split('T')[0];
        const nextMonth = new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0];

        container.innerHTML = `
            <div style="margin-bottom:12px;"><a href="#quotes" style="color:var(--text-muted);text-decoration:none;font-size:13px;">← Cotizaciones</a></div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">${editId ? 'Editar Cotización' : 'Nueva Cotización'}</h1>
                    <p class="page-subtitle">${editId ? `Editando ${quote?.quote_number}` : 'Crea un presupuesto para un cliente'}</p>
                </div>
                <button class="btn btn-ghost" onclick="window.location.hash='quotes'">Cancelar</button>
            </div>
            <form id="quote-form" class="card">
                <div class="card-body">
                    <div class="grid-2">
                        <div class="form-group"><label class="form-label">Cliente *</label>
                            <select id="q_client_id" class="form-control" required>
                                <option value="">Seleccione</option>
                                ${clients.map(c => `<option value="${c.id}" ${quote && quote.client_id == c.id ? 'selected' : ''}>${c.company_name || c.contact_name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label">Moneda</label>
                            <select id="q_currency" class="form-control">
                                <option value="USD" ${quote?.currency==='USD'?'selected':''}>USD</option>
                                <option value="DOP" ${quote?.currency==='DOP'?'selected':''}>DOP</option>
                                <option value="EUR" ${quote?.currency==='EUR'?'selected':''}>EUR</option>
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label">Emisión *</label><input type="date" id="q_issue_date" class="form-control" required value="${quote?.issue_date?.split('T')[0]||today}"></div>
                        <div class="form-group"><label class="form-label">Válida Hasta *</label><input type="date" id="q_expiry_date" class="form-control" required value="${quote?.expiry_date?.split('T')[0]||nextMonth}"></div>
                    </div>
                    <h3 class="mt-24 mb-16" style="font-size:16px;border-bottom:1px solid var(--border-color);padding-bottom:8px;">Conceptos</h3>
                    <div id="quote-items-container"></div>
                    <button type="button" class="btn btn-ghost mt-16" onclick="QuotesModule.addItem()">+ Agregar Concepto</button>
                    <div class="grid-2 mt-24">
                        <div><div class="form-group"><label class="form-label">Notas</label><textarea id="q_notes" class="form-control" rows="3">${quote?.notes||''}</textarea></div></div>
                        <div>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Descuento (%)</label><input type="number" id="q_discount" class="form-control" value="${quote?.discount_value||0}" min="0" max="100" step="0.01" oninput="QuotesModule.calculateTotals()"></div>
                                <div class="form-group"><label class="form-label">Impuesto (%)</label><input type="number" id="q_tax" class="form-control" value="${quote?.tax_rate??18}" min="0" max="100" step="0.01" oninput="QuotesModule.calculateTotals()"></div>
                            </div>
                            <div style="background:var(--bg-hover);padding:16px;border-radius:6px;margin-top:16px;">
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span style="color:var(--text-muted)">Subtotal:</span><span id="q_calc_subtotal" class="font-semibold">0.00</span></div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span style="color:var(--text-muted)">Descuento:</span><span id="q_calc_discount" class="font-semibold text-red">0.00</span></div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span style="color:var(--text-muted)">Impuesto:</span><span id="q_calc_tax" class="font-semibold">0.00</span></div>
                                <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border-color);padding-top:8px;margin-top:8px;"><span style="font-weight:bold;font-size:18px;">Total:</span><span id="q_calc_total" style="font-weight:bold;font-size:18px;color:var(--primary)">0.00</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-24"><button type="submit" class="btn btn-primary">${editId ? 'Actualizar Cotización' : 'Guardar Cotización'}</button></div>
                </div>
            </form>
        `;

        this.items = [];
        if (quote && quote.items) {
            quote.items.forEach((item, idx) => { this.items.push({ id: idx }); });
            this.renderItems();
            setTimeout(() => {
                quote.items.forEach((item, idx) => {
                    const d = document.getElementById(`q_item_desc_${idx}`); if(d) d.value = item.description;
                    const q = document.getElementById(`q_item_qty_${idx}`); if(q) q.value = item.quantity;
                    const p = document.getElementById(`q_item_price_${idx}`); if(p) p.value = item.unit_price;
                });
                this.calculateTotals();
            }, 50);
        } else { this.addItem(); }

        document.getElementById('quote-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const itemsToSave = this.items.map(item => ({
                description: document.getElementById(`q_item_desc_${item.id}`)?.value || '',
                quantity: parseFloat(document.getElementById(`q_item_qty_${item.id}`)?.value) || 0,
                unit_price: parseFloat(document.getElementById(`q_item_price_${item.id}`)?.value) || 0
            })).filter(i => i.description.trim() !== '');
            if (itemsToSave.length === 0) { window.App.showToast('Agrega al menos un concepto', 'error'); return; }

            const payload = {
                client_id: document.getElementById('q_client_id').value,
                currency: document.getElementById('q_currency').value,
                issue_date: document.getElementById('q_issue_date').value,
                expiry_date: document.getElementById('q_expiry_date').value,
                discount_type: 'percentage', discount_value: document.getElementById('q_discount').value,
                tax_rate: document.getElementById('q_tax').value, notes: document.getElementById('q_notes').value,
                items: itemsToSave
            };

            try {
                if (editId) {
                    await window.App.api(`quotes/${editId}`, { method: 'PUT', body: payload });
                    window.App.showToast('Cotización actualizada');
                } else {
                    await window.App.api('quotes', { method: 'POST', body: payload });
                    window.App.showToast('Cotización creada');
                }
                window.location.hash = 'quotes';
            } catch (err) {}
        });
    },

    addItem() { const idx = this.items ? this.items.length : 0; if(!this.items) this.items = []; this.items.push({ id: idx }); this.renderItems(); },
    removeItem(idx) { if(this.items.length <= 1) return; this.items = this.items.filter(i => i.id !== idx); this.renderItems(); this.calculateTotals(); },

    renderItems() {
        const c = document.getElementById('quote-items-container'); if(!c) return;
        const v = {}; this.items.forEach(item => { const d = document.getElementById(`q_item_desc_${item.id}`); if(d) v[item.id] = { desc: d.value, qty: document.getElementById(`q_item_qty_${item.id}`).value, price: document.getElementById(`q_item_price_${item.id}`).value }; });
        c.innerHTML = this.items.map(item => `
            <div style="display:flex;gap:12px;margin-bottom:12px;align-items:flex-start;">
                <div style="flex:1"><input type="text" id="q_item_desc_${item.id}" class="form-control" placeholder="Descripción..." required></div>
                <div style="width:100px"><input type="number" id="q_item_qty_${item.id}" class="form-control" placeholder="Cant." min="0.01" step="0.01" value="1" required oninput="QuotesModule.calculateTotals()"></div>
                <div style="width:150px"><input type="number" id="q_item_price_${item.id}" class="form-control" placeholder="Precio" min="0" step="0.01" required oninput="QuotesModule.calculateTotals()"></div>
                <button type="button" class="btn btn-icon" style="color:var(--red);padding:10px" onclick="QuotesModule.removeItem(${item.id})"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
            </div>
        `).join('');
        this.items.forEach(item => { if(v[item.id]) { document.getElementById(`q_item_desc_${item.id}`).value = v[item.id].desc; document.getElementById(`q_item_qty_${item.id}`).value = v[item.id].qty; document.getElementById(`q_item_price_${item.id}`).value = v[item.id].price; } });
    },

    calculateTotals() {
        let sub = 0; if(this.items) this.items.forEach(item => { sub += (parseFloat(document.getElementById(`q_item_qty_${item.id}`)?.value)||0) * (parseFloat(document.getElementById(`q_item_price_${item.id}`)?.value)||0); });
        const dr = parseFloat(document.getElementById('q_discount')?.value)||0, da = sub*(dr/100);
        const tr = parseFloat(document.getElementById('q_tax')?.value)||0, ta = (sub-da)*(tr/100), total = sub-da+ta;
        const fmt = v => window.App.formatCurrency(v,'');
        document.getElementById('q_calc_subtotal').textContent = fmt(sub);
        document.getElementById('q_calc_discount').textContent = fmt(da);
        document.getElementById('q_calc_tax').textContent = fmt(ta);
        document.getElementById('q_calc_total').textContent = fmt(total);
    },

    async convertToInvoice(id) {
        if(!confirm('¿Convertir esta cotización en factura? Se enviará al cliente por email.')) return;
        try {
            const res = await window.App.api(`quotes/${id}/convert`, { method: 'POST' });
            window.App.showToast(res.email_sent ? 'Cotización convertida y factura enviada por email' : 'Cotización convertida en factura');
            window.location.hash = 'invoices';
        } catch(e) {}
    },

    async sendEmail(id) {
        if(!confirm('¿Enviar cotización por correo?')) return;
        App.showToast('Enviando...', 'success');
        try { await App.api(`quotes/${id}/send-email`, { method: 'POST' }); App.showToast('Correo enviado'); } catch(e) {}
    },

    async duplicateQuote(id) {
        if(!confirm('¿Duplicar esta cotización?')) return;
        try { await window.App.api(`quotes/${id}/duplicate`, { method: 'POST' }); window.App.showToast('Cotización duplicada'); window.location.hash = 'quotes'; } catch(e) {}
    },

    async deleteQuote(id) {
        if(!confirm('⚠️ ¿Eliminar esta cotización?')) return;
        try { await window.App.api(`quotes/${id}`, { method: 'DELETE' }); window.App.showToast('Cotización eliminada'); window.location.hash = 'quotes'; } catch(e) {}
    }
};

window.QuotesModule = QuotesModule;
export default QuotesModule;
