export default {
    async render(container) {
        try {
            const data = await window.App.api('dashboard');
            const s = data.stats;
            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Panel de Control</h1>
                        <p class="page-subtitle">Resumen de tu negocio</p>
                    </div>
                </div>
                
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="flex-between">
                            <div class="kpi-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div>
                            <span class="badge badge-active">Ingresos</span>
                        </div>
                        <div class="mt-16">
                            <div class="kpi-label">Total Cobrado</div>
                            <div class="kpi-value">${window.App.formatCurrency(s.total_revenue)}</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="flex-between">
                            <div class="kpi-icon amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
                            <span class="badge badge-partial">Pendiente</span>
                        </div>
                        <div class="mt-16">
                            <div class="kpi-label">Por Cobrar</div>
                            <div class="kpi-value">${window.App.formatCurrency(s.pending_amount)}</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="flex-between">
                            <div class="kpi-icon red"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></div>
                            <span class="badge badge-overdue">Atención</span>
                        </div>
                        <div class="mt-16">
                            <div class="kpi-label">Vencido</div>
                            <div class="kpi-value" style="color: var(--red);">${window.App.formatCurrency(s.overdue_amount)}</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="flex-between">
                            <div class="kpi-icon purple"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                            <span class="badge badge-draft">Clientes</span>
                        </div>
                        <div class="mt-16">
                            <div class="kpi-label">Total Activos</div>
                            <div class="kpi-value">${s.total_clients}</div>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Facturas Recientes</div>
                            <a href="#invoices" class="btn btn-ghost btn-sm">Ver todas</a>
                        </div>
                        <div class="card-body p-0">
                            <table class="table">
                                <thead><tr><th>Factura</th><th>Cliente</th><th>Monto</th><th>Estado</th></tr></thead>
                                <tbody>
                                    ${data.recent_invoices.map(i => `
                                        <tr style="cursor:pointer" onclick="window.location.hash='invoices/${i.id}'">
                                            <td class="font-semibold text-mono">${i.invoice_number}</td>
                                            <td>${i.company_name || i.contact_name}</td>
                                            <td class="font-semibold">${window.App.formatCurrency(i.total, i.currency)}</td>
                                            <td><span class="badge badge-${i.status}">${i.status}</span></td>
                                        </tr>
                                    `).join('') || `<tr><td colspan="4" class="text-center text-muted py-4">No hay facturas recientes</td></tr>`}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Facturas Vencidas</div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table">
                                <thead><tr><th>Factura</th><th>Cliente</th><th>Vencimiento</th><th>Monto</th></tr></thead>
                                <tbody>
                                    ${data.overdue_invoices.map(i => `
                                        <tr style="cursor:pointer" onclick="window.location.hash='invoices/${i.id}'">
                                            <td class="font-semibold text-mono">${i.invoice_number}</td>
                                            <td>${i.company_name || i.contact_name}</td>
                                            <td style="color:var(--red)">${window.App.formatDate(i.due_date)}</td>
                                            <td class="font-semibold">${window.App.formatCurrency(i.total, i.currency)}</td>
                                        </tr>
                                    `).join('') || `<tr><td colspan="4" class="text-center text-muted py-4">¡No hay facturas vencidas! 🎉</td></tr>`}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        } catch (e) {
            container.innerHTML = `<div class="card"><div class="card-body text-center text-red">Error al cargar el panel</div></div>`;
        }
    }
};
