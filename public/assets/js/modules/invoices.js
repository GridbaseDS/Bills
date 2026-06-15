const InvoicesModule = {
    statusLabel: (s) => ({draft:'Borrador',sent:'Pendiente de Pago',paid:'Pagada',overdue:'Vencida',partial:'Pago Parcial',cancelled:'Cancelada'}[s]||s),
    dgiiLabel: (s) => ({accepted:'Aceptado',rejected:'Rechazado',pending:'Procesando',contingency:'Contingencia',portal_pending:'Portal Pendiente'}[s]||s||'—'),
    dgiiBadgeClass: (s) => ({accepted:'badge-paid',rejected:'badge-overdue',pending:'badge-sent',contingency:'badge-draft',portal_pending:'badge-sent'}[s]||''),

    async render(container, id) {
        if (id === 'new' || id === 'nueva') { this.renderForm(container); return; }
        if (id && (id.startsWith('edit/') || id.startsWith('editar/'))) { this.renderForm(container, id.replace(/^(edit|editar)\//, '')); return; }
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
                    <div style="display:flex; gap:12px;">
                        <a href="/api/invoices/export/csv" class="btn btn-secondary" target="_blank" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Exportar Excel
                        </a>
                        <button class="btn btn-primary" onclick="window.App.navigate('facturas/nueva')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Nueva Factura
                        </button>
                    </div>
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
                                <th style="width: 40px; padding-right: 0;"><input type="checkbox" id="bulk-select-all" onclick="InvoicesModule.toggleAllSelection(this.checked)" style="width:16px;height:16px;cursor:pointer;accent-color:#111827;"></th>
                                <th>Número</th><th>Cliente</th><th>Emisión</th><th>Vencimiento</th><th>Monto</th><th>Estado</th><th>DGII</th><th></th>
                            </tr></thead>
                            <tbody id="inv-tbody"></tbody>
                        </table>
                    </div>
                    <div id="inv-mobile-list" class="mobile-card-list"></div>
                </div>

                <!-- Floating Bulk Actions Bar -->
                <div id="bulk-actions-bar" class="bulk-actions-bar">
                    <div class="bulk-count" id="bulk-selected-count">0 seleccionadas</div>
                    <div class="bulk-actions-btns">
                        <button class="bulk-btn bulk-btn-primary" onclick="InvoicesModule.executeBulkAction('send_email')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            Enviar
                        </button>
                        <button class="bulk-btn bulk-btn-primary" onclick="InvoicesModule.executeBulkAction('mark_as_paid')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Cobrar
                        </button>
                        <button class="bulk-btn bulk-btn-primary" onclick="InvoicesModule.executeBulkAction('process_ecf')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            e-CF
                        </button>
                        <button class="bulk-btn bulk-btn-danger" onclick="InvoicesModule.executeBulkAction('delete')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            Eliminar
                        </button>
                    </div>
                </div>
            `;

            this._allInvoices = allInvoices;
            this._currentStatus = '';
            this.selectedIds = []; // Reset selected IDs on list render
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

        // Mobile card list (CSS shows/hides based on media query)
        const listEl = document.getElementById('inv-mobile-list');
        if (listEl) {
            listEl.innerHTML = filtered.length > 0 ? filtered.map(i => `
                <a href="#facturas/${i.id}" class="mobile-card">
                    <div class="mobile-card-top">
                        <div class="mobile-card-id" style="display:flex;align-items:center;gap:10px;">
                            <input type="checkbox" class="bulk-select-item-mobile" data-id="${i.id}" onclick="event.stopPropagation(); InvoicesModule.toggleItemSelect(${i.id})" style="width: 18px; height: 18px; cursor: pointer; accent-color: #111827;" ${this.isSelected(i.id) ? 'checked' : ''}>
                            ${i.is_ecf ? (i.encf || i.invoice_number) : i.invoice_number}
                            ${i.is_ecf ? '<span class="badge" style="background:var(--color-primary);color:#FFF;font-size:8px;padding:2px 5px;">e-CF</span>' : ''}
                        </div>
                        <span class="badge badge-${i.status}">${this.statusLabel(i.status)}</span>
                    </div>
                    <div class="mobile-card-middle">
                        <div class="mobile-card-avatar">${(i.company_name || i.contact_name || '?').charAt(0).toUpperCase()}</div>
                        <div class="mobile-card-info">
                            <div class="mobile-card-name">${i.company_name || i.contact_name}</div>
                            <div class="mobile-card-sub">Vence: ${App.formatDate(i.due_date)}</div>
                        </div>
                    </div>
                    <div class="mobile-card-bottom">
                        <div class="mobile-card-amount">
                            <div>${App.formatCurrency(i.total, i.currency)}</div>
                            ${i.currency !== 'DOP' && i.exchange_rate && i.exchange_rate != 1 ? `
                                <div style="font-size:10px;color:var(--color-text-muted);font-weight:400;margin-top:2px;text-align:right;">
                                    ≈ ${App.formatCurrency(i.total * i.exchange_rate, 'DOP')}
                                </div>
                            ` : ''}
                        </div>
                        <svg class="mobile-card-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </a>
            `).join('') : '<div class="text-center text-muted" style="padding:48px;">No hay facturas que coincidan</div>';
        }

        // Desktop table rows (CSS shows/hides based on media query)
        const tbody = document.getElementById('inv-tbody');
        if (!tbody) return;

        tbody.innerHTML = filtered.length > 0 ? filtered.map(i => `
            <tr>
                <td style="width: 40px; padding-right: 0;">
                    <input type="checkbox" class="bulk-select-item" data-id="${i.id}" onclick="event.stopPropagation(); InvoicesModule.toggleItemSelect(${i.id})" style="width: 16px; height: 16px; cursor: pointer; accent-color: #111827;" ${this.isSelected(i.id) ? 'checked' : ''}>
                </td>
                <td>
                    <a href="#facturas/${i.id}" class="link-id">${i.is_ecf ? (i.encf || i.invoice_number) : i.invoice_number}</a>
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
                <td style="font-weight:600;color:var(--color-text-primary)">
                    <div>${App.formatCurrency(i.total, i.currency)}</div>
                    ${i.currency !== 'DOP' && i.exchange_rate && i.exchange_rate != 1 ? `
                        <div style="font-size:11px;color:var(--color-text-muted);font-weight:400;margin-top:2px;">
                            ≈ DOP ${App.formatCurrency(i.total * i.exchange_rate, 'DOP')}
                        </div>
                    ` : ''}
                </td>
                <td><span class="badge badge-${i.status}">${this.statusLabel(i.status)}</span></td>
                <td>${i.is_ecf ? `<span class="badge ${this.dgiiBadgeClass(i.dgii_status)}" style="font-size:9px;">${this.dgiiLabel(i.dgii_status)}</span>` : '<span style="color:var(--color-text-tertiary)">—</span>'}</td>
                <td>
                        <div class="row-actions" style="gap: 6px;">
                            <a href="#facturas/${i.id}" class="btn-icon" style="width:28px;height:28px;" title="Ver"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>
                            <a href="/api/invoices/${i.id}/pdf?download=1" target="_blank" class="btn btn-secondary" style="padding:4px 8px; font-size:11px; height:auto; display:inline-flex; align-items:center; gap:4px; text-decoration:none;" title="Descargar PDF"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Descargar PDF</a>
                            <button class="btn-icon" style="width:28px;height:28px;" onclick="InvoicesModule.deleteInvoice(${i.id})" title="Eliminar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger-icon)" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                        </div>
                </td>
            </tr>
        `).join('') : '<tr><td colspan="9" class="text-center text-muted" style="padding:48px;">No hay facturas que coincidan</td></tr>';

        // Update bulk action bar count/visibility
        this.updateBulkActionBar();
    },

    /* ═══════════════════════════════════════════════
       DETAIL VIEW
       ═══════════════════════════════════════════════ */
    async renderDetails(container, id) {
        try {
            const inv = await App.api(`invoices/${id}`);
            container.innerHTML = `
                <div style="margin-bottom:12px;">
                    <a href="#facturas" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Facturas</a>
                    <span style="color:var(--color-text-muted);font-size:13px;"> / </span>
                    <span style="font-size:13px;">${inv.invoice_number}</span>
                </div>
                <div class="page-header">
                    <div>
                        <h1 class="page-title">${inv.is_ecf ? `e-CF ${inv.encf || inv.invoice_number}` : `Factura ${inv.invoice_number}`}</h1>
                        <p class="page-subtitle">Emitida el ${App.formatDate(inv.issue_date)}</p>
                    </div>
                    <div class="invoice-actions" style="display:flex;gap:8px;flex-wrap:wrap;width:100%;">
                        <a href="/api/invoices/${id}/pdf" target="_blank" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            Ver PDF
                        </a>
                        <a href="/api/invoices/${id}/pdf?template=thermal" target="_blank" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            Ticket Térmico
                        </a>
                        <button class="btn btn-secondary btn-sm" onclick="InvoicesModule.sendEmail(${id})" style="flex:1;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            Enviar
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="InvoicesModule.duplicateInvoice(${id})" style="flex:1;justify-content:center;">Duplicar</button>
                        ${inv.is_ecf && inv.encf && inv.ecf_type != 34 && inv.ecf_type != 33 ? `
                            <button class="btn btn-secondary btn-sm" style="color:var(--color-danger-icon);border-color:rgba(239,68,68,0.2);flex:1;justify-content:center;" onclick="InvoicesModule.issueCreditNote(${id})">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M9 14L4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5v0a5.5 5.5 0 0 1-5.5 5.5H11"/></svg>
                                Nota de Crédito
                            </button>
                        ` : ''}
                        <a href="#facturas/edit/${id}" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center;">Editar</a>
                        ${inv.status !== 'paid' ? `<button class="btn btn-primary btn-sm" style="width:100%;justify-content:center;margin-top:4px;" onclick="InvoicesModule.showPaymentModal(${id}, ${(inv.total || 0) - (inv.amount_paid || 0)})">Registrar Pago</button>` : ''}
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
                            ${inv.is_ecf && inv.encf ? `
                                <a href="/api/invoices/${inv.id}/download-xml" class="btn btn-secondary btn-sm" download>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    Descargar XML
                                </a>
                            ` : ''}
                            ${inv.dgii_status === 'accepted' ? `<span style="color:var(--color-success-icon);font-size:13px;font-weight:600;">Certificado DGII</span>` : ''}
                        </div>
                    </div>
                </div>
                ` : ''}

                <div class="table-outer mb-24">
                    <div style="padding:var(--spacing-xl);">
                        <div class="grid-2">
                            <div class="invoice-meta-left">
                                <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Facturado a</div>
                                <p style="font-size:16px;font-weight:600;margin:0;">${inv.company_name || inv.contact_name}</p>
                                <p style="margin:4px 0 0 0;color:var(--color-text-secondary);font-size:13px;">${inv.email || ''}</p>
                            </div>
                            <div class="invoice-meta-right text-right">
                                <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Detalles</div>
                                <p style="margin:0;font-size:13px;"><strong>Vence:</strong> ${App.formatDate(inv.due_date)}</p>
                                <p style="margin:4px 0 0 0;font-size:13px;"><strong>Estado:</strong> <span class="badge badge-${inv.status}">${this.statusLabel(inv.status)}</span></p>
                                ${inv.sent_at ? `<p style="margin:4px 0 0 0;color:var(--color-success-icon);font-size:12px;">Enviado ${App.formatDate(inv.sent_at)}${inv.sent_via && inv.sent_via.includes('whatsapp') ? ' <span title="Enviado por WhatsApp" style="font-size:11px;font-weight:500;"> (WhatsApp)</span>' : ''}</p>` : `<p style="margin:4px 0 0 0;color:var(--color-text-muted);font-size:12px;">No enviado</p>`}
                            </div>
                        </div>

                        <div class="mt-24">
                            <table class="data-table responsive-invoice-table" style="border:1px solid var(--color-border);border-radius:var(--radius-lg);overflow:hidden;width:100%;">
                                <thead><tr><th>Descripción</th><th class="text-right">Cant.</th><th class="text-right">Precio</th><th class="text-right">Total</th></tr></thead>
                                <tbody>
                                    ${inv.items.map(item => `
                                        <tr>
                                            <td data-label="Descripción" style="color:var(--color-text-primary);font-weight:600;white-space:normal;font-size:14px;">${item.description}</td>
                                            <td data-label="Cant." class="text-right">${item.quantity}</td>
                                            <td data-label="Precio" class="text-right">${App.formatCurrency(item.unit_price, inv.currency)}</td>
                                            <td data-label="Total" class="text-right font-bold" style="color:var(--color-primary);">${App.formatCurrency(item.amount, inv.currency)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>

                        <div class="grid-2 mt-24">
                            <div>
                                ${inv.notes ? `<div style="font-size:11px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Notas</div><p style="white-space:pre-wrap;font-size:13px;color:var(--color-text-secondary);">${inv.notes}</p>` : ''}
                            </div>
                            <div class="invoice-totals-wrapper">
                                <table class="invoice-totals-table" style="width:100%;border-collapse:collapse;">
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
        
        let rates = {};
        try {
            const ratesRes = await App.api('currency/rates');
            if (ratesRes.success) { rates = ratesRes.rates || {}; InvoicesModule.rates = rates; }
        } catch(e) {}
        
        try { this.availableItems = await App.api('items'); } catch(e) { this.availableItems = []; }

        const today = window.App.state.settings?.server_date_dr || new Date().toISOString().split('T')[0];
        
        // Calculate nextWeek properly by adding 7 days to today
        const parsedToday = new Date(today + 'T00:00:00');
        const dNextWeek = new Date(parsedToday.getTime() + 7 * 24 * 60 * 60 * 1000);
        const nextWeek = dNextWeek.getFullYear() + '-' + String(dNextWeek.getMonth() + 1).padStart(2, '0') + '-' + String(dNextWeek.getDate()).padStart(2, '0');

        let invoice = null;
        if (editId) {
            try { invoice = await App.api(`invoices/${editId}`); } catch(e) {
                container.innerHTML = `<div class="text-red">Error al cargar factura</div>`; return;
            }
        } else if (this._creditNotePrefill) {
            invoice = { ...this._creditNotePrefill };
            invoice.is_ecf = 1;
            invoice.ecf_type = 34;
            invoice.modified_ncf = this._creditNotePrefill.encf || this._creditNotePrefill.invoice_number;
            invoice.modification_code = 1;
            invoice.issue_date = today;
            invoice.due_date = today;
            this._creditNotePrefill = null;
        }

        container.innerHTML = `
            <div style="margin-bottom:12px;">
                <a href="#facturas" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Facturas</a>
            </div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">${editId ? 'Editar Factura' : 'Nueva Factura'}</h1>
                    <p class="page-subtitle">${editId ? `Editando ${invoice?.invoice_number}` : 'Crea una factura para un cliente'}</p>
                </div>
                <button class="btn btn-secondary" onclick="window.App.navigate('facturas')">Cancelar</button>
            </div>

            <form id="invoice-form" class="form-card">
                <div style="padding:var(--spacing-xl);">
                    <div class="grid-2">
                        <div class="form-group" style="grid-column:span 2;display:flex;align-items:center;gap:24px;background:var(--bg-hover);padding:12px 16px;border-radius:var(--radius-md);border:1px solid var(--color-border);">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:600;cursor:pointer;margin:0;">
                                <input type="checkbox" id="i_is_ecf" style="width:18px;height:18px;" ${invoice?.is_ecf || !editId ? 'checked' : ''} onchange="document.getElementById('ecf-type-wrapper').style.display = this.checked ? 'block' : 'none'; InvoicesModule.onEcfTypeChange();">
                                Emitir Factura Electronica (e-CF)
                            </label>
                            <div id="ecf-type-wrapper" style="display:${invoice?.is_ecf || !editId ? 'block' : 'none'};flex:1;">
                                <select id="i_ecf_type" class="form-control" style="max-width:320px;" onchange="InvoicesModule.onEcfTypeChange()">
                                    <option value="31" ${invoice?.ecf_type == 31 ? 'selected' : ''}>Credito Fiscal (B2B - Tipo 31)</option>
                                    <option value="32" ${invoice?.ecf_type == 32 || !invoice?.ecf_type ? 'selected' : ''}>Consumo (B2C - Tipo 32)</option>
                                    <option value="33" ${invoice?.ecf_type == 33 ? 'selected' : ''}>Nota de Debito (Tipo 33)</option>
                                    <option value="34" ${invoice?.ecf_type == 34 ? 'selected' : ''}>Nota de Credito (Tipo 34)</option>
                                    <option value="41" ${invoice?.ecf_type == 41 ? 'selected' : ''}>Compras (Tipo 41)</option>
                                    <option value="43" ${invoice?.ecf_type == 43 ? 'selected' : ''}>Gastos Menores (Tipo 43)</option>
                                    <option value="44" ${invoice?.ecf_type == 44 ? 'selected' : ''}>Regimenes Especiales (Tipo 44)</option>
                                    <option value="45" ${invoice?.ecf_type == 45 ? 'selected' : ''}>Gubernamental (Tipo 45)</option>
                                    <option value="46" ${invoice?.ecf_type == 46 ? 'selected' : ''}>Exportaciones (Tipo 46)</option>
                                    <option value="47" ${invoice?.ecf_type == 47 ? 'selected' : ''}>Pagos al Exterior (Tipo 47)</option>
                                </select>
                            </div>
                        </div>
                        <div id="ecf-ncf-mod-wrapper" class="grid-2" style="grid-column:span 2;display:${invoice?.ecf_type == 33 || invoice?.ecf_type == 34 ? 'grid' : 'none'};gap:16px;background:var(--bg-hover);padding:12px 16px;border-radius:var(--radius-md);border:1px solid var(--color-border);">
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">e-NCF Modificado *</label>
                                <input type="text" id="i_ncf_modificado" class="form-control" placeholder="Ej: E310000000001" value="${invoice?.modified_ncf || ''}" maxlength="13">
                                <small style="color:var(--color-text-muted);font-size:11px;">e-NCF del documento original a modificar</small>
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Codigo de Modificacion *</label>
                                <select id="i_codigo_modificacion" class="form-control">
                                    <option value="1" ${invoice?.modification_code == 1 ? 'selected' : ''}>1 - Anula Totalmente</option>
                                    <option value="2" ${invoice?.modification_code == 2 ? 'selected' : ''}>2 - Corrige Texto</option>
                                    <option value="3" ${invoice?.modification_code == 3 ? 'selected' : ''}>3 - Corrige Montos</option>
                                    <option value="4" ${invoice?.modification_code == 4 ? 'selected' : ''}>4 - Reemplaza NCF</option>
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
                                <option value="DOP" ${invoice?.currency === 'DOP' || !invoice?.currency ? 'selected' : ''}>DOP - Pesos Dominicanos</option>
                                <option value="USD" ${invoice?.currency === 'USD' ? 'selected' : ''}>USD - Dolares</option>
                                <option value="EUR" ${invoice?.currency === 'EUR' ? 'selected' : ''}>EUR - Euros</option>
                            </select>
                        </div>
                        <div class="form-group" id="exchange-rate-wrapper" style="display: ${invoice?.currency && invoice?.currency !== 'DOP' ? 'block' : 'none'};">
                            <label class="form-label">Tasa de Cambio</label>
                            <div style="display:flex;gap:12px;align-items:center;">
                                <input type="number" id="i_exchange_rate" class="form-control" step="0.0001" min="0.0001" value="${invoice?.exchange_rate || '1.0'}" style="flex:1;">
                                <span style="font-size:12px;color:var(--color-text-muted);white-space:nowrap;" id="live-rate-hint">
                                    ${invoice?.exchange_rate ? `Tasa: ${invoice.exchange_rate}` : ''}
                                </span>
                            </div>
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
                                    <label class="form-label">ITBIS (%)</label>
                                    <input type="number" id="i_tax" class="form-control" value="${invoice?.tax_rate ?? 18}" min="0" max="100" step="0.01" onchange="InvoicesModule.calculateTotals()" oninput="InvoicesModule.calculateTotals()">
                                    <small id="itbis_hint" style="color:var(--color-text-muted);font-size:11px;display:none;"></small>
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

        // Initialize currency selector live rates logic
        const currencySelect = document.getElementById('i_currency');
        const rateWrapper = document.getElementById('exchange-rate-wrapper');
        const rateInput = document.getElementById('i_exchange_rate');
        const rateHint = document.getElementById('live-rate-hint');
        
        if (currencySelect) {
            currencySelect.addEventListener('change', () => {
                const currency = currencySelect.value;
                if (currency === 'DOP') {
                    if (rateWrapper) rateWrapper.style.display = 'none';
                    if (rateInput) rateInput.value = '1.000000';
                    if (rateHint) rateHint.textContent = '';
                } else {
                    if (rateWrapper) rateWrapper.style.display = 'block';
                    const activeRates = InvoicesModule.rates || {};
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

        // Add datalist to document body if not exists
        if (!document.getElementById('catalog_items_list')) {
            const datalist = document.createElement('datalist');
            datalist.id = 'catalog_items_list';
            datalist.innerHTML = this.availableItems.map(i => `<option value="${i.name}">${window.App.formatCurrency(i.price, 'DOP')}</option>`).join('');
            document.body.appendChild(datalist);
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
                exchange_rate: parseFloat(document.getElementById('i_exchange_rate')?.value) || 1.0,
                issue_date: document.getElementById('i_issue_date').value,
                due_date: document.getElementById('i_due_date').value,
                discount_type: 'percentage',
                discount_value: document.getElementById('i_discount').value,
                tax_rate: document.getElementById('i_tax').value,
                notes: document.getElementById('i_notes').value,
                items: itemsToSave,
                is_ecf: document.getElementById('i_is_ecf').checked ? 1 : 0,
                ecf_type: document.getElementById('i_is_ecf').checked ? document.getElementById('i_ecf_type').value : null,
                modified_ncf: document.getElementById('i_ncf_modificado')?.value || null,
                modification_code: document.getElementById('i_codigo_modificacion')?.value || null
            };

            // Disable all form buttons and show loading feedback
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const cancelBtn = e.target.closest('.form-card')?.previousElementSibling?.querySelector('.btn-secondary');
            const origBtnText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<span class="spinner" style="width:14px;height:14px;border-width:2px;margin-right:8px;"></span> ${editId ? 'Actualizando...' : 'Guardando...'}`;
            }
            if (cancelBtn) cancelBtn.style.pointerEvents = 'none';
            // Disable all other buttons in the form
            const formBtns = e.target.querySelectorAll('button:not([type="submit"])');
            formBtns.forEach(b => b.disabled = true);

            try {
                if (editId) {
                    await App.api(`invoices/${editId}`, { method: 'PUT', body: payload });
                    App.showToast('Factura actualizada correctamente');
                } else {
                    const result = await App.api('invoices', { method: 'POST', body: payload });
                    App.showToast(result.email_sent ? 'Factura creada y enviada al cliente ✓' : 'Factura creada correctamente');
                }
                window.App.navigate('facturas');
            } catch (err) {
                // Re-enable buttons on error
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origBtnText;
                }
                if (cancelBtn) cancelBtn.style.pointerEvents = '';
                formBtns.forEach(b => b.disabled = false);
            }
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
                <div style="flex:1"><input type="text" id="item_desc_${item.id}" list="catalog_items_list" class="form-control" placeholder="Descripción del concepto..." required oninput="InvoicesModule.onItemDescChange(${item.id})"></div>
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

    onItemDescChange(itemId) {
        const descInput = document.getElementById(`item_desc_${itemId}`);
        const priceInput = document.getElementById(`item_price_${itemId}`);
        if (!descInput || !priceInput || !this.availableItems) return;

        const val = descInput.value;
        const matchedItem = this.availableItems.find(i => i.name === val);
        if (matchedItem) {
            priceInput.value = matchedItem.price;
            this.calculateTotals();
        }
    },

    onEcfTypeChange() {
        const isEcf = document.getElementById('i_is_ecf')?.checked;
        const type = document.getElementById('i_ecf_type')?.value;
        const taxInput = document.getElementById('i_tax');
        const hint = document.getElementById('itbis_hint');
        const modWrapper = document.getElementById('ecf-ncf-mod-wrapper');

        // Show/hide NC/ND fields
        if (modWrapper) {
            modWrapper.style.display = (type === '33' || type === '34') ? 'grid' : 'none';
        }

        if (!isEcf || !taxInput) return;

        // Types exempt from ITBIS (0%)
        const exemptTypes = ['43', '44', '46', '47'];
        // Types that require 18% ITBIS
        const taxedTypes = ['31', '32', '33', '34', '41', '45'];

        if (exemptTypes.includes(type)) {
            taxInput.value = '0';
            taxInput.readOnly = true;
            taxInput.style.backgroundColor = 'var(--bg-hover)';
            taxInput.style.color = 'var(--color-text-muted)';
            if (hint) {
                hint.style.display = 'block';
                hint.textContent = 'Este tipo de comprobante es exento de ITBIS';
            }
        } else if (taxedTypes.includes(type)) {
            taxInput.readOnly = false;
            taxInput.style.backgroundColor = '';
            taxInput.style.color = '';
            if (taxInput.value === '0') taxInput.value = '18';
            if (hint) hint.style.display = 'none';
        } else {
            taxInput.readOnly = false;
            taxInput.style.backgroundColor = '';
            taxInput.style.color = '';
            if (hint) hint.style.display = 'none';
        }

        this.calculateTotals();
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
            App.navigate(`facturas/${id}`);
        } catch(e) {}
    },

    async sendEmail(id) {
        this._showConfirm('¿Deseas enviar esta factura al cliente por correo y WhatsApp?', async () => {
            App.showToast('Enviando factura...', 'success');
            try {
                const res = await App.api(`invoices/${id}/send-email`, { method: 'POST' });
                const via = res.sent_via || 'email';
                if (via.includes('whatsapp')) {
                    App.showToast('Factura enviada por correo y WhatsApp ✓');
                } else {
                    App.showToast('Factura enviada por correo ✓');
                }
                App.navigate(`facturas/${id}`);
            } catch(e) {}
        });
    },

    async duplicateInvoice(id) {
        this._showConfirm('¿Duplicar esta factura?', async () => {
            try {
                await App.api(`invoices/${id}/duplicate`, { method: 'POST' });
                App.showToast('Factura duplicada correctamente');
                window.App.navigate('facturas');
            } catch(e) {}
        });
    },

    async issueCreditNote(id) {
        this._showConfirm('¿Deseas emitir una Nota de Crédito para esta factura?', async () => {
            try {
                window.App.showToast('Cargando datos de factura...', 'info');
                const invoice = await App.api(`invoices/${id}`);
                this._creditNotePrefill = invoice;
                window.App.navigate('facturas/nueva');
            } catch(e) {
                window.App.showToast('Error al cargar datos de la factura', 'error');
            }
        });
    },

    async deleteInvoice(id) {
        this._showConfirm('¿Estás seguro de eliminar esta factura? Esta acción no se puede deshacer.', async () => {
            try {
                await App.api(`invoices/${id}`, { method: 'DELETE' });
                App.showToast('Factura eliminada');
                window.App.navigate('facturas');
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
    },

    isSelected(id) {
        return this.selectedIds && this.selectedIds.includes(id);
    },

    toggleAllSelection(isChecked) {
        const checkboxes = document.querySelectorAll('.bulk-select-item, .bulk-select-item-mobile');
        this.selectedIds = [];
        
        if (isChecked) {
            const search = (document.getElementById('inv-search')?.value || '').toLowerCase();
            const status = this._currentStatus || '';
            
            let filtered = this._allInvoices || [];
            if (search) {
                filtered = filtered.filter(i =>
                    (i.invoice_number||'').toLowerCase().includes(search) ||
                    (i.company_name||'').toLowerCase().includes(search) ||
                    (i.contact_name||'').toLowerCase().includes(search)
                );
            }
            if (status) filtered = filtered.filter(i => i.status === status);
            
            this.selectedIds = filtered.map(i => i.id);
        }
        
        checkboxes.forEach(cb => {
            cb.checked = isChecked;
        });
        
        this.updateBulkActionBar();
    },

    toggleItemSelect(id) {
        if (!this.selectedIds) this.selectedIds = [];
        const index = this.selectedIds.indexOf(id);
        if (index > -1) {
            this.selectedIds.splice(index, 1);
        } else {
            this.selectedIds.push(id);
        }
        
        const cbs = document.querySelectorAll(`.bulk-select-item[data-id="${id}"], .bulk-select-item-mobile[data-id="${id}"]`);
        cbs.forEach(cb => {
            cb.checked = this.selectedIds.includes(id);
        });
        
        const selectAllCb = document.getElementById('bulk-select-all');
        if (selectAllCb) {
            const search = (document.getElementById('inv-search')?.value || '').toLowerCase();
            const status = this._currentStatus || '';
            let filtered = this._allInvoices || [];
            if (search) {
                filtered = filtered.filter(i =>
                    (i.invoice_number||'').toLowerCase().includes(search) ||
                    (i.company_name||'').toLowerCase().includes(search) ||
                    (i.contact_name||'').toLowerCase().includes(search)
                );
            }
            if (status) filtered = filtered.filter(i => i.status === status);
            
            selectAllCb.checked = filtered.length > 0 && filtered.every(i => this.selectedIds.includes(i.id));
        }
        
        this.updateBulkActionBar();
    },

    updateBulkActionBar() {
        const count = this.selectedIds ? this.selectedIds.length : 0;
        const bar = document.getElementById('bulk-actions-bar');
        const countEl = document.getElementById('bulk-selected-count');
        
        if (countEl) {
            countEl.textContent = `${count} ${count === 1 ? 'seleccionada' : 'seleccionadas'}`;
        }
        
        if (bar) {
            if (count > 0) {
                bar.classList.add('active');
            } else {
                bar.classList.remove('active');
            }
        }
    },

    async executeBulkAction(action) {
        if (!this.selectedIds || this.selectedIds.length === 0) {
            App.showToast('No hay facturas seleccionadas', 'error');
            return;
        }
        
        const count = this.selectedIds.length;
        let confirmMsg = '';
        switch (action) {
            case 'delete':
                confirmMsg = `¿Estás seguro de eliminar ${count} ${count === 1 ? 'factura' : 'facturas'}? Esta acción no se puede deshacer.`;
                break;
            case 'mark_as_paid':
                confirmMsg = `¿Deseas registrar pago y marcar como pagadas ${count} ${count === 1 ? 'factura' : 'facturas'}?`;
                break;
            case 'send_email':
                confirmMsg = `¿Deseas enviar ${count} ${count === 1 ? 'factura' : 'facturas'} por correo/WhatsApp a sus respectivos clientes?`;
                break;
            case 'process_ecf':
                confirmMsg = `¿Deseas procesar con la DGII las ${count} ${count === 1 ? 'factura electrónica' : 'facturas electrónicas'} seleccionadas?`;
                break;
        }
        
        this._showConfirm(confirmMsg, async () => {
            App.showToast('Procesando acción en lote...', 'info');
            try {
                const res = await App.api('invoices/bulk', {
                    method: 'POST',
                    body: { ids: this.selectedIds, action: action }
                });
                if (res.success) {
                    App.showToast(res.message || 'Acción completada con éxito', 'success');
                    this.selectedIds = [];
                    const selectAllCb = document.getElementById('bulk-select-all');
                    if (selectAllCb) selectAllCb.checked = false;
                    this.updateBulkActionBar();
                    
                    // Refresh list
                    this.renderList(document.getElementById('app-content'));
                } else {
                    App.showToast(res.error || 'Error al procesar acción masiva', 'error');
                }
            } catch (e) {
                App.showToast('Ocurrió un error en el servidor', 'error');
            }
        });
    }
};

window.InvoicesModule = InvoicesModule;
export default InvoicesModule;
