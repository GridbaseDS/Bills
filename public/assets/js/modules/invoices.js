const InvoicesModule = {
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
            const data = await App.api('invoices');
            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Facturas</h1>
                        <p class="page-subtitle">Administra tu facturación y pagos</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.location.hash='invoices/new'">+ Nueva Factura</button>
                </div>
                <div class="card">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Cliente</th>
                                    <th>Fecha Emisión</th>
                                    <th>Vencimiento</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.data.map(i => `
                                    <tr>
                                        <td class="font-semibold text-mono"><a href="#invoices/${i.id}" style="color:inherit;text-decoration:none">${i.invoice_number}</a></td>
                                        <td>${i.company_name || i.contact_name}</td>
                                        <td>${App.formatDate(i.issue_date)}</td>
                                        <td style="${i.status === 'overdue' ? 'color: var(--red)' : ''}">${App.formatDate(i.due_date)}</td>
                                        <td class="font-semibold">${App.formatCurrency(i.total, i.currency)}</td>
                                        <td><span class="badge badge-${i.status}">${i.status}</span></td>
                                        <td>
                                            <a href="#invoices/${i.id}" class="btn btn-ghost btn-sm" title="Ver Detalles">Ver</a>
                                            <a href="/api/invoices/${i.id}/pdf?download=1" target="_blank" class="btn btn-ghost btn-sm" title="Descargar PDF"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></a>
                                        </td>
                                    </tr>
                                `).join('') || `<tr><td colspan="7" class="text-center py-8 text-muted">No se encontraron facturas</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar facturas</div>`;
        }
    },

    async renderDetails(container, id) {
        try {
            const inv = await App.api(`invoices/${id}`);
            
            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Factura ${inv.invoice_number}</h1>
                        <p class="page-subtitle">Emitida el ${App.formatDate(inv.issue_date)}</p>
                    </div>
                    <div>
                        <a href="/api/invoices/${id}/pdf?download=1" target="_blank" class="btn btn-ghost">Descargar PDF</a>
                        <button class="btn btn-ghost" onclick="window.InvoicesModule.sendEmail(${id})">✉️ Enviar Correo</button>
                        ${inv.status !== 'paid' ? `<button class="btn btn-primary" onclick="window.InvoicesModule.markAsPaid(${id}, ${inv.total - inv.amount_paid})">Marcar Pagada</button>` : ''}
                    </div>
                </div>

                <div class="card mb-24">
                    <div class="card-body">
                        <div class="grid-2">
                            <div>
                                <h3 style="font-size: 14px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Facturado a</h3>
                                <p class="font-semibold" style="font-size: 16px; margin: 0;">${inv.company_name || inv.contact_name}</p>
                                <p style="margin: 4px 0 0 0;">${inv.email}</p>
                            </div>
                            <div class="text-right">
                                <h3 style="font-size: 14px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Detalles</h3>
                                <p style="margin: 0;"><strong>Vence:</strong> ${App.formatDate(inv.due_date)}</p>
                                <p style="margin: 4px 0 0 0;"><strong>Estado:</strong> <span class="badge badge-${inv.status}">${inv.status}</span></p>
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
                                    ${inv.items.map(item => `
                                        <tr>
                                            <td>${item.description}</td>
                                            <td class="text-right">${item.quantity}</td>
                                            <td class="text-right">${App.formatCurrency(item.unit_price, inv.currency)}</td>
                                            <td class="text-right font-semibold">${App.formatCurrency(item.amount, inv.currency)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>

                        <div class="grid-2 mt-24">
                            <div>
                                ${inv.notes ? `
                                    <h3 style="font-size: 14px; color: var(--text-muted); margin-bottom: 8px;">Notas</h3>
                                    <p style="white-space: pre-wrap;">${inv.notes}</p>
                                ` : ''}
                            </div>
                            <div>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr><td style="padding: 8px 0; text-align: right; color: var(--text-muted);">Subtotal</td><td style="padding: 8px 0; text-align: right; font-weight: 500;">${App.formatCurrency(inv.subtotal, inv.currency)}</td></tr>
                                    ${inv.discount_amount > 0 ? `<tr><td style="padding: 8px 0; text-align: right; color: var(--text-muted);">Descuento</td><td style="padding: 8px 0; text-align: right; font-weight: 500;">-${App.formatCurrency(inv.discount_amount, inv.currency)}</td></tr>` : ''}
                                    ${inv.tax_amount > 0 ? `<tr><td style="padding: 8px 0; text-align: right; color: var(--text-muted);">Impuesto (${inv.tax_rate}%)</td><td style="padding: 8px 0; text-align: right; font-weight: 500;">${App.formatCurrency(inv.tax_amount, inv.currency)}</td></tr>` : ''}
                                    <tr style="border-top: 2px solid var(--border-color);"><td style="padding: 12px 0; text-align: right; font-size: 18px; font-weight: 600;">Total</td><td style="padding: 12px 0; text-align: right; font-size: 18px; font-weight: 700; color: var(--primary);">${App.formatCurrency(inv.total, inv.currency)}</td></tr>
                                    ${inv.amount_paid > 0 ? `<tr><td style="padding: 8px 0; text-align: right; color: var(--text-muted);">Pagado</td><td style="padding: 8px 0; text-align: right; font-weight: 500; color: var(--green);">-${App.formatCurrency(inv.amount_paid, inv.currency)}</td></tr>` : ''}
                                    ${inv.status !== 'paid' ? `<tr><td style="padding: 8px 0; text-align: right; font-weight: 600;">Balance Pendiente</td><td style="padding: 8px 0; text-align: right; font-weight: 600; color: var(--red);">${App.formatCurrency(inv.total - inv.amount_paid, inv.currency)}</td></tr>` : ''}
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar detalles de la factura</div>`;
        }
    },

    async renderForm(container) {
        // Fetch clients for dropdown
        let clients = [];
        try {
            const res = await App.api('clients');
            clients = res.data || [];
        } catch(e) {}

        const today = new Date().toISOString().split('T')[0];
        const nextWeek = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

        container.innerHTML = `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Nueva Factura</h1>
                    <p class="page-subtitle">Crea una factura para un cliente</p>
                </div>
                <button class="btn btn-ghost" onclick="window.location.hash='invoices'">Cancelar</button>
            </div>
            
            <form id="invoice-form" class="card">
                <div class="card-body">
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Cliente *</label>
                            <select id="i_client_id" class="form-control" required>
                                <option value="">Seleccione un cliente</option>
                                ${clients.map(c => `<option value="${c.id}">${c.company_name || c.contact_name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Moneda</label>
                            <select id="i_currency" class="form-control">
                                <option value="USD">USD - Dólares</option>
                                <option value="DOP">DOP - Pesos Dominicanos</option>
                                <option value="EUR">EUR - Euros</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha de Emisión *</label>
                            <input type="date" id="i_issue_date" class="form-control" required value="${today}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha de Vencimiento *</label>
                            <input type="date" id="i_due_date" class="form-control" required value="${nextWeek}">
                        </div>
                    </div>
                    
                    <h3 class="mt-24 mb-16" style="font-size: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Conceptos</h3>
                    
                    <div id="invoice-items-container"></div>
                    
                    <button type="button" class="btn btn-ghost mt-16" onclick="window.InvoicesModule.addItem()">+ Agregar Concepto</button>

                    <div class="grid-2 mt-24">
                        <div>
                            <div class="form-group">
                                <label class="form-label">Notas</label>
                                <textarea id="i_notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Descuento (%)</label>
                                    <input type="number" id="i_discount" class="form-control" value="0" min="0" max="100" step="0.01" onchange="window.InvoicesModule.calculateTotals()">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Impuesto (%)</label>
                                    <input type="number" id="i_tax" class="form-control" value="18" min="0" max="100" step="0.01" onchange="window.InvoicesModule.calculateTotals()">
                                </div>
                            </div>
                            <div style="background: var(--bg-hover); padding: 16px; border-radius: 6px; margin-top: 16px;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="color:var(--text-muted)">Subtotal:</span>
                                    <span id="calc_subtotal" class="font-semibold">0.00</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="color:var(--text-muted)">Descuento:</span>
                                    <span id="calc_discount" class="font-semibold text-red">0.00</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="color:var(--text-muted)">Impuesto:</span>
                                    <span id="calc_tax" class="font-semibold">0.00</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-top:1px solid var(--border-color); padding-top:8px; margin-top:8px;">
                                    <span style="font-weight:bold; font-size:18px;">Total:</span>
                                    <span id="calc_total" style="font-weight:bold; font-size:18px; color:var(--primary)">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-24">
                        <button type="submit" class="btn btn-primary">Guardar Factura</button>
                    </div>
                </div>
            </form>
        `;

        // Initialize state for items
        window.InvoicesModule.items = [];
        window.InvoicesModule.addItem(); // add first empty row

        document.getElementById('invoice-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Collect items
            const itemsToSave = window.InvoicesModule.items.map((item, idx) => {
                return {
                    description: document.getElementById(`item_desc_${idx}`).value,
                    quantity: parseFloat(document.getElementById(`item_qty_${idx}`).value) || 0,
                    unit_price: parseFloat(document.getElementById(`item_price_${idx}`).value) || 0
                };
            }).filter(i => i.description.trim() !== '');

            if (itemsToSave.length === 0) {
                App.showToast('Debes agregar al menos un concepto', 'error');
                return;
            }

            const payload = {
                client_id: document.getElementById('i_client_id').value,
                currency: document.getElementById('i_currency').value,
                issue_date: document.getElementById('i_issue_date').value,
                due_date: document.getElementById('i_due_date').value,
                discount_type: 'percentage',
                discount_value: document.getElementById('i_discount').value,
                tax_rate: document.getElementById('i_tax').value,
                notes: document.getElementById('i_notes').value,
                items: itemsToSave
            };

            try {
                await App.api('invoices', { method: 'POST', body: payload });
                App.showToast('Factura creada correctamente');
                window.location.hash = 'invoices';
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
        const container = document.getElementById('invoice-items-container');
        if(!container) return;
        
        // Preserve values before re-render
        const values = {};
        this.items.forEach(item => {
            const descEl = document.getElementById(`item_desc_${item.id}`);
            if(descEl) {
                values[item.id] = {
                    desc: descEl.value,
                    qty: document.getElementById(`item_qty_${item.id}`).value,
                    price: document.getElementById(`item_price_${item.id}`).value
                };
            }
        });

        container.innerHTML = this.items.map((item) => `
            <div style="display:flex; gap:12px; margin-bottom:12px; align-items:flex-start;">
                <div style="flex:1">
                    <input type="text" id="item_desc_${item.id}" class="form-control" placeholder="Descripción del concepto..." required>
                </div>
                <div style="width: 100px;">
                    <input type="number" id="item_qty_${item.id}" class="form-control" placeholder="Cant." min="0.01" step="0.01" value="1" required onchange="window.InvoicesModule.calculateTotals()" onkeyup="window.InvoicesModule.calculateTotals()">
                </div>
                <div style="width: 150px;">
                    <input type="number" id="item_price_${item.id}" class="form-control" placeholder="Precio" min="0" step="0.01" required onchange="window.InvoicesModule.calculateTotals()" onkeyup="window.InvoicesModule.calculateTotals()">
                </div>
                <button type="button" class="btn btn-icon" style="color:var(--red); padding:10px" onclick="window.InvoicesModule.removeItem(${item.id})">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>
        `).join('');

        // Restore values
        this.items.forEach(item => {
            if(values[item.id]) {
                document.getElementById(`item_desc_${item.id}`).value = values[item.id].desc;
                document.getElementById(`item_qty_${item.id}`).value = values[item.id].qty;
                document.getElementById(`item_price_${item.id}`).value = values[item.id].price;
            }
        });
    },

    calculateTotals() {
        let subtotal = 0;
        if(this.items) {
            this.items.forEach(item => {
                const qty = parseFloat(document.getElementById(`item_qty_${item.id}`)?.value) || 0;
                const price = parseFloat(document.getElementById(`item_price_${item.id}`)?.value) || 0;
                subtotal += (qty * price);
            });
        }
        
        const discountRate = parseFloat(document.getElementById('i_discount')?.value) || 0;
        const discountAmt = subtotal * (discountRate / 100);
        
        const taxRate = parseFloat(document.getElementById('i_tax')?.value) || 0;
        const taxAmt = (subtotal - discountAmt) * (taxRate / 100);
        
        const total = subtotal - discountAmt + taxAmt;

        document.getElementById('calc_subtotal').textContent = App.formatCurrency(subtotal, '');
        document.getElementById('calc_discount').textContent = App.formatCurrency(discountAmt, '');
        document.getElementById('calc_tax').textContent = App.formatCurrency(taxAmt, '');
        document.getElementById('calc_total').textContent = App.formatCurrency(total, '');
    },

    async markAsPaid(id, amount) {
        if(!confirm('¿Estás seguro de marcar esta factura como pagada?')) return;
        try {
            await App.api(`invoices/${id}/payment`, {
                method: 'POST',
                body: { amount: amount, payment_method: 'other' }
            });
            App.showToast('Pago registrado correctamente');
            App.navigate(`invoices/${id}`); // reload view
        } catch(e) {}
    },

    async sendEmail(id) {
        if(!confirm('¿Deseas enviar esta factura por correo al cliente?')) return;
        try {
            await App.api(`invoices/${id}/send-email`, { method: 'POST' });
            App.showToast('Correo enviado exitosamente');
        } catch(e) {}
    }
};

// Export to window for inline onclick handlers
// Note: the default export is referenced directly at the top of the file
// We assign `this` module to window inside render methods instead
window.InvoicesModule = InvoicesModule;
export default InvoicesModule;
