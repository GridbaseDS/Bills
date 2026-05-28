const QuotesModule = {
    statusLabel: (s) => ({draft:'Borrador',sent:'Enviada',converted:'Convertida',expired:'Expirada',rejected:'Rechazada'}[s]||s),

    async render(container, id) {
        if (id === 'new' || id === 'nueva') { this.renderForm(container); return; }
        if (id && (id.startsWith('edit/') || id.startsWith('editar/'))) { this.renderForm(container, id.replace(/^(edit|editar)\//, '')); return; }
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
                    <div style="display:flex; gap:12px;">
                        <a href="/api/quotes/export/csv" class="btn btn-secondary" target="_blank" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Exportar Excel
                        </a>
                        <button class="btn btn-primary" onclick="window.App.navigate('cotizaciones/nueva')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Nueva Cotización
                        </button>
                    </div>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-lg);flex-wrap:wrap;gap:12px;">
                    <div class="segmented-control" id="qt-status-tabs">
                        <button class="segment-item active" data-status="">Todas <span style="opacity:.5;margin-left:4px;" id="qt-count">${allQuotes.length}</span></button>
                        <button class="segment-item" data-status="draft">Borrador</button>
                        <button class="segment-item" data-status="sent">Enviada</button>
                        <button class="segment-item" data-status="converted">Convertida</button>
                        <button class="segment-item" data-status="expired">Expirada</button>
                    </div>
                    <div class="search-wrapper">
                        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input class="search-input" type="text" id="qt-search" placeholder="Buscar cotización...">
                    </div>
                </div>

                <div class="table-outer">
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead><tr><th>Número</th><th>Cliente</th><th>Emisión</th><th>Válida Hasta</th><th>Monto</th><th>Estado</th><th></th></tr></thead>
                            <tbody id="qt-tbody"></tbody>
                        </table>
                    </div>
                    <div id="qt-mobile-list" class="mobile-card-list"></div>
                </div>
            `;

            this._allQuotes = allQuotes;
            this._currentStatus = '';
            this.filterQuotes();
            document.getElementById('qt-search').addEventListener('input', () => this.filterQuotes());
            document.querySelectorAll('#qt-status-tabs .segment-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('#qt-status-tabs .segment-item').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    this._currentStatus = btn.dataset.status;
                    this.filterQuotes();
                });
            });
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar cotizaciones</div>`;
        }
    },

    filterQuotes() {
        const search = (document.getElementById('qt-search')?.value || '').toLowerCase();
        const status = this._currentStatus || '';
        let filtered = this._allQuotes || [];
        if (search) filtered = filtered.filter(q => (q.quote_number||'').toLowerCase().includes(search) || (q.company_name||'').toLowerCase().includes(search));
        if (status) filtered = filtered.filter(q => q.status === status);
        document.getElementById('qt-count').textContent = filtered.length;

        // Mobile card list (CSS controls visibility)
        const listEl = document.getElementById('qt-mobile-list');
        if (listEl) {
            listEl.innerHTML = filtered.length > 0 ? filtered.map(q => `
                <a href="#cotizaciones/${q.id}" class="mobile-card">
                    <div class="mobile-card-top">
                        <div class="mobile-card-id">${q.quote_number}</div>
                        <span class="badge badge-${q.status}">${this.statusLabel(q.status)}</span>
                    </div>
                    <div class="mobile-card-middle">
                        <div class="mobile-card-avatar">${(q.company_name || q.contact_name || '?').charAt(0).toUpperCase()}</div>
                        <div class="mobile-card-info">
                            <div class="mobile-card-name">${q.company_name || q.contact_name}</div>
                            <div class="mobile-card-sub">Válida: ${window.App.formatDate(q.expiry_date)}</div>
                        </div>
                    </div>
                    <div class="mobile-card-bottom">
                        <div class="mobile-card-amount">
                            <div>${window.App.formatCurrency(q.total, q.currency)}</div>
                            ${q.currency !== 'DOP' && q.exchange_rate && q.exchange_rate != 1 ? `
                                <div style="font-size:10px;color:var(--color-text-muted);font-weight:400;margin-top:2px;text-align:right;">
                                    ≈ ${window.App.formatCurrency(q.total * q.exchange_rate, 'DOP')}
                                </div>
                            ` : ''}
                        </div>
                        <svg class="mobile-card-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </a>
            `).join('') : '<div class="text-center text-muted" style="padding:48px;">No hay cotizaciones</div>';
        }

        // Desktop table (CSS controls visibility)
        const tbody = document.getElementById('qt-tbody');
        if (tbody) {
            tbody.innerHTML = filtered.length > 0 ? filtered.map(q => `
            <tr>
                <td><a href="#cotizaciones/${q.id}" class="link-id">${q.quote_number}</a></td>
                <td>
                    <div class="user-cell">
                        <div class="user-avatar-sm">${(q.company_name || q.contact_name || '?').charAt(0).toUpperCase()}</div>
                        <div class="user-details"><span class="user-name">${q.company_name || q.contact_name}</span></div>
                    </div>
                </td>
                <td>${window.App.formatDate(q.issue_date)}</td>
                <td>${window.App.formatDate(q.expiry_date)}</td>
                <td style="font-weight:600">
                    <div>${window.App.formatCurrency(q.total, q.currency)}</div>
                    ${q.currency !== 'DOP' && q.exchange_rate && q.exchange_rate != 1 ? `
                        <div style="font-size:11px;color:var(--color-text-muted);font-weight:400;margin-top:2px;">
                            ≈ DOP ${window.App.formatCurrency(q.total * q.exchange_rate, 'DOP')}
                        </div>
                    ` : ''}
                </td>
                <td><span class="badge badge-${q.status}">${this.statusLabel(q.status)}</span></td>
                <td>
                    <div class="row-actions">
                        <a href="#cotizaciones/${q.id}" class="btn-icon" style="width:28px;height:28px;" title="Ver"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>
                        <a href="/api/quotes/${q.id}/pdf?download=1" target="_blank" class="btn-icon" style="width:28px;height:28px;" title="PDF"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg></a>
                        <button class="btn-icon" style="width:28px;height:28px;" onclick="QuotesModule.deleteQuote(${q.id})" title="Eliminar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger-icon)" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                    </div>
                </td>
            </tr>
        `).join('') : '<tr><td colspan="7" class="text-center text-muted" style="padding:48px;">No hay cotizaciones</td></tr>';
        }
    },

    async renderDetails(container, id) {
        try {
            const quote = await window.App.api(`quotes/${id}`);
            container.innerHTML = `
                <div style="margin-bottom:12px;">
                    <a href="#cotizaciones" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Cotizaciones</a>
                    <span style="color:var(--color-text-muted);font-size:13px;"> / </span>
                    <span style="font-size:13px;">${quote.quote_number}</span>
                </div>
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Cotización ${quote.quote_number}</h1>
                        <p class="page-subtitle">Emitida el ${window.App.formatDate(quote.issue_date)}</p>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        ${quote.status !== 'converted' ? `<button class="btn btn-primary" onclick="QuotesModule.convertToInvoice(${id})">Convertir a Factura</button>` : '<span class="badge badge-converted" style="padding:8px 16px;">Ya Facturada</span>'}
                        <a href="/api/quotes/${id}/pdf" target="_blank" class="btn btn-secondary btn-sm">Ver PDF</a>
                        <button class="btn btn-secondary btn-sm" onclick="QuotesModule.sendEmail(${id})">Enviar</button>
                        <button class="btn btn-secondary btn-sm" onclick="QuotesModule.duplicateQuote(${id})">Duplicar</button>
                        <a href="#cotizaciones/edit/${id}" class="btn btn-secondary btn-sm">Editar</a>
                    </div>
                </div>

                <div class="table-outer mb-24">
                    <div style="padding:var(--spacing-xl);">
                        <div class="grid-2">
                            <div>
                                <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Cotizado a</div>
                                <p style="font-size:16px;font-weight:600;margin:0;">${quote.company_name || quote.contact_name}</p>
                                <p style="margin:4px 0 0 0;color:var(--color-text-secondary);font-size:13px;">${quote.email || ''}</p>
                            </div>
                            <div class="text-right">
                                <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Detalles</div>
                                <p style="margin:0;font-size:13px;"><strong>Válida Hasta:</strong> ${window.App.formatDate(quote.expiry_date)}</p>
                                <p style="margin:4px 0 0 0;font-size:13px;"><strong>Estado:</strong> <span class="badge badge-${quote.status}">${this.statusLabel(quote.status)}</span></p>
                            </div>
                        </div>
                        <div class="mt-24">
                            <table class="data-table" style="border:1px solid var(--color-border);border-radius:var(--radius-lg);overflow:hidden;">
                                <thead><tr><th>Descripción</th><th class="text-right">Cant.</th><th class="text-right">Precio</th><th class="text-right">Total</th></tr></thead>
                                <tbody>${quote.items.map(item => `<tr><td style="color:var(--color-text-primary);font-weight:500">${item.description}</td><td class="text-right">${item.quantity}</td><td class="text-right">${window.App.formatCurrency(item.unit_price, quote.currency)}</td><td class="text-right font-semibold">${window.App.formatCurrency(item.amount, quote.currency)}</td></tr>`).join('')}</tbody>
                            </table>
                        </div>
                        <div class="grid-2 mt-24">
                            <div>${quote.notes ? `<div style="font-size:11px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Notas</div><p style="white-space:pre-wrap;font-size:13px;color:var(--color-text-secondary);">${quote.notes}</p>` : ''}</div>
                            <div>
                                <table style="width:100%;border-collapse:collapse;">
                                    <tr><td style="padding:8px 0;text-align:right;color:var(--color-text-muted);font-size:13px;">Subtotal</td><td style="padding:8px 0;text-align:right;font-weight:500;font-size:13px;">${window.App.formatCurrency(quote.subtotal, quote.currency)}</td></tr>
                                    ${quote.discount_amount > 0 ? `<tr><td style="padding:8px 0;text-align:right;color:var(--color-text-muted);font-size:13px;">Descuento</td><td style="padding:8px 0;text-align:right;font-weight:500;font-size:13px;">-${window.App.formatCurrency(quote.discount_amount, quote.currency)}</td></tr>` : ''}
                                    ${quote.tax_amount > 0 ? `<tr><td style="padding:8px 0;text-align:right;color:var(--color-text-muted);font-size:13px;">ITBIS (${quote.tax_rate}%)</td><td style="padding:8px 0;text-align:right;font-weight:500;font-size:13px;">${window.App.formatCurrency(quote.tax_amount, quote.currency)}</td></tr>` : ''}
                                    <tr style="border-top:2px solid var(--color-border);"><td style="padding:12px 0;text-align:right;font-size:18px;font-weight:600;">Total</td><td style="padding:12px 0;text-align:right;font-size:18px;font-weight:700;color:var(--color-primary);">${window.App.formatCurrency(quote.total, quote.currency)}</td></tr>
                                    ${quote.currency !== 'DOP' && quote.exchange_rate && quote.exchange_rate != 1 ? `
                                        <tr>
                                            <td colspan="2" style="padding:4px 0;text-align:right;font-size:12px;color:var(--color-text-muted);">
                                                Equivalente a tasa ${quote.exchange_rate}: <strong>DOP ${window.App.formatCurrency(quote.total * quote.exchange_rate, 'DOP')}</strong>
                                            </td>
                                        </tr>
                                    ` : ''}
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
        
        let rates = {};
        try {
            const ratesRes = await window.App.api('currency/rates');
            if (ratesRes.success) { rates = ratesRes.rates || {}; QuotesModule.rates = rates; }
        } catch(e) {}
        
        try { this.availableItems = await window.App.api('items'); } catch(e) { this.availableItems = []; }
        let quote = null;
        if (editId) { try { quote = await window.App.api(`quotes/${editId}`); } catch(e) { container.innerHTML = `<div class="text-red">Error</div>`; return; } }
        const today = new Date().toISOString().split('T')[0];
        const nextMonth = new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0];

        container.innerHTML = `
            <div style="margin-bottom:12px;"><a href="#cotizaciones" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Cotizaciones</a></div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">${editId ? 'Editar Cotización' : 'Nueva Cotización'}</h1>
                    <p class="page-subtitle">${editId ? `Editando ${quote?.quote_number}` : 'Crea un presupuesto para un cliente'}</p>
                </div>
                <button class="btn btn-secondary" onclick="window.App.navigate('cotizaciones')">Cancelar</button>
            </div>
            <form id="quote-form" class="form-card">
                <div style="padding:var(--spacing-xl);">
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
                        <div class="form-group" id="q-exchange-rate-wrapper" style="display: ${quote?.currency && quote?.currency !== 'DOP' ? 'block' : 'none'};">
                            <label class="form-label">Tasa de Cambio</label>
                            <div style="display:flex;gap:12px;align-items:center;">
                                <input type="number" id="q_exchange_rate" class="form-control" step="0.0001" min="0.0001" value="${quote?.exchange_rate || '1.0'}" style="flex:1;">
                                <span style="font-size:12px;color:var(--color-text-muted);white-space:nowrap;" id="q-live-rate-hint">
                                    ${quote?.exchange_rate ? `Tasa: ${quote.exchange_rate}` : ''}
                                </span>
                            </div>
                        </div>
                        <div class="form-group"><label class="form-label">Emisión *</label><input type="date" id="q_issue_date" class="form-control" required value="${quote?.issue_date?.split('T')[0]||today}"></div>
                        <div class="form-group"><label class="form-label">Válida Hasta *</label><input type="date" id="q_expiry_date" class="form-control" required value="${quote?.expiry_date?.split('T')[0]||nextMonth}"></div>
                    </div>
                    <h3 class="mt-24 mb-16" style="font-size:15px;font-weight:600;border-bottom:1px solid var(--color-border);padding-bottom:8px;">Conceptos</h3>
                    <div id="quote-items-container"></div>
                    <button type="button" class="btn btn-ghost mt-16" onclick="QuotesModule.addItem()">+ Agregar Concepto</button>
                    <div class="grid-2 mt-24">
                        <div><div class="form-group"><label class="form-label">Notas</label><textarea id="q_notes" class="form-control" rows="3">${quote?.notes||''}</textarea></div></div>
                        <div>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Descuento (%)</label><input type="number" id="q_discount" class="form-control" value="${quote?.discount_value||0}" min="0" max="100" step="0.01" oninput="QuotesModule.calculateTotals()"></div>
                                <div class="form-group"><label class="form-label">Impuesto (%)</label><input type="number" id="q_tax" class="form-control" value="${quote?.tax_rate??18}" min="0" max="100" step="0.01" oninput="QuotesModule.calculateTotals()"></div>
                            </div>
                            <div style="background:var(--bg-hover);padding:16px;border-radius:var(--radius-lg);margin-top:16px;">
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;"><span style="color:var(--color-text-muted)">Subtotal:</span><span id="q_calc_subtotal" class="font-semibold">0.00</span></div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;"><span style="color:var(--color-text-muted)">Descuento:</span><span id="q_calc_discount" class="font-semibold text-red">0.00</span></div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;"><span style="color:var(--color-text-muted)">Impuesto:</span><span id="q_calc_tax" class="font-semibold">0.00</span></div>
                                <div style="display:flex;justify-content:space-between;border-top:1px solid var(--color-border);padding-top:8px;margin-top:8px;"><span style="font-weight:700;font-size:18px;">Total:</span><span id="q_calc_total" style="font-weight:700;font-size:18px;color:var(--color-primary)">0.00</span></div>
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

        // Initialize currency selector live rates logic
        const currencySelect = document.getElementById('q_currency');
        const rateWrapper = document.getElementById('q-exchange-rate-wrapper');
        const rateInput = document.getElementById('q_exchange_rate');
        const rateHint = document.getElementById('q-live-rate-hint');
        
        if (currencySelect) {
            currencySelect.addEventListener('change', () => {
                const currency = currencySelect.value;
                if (currency === 'DOP') {
                    if (rateWrapper) rateWrapper.style.display = 'none';
                    if (rateInput) rateInput.value = '1.000000';
                    if (rateHint) rateHint.textContent = '';
                } else {
                    if (rateWrapper) rateWrapper.style.display = 'block';
                    const activeRates = QuotesModule.rates || {};
                    let rate = 1.0;
                    if (currency === 'USD' && activeRates.USD_TO_DOP) {
                        rate = activeRates.USD_TO_DOP;
                    } else if (currency === 'EUR' && activeRates.EUR_TO_DOP) {
                        rate = activeRates.EUR_TO_DOP;
                    }
                    if (rateInput) rateInput.value = rate;
                    if (rateHint) rateHint.textContent = `Tasa sugerida: ${rate}`;
                }
            });
        }

        // Add datalist to document body if not exists
        if (!document.getElementById('catalog_items_list')) {
            const datalist = document.createElement('datalist');
            datalist.id = 'catalog_items_list';
            datalist.innerHTML = this.availableItems.map(i => `<option value="${i.name}">${window.App.formatCurrency(i.price, 'DOP')}</option>`).join('');
            document.body.appendChild(datalist);
        }

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
                exchange_rate: parseFloat(document.getElementById('q_exchange_rate')?.value) || 1.0,
                issue_date: document.getElementById('q_issue_date').value,
                expiry_date: document.getElementById('q_expiry_date').value,
                discount_type: 'percentage', discount_value: document.getElementById('q_discount').value,
                tax_rate: document.getElementById('q_tax').value, notes: document.getElementById('q_notes').value,
                items: itemsToSave
            };
            try {
                if (editId) { await window.App.api(`quotes/${editId}`, { method: 'PUT', body: payload }); window.App.showToast('Cotización actualizada'); }
                else { await window.App.api('quotes', { method: 'POST', body: payload }); window.App.showToast('Cotización creada'); }
                window.App.navigate('cotizaciones');
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
                <div style="flex:1"><input type="text" id="q_item_desc_${item.id}" list="catalog_items_list" class="form-control" placeholder="Descripción..." required oninput="QuotesModule.onItemDescChange(${item.id})"></div>
                <div style="width:100px"><input type="number" id="q_item_qty_${item.id}" class="form-control" placeholder="Cant." min="0.01" step="0.01" value="1" required oninput="QuotesModule.calculateTotals()"></div>
                <div style="width:150px"><input type="number" id="q_item_price_${item.id}" class="form-control" placeholder="Precio" min="0" step="0.01" required oninput="QuotesModule.calculateTotals()"></div>
                <button type="button" class="btn-icon" style="color:var(--color-danger-icon);width:38px;height:38px;" onclick="QuotesModule.removeItem(${item.id})"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
            </div>
        `).join('');
        this.items.forEach(item => { if(v[item.id]) { document.getElementById(`q_item_desc_${item.id}`).value = v[item.id].desc; document.getElementById(`q_item_qty_${item.id}`).value = v[item.id].qty; document.getElementById(`q_item_price_${item.id}`).value = v[item.id].price; } });
    },

    onItemDescChange(itemId) {
        const descInput = document.getElementById(`q_item_desc_${itemId}`);
        const priceInput = document.getElementById(`q_item_price_${itemId}`);
        if (!descInput || !priceInput || !this.availableItems) return;

        const val = descInput.value;
        const matchedItem = this.availableItems.find(i => i.name === val);
        if (matchedItem) {
            priceInput.value = matchedItem.price;
            this.calculateTotals();
        }
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
        this._showConfirm('¿Convertir esta cotización en factura?', async () => {
            try {
                const res = await window.App.api(`quotes/${id}/convert`, { method: 'POST' });
                window.App.showToast(res.email_sent ? 'Convertida y factura enviada' : 'Convertida en factura');
                window.App.navigate('facturas');
            } catch(e) {}
        });
    },

    async sendEmail(id) {
        this._showConfirm('¿Enviar cotización por correo?', async () => {
            App.showToast('Enviando...', 'success');
            try { await App.api(`quotes/${id}/send-email`, { method: 'POST' }); App.showToast('Correo enviado'); } catch(e) {}
        });
    },

    async duplicateQuote(id) {
        this._showConfirm('¿Duplicar esta cotización?', async () => {
            try { await window.App.api(`quotes/${id}/duplicate`, { method: 'POST' }); window.App.showToast('Cotizacion duplicada'); window.App.navigate('cotizaciones'); } catch(e) {}
        });
    },

    async deleteQuote(id) {
        this._showConfirm('¿Eliminar esta cotización?', async () => {
            try { await window.App.api(`quotes/${id}`, { method: 'DELETE' }); window.App.showToast('Cotizacion eliminada'); window.App.navigate('cotizaciones'); } catch(e) {}
        });
    },

    _showConfirm(message, onConfirm) {
        document.getElementById('confirm-modal')?.remove();
        const modal = document.createElement('div');
        modal.id = 'confirm-modal';
        modal.className = 'modal-overlay open';
        modal.innerHTML = `<div class="modal" style="max-width:400px;"><div class="modal-body" style="text-align:center;padding:32px;">
            <p style="font-size:15px;margin:0 0 24px 0;line-height:1.5;">${message}</p>
            <div style="display:flex;gap:12px;justify-content:center;"><button class="btn btn-secondary" id="confirm-no">Cancelar</button><button class="btn btn-primary" id="confirm-yes">Confirmar</button></div>
        </div></div>`;
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
        document.getElementById('confirm-no').addEventListener('click', () => modal.remove());
        document.getElementById('confirm-yes').addEventListener('click', () => { modal.remove(); onConfirm(); });
    }
};

window.QuotesModule = QuotesModule;
export default QuotesModule;
