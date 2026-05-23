/**
 * Received Invoices Module — Aprobaciones Comerciales
 * Manages incoming e-CFs from suppliers and commercial approval/rejection workflow.
 */
const ReceivedInvoices = {
    container: null,
    data: [],
    summary: { pending: 0, approved: 0, rejected: 0, total: 0 },
    currentFilter: 'all',

    async render(container) {
        this.container = container || document.getElementById('app-content');
        this.container.innerHTML = '<div class="text-center" style="padding:48px;"><div class="spinner mx-auto"></div></div>';
        await Promise.all([this.loadSummary(), this.loadData()]);
        this.renderContent();
    },

    async loadSummary() {
        try {
            this.summary = await App.api('received-invoices/summary');
        } catch (e) {
            console.error('Error loading summary:', e);
        }
    },

    async loadData() {
        try {
            const params = new URLSearchParams();
            if (this.currentFilter !== 'all') params.set('status', this.currentFilter);
            const res = await App.api(`received-invoices?${params.toString()}`);
            this.data = res.data || res;
        } catch (e) {
            console.error('Error loading received invoices:', e);
            this.data = [];
        }
    },

    renderContent() {
        const wp = this.container;
        const p = this.summary.pending;
        const a = this.summary.approved;
        const r = this.summary.rejected;

        wp.innerHTML = `
            <div class="workspace-panel">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Facturas Recibidas</h1>
                        <p class="page-subtitle">Gestión de Aprobaciones Comerciales (ACECF)</p>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--spacing-lg);margin-bottom:var(--spacing-xl);">
                    <div class="stat-card" style="cursor:pointer;" data-filter="pending">
                        <div class="stat-label">Pendientes</div>
                        <div class="stat-value" style="color:#f59e0b;">${p}</div>
                        <div class="stat-change" style="color:var(--color-text-tertiary);">Requieren acción</div>
                    </div>
                    <div class="stat-card" style="cursor:pointer;" data-filter="approved">
                        <div class="stat-label">Aprobadas</div>
                        <div class="stat-value" style="color:#22c55e;">${a}</div>
                        <div class="stat-change" style="color:var(--color-text-tertiary);">Enviadas a DGII</div>
                    </div>
                    <div class="stat-card" style="cursor:pointer;" data-filter="rejected">
                        <div class="stat-label">Rechazadas</div>
                        <div class="stat-value" style="color:#ef4444;">${r}</div>
                        <div class="stat-change" style="color:var(--color-text-tertiary);">Con motivo</div>
                    </div>
                    <div class="stat-card" style="cursor:pointer;" data-filter="all">
                        <div class="stat-label">Total</div>
                        <div class="stat-value">${this.summary.total}</div>
                        <div class="stat-change" style="color:var(--color-text-tertiary);">Recibidas</div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div style="display:flex;gap:8px;margin-bottom:var(--spacing-lg);">
                    ${['all', 'pending', 'approved', 'rejected'].map(f => `
                        <button class="btn ${this.currentFilter === f ? 'btn-primary' : 'btn-secondary'}" data-tab="${f}" style="font-size:12px;padding:6px 16px;">
                            ${f === 'all' ? 'Todas' : f === 'pending' ? '⏳ Pendientes' : f === 'approved' ? '✅ Aprobadas' : '❌ Rechazadas'}
                        </button>
                    `).join('')}
                </div>

                <!-- Table -->
                <div class="table-outer">
                    <table>
                        <thead>
                            <tr>
                                <th>e-NCF</th>
                                <th>RNC Emisor</th>
                                <th>Razón Social</th>
                                <th>Tipo</th>
                                <th>Fecha Emisión</th>
                                <th style="text-align:right;">Monto</th>
                                <th>Estado</th>
                                <th style="text-align:center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${this.data.length === 0 ? `
                                <tr><td colspan="8" style="text-align:center;padding:48px;color:var(--color-text-tertiary);">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;opacity:0.4;">
                                        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                                    </svg>
                                    <br>No hay facturas recibidas ${this.currentFilter !== 'all' ? 'con este filtro' : 'aún'}
                                </td></tr>
                            ` : this.data.map(inv => this.renderRow(inv)).join('')}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rejection Modal -->
            <div id="reject-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:1000;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
                <div style="background:var(--color-bg-primary);border-radius:var(--radius-xl);padding:32px;max-width:480px;width:90%;box-shadow:var(--shadow-xl);">
                    <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;color:var(--color-text-primary);">Rechazar Factura</h3>
                    <p style="font-size:13px;color:var(--color-text-secondary);margin-bottom:16px;" id="reject-modal-info"></p>
                    <div class="form-group">
                        <label class="form-label">Motivo del Rechazo *</label>
                        <textarea id="reject-reason" class="form-input" rows="3" placeholder="Describa el motivo del rechazo..." maxlength="250" style="resize:vertical;"></textarea>
                        <span style="font-size:11px;color:var(--color-text-tertiary);">Máximo 250 caracteres</span>
                    </div>
                    <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
                        <button class="btn btn-secondary" id="reject-cancel">Cancelar</button>
                        <button class="btn" id="reject-confirm" style="background:#ef4444;color:#fff;">Confirmar Rechazo</button>
                    </div>
                </div>
            </div>
        `;

        this.bindEvents();
    },

    renderRow(inv) {
        const statusBadge = {
            pending: '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:var(--radius-full);font-size:11px;font-weight:600;background:#fef3c7;color:#92400e;">⏳ Pendiente</span>',
            approved: '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:var(--radius-full);font-size:11px;font-weight:600;background:#dcfce7;color:#166534;">✅ Aprobada</span>',
            rejected: '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:var(--radius-full);font-size:11px;font-weight:600;background:#fee2e2;color:#991b1b;">❌ Rechazada</span>',
        };

        const fecha = inv.fecha_emision ? new Date(inv.fecha_emision).toLocaleDateString('es-DO') : '—';
        const monto = inv.monto_total ? parseFloat(inv.monto_total).toLocaleString('es-DO', { style: 'currency', currency: 'DOP' }) : '$0.00';

        const actions = inv.approval_status === 'pending' ? `
            <div style="display:flex;gap:6px;justify-content:center;">
                <button class="btn btn-primary" style="font-size:11px;padding:4px 12px;" data-approve="${inv.id}" title="Aprobar Comercialmente">
                    ✅ Aprobar
                </button>
                <button class="btn btn-secondary" style="font-size:11px;padding:4px 12px;color:#ef4444;" data-reject="${inv.id}" data-encf="${inv.encf}" title="Rechazar Comercialmente">
                    ❌ Rechazar
                </button>
            </div>
        ` : `<span style="font-size:11px;color:var(--color-text-tertiary);">${inv.acecf_sent_to_dgii ? '📡 Enviado' : '—'}</span>`;

        return `
            <tr>
                <td><strong style="font-family:'JetBrains Mono',monospace;font-size:12px;">${inv.encf || '—'}</strong></td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:12px;">${inv.rnc_emisor || '—'}</td>
                <td>${inv.razon_social_emisor || '<span style="color:var(--color-text-tertiary);">—</span>'}</td>
                <td><span style="padding:2px 8px;border-radius:var(--radius-sm);background:var(--color-bg-tertiary);font-size:11px;font-weight:600;">${inv.ecf_type || '—'}</span></td>
                <td>${fecha}</td>
                <td style="text-align:right;font-weight:600;">${monto}</td>
                <td>${statusBadge[inv.approval_status] || '—'}</td>
                <td style="text-align:center;">${actions}</td>
            </tr>
        `;
    },

    bindEvents() {
        // Filter tabs
        document.querySelectorAll('[data-tab]').forEach(btn => {
            btn.addEventListener('click', async () => {
                this.currentFilter = btn.dataset.tab;
                await this.loadData();
                this.renderContent();
            });
        });

        // Stat card filter
        document.querySelectorAll('[data-filter]').forEach(card => {
            card.addEventListener('click', async () => {
                this.currentFilter = card.dataset.filter;
                await this.loadData();
                this.renderContent();
            });
        });

        // Approve buttons
        document.querySelectorAll('[data-approve]').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('¿Confirma que desea APROBAR comercialmente esta factura? Se enviará la aprobación a la DGII.')) return;
                
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner" style="width:12px;height:12px;border-width:2px;"></span>';

                try {
                    const res = await App.api(`received-invoices/${btn.dataset.approve}/approve`, { method: 'POST' });
                    if (res.success) {
                        App.showToast('✅ Aprobación Comercial enviada exitosamente', 'success');
                    } else {
                        App.showToast('❌ ' + (res.message || 'Error'), 'error');
                    }
                } catch (e) {
                    App.showToast('❌ Error: ' + e.message, 'error');
                }

                await Promise.all([this.loadSummary(), this.loadData()]);
                this.renderContent();
            });
        });

        // Reject buttons — open modal
        document.querySelectorAll('[data-reject]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = document.getElementById('reject-modal');
                const info = document.getElementById('reject-modal-info');
                info.textContent = `e-NCF: ${btn.dataset.encf}`;
                modal.style.display = 'flex';
                modal.dataset.invoiceId = btn.dataset.reject;
                document.getElementById('reject-reason').value = '';
                document.getElementById('reject-reason').focus();
            });
        });

        // Modal cancel
        const cancelBtn = document.getElementById('reject-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                document.getElementById('reject-modal').style.display = 'none';
            });
        }

        // Modal confirm reject
        const confirmBtn = document.getElementById('reject-confirm');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', async () => {
                const modal = document.getElementById('reject-modal');
                const reason = document.getElementById('reject-reason').value.trim();
                const id = modal.dataset.invoiceId;

                if (!reason) {
                    App.showToast('Debe indicar el motivo del rechazo', 'error');
                    return;
                }

                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner" style="width:12px;height:12px;border-width:2px;"></span> Enviando...';

                try {
                    const res = await App.api(`received-invoices/${id}/reject`, {
                        method: 'POST',
                        body: JSON.stringify({ reason }),
                    });
                    if (res.success) {
                        App.showToast('Rechazo Comercial enviado exitosamente', 'success');
                    } else {
                        App.showToast('❌ ' + (res.message || 'Error'), 'error');
                    }
                } catch (e) {
                    App.showToast('❌ Error: ' + e.message, 'error');
                }

                modal.style.display = 'none';
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = 'Confirmar Rechazo';

                await Promise.all([this.loadSummary(), this.loadData()]);
                this.renderContent();
            });
        }
    },
};

export default ReceivedInvoices;

