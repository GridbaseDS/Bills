/**
 * GridBase Digital Solutions — Bills System
 * Módulo: Seguimiento de Comprobante (Document Lineage & Interactive Node Graph)
 * Permite visualizar el ciclo de vida fiscal como un grafo de nodos interactivo:
 * [Cotización] ➔ [Factura Base] ➔ [Notas de Crédito / Débito & Pagos] ➔ [Balance Consolidado]
 */

const AuditTrailModule = {
    currentQuery: null,
    searchDebounceTimer: null,
    activeViewMode: 'nodes', // 'nodes' or 'timeline'
    resizeHandler: null,
    lastData: null,

    async render(container, voucherParam = null) {
        container.innerHTML = `
            <div class="page-header" style="margin-bottom:var(--spacing-lg);">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                        <h1 class="page-title" style="margin:0;">Seguimiento de Comprobante</h1>
                        <span class="badge badge-primary" style="font-size:11px;font-weight:700;letter-spacing:0.5px;">GRAFO DE NODOS</span>
                    </div>
                    <p class="page-subtitle" style="margin:0;">Visualizador interactivo de linaje documental y auditoría de eventos fiscales</p>
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                    </div>
                    <h3 style="font-size:16px;font-weight:600;color:var(--color-text-primary);margin:0 0 6px 0;">Ingresa o selecciona un comprobante</h3>
                    <p style="font-size:13px;max-width:440px;margin:0 auto;">Escribe un e-NCF (ej. E3100000001, E3400000001), número de factura o cliente para visualizar el <strong>Grafo de Nodos</strong> con su cadena de notas de crédito, pagos y balance real.</p>
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
                            ${r.modified_ncf ? `<span style="font-size:11px;color:var(--color-text-muted);">&rarr; Modifica: <code>${r.modified_ncf}</code></span>` : ''}
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
                <div style="font-size:13px;color:var(--color-text-muted);">Generando grafo de nodos y trazabilidad documental...</div>
            </div>
        `;

        try {
            const endpoint = isId ? `audit-trail/trace?id=${identifier}` : `audit-trail/trace?query=${encodeURIComponent(identifier)}`;
            const data = await App.api(endpoint);

            this.lastData = data;
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

        const statusColors = {
            settled: { bg: 'rgba(16,185,129,0.08)', border: '#10b981', text: 'var(--color-success-text, #10b981)', badge: 'badge-active', label: 'Saldada Totalmente' },
            credit_in_favor: { bg: 'rgba(14,165,233,0.08)', border: '#0ea5e9', text: 'var(--color-info-text, #38bdf8)', badge: 'badge-info', label: 'Saldo a Favor' },
            credited: { bg: 'rgba(239,68,68,0.08)', border: '#ef4444', text: 'var(--color-danger-text, #f87171)', badge: 'badge-overdue', label: 'Anulada por Nota de Crédito' },
            cancelled: { bg: 'rgba(239,68,68,0.08)', border: '#ef4444', text: 'var(--color-danger-text, #f87171)', badge: 'badge-overdue', label: 'Factura Anulada' },
            partial: { bg: 'rgba(245,158,11,0.08)', border: '#f59e0b', text: 'var(--color-warning-text, #fbbf24)', badge: 'badge-sent', label: 'Saldo Parcial' },
            pending: { bg: 'rgba(59,130,246,0.08)', border: '#3b82f6', text: 'var(--color-primary-text, #60a5fa)', badge: 'badge-primary', label: 'Pendiente de Pago' },
        };
        const currentStatus = statusColors[fin.financial_status] || statusColors.pending;

        container.innerHTML = `
            <!-- Child doc notification banner if user searched for a Credit Note directly -->
            ${data.is_child_doc ? `
                <div class="trail-child-doc-banner">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="color:var(--color-success-text);display:flex;align-items:center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        </div>
                        <div class="banner-message">
                            Consultaste una <strong>Nota de Crédito/Débito modificatoria</strong>. El sistema ubicó su <strong>Factura Base (${root.encf || root.invoice_number})</strong> y armó el grafo de nodos a partir de ella.
                        </div>
                    </div>
                    <a href="#facturas/${root.id}" class="btn btn-secondary btn-sm" style="font-size:11px;">Ver Factura Base</a>
                </div>
            ` : ''}

            <!-- Header Action & Mode Switcher Bar -->
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:18px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="display:flex;background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:3px;">
                        <button type="button" id="tab-btn-nodes" class="btn btn-sm ${this.activeViewMode === 'nodes' ? 'btn-primary' : 'btn-ghost'}" style="font-size:12px;display:inline-flex;align-items:center;gap:6px;border-radius:var(--radius-md);" onclick="window.AuditTrailModule.switchView('nodes')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                            Grafo de Nodos
                        </button>
                        <button type="button" id="tab-btn-timeline" class="btn btn-sm ${this.activeViewMode === 'timeline' ? 'btn-primary' : 'btn-ghost'}" style="font-size:12px;display:inline-flex;align-items:center;gap:6px;border-radius:var(--radius-md);" onclick="window.AuditTrailModule.switchView('timeline')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Historial Cronológico
                        </button>
                    </div>
                    <span style="font-size:12px;color:var(--color-text-muted);">
                        Mostrando comprobante: <strong style="color:var(--color-text-primary);font-family:'JetBrains Mono',monospace;">${root.encf || root.invoice_number}</strong>
                    </span>
                </div>

                <!-- Quick Action Buttons -->
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <a href="#facturas/${root.id}" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px;">
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
                    ${root.is_ecf && root.encf && root.status !== 'cancelled' && !fin.is_fully_credited ? `
                        <button type="button" class="btn btn-secondary btn-sm" style="color:var(--color-danger-icon);border-color:rgba(239,68,68,0.25);" onclick="InvoicesModule.issueCreditNote(${root.id})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M9 14L4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5v0a5.5 5.5 0 0 1-5.5 5.5H11"/></svg>
                            Emitir Nota de Crédito
                        </button>
                    ` : ''}
                </div>
            </div>

            <!-- VIEW 1: INTERACTIVE NODE GRAPH VIEW -->
            <div id="view-nodes-wrapper" style="display:${this.activeViewMode === 'nodes' ? 'block' : 'none'};">
                <div class="node-graph-viewport" id="nodes-viewport">
                    <div class="node-graph-canvas" id="nodes-canvas">
                        <!-- Dynamic SVG Connection Layer -->
                        <svg class="node-svg-layer" id="nodes-svg-layer"></svg>

                        <!-- STAGE 1: ORIGIN (QUOTE) -->
                        ${quote ? `
                            <div class="node-column" id="col-origin">
                                <div class="node-column-header">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    1. Origen
                                </div>
                                <div class="node-card node-quote" id="node-quote">
                                    <div class="node-port port-out" title="Conector Salida"></div>
                                    <div class="node-header" style="background:rgba(139,92,246,0.08);color:#7c3aed;">
                                        <div style="font-size:11px;font-weight:700;display:flex;align-items:center;gap:6px;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            COTIZACIÓN ORIGEN
                                        </div>
                                        <span class="badge badge-info" style="font-size:9px;">Aprobada</span>
                                    </div>
                                    <div class="node-body">
                                        <div class="node-code">#${quote.quote_number}</div>
                                        <div class="node-amount" style="color:var(--color-text-primary);">${App.formatCurrency(quote.total, quote.currency)}</div>
                                        <div class="node-meta">
                                            <div>Emitida: ${App.formatDate(quote.issue_date)}</div>
                                            <div style="color:var(--color-text-secondary);margin-top:2px;">Convertida a factura</div>
                                        </div>
                                    </div>
                                    <div class="node-footer">
                                        <a href="#cotizaciones/${quote.id}" class="btn btn-secondary btn-sm" style="font-size:11px;padding:3px 8px;">Ver Cotización</a>
                                    </div>
                                </div>
                            </div>
                        ` : ''}

                        <!-- STAGE 2: ROOT INVOICE (CENTRAL ANCHOR) -->
                        <div class="node-column" id="col-root">
                            <div class="node-column-header">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
                                ${quote ? '2.' : '1.'} Documento Base
                            </div>
                            <div class="node-card node-root" id="node-root">
                                ${quote ? '<div class="node-port port-in" title="Conector Entrada"></div>' : ''}
                                <div class="node-port port-out" title="Conector Salida"></div>
                                <div class="node-header" style="background:rgba(37,99,235,0.08);color:#2563eb;">
                                    <div style="font-size:11px;font-weight:700;display:flex;align-items:center;gap:6px;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
                                        ${root.is_ecf ? 'e-CF BASE' : 'FACTURA BASE'}
                                    </div>
                                    <span class="badge ${root.dgii_status === 'accepted' ? 'badge-active' : 'badge-primary'}" style="font-size:9px;">
                                        ${root.dgii_status === 'accepted' ? 'DGII Aprobada' : (root.dgii_status || 'Emitida')}
                                    </span>
                                </div>
                                <div class="node-body">
                                    <div class="node-code" style="font-size:15px;color:var(--color-primary);">${root.encf || root.invoice_number}</div>
                                    <div class="node-amount" style="color:var(--color-text-primary);">${App.formatCurrency(root.total, root.currency)}</div>
                                    <div class="node-meta">
                                        <div><strong>Cliente:</strong> ${root.client?.name || 'Consumidor Final'}</div>
                                        ${root.client?.rnc ? `<div><strong>RNC/Céd:</strong> <code>${root.client.rnc}</code></div>` : ''}
                                        <div><strong>Emisión:</strong> ${App.formatDate(root.issue_date)}</div>
                                        ${root.dgii_track_id ? `<div style="color:var(--color-success-text);font-weight:600;margin-top:2px;">TrackID: <code>${root.dgii_track_id}</code></div>` : ''}
                                    </div>
                                    <div style="margin-top:10px;padding-top:8px;border-top:1px dashed var(--color-border);display:flex;justify-content:space-between;font-size:11px;color:var(--color-text-muted);">
                                        <span>Subtotal: ${App.formatCurrency(root.subtotal, root.currency)}</span>
                                        <span>ITBIS: ${App.formatCurrency(root.tax_amount, root.currency)}</span>
                                    </div>
                                </div>
                                <div class="node-footer">
                                    <a href="#facturas/${root.id}" class="btn btn-secondary btn-sm" style="font-size:11px;padding:3px 8px;">Ver Detalle</a>
                                </div>
                            </div>
                        </div>

                        <!-- STAGE 3: BRANCHES (MODIFIERS & PAYMENTS) -->
                        <div class="node-column" id="col-branches">
                            <div class="node-column-header">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                                ${quote ? '3.' : '2.'} Modificaciones & Pagos
                            </div>

                            <!-- Modifying Documents Nodes (Credit Notes / Debit Notes) -->
                            ${modifyingDocs.map(doc => `
                                <div class="node-card ${doc.is_credit_note ? 'node-credit' : 'node-debit'}" id="node-doc-${doc.id}" data-node-type="modifier">
                                    <div class="node-port port-in" title="Entrada"></div>
                                    <div class="node-port port-out" title="Salida"></div>
                                    <div class="node-header" style="background:${doc.is_credit_note ? 'rgba(239,68,68,0.08)' : 'rgba(245,158,11,0.08)'};color:${doc.is_credit_note ? '#dc2626' : '#d97706'};">
                                        <div style="font-size:11px;font-weight:700;display:flex;align-items:center;gap:6px;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                                            ${doc.type_label.toUpperCase()}
                                        </div>
                                        <span class="badge ${doc.is_credit_note ? 'badge-danger' : 'badge-warning'}" style="font-size:9px;">
                                            ${doc.dgii_status === 'accepted' ? 'Aprobada' : 'Emitida'}
                                        </span>
                                    </div>
                                    <div class="node-body">
                                        <div class="node-code">${doc.encf || doc.invoice_number}</div>
                                        <div class="node-amount" style="color:${doc.is_credit_note ? 'var(--color-danger-icon)' : 'var(--color-warning-text, #f59e0b)'};">
                                            ${doc.is_credit_note ? '-' : '+'}${App.formatCurrency(doc.total, doc.currency)}
                                        </div>
                                        <div class="node-meta">
                                            <div><strong>Motivo DGII (Cód. ${doc.modification_code || '1'}):</strong></div>
                                            <div style="color:var(--color-text-secondary);font-size:11px;">${doc.modification_code_desc}</div>
                                            ${doc.modification_reason ? `<div style="font-style:italic;color:var(--color-text-muted);margin-top:2px;">"${doc.modification_reason}"</div>` : ''}
                                            <div style="margin-top:4px;">Fecha: ${App.formatDate(doc.issue_date)}</div>
                                        </div>
                                    </div>
                                    <div class="node-footer">
                                        <a href="#facturas/${doc.id}" class="btn btn-secondary btn-sm" style="font-size:11px;padding:3px 8px;">Ver ${doc.is_credit_note ? 'NC' : 'ND'}</a>
                                    </div>
                                </div>
                            `).join('')}

                            <!-- Payment Nodes -->
                            ${payments.map(p => `
                                <div class="node-card node-payment" id="node-pay-${p.id}" data-node-type="payment">
                                    <div class="node-port port-in" title="Entrada"></div>
                                    <div class="node-port port-out" title="Salida"></div>
                                    <div class="node-header" style="background:rgba(16,185,129,0.08);color:#059669;">
                                        <div style="font-size:11px;font-weight:700;display:flex;align-items:center;gap:6px;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                            PAGO REGISTRADO
                                        </div>
                                        <span class="badge badge-active" style="font-size:9px;">Abono</span>
                                    </div>
                                    <div class="node-body">
                                        <div class="node-code" style="text-transform:capitalize;">${p.payment_method || 'Pago'}</div>
                                        <div class="node-amount" style="color:var(--color-success-icon);">
                                            +${App.formatCurrency(p.amount, root.currency)}
                                        </div>
                                        <div class="node-meta">
                                            <div>Fecha: ${App.formatDate(p.payment_date)}</div>
                                            ${p.reference ? `<div>Ref: <code>${p.reference}</code></div>` : ''}
                                            ${p.notes ? `<div style="font-style:italic;color:var(--color-text-muted);">${p.notes}</div>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}

                            <!-- If no modifiers and no payments, show direct bypass node -->
                            ${modifyingDocs.length === 0 && payments.length === 0 ? `
                                <div class="node-card" style="border-style:dashed;text-align:center;padding:24px 18px;">
                                    <div style="width:36px;height:36px;border-radius:50%;background:rgba(100,116,139,0.1);color:var(--color-text-muted);display:flex;align-items:center;justify-content:center;margin:0 auto 10px auto;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                    </div>
                                    <div style="font-size:13px;font-weight:600;color:var(--color-text-primary);">Sin modificaciones ni cobros</div>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">No se han emitido notas de crédito ni registrado pagos parciales.</div>
                                </div>
                            ` : ''}
                        </div>

                        <!-- STAGE 4: TERMINAL OUTCOME NODE -->
                        <div class="node-column" id="col-outcome">
                            <div class="node-column-header">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 10"/></svg>
                                ${quote ? '4.' : '3.'} Liquidación & Balance
                            </div>
                            <div class="node-card node-outcome status-${fin.financial_status || 'pending'}" id="node-outcome" style="border-color:${currentStatus.border};">
                                <div class="node-port port-in" title="Entrada"></div>
                                <div class="node-header" style="background:${currentStatus.bg};color:${currentStatus.text};">
                                    <div style="font-size:11px;font-weight:700;display:flex;align-items:center;gap:6px;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
                                        BALANCE REAL CONCILIADO
                                    </div>
                                    <span class="badge ${currentStatus.badge}" style="font-size:9px;">${currentStatus.label}</span>
                                </div>
                                <div class="node-body">
                                    ${fin.credit_balance > 0 ? `
                                        <div class="trail-credit-text" style="font-size:11px;text-transform:uppercase;font-weight:700;letter-spacing:0.5px;">Crédito a Favor del Cliente</div>
                                        <div class="node-amount trail-credit-text" style="font-size:24px;">
                                            +${App.formatCurrency(fin.credit_balance, root.currency)}
                                        </div>
                                    ` : `
                                        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;font-weight:600;">Saldo Efectivo Pendiente</div>
                                        <div class="node-amount" style="font-size:24px;color:${currentStatus.text};">
                                            ${App.formatCurrency(fin.net_balance, root.currency)}
                                        </div>
                                    `}

                                    <div class="node-breakdown-box">
                                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                            <span>Original Facturado:</span>
                                            <strong>${App.formatCurrency(fin.original_total, root.currency)}</strong>
                                        </div>
                                        ${fin.credit_notes_total > 0 ? `
                                            <div style="display:flex;justify-content:space-between;margin-bottom:4px;color:var(--color-danger-icon);">
                                                <span>(-) Notas de Crédito:</span>
                                                <strong>-${App.formatCurrency(fin.credit_notes_total, root.currency)}</strong>
                                            </div>
                                        ` : ''}
                                        ${fin.debit_notes_total > 0 ? `
                                            <div style="display:flex;justify-content:space-between;margin-bottom:4px;color:var(--color-warning-text);">
                                                <span>(+) Notas de Débito:</span>
                                                <strong>+${App.formatCurrency(fin.debit_notes_total, root.currency)}</strong>
                                            </div>
                                        ` : ''}
                                        ${(fin.credit_notes_total > 0 || fin.debit_notes_total > 0) ? `
                                            <div style="display:flex;justify-content:space-between;padding-top:4px;margin-bottom:4px;border-top:1px dashed var(--color-border);font-weight:600;color:var(--color-text-primary);">
                                                <span>(=) Facturado Neto Ajustado:</span>
                                                <span>${App.formatCurrency(fin.effective_invoiced_total, root.currency)}</span>
                                            </div>
                                        ` : ''}
                                        ${fin.payments_total > 0 ? `
                                            <div style="display:flex;justify-content:space-between;margin-bottom:4px;color:var(--color-success-icon);">
                                                <span>(-) Total Pagado:</span>
                                                <strong>-${App.formatCurrency(fin.payments_total, root.currency)}</strong>
                                            </div>
                                        ` : ''}
                                        ${fin.credit_balance > 0 ? `
                                            <div class="trail-credit-text" style="display:flex;justify-content:space-between;padding-top:6px;margin-top:4px;border-top:1px solid var(--color-border);font-weight:700;">
                                                <span>(=) Saldo a Favor del Cliente:</span>
                                                <span>+${App.formatCurrency(fin.credit_balance, root.currency)}</span>
                                            </div>
                                        ` : (fin.net_balance > 0 ? `
                                            <div style="display:flex;justify-content:space-between;padding-top:6px;margin-top:4px;border-top:1px solid var(--color-border);font-weight:700;color:${currentStatus.text};">
                                                <span>(=) Saldo Pendiente por Cobrar:</span>
                                                <span>${App.formatCurrency(fin.net_balance, root.currency)}</span>
                                            </div>
                                        ` : `
                                            <div style="display:flex;justify-content:space-between;padding-top:6px;margin-top:4px;border-top:1px solid var(--color-border);font-weight:700;color:var(--color-success-icon);">
                                                <span>(=) Balance Pendiente:</span>
                                                <span>${App.formatCurrency(0, root.currency)}</span>
                                            </div>
                                        `)}
                                    </div>

                                    ${fin.credit_balance > 0 ? `
                                        <div class="trail-credit-notice">
                                            <strong>Aviso Contable:</strong> El pago se registró antes de la Nota de Crédito. El cliente dispone de <strong>${App.formatCurrency(fin.credit_balance, root.currency)}</strong> a su favor.
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- VIEW 2: DETAILED CHRONOLOGICAL TIMELINE (COLLAPSIBLE / SWITCHABLE) -->
            <div id="view-timeline-wrapper" style="display:${this.activeViewMode === 'timeline' ? 'block' : 'none'};">
                <div class="table-outer" style="padding:var(--spacing-xl);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;border-bottom:1px solid var(--color-border);padding-bottom:12px;">
                        <div>
                            <h3 style="margin:0;font-size:16px;font-weight:700;color:var(--color-text-primary);">Historial Cronológico Completo</h3>
                            <p style="margin:2px 0 0 0;font-size:12px;color:var(--color-text-muted);">Registro de eventos ordenados en el tiempo con acuses y pistas de auditoría</p>
                        </div>
                        <span class="badge badge-secondary" style="font-size:11px;font-weight:600;">${timeline.length} eventos</span>
                    </div>

                    <div class="smart-audit-timeline" style="position:relative;padding-left:32px;">
                        <div style="position:absolute;left:13px;top:10px;bottom:10px;width:2px;background:var(--color-border);"></div>

                        ${timeline.map(item => {
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
                                    <div style="position:absolute;left:-32px;top:0;width:28px;height:28px;border-radius:50%;background:${dotScheme.dotBg};color:${dotScheme.dotColor};display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 4px var(--color-bg-primary);z-index:2;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    </div>
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

        // Render the SVG lines connecting the nodes
        setTimeout(() => {
            this.drawNodeConnectors();
        }, 60);

        // Bind resize listener to recompute node connection lines
        if (this.resizeHandler) {
            window.removeEventListener('resize', this.resizeHandler);
        }
        this.resizeHandler = () => this.drawNodeConnectors();
        window.addEventListener('resize', this.resizeHandler);
    },

    switchView(mode) {
        this.activeViewMode = mode;
        const nodesWrap = document.getElementById('view-nodes-wrapper');
        const timelineWrap = document.getElementById('view-timeline-wrapper');
        const btnNodes = document.getElementById('tab-btn-nodes');
        const btnTimeline = document.getElementById('tab-btn-timeline');

        if (nodesWrap) nodesWrap.style.display = mode === 'nodes' ? 'block' : 'none';
        if (timelineWrap) timelineWrap.style.display = mode === 'timeline' ? 'block' : 'none';

        if (btnNodes) {
            btnNodes.className = `btn btn-sm ${mode === 'nodes' ? 'btn-primary' : 'btn-ghost'}`;
        }
        if (btnTimeline) {
            btnTimeline.className = `btn btn-sm ${mode === 'timeline' ? 'btn-primary' : 'btn-ghost'}`;
        }

        if (mode === 'nodes') {
            setTimeout(() => this.drawNodeConnectors(), 40);
        }
    },

    drawNodeConnectors() {
        const svg = document.getElementById('nodes-svg-layer');
        const container = document.getElementById('nodes-canvas');
        if (!svg || !container) return;

        const containerRect = container.getBoundingClientRect();
        const scrollW = Math.max(container.scrollWidth, container.clientWidth);
        const scrollH = Math.max(container.scrollHeight, container.clientHeight);

        svg.setAttribute('width', scrollW);
        svg.setAttribute('height', scrollH);
        svg.style.width = scrollW + 'px';
        svg.style.height = scrollH + 'px';

        let pathsHtml = `
            <defs>
                <marker id="arrow-primary" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                    <path d="M 0 1 L 10 5 L 0 9 z" fill="#3b82f6" />
                </marker>
                <marker id="arrow-credit" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                    <path d="M 0 1 L 10 5 L 0 9 z" fill="#ef4444" />
                </marker>
                <marker id="arrow-debit" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                    <path d="M 0 1 L 10 5 L 0 9 z" fill="#f59e0b" />
                </marker>
                <marker id="arrow-payment" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                    <path d="M 0 1 L 10 5 L 0 9 z" fill="#10b981" />
                </marker>
                <marker id="arrow-outcome" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                    <path d="M 0 1 L 10 5 L 0 9 z" fill="#6366f1" />
                </marker>
            </defs>
        `;

        const getPortCenter = (nodeId, isOut) => {
            const node = document.getElementById(nodeId);
            if (!node) return null;
            const port = node.querySelector(isOut ? '.port-out' : '.port-in');
            const target = port || node;
            const r = target.getBoundingClientRect();
            return {
                x: (r.left + r.width / 2) - containerRect.left + container.scrollLeft,
                y: (r.top + r.height / 2) - containerRect.top + container.scrollTop
            };
        };

        const drawCurve = (startId, endId, colorClass, markerId) => {
            const p1 = getPortCenter(startId, true);
            const p2 = getPortCenter(endId, false);
            if (!p1 || !p2) return;

            const dx = Math.max(35, Math.abs(p2.x - p1.x) * 0.45);
            const d = `M ${p1.x} ${p1.y} C ${p1.x + dx} ${p1.y}, ${p2.x - dx} ${p2.y}, ${p2.x} ${p2.y}`;
            pathsHtml += `<path class="node-connector-path ${colorClass}" d="${d}" marker-end="url(#${markerId})"/>`;
        };

        // 1. Quote to Root Invoice (if exists)
        if (document.getElementById('node-quote')) {
            drawCurve('node-quote', 'node-root', 'path-primary', 'arrow-primary');
        }

        // 2. Root Invoice to each Modifying Document (Credit / Debit Notes)
        const modifyingNodes = container.querySelectorAll('[data-node-type="modifier"]');
        modifyingNodes.forEach(m => {
            const isDebit = m.classList.contains('node-debit');
            const pathClass = isDebit ? 'path-debit' : 'path-credit';
            const markerId = isDebit ? 'arrow-debit' : 'arrow-credit';
            drawCurve('node-root', m.id, pathClass, markerId);
            // And from modifier to outcome
            drawCurve(m.id, 'node-outcome', pathClass, 'arrow-outcome');
        });

        // 3. Root Invoice to each Payment
        const paymentNodes = container.querySelectorAll('[data-node-type="payment"]');
        paymentNodes.forEach(p => {
            drawCurve('node-root', p.id, 'path-payment', 'arrow-payment');
            // And from payment to outcome
            drawCurve(p.id, 'node-outcome', 'path-payment', 'arrow-outcome');
        });

        // If no modifiers and no payments, connect root directly to outcome
        if (modifyingNodes.length === 0 && paymentNodes.length === 0) {
            drawCurve('node-root', 'node-outcome', 'path-primary', 'arrow-outcome');
        }

        svg.innerHTML = pathsHtml;
    }
};

export default AuditTrailModule;
window.AuditTrailModule = AuditTrailModule;
