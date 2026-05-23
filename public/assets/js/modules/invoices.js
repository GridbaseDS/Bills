const InvoicesModule = {
    statusLabel: (s) => ({draft:'Borrador',sent:'Pendiente de Pago',paid:'Pagada',overdue:'Vencida',partial:'Pago Parcial',cancelled:'Cancelada'}[s]||s),

    async render(container, id) {
        if (id === 'new') { this.renderForm(container); return; }
        if (id && id.startsWith('edit/')) { this.renderForm(container, id.replace('edit/', '')); return; }
        if (id) { this.renderDetails(container, id); return; }
        this.renderList(container);
    },

    /* ═══════════════════════════════════════════════
       LIST — Segmented Control + Data Table
       ═══════════════════════════════════════════════ */
    async renderList(container) {
        try {
            const data = await App.api('invoices');
            const allInvoices = data.data || [];

            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Facturas</h1>
                        <p class="page-subtitle">Administra tu facturación y pagos</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.App.navigate('invoices/new')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Nueva Factura
                    </button>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-lg);flex-wrap:wrap;gap:12px;">
                    <div class="segmented-control" id="inv-status-tabs">
                        <button class="segment-item active" data-status="">Todas <span style="opacity:.5;margin-left:4px;" id="inv-count">${allInvoices.length}</span></button>
                        <button class="segment-item" data-status="draft">Borrador</button>
                        <button class="segment-item" data-status="sent">Pendiente</button>
                        <button class="segment-item" data-status="paid">Pagada</button>
                        <button class="segment-item" data-status="overdue">Vencida</button>
                        <button class="segment-item" data-status="partial">Parcial</button>
                    </div>
                    <div class="search-wrapper">
                        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input class="search-input" type="text" id="inv-search" placeholder="Buscar factura...">
                    </div>
                </div>

                <div class="table-outer">
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead><tr>
                                <th>Número</th><th>Cliente</th><th>Emisión</th><th>Vencimiento</th><th>Monto</th><th>Estado</th><th></th>
                            </tr></thead>
                            <tbody id="inv-tbody"></tbody>
                        </table>
                    </div>
                </div>
            `;

            this._allInvoices = allInvoices;
            this._currentStatus = '';
            this.filterInvoices();

            document.getElementById('inv-search').addEventListener('input', () => this.filterInvoices());
            document.querySelectorAll('#inv-status-tabs .segment-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('#inv-status-tabs .segment-item').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    this._currentStatus = btn.dataset.status;
                    this.filterInvoices();
                });
            });
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar facturas</div>`;
        }
    },

    filterInvoices() {
        const search = (document.getElementById('inv-search')?.value || '').toLowerCase();
        const status = this._currentStatus || '';
        const tbody = document.getElementById('inv-tbody');
        if (!tbody) return;

        let filtered = this._allInvoices;
        if (search) {
            filtered = filtered.filter(i =>
                (i.invoice_number||'').toLowerCase().includes(search) ||
                (i.company_name||'').toLowerCase().includes(search) ||
                (i.contact_name||'').toLowerCase().includes(search)
            );
        }
        if (status) filtered = filtered.filter(i => i.status === status);

        document.getElementById('inv-count').textContent = filtered.length;

        tbody.innerHTML = filtered.length > 0 ? filtered.map(i => `
            <tr>
                <td>
                    <a href="#invoices/${i.id}" class="link-id">${i.is_ecf ? (i.encf || i.invoice_number) : i.invoice_number}</a>
                    ${i.is_ecf ? `<span class="badge" style="background:var(--color-primary);color:#FFF;margin-left:6px;font-size:8px;padding:2px 5px;">e-CF</span>` : ''}
                </td>
                <td>
                    <div class="user-cell">
                        <div class="user-avatar-sm">${(i.company_name || i.contact_name || '?').charAt(0).toUpperCase()}</div>
                        <div class="user-details">
                            <span class="user-name">${i.company_name || i.contact_name}</span>
                        </div>
                    </div>
                </td>
                <td>${App.formatDate(i.issue_date)}</td>
                <td style="${i.status === 'overdue' ? 'color:var(--color-danger-icon)' : ''}">${App.formatDate(i.due_date)}</td>
                <td style="font-weight:600;color:var(--color-text-primary)">${App.formatCurrency(i.total, i.currency)}</td>
                <td><span class="badge badge-${i.status}">${this.statusLabel(i.status)}</span></td>
                <td>
                    <div class="row-actions">
                        <a href="#invoices/${i.id}" class="btn-icon" style="width:28px;height:28px;" title="Ver"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>
                        <a href="/api/invoices/${i.id}/pdf?download=1" target="_blank" class="btn-icon" style="width:28px;height:28px;" title="PDF"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg></a>
                        <button class="btn-icon" style="width:28px;height:28px;" onclick="InvoicesModule.deleteInvoice(${i.id})" title="Eliminar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger-icon)" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                    </div>
                </td>
            </tr>
        `).join('') : '<tr><td colspan="7" class="text-center text-muted" style="padding:48px;">No hay facturas que coincidan</td></tr>';
    },

    /* ═══════════════════════════════════════════════
       DETAIL VIEW
       ═══════════════════════════════════════════════ */
    async renderDetails(container, id) {
        try {
            const inv = await App.api(`invoices/${id}`);
            container.innerHTML = `
                <div style="margin-bottom:12px;">
                    <a href="#invoices" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Facturas</a>
                    <span style="color:var(--color-text-muted);font-size:13px;"> / </span>
                    <span style="font-size:13px;">${inv.invoice_number}</span>
                </div>
                <div class="page-header">
                    <div>
                        <h1 class="page-title">${inv.is_ecf ? `e-CF ${inv.encf || inv.invoice_number}` : `Factura ${inv.invoice_number}`}</h1>
                        <p class="page-subtitle">Emitida el ${App.formatDate(inv.issue_date)}</p>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="/api/invoices/${id}/pdf" target="_blank" class="btn btn-secondary btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            Ver PDF
                        </a>
                        <button class="btn btn-secondary btn-sm" onclick="InvoicesModule.sendEmail(${id})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            Enviar
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="InvoicesModule.duplicateInvoice(${id})">Duplicar</button>
                        <a href="#invoices/edit/${id}" class="btn btn-secondary btn-sm">Editar</a>
                        ${inv.status !== 'paid' ? `<button class="btn btn-primary btn-sm" onclick="InvoicesModule.showPaymentModal(${id}, ${(inv.total || 0) - (inv.amount_paid || 0)})">Registrar Pago</button>` : ''}
                    </div>
                </div>

                ${inv.is_ecf ? `
                <div class="table-outer mb-24" style="border-left: 3px solid ${
                    inv.dgii_status === 'accepted' ? 'var(--color-success-icon)' :
                    inv.dgii_status === 'rejected' ? 'var(--color-danger-icon)' :
                    inv.dgii_status === 'contingency' ? 'var(--amber)' :
                    inv.dgii_status === 'portal_pending' ? '#8b5cf6' : 'var(--color-primary)'
                };">
                    <div style="padding:var(--spacing-xl);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                        <div>
                            <div style="font-size:15px;font-weight:600;display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                                Factura Electrónica (e-CF)
                                <span class="badge badge-${
                                    inv.dgii_status === 'accepted' ? 'active' :
                                    inv.dgii_status === 'rejected' ? 'overdue' :
                                    inv.dgii_status === 'contingency' ? 'onboarding' :
                                    inv.dgii_status === 'portal_pending' ? 'sent' : 'sent'
                                }">
                                    ${inv.dgii_status === 'accepted' ? 'Aprobado' :
                                      inv.dgii_status === 'rejected' ? 'Rechazado' :
                                      inv.dgii_status === 'contingency' ? 'Contingencia' :
                                      inv.dgii_status === 'pending' ? 'Procesando' :
                                      inv.dgii_status === 'portal_pending' ? 'Subir al Portal' :
                                      inv.dgii_status === 'signed' ? 'Firmado' : '—'}
                                </span>
                            </div>
                            <p style="margin:0;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--color-text-muted);">
                                <strong>e-NCF:</strong> ${inv.encf} | <strong>Cód. Seguridad:</strong> ${inv.security_code || '—'}
                            </p>
                            ${inv.dgii_track_id ? `<p style="margin:4px 0 0 0;font-size:12px;color:var(--color-text-muted);"><strong>DGII Track ID:</strong> <code>${inv.dgii_track_id}</code></p>` : ''}
                            ${inv.dgii_status === 'portal_pending' ? `
                                <div style="margin-top:12px;padding:10px;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:var(--radius-md);font-size:12px;color:#6d28d9;">
                                    <strong>FC<250k:</strong> Esta factura debe subirse manualmente al portal DGII → "Facturas de consumo < 250Mil"
                                </div>
                            ` : ''}
                            ${inv.dgii_error_messages && inv.dgii_status !== 'portal_pending' ? `
                                <div style="margin-top:12px;padding:10px;background:var(--color-danger-bg);border:1px solid rgba(220,38,38,.1);border-radius:var(--radius-md);color:var(--color-danger-text);font-size:12px;white-space:pre-wrap;">
                                    <strong>Errores DGII:</strong><br>${inv.dgii_error_messages}
                                </div>
                            ` : ''}
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            ${inv.dgii_status === 'pending' ? `
                                <button class="btn btn-secondary btn-sm" onclick="InvoicesModule.checkEcfStatus(${inv.id})">
                                    Verificar Estado
                                </button>
                            ` : ''}
                            ${['contingency','rejected','signed'].includes(inv.dgii_status) ? `
                                <button class="btn btn-primary btn-sm" onclick="InvoicesModule.processEcf(${inv.id})">
                                    Reintentar Envío
                                </button>
                            ` : ''}
                            ${!inv.dgii_status ? `
                                <button class="btn btn-primary btn-sm" onclick="InvoicesModule.processEcf(${inv.id})">
                                    Enviar a DGII
                                </button>
                            ` : ''}
                            ${inv.dgii_status === 'accepted' ? `<span style="color:var(--color-success-icon);font-size:13px;font-weight:600;">Certificado DGII</span>` : ''}
                        </div>
                    </div>
                </div>
                ` : ''}

                <div class="table-outer mb-24">
                    <div style="padding:var(--spacing-xl);">
                        <div class="grid-2">
                            <div>
                                <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Facturado a</div>
                                <p style="font-size:16px;font-weight:600;margin:0;">${inv.company_name || inv.contact_name}</p>
                                <p style="margin:4px 0 0 0;color:var(--color-text-secondary);font-size:13px;">${inv.email || ''}</p>
                            </div>
                            <div class="text-right">
                                <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Detalles</div>
                                <p style="margin:0;font-size:13px;"><strong>Vence:</strong> ${App.formatDate(inv.due_date)}</p>
                                <p style="margin:4px 0 0 0;font-size:13px;"><strong>Estado:</strong> <span class="badge badge-${inv.status}">${this.statusLabel(inv.status)}</span></p>
                                ${inv.sent_at ? `<p style="margin:4px 0 0 0;color:var(--color-success-icon);font-size:12px;">Enviado ${App.formatDate(inv.sent_at)}</p>` : `<p style="margin:4px 0 0 0;color:var(--color-text-muted);font-size:12px;">No enviado</p>`}
                            </div>
                        </div>

                        <div class="mt-24">
                            <table class="data-table" style="border:1px solid var(--color-border);border-radius:var(--radius-lg);overflow:hidden;">
                                <thead><tr><th>Descripción</th><th class="text-right">Cant.</th><th class="text-right">Precio</th><th class="text-right">Total</th></tr></thead>
                                <tbody>
                                    ${inv.items.map(item => `
                                        <tr>
                                            <td style="color:var(--color-text-primary);font-weight:500">${item.description}</td>
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
                                ${inv.notes ? `<div style="font-size:11px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Notas</div><p style="white-space:pre-wrap;font-size:13px;color:var(--color-text-secondary);">${inv.notes}</p>` : ''}
                            </div>
                            <div>
                                <table style="width:100%;border-collapse:collapse;">
                                    <tr><td style="padding:8px 0;text-align:right;color:var(--color-text-muted);font-size:13px;">Subtotal</td><td style="padding:8px 0;text-align:right;font-weight:500;font-size:13px;">${App.formatCurrency(inv.subtotal, inv.currency)}</td></tr>
                                    ${inv.discount_amount > 0 ? `<tr><td style="padding:8px 0;text-align:right;color:var(--color-text-muted);font-size:13px;">Descuento</td><td style="padding:8px 0;text-align:right;font-weight:500;font-size:13px;">-${App.formatCurrency(inv.discount_amount, inv.currency)}</td></tr>` : ''}
                                    ${inv.tax_amount > 0 ? `<tr><td style="padding:8px 0;text-align:right;color:var(--color-text-muted);font-size:13px;">ITBIS (${inv.tax_rate}%)</td><td style="padding:8px 0;text-align:right;font-weight:500;font-size:13px;">${App.formatCurrency(inv.tax_amount, inv.currency)}</td></tr>` : ''}
                                    <tr style="border-top:2px solid var(--color-border);"><td style="padding:12px 0;text-align:right;font-size:18px;font-weight:600;">Total</td><td style="padding:12px 0;text-align:right;font-size:18px;font-weight:700;color:var(--color-primary);">${App.formatCurrency(inv.total, inv.currency)}</td></tr>
                                    ${inv.amount_paid > 0 ? `<tr><td style="padding:8px 0;text-align:right;color:var(--color-text-muted);font-size:13px;">Pagado</td><td style="padding:8px 0;text-align:right;font-weight:500;color:var(--color-success-icon);font-size:13px;">-${App.formatCurrency(inv.amount_paid, inv.currency)}</td></tr>` : ''}
                                    ${inv.status !== 'paid' ? `<tr><td style="padding:8px 0;text-align:right;font-weight:600;font-size:13px;">Balance</td><td style="padding:8px 0;text-align:right;font-weight:600;color:var(--color-danger-icon);font-size:13px;">${App.formatCurrency(inv.total - inv.amount_paid, inv.currency)}</td></tr>` : ''}
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

    /* ═══════════════════════════════════════════════
       FORM — Create / Edit
       ═══════════════════════════════════════════════ */
    async renderForm(container, editId = null) {
        let clients = [];
        try { const res = await App.api('clients'); clients = res.data || []; } catch(e) {}

        let invoice = null;
        if (editId) {
            try { invoice = await App.api(`invoices/${editId}`); } catch(e) {
                container.innerHTML = `<div class="text-red">Error al cargar factura</div>`; return;
            }
        }

        const today = new Date().toISOString().split('T')[0];
        const nextWeek = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

        container.innerHTML = `
            <div style="margin-bottom:12px;">
                <a href="#invoices" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Facturas</a>
            </div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">${editId ? 'Editar Factura' : 'Nueva Factura'}</h1>
                    <p class="page-subtitle">${editId ? `Editando ${invoice?.invoice_number}` : 'Crea una factura para un cliente'}</p>
                </div>
                <button class="btn btn-secondary" onclick="window.App.navigate('invoices')">Cancelar</button>
            </div>

            <form id="invoice-form" class="table-outer">
                <div style="padding:var(--spacing-xl);">
                    <div class="grid-2">
                        <div class="form-group" style="grid-column:span 2;display:flex;align-items:center;gap:24px;background:var(--bg-hover);padding:12px 16px;border-radius:var(--radius-md);border:1px solid var(--color-border);">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:600;cursor:pointer;margin:0;">
                                <input type="checkbox" id="i_is_ecf" style="width:18px;height:18px;" ${invoice?.is_ecf ? 'checked' : ''} onchange="document.getElementById('ecf-type-wrapper').style.display = this.checked ? 'block' : 'none'">
                                ¿Emitir Factura Electrónica (e-CF)?
                            </label>
                            <div id="ecf-type-wrapper" style="display:${invoice?.is_ecf ? 'block' : 'none'};flex:1;">
                                <select id="i_ecf_type" class="form-control" style="max-width:320px;">
                                    <option value="31" ${invoice?.ecf_type == 31 ? 'selected' : ''}>e-Crédito Fiscal (B2B - Tipo 31)</option>
                                    <option value="32" ${invoice?.ecf_type == 32 || !invoice?.ecf_type ? 'selected' : ''}>e-Consumo (B2C - Tipo 32)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cliente *</label>
                            <select id="i_client_id" class="form-control" required>
                                <option value="">Seleccione un cliente</option>
                                ${clients.map(c => `<option value="${c.id}" ${invoice && invoice.client_id == c.id ? 'selected' : ''}>${c.company_name || c.contact_name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Moneda</label>
                            <select id="i_currency" class="form-control">
                                <option value="USD" ${invoice?.currency === 'USD' ? 'selected' : ''}>USD - Dólares</option>
                                <option value="DOP" ${invoice?.currency === 'DOP' ? 'selected' : ''}>DOP - Pesos Dominicanos</option>
                                <option value="EUR" ${invoice?.currency === 'EUR' ? 'selected' : ''}>EUR - Euros</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha de Emisión *</label>
                            <input type="date" id="i_issue_date" class="form-control" required value="${invoice?.issue_date?.split('T')[0] || today}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha de Vencimiento *</label>
                            <input type="date" id="i_due_date" class="form-control" required value="${invoice?.due_date?.split('T')[0] || nextWeek}">
                        </div>
                    </div>

                    <h3 class="mt-24 mb-16" style="font-size:15px;font-weight:600;border-bottom:1px solid var(--color-border);padding-bottom:8px;">Conceptos</h3>
                    <div id="invoice-items-container"></div>
                    <button type="button" class="btn btn-ghost mt-16" onclick="InvoicesModule.addItem()">+ Agregar Concepto</button>

                    <div class="grid-2 mt-24">
                        <div>
                            <div class="form-group">
                                <label class="form-label">Notas</label>
                                <textarea id="i_notes" class="form-control" rows="3">${invoice?.notes || ''}</textarea>
                            </div>
                        </div>
                        <div>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Descuento (%)</label>
                                    <input type="number" id="i_discount" class="form-control" value="${invoice?.discount_value || 0}" min="0" max="100" step="0.01" onchange="InvoicesModule.calculateTotals()" oninput="InvoicesModule.calculateTotals()">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Impuesto (%)</label>
                                    <input type="number" id="i_tax" class="form-control" value="${invoice?.tax_rate ?? 18}" min="0" max="100" step="0.01" onchange="InvoicesModule.calculateTotals()" oninput="InvoicesModule.calculateTotals()">
                                </div>
                            </div>
                            <div style="background:var(--bg-hover);padding:16px;border-radius:var(--radius-lg);margin-top:16px;">
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;"><span style="color:var(--color-text-muted)">Subtotal:</span><span id="calc_subtotal" class="font-semibold">0.00</span></div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;"><span style="color:var(--color-text-muted)">Descuento:</span><span id="calc_discount" class="font-semibold text-red">0.00</span></div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;"><span style="color:var(--color-text-muted)">Impuesto:</span><span id="calc_tax" class="font-semibold">0.00</span></div>
                                <div style="display:flex;justify-content:space-between;border-top:1px solid var(--color-border);padding-top:8px;margin-top:8px;"><span style="font-weight:700;font-size:18px;">Total:</span><span id="calc_total" style="font-weight:700;font-size:18px;color:var(--color-primary)">0.00</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-24">
                        <button type="submit" class="btn btn-primary">${editId ? 'Actualizar Factura' : 'Guardar Factura'}</button>
                    </div>
                </div>
            </form>
        `;

        // Initialize items
        this.items = [];
        if (invoice && invoice.items) {
            invoice.items.forEach((item, idx) => {
                this.items.push({ id: idx, desc: item.description, qty: item.quantity, price: item.unit_price });
            });
            this.renderItems();
            setTimeout(() => {
                invoice.items.forEach((item, idx) => {
                    const d = document.getElementById(`item_desc_${idx}`);
                    if (d) d.value = item.description;
                    const q = document.getElementById(`item_qty_${idx}`);
                    if (q) q.value = item.quantity;
                    const p = document.getElementById(`item_price_${idx}`);
                    if (p) p.value = item.unit_price;
                });
                this.calculateTotals();
            }, 50);
        } else {
            this.addItem();
        }

        document.getElementById('invoice-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const itemsToSave = this.items.map((item) => ({
                description: document.getElementById(`item_desc_${item.id}`)?.value || '',
                quantity: parseFloat(document.getElementById(`item_qty_${item.id}`)?.value) || 0,
                unit_price: parseFloat(document.getElementById(`item_price_${item.id}`)?.value) || 0
            })).filter(i => i.description.trim() !== '');

            if (itemsToSave.length === 0) { App.showToast('Debes agregar al menos un concepto', 'error'); return; }

            const payload = {
                client_id: document.getElementById('i_client_id').value,
                currency: document.getElementById('i_currency').value,
                issue_date: document.getElementById('i_issue_date').value,
                due_date: document.getElementById('i_due_date').value,
                discount_type: 'percentage',
                discount_value: document.getElementById('i_discount').value,
                tax_rate: document.getElementById('i_tax').value,
                notes: document.getElementById('i_notes').value,
                items: itemsToSave,
                is_ecf: document.getElementById('i_is_ecf').checked ? 1 : 0,
                ecf_type: document.getElementById('i_is_ecf').checked ? document.getElementById('i_ecf_type').value : null
            };

            try {
                if (editId) {
                    await App.api(`invoices/${editId}`, { method: 'PUT', body: payload });
                    App.showToast('Factura actualizada correctamente');
                } else {
                    const result = await App.api('invoices', { method: 'POST', body: payload });
                    App.showToast(result.email_sent ? 'Factura creada y enviada por email' : 'Factura creada correctamente');
                }
                window.App.navigate('invoices');
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
        const container = document.getElementById('invoice-items-container');
        if(!container) return;
        const values = {};
        this.items.forEach(item => {
            const d = document.getElementById(`item_desc_${item.id}`);
            if(d) { values[item.id] = { desc: d.value, qty: document.getElementById(`item_qty_${item.id}`).value, price: document.getElementById(`item_price_${item.id}`).value }; }
        });

        container.innerHTML = this.items.map(item => `
            <div style="display:flex;gap:12px;margin-bottom:12px;align-items:flex-start;">
                <div style="flex:1"><input type="text" id="item_desc_${item.id}" class="form-control" placeholder="Descripción del concepto..." required></div>
                <div style="width:100px"><input type="number" id="item_qty_${item.id}" class="form-control" placeholder="Cant." min="0.01" step="0.01" value="${item.qty || 1}" required oninput="InvoicesModule.calculateTotals()"></div>
                <div style="width:150px"><input type="number" id="item_price_${item.id}" class="form-control" placeholder="Precio" min="0" step="0.01" value="${item.price || ''}" required oninput="InvoicesModule.calculateTotals()"></div>
                <button type="button" class="btn-icon" style="color:var(--color-danger-icon);width:38px;height:38px;" onclick="InvoicesModule.removeItem(${item.id})">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>
        `).join('');

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
                subtotal += qty * price;
            });
        }
        const discountRate = parseFloat(document.getElementById('i_discount')?.value) || 0;
        const discountAmt = subtotal * (discountRate / 100);
        const taxRate = parseFloat(document.getElementById('i_tax')?.value) || 0;
        const taxAmt = (subtotal - discountAmt) * (taxRate / 100);
        const total = subtotal - discountAmt + taxAmt;

        const fmt = (v) => App.formatCurrency(v, '');
        document.getElementById('calc_subtotal').textContent = fmt(subtotal);
        document.getElementById('calc_discount').textContent = fmt(discountAmt);
        document.getElementById('calc_tax').textContent = fmt(taxAmt);
        document.getElementById('calc_total').textContent = fmt(total);
    },

    /* ═══════════════════════════════════════════════
       ACTIONS — Payment, Email, Duplicate, Delete, e-CF
       ═══════════════════════════════════════════════ */
    showPaymentModal(id, balance) {
        balance = parseFloat(balance) || 0;
        document.getElementById('payment-modal')?.remove();
        const modal = document.createElement('div');
        modal.id = 'payment-modal';
        modal.className = 'modal-overlay open';
        modal.innerHTML = `
            <div class="modal" style="max-width:420px;">
                <div class="modal-header">
                    <div class="modal-title">Registrar Pago</div>
                    <button class="btn-icon" style="width:28px;height:28px;" id="payment-cancel"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                </div>
                <div class="modal-body">
                    <p style="margin:0 0 20px 0;color:var(--color-text-secondary);font-size:13px;">Balance pendiente: <strong style="color:var(--color-danger-icon)">${App.formatCurrency(balance, '')}</strong></p>
                    <div class="form-group">
                        <label class="form-label">Monto a registrar</label>
                        <input type="number" id="payment-amount" class="form-control" value="${balance.toFixed(2)}" min="0.01" step="0.01" style="font-size:18px;font-weight:600;text-align:center;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Método de pago</label>
                        <select id="payment-method" class="form-control">
                            <option value="bank_transfer">Transferencia Bancaria</option>
                            <option value="cash">Efectivo</option>
                            <option value="credit_card">Tarjeta de Crédito</option>
                            <option value="check">Cheque</option>
                            <option value="paypal">PayPal</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" id="payment-cancel-2">Cancelar</button>
                    <button class="btn btn-primary" id="payment-confirm">Confirmar Pago</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        setTimeout(() => document.getElementById('payment-amount')?.focus(), 100);
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
        document.getElementById('payment-cancel').addEventListener('click', () => modal.remove());
        document.getElementById('payment-cancel-2').addEventListener('click', () => modal.remove());
        document.getElementById('payment-confirm').addEventListener('click', () => {
            const amount = parseFloat(document.getElementById('payment-amount').value);
            const method = document.getElementById('payment-method').value;
            if (isNaN(amount) || amount <= 0) { App.showToast('Monto inválido', 'error'); return; }
            modal.remove();
            this.markAsPaid(id, amount, method);
        });
    },

    async markAsPaid(id, amount, method = 'other') {
        try {
            await App.api(`invoices/${id}/payment`, { method: 'POST', body: { amount, payment_method: method } });
            App.showToast('Pago registrado correctamente');
            App.navigate(`invoices/${id}`);
        } catch(e) {}
    },

    async sendEmail(id) {
        this._showConfirm('¿Deseas enviar esta factura por correo al cliente?', async () => {
            App.showToast('Enviando correo...', 'success');
            try {
                await App.api(`invoices/${id}/send-email`, { method: 'POST' });
                App.showToast('Correo enviado exitosamente');
                App.navigate(`invoices/${id}`);
            } catch(e) {}
        });
    },

    async duplicateInvoice(id) {
        this._showConfirm('¿Duplicar esta factura?', async () => {
            try {
                await App.api(`invoices/${id}/duplicate`, { method: 'POST' });
                App.showToast('Factura duplicada correctamente');
                window.App.navigate('invoices');
            } catch(e) {}
        });
    },

    async deleteInvoice(id) {
        this._showConfirm('¿Estás seguro de eliminar esta factura? Esta acción no se puede deshacer.', async () => {
            try {
                await App.api(`invoices/${id}`, { method: 'DELETE' });
                App.showToast('Factura eliminada');
                window.App.navigate('invoices');
            } catch(e) {}
        });
    },

    async processEcf(id) {
        try {
            window.App.showToast('Enviando comprobante a la DGII...', 'info');
            const res = await window.App.api(`invoices/${id}/process-ecf`, { method: 'POST' });
            if (res.success && res.status === 'accepted') {
                window.App.showToast('¡Comprobante aprobado por la DGII!', 'success');
            } else if (res.status === 'portal_pending') {
                window.App.showToast('FC<250k firmada. Súbela al portal DGII.', 'info');
            } else if (res.status === 'pending') {
                window.App.showToast('Enviado. Pendiente de aprobación DGII.', 'info');
            } else if (res.status === 'contingency') {
                window.App.showToast('Guardado en contingencia.', 'warning');
            } else if (res.status === 'rejected') {
                window.App.showToast('Rechazado por la DGII.', 'error');
            } else {
                window.App.showToast('Procesamiento completado.');
            }
            this.renderDetails(document.getElementById('app-content'), id);
        } catch(e) {
            window.App.showToast('Error procesando e-CF', 'error');
        }
    },

    async checkEcfStatus(id) {
        try {
            window.App.showToast('Verificando estado en DGII...', 'info');
            const res = await window.App.api(`invoices/${id}/ecf-status`);
            if (res.status === 'accepted') {
                window.App.showToast('Aprobado por la DGII.', 'success');
            } else if (res.status === 'rejected') {
                window.App.showToast('Rechazado por la DGII.', 'error');
            } else {
                window.App.showToast('Aún en procesamiento.', 'info');
            }
            this.renderDetails(document.getElementById('app-content'), id);
        } catch(e) {
            window.App.showToast('Error verificando estado', 'error');
        }
    },

    _showConfirm(message, onConfirm) {
        document.getElementById('confirm-modal')?.remove();
        const modal = document.createElement('div');
        modal.id = 'confirm-modal';
        modal.className = 'modal-overlay open';
        modal.innerHTML = `
            <div class="modal" style="max-width:400px;">
                <div class="modal-body" style="text-align:center;padding:32px;">
                    <p style="font-size:15px;margin:0 0 24px 0;line-height:1.5;color:var(--color-text-primary);">${message}</p>
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

window.InvoicesModule = InvoicesModule;
export default InvoicesModule;
