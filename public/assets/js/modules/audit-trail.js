/**
 * GridBase Digital Solutions — Bills System
 * Módulo: Seguimiento de Comprobante (Document Lineage & Smart Audit Trail)
 * Permite rastrear el ciclo de vida documental: Cotización -> Factura -> e-CF -> Pagos -> Notas de Crédito/Débito -> Anulación
 */

const AuditTrailModule = {
    currentQuery: null,
    searchDebounceTimer: null,

    async render(container, voucherParam = null) {
        container.innerHTML = `
            <div class="page-header" style="margin-bottom:var(--spacing-lg);">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                        <h1 class="page-title" style="margin:0;">Seguimiento de Comprobante</h1>
                        <span class="badge badge-primary" style="font-size:11px;font-weight:700;letter-spacing:0.5px;">TRAZABILIDAD FISCAL</span>
                    </div>
                    <p class="page-subtitle" style="margin:0;">Historial inteligente y linaje entre facturas, cotizaciones, notas de crédito, pagos y acuses DGII</p>
                </div>
            </div>

            <!-- Omnisearch Bar -->
            <div class="table-outer" style="padding:var(--spacing-lg);margin-bottom:var(--spacing-xl);position:relative;overflow:visible;">
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <div style="position:relative;flex:1;min-width:260px;">
                        <div style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--color-text-muted);pointer-events:none;display:flex;align-items:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </div>
                        <input type="text" id="trail-search-input" placeholder="Buscar por e-NCF (ej. E3100000001, E34...), Factura #, RNC o Cliente..." 
                            style="width:100%;padding:12px 14px 12px 42px;border:1px solid var(--color-border);border-radius:var(--radius-lg);font-size:14px;background:var(--color-bg-primary);color:var(--color-text-primary);box-shadow:var(--shadow-sm);transition:all .2s ease;"
                            autocomplete="off">
                        <button id="trail-search-clear" style="display:none;position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--color-text-muted);cursor:pointer;font-size:16px;padding:4px;">✕</button>
                    </div>
                    <button id="btn-do-search" class="btn btn-primary" style="padding:12px 20px;font-weight:600;display:inline-flex;align-items:center;gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Rastrear
                    </button>
                </div>

                <!-- Live Auto-complete Dropdown -->
                <div id="trail-search-dropdown" style="display:none;position:absolute;left:var(--spacing-lg);right:var(--spacing-lg);top:calc(100% - 6px);background:var(--color-bg-primary);border:1px solid var(--color-border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);z-index:99;max-height:360px;overflow-y:auto;"></div>

                <!-- Recent Chips Bar -->
                <div style="margin-top:14px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-size:12px;font-weight:600;color:var(--color-text-muted);white-space:nowrap;">Comprobantes recientes:</span>
                    <div id="trail-recent-chips" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                        <span style="font-size:12px;color:var(--color-text-muted);opacity:0.7;">Cargando historial...</span>
                    </div>
                </div>
            </div>

            <!-- Content Container -->
            <div id="trail-content">
                <div class="text-center" style="padding:60px 20px;color:var(--color-text-muted);">
                    <div style="width:64px;height:64px;border-radius:50%;background:rgba(99,102,241,0.08);color:var(--color-primary);margin:0 auto 16px auto;display:flex;align-items:center;justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <h3 style="font-size:16px;font-weight:600;color:var(--color-text-primary);margin:0 0 6px 0;">Ingresa o selecciona un comprobante</h3>
                    <p style="font-size:13px;max-width:440px;margin:0 auto;">Escribe un e-NCF (ej. E3100000001, E3400000001), número de factura o nombre de cliente para ver su árbol completo de linaje, notas de crédito, pagos y balance neto.</p>
                </div>
            </div>
        `;

        this.bindEvents();
        this.loadRecentChips();

        if (voucherParam) {
            const input = document.getElementById('trail-search-input');
            if (input) input.value = voucherParam;
            this.loadTrace(voucherParam, !isNaN(voucherParam));
        }
    },

    bindEvents() {
        const input = document.getElementById('trail-search-input');
        const clearBtn = document.getElementById('trail-search-clear');
        const searchBtn = document.getElementById('btn-do-search');
        const dropdown = document.getElementById('trail-search-dropdown');

        if (!input) return;

        // Debounced live search
        input.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            clearBtn.style.display = val.length > 0 ? 'block' : 'none';

            clearTimeout(this.searchDebounceTimer);
            if (val.length < 2) {
                dropdown.style.display = 'none';
                return;
            }

            this.searchDebounceTimer = setTimeout(() => {
                this.executeAutocomplete(val);
            }, 250);
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                dropdown.style.display = 'none';
                const val = input.value.trim();
                if (val) this.loadTrace(val);
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
            }
        });

        clearBtn.addEventListener('click', () => {
            input.value = '';
            clearBtn.style.display = 'none';
            dropdown.style.display = 'none';
            input.focus();
        });

        searchBtn.addEventListener('click', () => {
            dropdown.style.display = 'none';
            const val = input.value.trim();
            if (val) this.loadTrace(val);
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#trail-search-input') && !e.target.closest('#trail-search-dropdown')) {
                if (dropdown) dropdown.style.display = 'none';
            }
        });
    },

    async executeAutocomplete(query) {
        const dropdown = document.getElementById('trail-search-dropdown');
        if (!dropdown) return;

        try {
            const res = await App.api(`audit-trail/search?q=${encodeURIComponent(query)}`);
            const results = res.results || [];

            if (results.length === 0) {
                dropdown.innerHTML = `
                    <div style="padding:14px 18px;font-size:13px;color:var(--color-text-muted);text-align:center;">
                        No se encontraron comprobantes que coincidan con "<strong>${query}</strong>"
                    </div>
                `;
                dropdown.style.display = 'block';
                return;
            }

            dropdown.innerHTML = results.map(r => `
                <div class="trail-dropdown-item" style="padding:10px 16px;border-bottom:1px solid var(--color-border);cursor:pointer;display:flex;justify-content:space-between;align-items:center;transition:background .15s ease;"
                     onclick="window.AuditTrailModule.selectSearchResult('${r.encf || r.invoice_number}')"
                     onmouseover="this.style.background='var(--color-bg-secondary)'"
                     onmouseout="this.style.background='transparent'">
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-family:'JetBrains Mono',monospace;font-weight:700;font-size:13px;color:var(--color-text-primary);">${r.encf || r.invoice_number}</span>
                            <span class="badge ${r.type_label.includes('Crédito') ? 'badge-danger' : (r.type_label.includes('Débito') ? 'badge-warning' : 'badge-primary')}" style="font-size:10px;padding:2px 6px;">${r.type_label}</span>
                            ${r.modified_ncf ? `<span style="font-size:11px;color:var(--color-text-muted);">➜ Modifica: <code>${r.modified_ncf}</code></span>` : ''}
                        </div>
                        <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px;">
                            ${r.client_name} ${r.client_rnc ? `· RNC/Cédula: ${r.client_rnc}` : ''} · ${r.issue_date}
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:700;font-size:13px;color:var(--color-text-primary);">${App.formatCurrency(r.total, r.currency)}</div>
                        <span class="badge badge-${r.status === 'paid' ? 'active' : (r.status === 'cancelled' ? 'overdue' : 'sent')}" style="font-size:10px;padding:2px 6px;">
                            ${r.status === 'paid' ? 'Pagada' : (r.status === 'cancelled' ? 'Anulada' : 'Emitida')}
                        </span>
                    </div>
                </div>
            `).join('');

            dropdown.style.display = 'block';
        } catch (e) {
            console.error('Error fetching search results:', e);
        }
    },

    selectSearchResult(val) {
        const input = document.getElementById('trail-search-input');
        const dropdown = document.getElementById('trail-search-dropdown');
        if (input) input.value = val;
        if (dropdown) dropdown.style.display = 'none';
        this.loadTrace(val);
    },

    async loadRecentChips() {
        const container = document.getElementById('trail-recent-chips');
        if (!container) return;

        try {
            const res = await App.api('audit-trail/recent');
            const recents = res.recent || [];

            if (recents.length === 0) {
                container.innerHTML = `<span style="font-size:12px;color:var(--color-text-muted);">Sin comprobantes recientes</span>`;
                return;
            }

            container.innerHTML = recents.map(r => `
                <button type="button" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:11px;font-family:'JetBrains Mono',monospace;border-radius:var(--radius-full);display:inline-flex;align-items:center;gap:6px;"
                        onclick="window.AuditTrailModule.selectSearchResult('${r.encf || r.invoice_number}')">
                    <span>${r.encf || r.invoice_number}</span>
                    <span style="opacity:0.6;font-family:inherit;">${App.formatCurrency(r.total, r.currency)}</span>
                </button>
            `).join('');
        } catch (e) {
            container.innerHTML = '';
        }
    },

    async loadTrace(identifier, isId = false) {
        const content = document.getElementById('trail-content');
        if (!content) return;

        content.innerHTML = `
            <div class="text-center" style="padding:60px 20px;">
                <div class="spinner mx-auto" style="margin-bottom:16px;"></div>
                <div style="font-size:13px;color:var(--color-text-muted);">Rastreando linaje del comprobante y conciliando historial...</div>
            </div>
        `;

        try {
            const endpoint = isId ? `audit-trail/trace?id=${identifier}` : `audit-trail/trace?query=${encodeURIComponent(identifier)}`;
            const data = await App.api(endpoint);

            this.renderTraceDetails(content, data);
            
            // Push route without reload
            const cleanRef = data.root_invoice.encf || data.root_invoice.invoice_number;
            history.replaceState(null, '', `/seguimiento/${cleanRef}`);
        } catch (err) {
            content.innerHTML = `
                <div class="table-outer" style="padding:40px 20px;text-align:center;">
                    <div style="width:48px;height:48px;border-radius:50%;background:var(--color-danger-bg);color:var(--color-danger-icon);margin:0 auto 12px auto;display:flex;align-items:center;justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <h3 style="font-size:16px;font-weight:600;color:var(--color-text-primary);margin:0 0 6px 0;">No se pudo encontrar el comprobante</h3>
                    <p style="font-size:13px;color:var(--color-text-muted);max-width:420px;margin:0 auto 16px auto;">${err.message || 'Verifica el número ingresado e intenta nuevamente.'}</p>
                    <button class="btn btn-secondary btn-sm" onclick="document.getElementById('trail-search-input').focus()">Intentar otra búsqueda</button>
                </div>
            `;
        }
    },

    renderTraceDetails(container, data) {
        const root = data.root_invoice;
        const quote = data.origin_quote;
        const modifyingDocs = data.modifying_invoices || [];
        const payments = data.payments || [];
        const fin = data.financial_summary || {};
        const timeline = data.timeline || [];

        // Financial status banner color scheme
        const statusColors = {
            settled: { bg: 'rgba(16,185,129,0.08)', border: 'rgba(16,185,129,0.25)', text: '#059669', badge: 'badge-active', label: 'Saldada Totalmente' },
            credited: { bg: 'rgba(239,68,68,0.08)', border: 'rgba(239,68,68,0.25)', text: '#dc2626', badge: 'badge-overdue', label: 'Anulada por Nota de Crédito' },
            cancelled: { bg: 'rgba(239,68,68,0.08)', border: 'rgba(239,68,68,0.25)', text: '#dc2626', badge: 'badge-overdue', label: 'Factura Anulada' },
            partial: { bg: 'rgba(245,158,11,0.08)', border: 'rgba(245,158,11,0.25)', text: '#d97706', badge: 'badge-sent', label: 'Saldo Parcial' },
            pending: { bg: 'rgba(59,130,246,0.08)', border: 'rgba(59,130,246,0.25)', text: '#2563eb', badge: 'badge-primary', label: 'Pendiente de Pago' },
        };
        const currentStatus = statusColors[fin.financial_status] || statusColors.pending;

        container.innerHTML = `
            <!-- Child doc notification banner if user searched for a Credit Note directly -->
            ${data.is_child_doc ? `
                <div style="padding:12px 18px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-lg);margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:18px;">💡</span>
                        <div style="font-size:13px;color:#166534;">
                            Consultaste una <strong>Nota de Crédito/Débito modificatoria</strong>. El sistema identificó y vinculó automáticamente su <strong>Factura Base (${root.encf || root.invoice_number})</strong> para mostrar la trazabilidad completa.
                        </div>
                    </div>
                    <a href="#facturas/${root.id}" class="btn btn-secondary btn-sm" style="font-size:11px;background:#ffffff;">Ver Factura Base</a>
                </div>
            ` : ''}

            <!-- Header Card & Quick Actions -->
            <div class="table-outer" style="padding:var(--spacing-xl);margin-bottom:var(--spacing-xl);">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
                    <div>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
                            <span style="font-size:12px;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.5px;">Documento Base</span>
                            <span class="badge ${currentStatus.badge}">${currentStatus.label}</span>
                            ${root.is_ecf ? `
                                <span class="badge badge-active" style="display:inline-flex;align-items:center;gap:4px;">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    ${root.dgii_status === 'accepted' ? 'DGII Aprobado' : (root.dgii_status || 'e-CF')}
                                </span>
                            ` : ''}
                        </div>
                        <h2 style="margin:0 0 6px 0;font-size:22px;font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--color-text-primary);letter-spacing:-0.5px;">
                            ${root.encf || root.invoice_number}
                        </h2>
                        <div style="font-size:13px;color:var(--color-text-muted);display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                            <span><strong>Cliente:</strong> ${root.client?.name || 'Consumidor Final'}</span>
                            ${root.client?.rnc ? `<span><strong>RNC/Cédula:</strong> <code style="font-family:inherit;">${root.client.rnc}</code></span>` : ''}
                            <span><strong>Emisión:</strong> ${App.formatDate(root.issue_date)}</span>
                            ${root.dgii_track_id ? `<span><strong>Track ID:</strong> <code>${root.dgii_track_id}</code></span>` : ''}
                        </div>
                    </div>

                    <!-- Direct Action Buttons -->
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <a href="#facturas/${root.id}" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            Ver Factura
                        </a>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="InvoicesModule.printInvoice(${root.id}, 'thermal')" style="display:inline-flex;align-items:center;gap:6px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            Ticket
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="InvoicesModule.printInvoice(${root.id}, 'normal')" style="display:inline-flex;align-items:center;gap:6px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            A4
                        </button>
                        <a href="/api/invoices/${root.id}/pdf?template=normal&download=1" download class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            PDF
                        </a>
                        ${root.is_ecf && root.encf && root.status !== 'cancelled' && !fin.is_fully_credited ? `
                            <button type="button" class="btn btn-secondary btn-sm" style="color:var(--color-danger-icon);border-color:rgba(239,68,68,0.25);" onclick="InvoicesModule.issueCreditNote(${root.id})">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M9 14L4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5v0a5.5 5.5 0 0 1-5.5 5.5H11"/></svg>
                                Emitir Nota de Crédito
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>

            <!-- Financial Reconciliation Metric Cards -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:16px;margin-bottom:var(--spacing-xl);">
                <div class="table-outer" style="padding:18px 20px;border-left:4px solid var(--color-primary);">
                    <div style="font-size:12px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;margin-bottom:4px;">1. Monto Facturado Base</div>
                    <div style="font-size:20px;font-weight:700;color:var(--color-text-primary);">${App.formatCurrency(fin.original_total, root.currency)}</div>
                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Comprobante original emitido</div>
                </div>

                <div class="table-outer" style="padding:18px 20px;border-left:4px solid ${fin.credit_notes_total > 0 ? 'var(--color-danger-icon)' : 'var(--color-border)'};">
                    <div style="font-size:12px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;margin-bottom:4px;">2. Notas de Crédito</div>
                    <div style="font-size:20px;font-weight:700;color:${fin.credit_notes_total > 0 ? 'var(--color-danger-icon)' : 'var(--color-text-primary)'};">
                        ${fin.credit_notes_total > 0 ? '-' : ''}${App.formatCurrency(fin.credit_notes_total, root.currency)}
                    </div>
                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">${modifyingDocs.length} nota(s) vinculada(s)</div>
                </div>

                <div class="table-outer" style="padding:18px 20px;border-left:4px solid ${fin.payments_total > 0 ? 'var(--color-success-icon)' : 'var(--color-border)'};">
                    <div style="font-size:12px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;margin-bottom:4px;">3. Pagos Registrados</div>
                    <div style="font-size:20px;font-weight:700;color:${fin.payments_total > 0 ? 'var(--color-success-icon)' : 'var(--color-text-primary)'};">
                        ${fin.payments_total > 0 ? '-' : ''}${App.formatCurrency(fin.payments_total, root.currency)}
                    </div>
                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">${payments.length} abono(s) recibido(s)</div>
                </div>

                <div class="table-outer" style="padding:18px 20px;border-left:4px solid ${currentStatus.text};background:${currentStatus.bg};">
                    <div style="font-size:12px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;margin-bottom:4px;">4. Balance Neto Real</div>
                    <div style="font-size:22px;font-weight:800;color:${currentStatus.text};">
                        ${App.formatCurrency(fin.net_balance, root.currency)}
                    </div>
                    <div style="font-size:11px;font-weight:600;color:${currentStatus.text};margin-top:4px;">${currentStatus.label}</div>
                </div>
            </div>

            <!-- Main Layout: Left: Lineage Tree & Documents / Right: Chronological Timeline -->
            <div style="display:grid;grid-template-columns:1fr;gap:24px;margin-bottom:var(--spacing-xl);">
                
                <!-- Lineage Tree Card -->
                <div class="table-outer" style="padding:var(--spacing-xl);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid var(--color-border);padding-bottom:12px;">
                        <div>
                            <h3 style="margin:0;font-size:16px;font-weight:700;color:var(--color-text-primary);">Árbol de Linaje Documental</h3>
                            <p style="margin:2px 0 0 0;font-size:12px;color:var(--color-text-muted);">Estructura jerárquica y documentos vinculados a esta transacción</p>
                        </div>
                    </div>

                    <div class="lineage-tree-wrapper" style="display:flex;flex-direction:column;gap:16px;">
                        
                        <!-- 1. Origin Quote (if exists) -->
                        ${quote ? `
                            <div class="lineage-node-card" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-lg);border-left:4px solid #6366f1;">
                                <div style="display:flex;align-items:center;gap:14px;">
                                    <div style="width:36px;height:36px;border-radius:var(--radius-md);background:rgba(99,102,241,0.1);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                    </div>
                                    <div>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <span style="font-weight:700;font-size:13px;color:var(--color-text-primary);">Cotización Origen: #${quote.quote_number}</span>
                                            <span class="badge badge-info" style="font-size:10px;">Aprobada y Convertida</span>
                                        </div>
                                        <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px;">
                                            Fecha: ${App.formatDate(quote.issue_date)} · Monto: ${App.formatCurrency(quote.total, quote.currency)}
                                        </div>
                                    </div>
                                </div>
                                <a href="#cotizaciones/${quote.id}" class="btn btn-secondary btn-sm" style="font-size:11px;">Ver Cotización</a>
                            </div>

                            <!-- Visual Connector Arrow -->
                            <div style="display:flex;align-items:center;justify-content:center;height:24px;color:var(--color-text-muted);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                            </div>
                        ` : ''}

                        <!-- 2. Root Invoice Node -->
                        <div class="lineage-node-card" style="padding:18px 20px;background:var(--color-bg-primary);border:2px solid var(--color-primary);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
                                <div style="display:flex;align-items:center;gap:14px;">
                                    <div style="width:40px;height:40px;border-radius:var(--radius-md);background:rgba(37,99,235,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                                    </div>
                                    <div>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <span style="font-size:15px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--color-text-primary);">${root.encf || root.invoice_number}</span>
                                            <span class="badge badge-primary" style="font-size:10px;">Factura Base Principal</span>
                                            ${root.status === 'cancelled' ? '<span class="badge badge-overdue" style="font-size:10px;">Anulada</span>' : ''}
                                        </div>
                                        <div style="font-size:12px;color:var(--color-text-muted);margin-top:4px;">
                                            Subtotal: ${App.formatCurrency(root.subtotal, root.currency)} | ITBIS: ${App.formatCurrency(root.tax_amount, root.currency)} | Total: <strong>${App.formatCurrency(root.total, root.currency)}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:11px;color:var(--color-text-muted);">Emitida: ${App.formatDate(root.issue_date)}</div>
                                    ${root.dgii_track_id ? `<div style="font-size:11px;color:var(--color-success-text);font-weight:600;margin-top:2px;">Track ID: ${root.dgii_track_id}</div>` : ''}
                                </div>
                            </div>
                        </div>

                        <!-- 3. Modifying Documents Section (Notas de Crédito / Débito) -->
                        <div style="margin-top:8px;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                                <span style="font-size:13px;font-weight:700;color:var(--color-text-primary);">Documentos Modificatorios Vinculados</span>
                                <span class="badge badge-secondary" style="font-size:11px;">${modifyingDocs.length}</span>
                            </div>

                            ${modifyingDocs.length === 0 ? `
                                <div style="padding:20px;border:1px dashed var(--color-border);border-radius:var(--radius-lg);text-align:center;color:var(--color-text-muted);font-size:13px;">
                                    <span>Esta factura no tiene Notas de Crédito ni de Débito vinculadas. Su monto original no ha sufrido modificaciones fiscales.</span>
                                </div>
                            ` : `
                                <div style="display:flex;flex-direction:column;gap:12px;">
                                    ${modifyingDocs.map(doc => `
                                        <div style="padding:14px 18px;background:var(--color-bg-secondary);border:1px solid ${doc.is_credit_note ? 'rgba(239,68,68,0.3)' : 'rgba(245,158,11,0.3)'};border-radius:var(--radius-lg);border-left:4px solid ${doc.is_credit_note ? 'var(--color-danger-icon)' : '#d97706'};display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                                            <div style="display:flex;align-items:center;gap:14px;">
                                                <div style="width:36px;height:36px;border-radius:var(--radius-md);background:${doc.is_credit_note ? 'rgba(239,68,68,0.1)' : 'rgba(245,158,11,0.1)'};color:${doc.is_credit_note ? 'var(--color-danger-icon)' : '#d97706'};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                                                </div>
                                                <div>
                                                    <div style="display:flex;align-items:center;gap:8px;">
                                                        <span style="font-family:'JetBrains Mono',monospace;font-weight:700;font-size:13px;color:var(--color-text-primary);">${doc.encf || doc.invoice_number}</span>
                                                        <span class="badge ${doc.is_credit_note ? 'badge-danger' : 'badge-warning'}" style="font-size:10px;">${doc.type_label}</span>
                                                        ${doc.dgii_status === 'accepted' ? '<span class="badge badge-active" style="font-size:10px;">DGII Aprobada</span>' : ''}
                                                    </div>
                                                    <div style="font-size:12px;color:var(--color-text-muted);margin-top:3px;">
                                                        <strong>Motivo DGII (Cód. ${doc.modification_code || '1'}):</strong> ${doc.modification_code_desc}
                                                        ${doc.modification_reason ? ` · <span style="font-style:italic;">"${doc.modification_reason}"</span>` : ''}
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:14px;">
                                                <div style="text-align:right;">
                                                    <div style="font-weight:700;font-size:15px;color:${doc.is_credit_note ? 'var(--color-danger-icon)' : 'var(--color-text-primary)'};">
                                                        ${doc.is_credit_note ? '-' : '+'}${App.formatCurrency(doc.total, doc.currency)}
                                                    </div>
                                                    <div style="font-size:11px;color:var(--color-text-muted);">${App.formatDate(doc.issue_date)}</div>
                                                </div>
                                                <a href="#facturas/${doc.id}" class="btn btn-secondary btn-sm" style="font-size:11px;">Ver Documento</a>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            `}
                        </div>

                        <!-- 4. Payments Applied Section -->
                        <div style="margin-top:12px;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                                <span style="font-size:13px;font-weight:700;color:var(--color-text-primary);">Abonos y Pagos Registrados</span>
                                <span class="badge badge-secondary" style="font-size:11px;">${payments.length}</span>
                            </div>

                            ${payments.length === 0 ? `
                                <div style="padding:16px;border:1px dashed var(--color-border);border-radius:var(--radius-lg);text-align:center;color:var(--color-text-muted);font-size:12px;">
                                    No se han registrado pagos en caja ni transferencias para este comprobante.
                                </div>
                            ` : `
                                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:10px;">
                                    ${payments.map(p => `
                                        <div style="padding:12px 14px;background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-md);display:flex;justify-content:space-between;align-items:center;">
                                            <div>
                                                <div style="font-size:12px;font-weight:600;color:var(--color-text-primary);text-transform:capitalize;">${p.payment_method || 'Pago'}</div>
                                                <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px;">
                                                    ${App.formatDate(p.payment_date)} ${p.reference ? `· Ref: ${p.reference}` : ''}
                                                </div>
                                            </div>
                                            <div style="font-weight:700;font-size:13px;color:var(--color-success-icon);">
                                                +${App.formatCurrency(p.amount, root.currency)}
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            `}
                        </div>

                    </div>
                </div>

                <!-- Chronological Smart Timeline -->
                <div class="table-outer" style="padding:var(--spacing-xl);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;border-bottom:1px solid var(--color-border);padding-bottom:12px;">
                        <div>
                            <h3 style="margin:0;font-size:16px;font-weight:700;color:var(--color-text-primary);">Historial Cronológico Completo</h3>
                            <p style="margin:2px 0 0 0;font-size:12px;color:var(--color-text-muted);">Registro ordenado en el tiempo de cada evento comercial y fiscal</p>
                        </div>
                        <span class="badge badge-secondary" style="font-size:11px;font-weight:600;">${timeline.length} eventos</span>
                    </div>

                    <div class="smart-audit-timeline" style="position:relative;padding-left:32px;">
                        <!-- Continuous Vertical Spine -->
                        <div style="position:absolute;left:13px;top:10px;bottom:10px;width:2px;background:var(--color-border);"></div>

                        ${timeline.map((item, idx) => {
                            const iconMap = {
                                quote: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
                                invoice: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>',
                                'shield-check': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>',
                                'shield-x': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>',
                                shield: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
                                send: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
                                'credit-card': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
                                'corner-down-left': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 10 4 15 9 20"/><path d="M20 4v7a4 4 0 0 1-4 4H4"/></svg>',
                                'corner-up-right': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 14 20 9 15 4"/><path d="M4 20v-7a4 4 0 0 1 4-4h12"/></svg>',
                                'x-circle': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
                            };

                            const badgeColors = {
                                'badge-info': { dotBg: '#6366f1', dotColor: '#fff' },
                                'badge-primary': { dotBg: 'var(--color-primary)', dotColor: '#fff' },
                                'badge-success': { dotBg: '#10b981', dotColor: '#fff' },
                                'badge-danger': { dotBg: '#ef4444', dotColor: '#fff' },
                                'badge-warning': { dotBg: '#f59e0b', dotColor: '#fff' },
                            };
                            const dotScheme = badgeColors[item.badge_class] || { dotBg: 'var(--color-primary)', dotColor: '#fff' };

                            return `
                                <div class="timeline-step" style="position:relative;margin-bottom:24px;">
                                    <!-- Node Icon Dot -->
                                    <div style="position:absolute;left:-32px;top:0;width:28px;height:28px;border-radius:50%;background:${dotScheme.dotBg};color:${dotScheme.dotColor};display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 4px var(--color-bg-primary);z-index:2;">
                                        ${iconMap[item.icon] || iconMap.invoice}
                                    </div>

                                    <!-- Step Content -->
                                    <div style="background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:14px 18px;">
                                        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
                                            <div>
                                                <div style="display:flex;align-items:center;gap:8px;">
                                                    <strong style="font-size:14px;color:var(--color-text-primary);">${item.title}</strong>
                                                    <span class="badge ${item.badge_class}" style="font-size:10px;padding:2px 6px;">${item.badge}</span>
                                                </div>
                                                <div style="font-size:12px;color:var(--color-text-muted);margin-top:4px;">${item.subtitle}</div>
                                            </div>
                                            <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);white-space:nowrap;">
                                                ${item.timestamp ? new Date(item.timestamp).toLocaleString('es-DO', { dateStyle: 'short', timeStyle: 'short' }) : (item.date_formatted || '—')}
                                            </div>
                                        </div>

                                        ${item.details?.track_id ? `
                                            <div style="margin-top:8px;padding:6px 10px;background:var(--color-bg-primary);border-radius:var(--radius-md);font-size:11px;font-family:'JetBrains Mono',monospace;color:var(--color-text-muted);display:inline-flex;align-items:center;gap:6px;">
                                                <span>DGII TrackID: <strong>${item.details.track_id}</strong></span>
                                                ${item.details.security_code ? `<span>| Sello: ${item.details.security_code}</span>` : ''}
                                            </div>
                                        ` : ''}

                                        ${item.details?.error_messages ? `
                                            <div style="margin-top:8px;padding:8px;background:var(--color-danger-bg);border:1px solid rgba(220,38,38,0.1);border-radius:var(--radius-md);color:var(--color-danger-text);font-size:11px;">
                                                ${item.details.error_messages}
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>

            </div>
        `;
    }
};

export default AuditTrailModule;
window.AuditTrailModule = AuditTrailModule;
