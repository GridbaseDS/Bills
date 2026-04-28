const QuotesModule = {
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
            const data = await window.App.api('quotes');
            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Cotizaciones</h1>
                        <p class="page-subtitle">Administra presupuestos de clientes</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.location.hash='quotes/new'">+ Nueva Cotización</button>
                </div>
                <div class="card">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Cliente</th>
                                    <th>Fecha Emisión</th>
                                    <th>Válida Hasta</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.data.map(q => `
                                    <tr>
                                        <td class="font-semibold text-mono"><a href="#quotes/${q.id}" style="color:inherit;text-decoration:none">${q.quote_number}</a></td>
                                        <td>${q.company_name || q.contact_name}</td>
                                        <td>${window.App.formatDate(q.issue_date)}</td>
                                        <td>${window.App.formatDate(q.expiry_date)}</td>
                                        <td class="font-semibold">${window.App.formatCurrency(q.total, q.currency)}</td>
                                        <td><span class="badge badge-${q.status}">${q.status}</span></td>
                                        <td>
                                            <a href="#quotes/${q.id}" class="btn btn-ghost btn-sm" title="Ver Detalles">Ver</a>
                                            <a href="/api/quotes/${q.id}/pdf?download=1" target="_blank" class="btn btn-ghost btn-sm" title="Descargar PDF"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></a>
                                        </td>
                                    </tr>
                                `).join('') || `<tr><td colspan="7" class="text-center py-8 text-muted">No se encontraron cotizaciones</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar cotizaciones</div>`;
        }
    },

    async renderDetails(container, id) {
        try {
            const quote = await window.App.api(`quotes/${id}`);
            
            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Cotización ${quote.quote_number}</h1>
                        <p class="page-subtitle">Emitida el ${window.App.formatDate(quote.issue_date)}</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="window.QuotesModule.convertToInvoice(${id})" ${quote.status === 'invoiced' ? 'disabled' : ''}>${quote.status === 'invoiced' ? 'Facturada' : 'Convertir a Factura'}</button>
                        <a href="/api/quotes/${id}/pdf?download=1" target="_blank" class="btn btn-ghost">Descargar PDF</a>
                        <button class="btn btn-ghost" onclick="window.QuotesModule.sendEmail(${id})">✉️ Enviar Correo</button>
                    </div>
                </div>

                <div class="card mb-24">
                    <div class="card-body">
                        <div class="grid-2">
                            <div>
                                <h3 style="font-size: 14px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Cotizado a</h3>
                                <p class="font-semibold" style="font-size: 16px; margin: 0;">${quote.company_name || quote.contact_name}</p>
                                <p style="margin: 4px 0 0 0;">${quote.email}</p>
                            </div>
                            <div class="text-right">
                                <h3 style="font-size: 14px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Detalles</h3>
                                <p style="margin: 0;"><strong>Válida Hasta:</strong> ${window.App.formatDate(quote.expiry_date)}</p>
                                <p style="margin: 4px 0 0 0;"><strong>Estado:</strong> <span class="badge badge-${quote.status}">${quote.status}</span></p>
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
                            <div>
                                ${quote.notes ? `
                                    <h3 style="font-size: 14px; color: var(--text-muted); margin-bottom: 8px;">Notas</h3>
                                    <p style="white-space: pre-wrap;">${quote.notes}</p>
                                ` : ''}
                            </div>
                            <div>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr><td style="padding: 8px 0; text-align: right; color: var(--text-muted);">Subtotal</td><td style="padding: 8px 0; text-align: right; font-weight: 500;">${window.App.formatCurrency(quote.subtotal, quote.currency)}</td></tr>
                                    ${quote.discount_amount > 0 ? `<tr><td style="padding: 8px 0; text-align: right; color: var(--text-muted);">Descuento</td><td style="padding: 8px 0; text-align: right; font-weight: 500;">-${window.App.formatCurrency(quote.discount_amount, quote.currency)}</td></tr>` : ''}
                                    ${quote.tax_amount > 0 ? `<tr><td style="padding: 8px 0; text-align: right; color: var(--text-muted);">Impuesto (${quote.tax_rate}%)</td><td style="padding: 8px 0; text-align: right; font-weight: 500;">${window.App.formatCurrency(quote.tax_amount, quote.currency)}</td></tr>` : ''}
                                    <tr style="border-top: 2px solid var(--border-color);"><td style="padding: 12px 0; text-align: right; font-size: 18px; font-weight: 600;">Total</td><td style="padding: 12px 0; text-align: right; font-size: 18px; font-weight: 700; color: var(--primary);">${window.App.formatCurrency(quote.total, quote.currency)}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar detalles de la cotización</div>`;
        }
    },

    async renderForm(container) {
        // Fetch clients for dropdown
        let clients = [];
        try {
            const res = await window.App.api('clients');
            clients = res.data || [];
        } catch(e) {}

        const today = new Date().toISOString().split('T')[0];
        const nextMonth = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

        container.innerHTML = `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Nueva Cotización</h1>
                    <p class="page-subtitle">Crea un presupuesto para un cliente</p>
                </div>
                <button class="btn btn-ghost" onclick="window.location.hash='quotes'">Cancelar</button>
            </div>
            
            <form id="quote-form" class="card">
                <div class="card-body">
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Cliente *</label>
                            <select id="q_client_id" class="form-control" required>
                                <option value="">Seleccione un cliente</option>
                                ${clients.map(c => `<option value="${c.id}">${c.company_name || c.contact_name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Moneda</label>
                            <select id="q_currency" class="form-control">
                                <option value="USD">USD - Dólares</option>
                                <option value="DOP">DOP - Pesos Dominicanos</option>
                                <option value="EUR">EUR - Euros</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha de Emisión *</label>
                            <input type="date" id="q_issue_date" class="form-control" required value="${today}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Válida Hasta *</label>
                            <input type="date" id="q_expiry_date" class="form-control" required value="${nextMonth}">
                        </div>
                    </div>
                    
                    <h3 class="mt-24 mb-16" style="font-size: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Conceptos</h3>
                    
                    <div id="quote-items-container"></div>
                    
                    <button type="button" class="btn btn-ghost mt-16" onclick="window.QuotesModule.addItem()">+ Agregar Concepto</button>

                    <div class="grid-2 mt-24">
                        <div>
                            <div class="form-group">
                                <label class="form-label">Notas</label>
                                <textarea id="q_notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Descuento (%)</label>
                                    <input type="number" id="q_discount" class="form-control" value="0" min="0" max="100" step="0.01" onchange="window.QuotesModule.calculateTotals()">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Impuesto (%)</label>
                                    <input type="number" id="q_tax" class="form-control" value="18" min="0" max="100" step="0.01" onchange="window.QuotesModule.calculateTotals()">
                                </div>
                            </div>
                            <div style="background: var(--bg-hover); padding: 16px; border-radius: 6px; margin-top: 16px;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="color:var(--text-muted)">Subtotal:</span>
                                    <span id="q_calc_subtotal" class="font-semibold">0.00</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="color:var(--text-muted)">Descuento:</span>
                                    <span id="q_calc_discount" class="font-semibold text-red">0.00</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="color:var(--text-muted)">Impuesto:</span>
                                    <span id="q_calc_tax" class="font-semibold">0.00</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-top:1px solid var(--border-color); padding-top:8px; margin-top:8px;">
                                    <span style="font-weight:bold; font-size:18px;">Total:</span>
                                    <span id="q_calc_total" style="font-weight:bold; font-size:18px; color:var(--primary)">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-24">
                        <button type="submit" class="btn btn-primary">Guardar Cotización</button>
                    </div>
                </div>
            </form>
        `;

        // Initialize state for items
        window.QuotesModule.items = [];
        window.QuotesModule.addItem(); // add first empty row

        document.getElementById('quote-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Collect items
            const itemsToSave = window.QuotesModule.items.map((item, idx) => {
                return {
                    description: document.getElementById(`q_item_desc_${idx}`).value,
                    quantity: parseFloat(document.getElementById(`q_item_qty_${idx}`).value) || 0,
                    unit_price: parseFloat(document.getElementById(`q_item_price_${idx}`).value) || 0
                };
            }).filter(i => i.description.trim() !== '');

            if (itemsToSave.length === 0) {
                window.App.showToast('Debes agregar al menos un concepto', 'error');
                return;
            }

            const payload = {
                client_id: document.getElementById('q_client_id').value,
                currency: document.getElementById('q_currency').value,
                issue_date: document.getElementById('q_issue_date').value,
                expiry_date: document.getElementById('q_expiry_date').value,
                discount_type: 'percentage',
                discount_value: document.getElementById('q_discount').value,
                tax_rate: document.getElementById('q_tax').value,
                notes: document.getElementById('q_notes').value,
                items: itemsToSave
            };

            try {
                await window.App.api('quotes', { method: 'POST', body: payload });
                window.App.showToast('Cotización creada correctamente');
                window.location.hash = 'quotes';
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
        if(this.items.length <= 1) return; // keep at least one
        this.items = this.items.filter(i => i.id !== idx);
        this.renderItems();
        this.calculateTotals();
    },

    renderItems() {
        const container = document.getElementById('quote-items-container');
        if(!container) return;
        
        // Preserve values before re-render
        const values = {};
        this.items.forEach(item => {
            const descEl = document.getElementById(`q_item_desc_${item.id}`);
            if(descEl) {
                values[item.id] = {
                    desc: descEl.value,
                    qty: document.getElementById(`q_item_qty_${item.id}`).value,
                    price: document.getElementById(`q_item_price_${item.id}`).value
                };
            }
        });

        container.innerHTML = this.items.map((item) => `
            <div style="display:flex; gap:12px; margin-bottom:12px; align-items:flex-start;">
                <div style="flex:1">
                    <input type="text" id="q_item_desc_${item.id}" class="form-control" placeholder="Descripción del concepto..." required>
                </div>
                <div style="width: 100px;">
                    <input type="number" id="q_item_qty_${item.id}" class="form-control" placeholder="Cant." min="0.01" step="0.01" value="1" required onchange="window.QuotesModule.calculateTotals()" onkeyup="window.QuotesModule.calculateTotals()">
                </div>
                <div style="width: 150px;">
                    <input type="number" id="q_item_price_${item.id}" class="form-control" placeholder="Precio" min="0" step="0.01" required onchange="window.QuotesModule.calculateTotals()" onkeyup="window.QuotesModule.calculateTotals()">
                </div>
                <button type="button" class="btn btn-icon" style="color:var(--red); padding:10px" onclick="window.QuotesModule.removeItem(${item.id})">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>
        `).join('');

        // Restore values
        this.items.forEach(item => {
            if(values[item.id]) {
                document.getElementById(`q_item_desc_${item.id}`).value = values[item.id].desc;
                document.getElementById(`q_item_qty_${item.id}`).value = values[item.id].qty;
                document.getElementById(`q_item_price_${item.id}`).value = values[item.id].price;
            }
        });
    },

    calculateTotals() {
        let subtotal = 0;
        if(this.items) {
            this.items.forEach(item => {
                const qty = parseFloat(document.getElementById(`q_item_qty_${item.id}`)?.value) || 0;
                const price = parseFloat(document.getElementById(`q_item_price_${item.id}`)?.value) || 0;
                subtotal += (qty * price);
            });
        }
        
        const discountRate = parseFloat(document.getElementById('q_discount')?.value) || 0;
        const discountAmt = subtotal * (discountRate / 100);
        
        const taxRate = parseFloat(document.getElementById('q_tax')?.value) || 0;
        const taxAmt = (subtotal - discountAmt) * (taxRate / 100);
        
        const total = subtotal - discountAmt + taxAmt;

        document.getElementById('q_calc_subtotal').textContent = window.App.formatCurrency(subtotal, '');
        document.getElementById('q_calc_discount').textContent = window.App.formatCurrency(discountAmt, '');
        document.getElementById('q_calc_tax').textContent = window.App.formatCurrency(taxAmt, '');
        document.getElementById('q_calc_total').textContent = window.App.formatCurrency(total, '');
    },

    async convertToInvoice(id) {
        if(!confirm('¿Deseas convertir esta cotización en una factura?')) return;
        try {
            await window.App.api(`quotes/${id}/convert`, { method: 'POST' });
            window.App.showToast('Cotización convertida en factura', 'success');
            window.location.hash = 'invoices';
        } catch(e) {}
    },

    async sendEmail(id) {
        if(!confirm('¿Deseas enviar esta cotización por correo al cliente?')) return;
        App.showToast('Enviando correo, por favor espera...', 'success');
        try {
            await App.api(`quotes/${id}/send-email`, { method: 'POST' });
            App.showToast('Correo enviado exitosamente');
        } catch(e) {}
    }
};

// Export to window for inline onclick handlers
window.QuotesModule = QuotesModule;
export default QuotesModule;
