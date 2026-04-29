export default {
    async render(container) {
        const statusLabel = (s) => ({draft:'Borrador',sent:'Pendiente de Pago',paid:'Pagada',overdue:'Vencida',partial:'Pago Parcial',cancelled:'Cancelada',converted:'Convertida'}[s]||s);
        try {
            const data = await window.App.api('dashboard');
            const s = data.stats;
            const monthChange = s.revenue_last_month > 0
                ? Math.round(((s.revenue_this_month - s.revenue_last_month) / s.revenue_last_month) * 100)
                : (s.revenue_this_month > 0 ? 100 : 0);
            const invChange = s.invoices_last_month > 0
                ? Math.round(((s.invoices_this_month - s.invoices_last_month) / s.invoices_last_month) * 100)
                : (s.invoices_this_month > 0 ? 100 : 0);

            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Panel de Control</h1>
                        <p class="page-subtitle">Resumen general de tu negocio</p>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary" onclick="window.location.hash='invoices/new'">+ Nueva Factura</button>
                        <button class="btn btn-ghost" onclick="window.location.hash='quotes/new'">+ Cotización</button>
                    </div>
                </div>
                
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="flex-between">
                            <div class="kpi-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div>
                            <span class="badge badge-paid">Ingresos</span>
                        </div>
                        <div class="mt-16">
                            <div class="kpi-label">Total Cobrado</div>
                            <div class="kpi-value">${window.App.formatCurrency(s.total_revenue)}</div>
                            <div style="font-size:12px;margin-top:4px;color:${monthChange >= 0 ? 'var(--green)' : 'var(--red)'}">
                                ${monthChange >= 0 ? '▲' : '▼'} ${Math.abs(monthChange)}% vs mes anterior
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="flex-between">
                            <div class="kpi-icon amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
                            <span class="badge badge-sent">Pendiente</span>
                        </div>
                        <div class="mt-16">
                            <div class="kpi-label">Por Cobrar</div>
                            <div class="kpi-value">${window.App.formatCurrency(s.pending_amount)}</div>
                            <div style="font-size:12px;margin-top:4px;color:var(--text-muted)">
                                ${s.invoices_this_month} facturas este mes
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="flex-between">
                            <div class="kpi-icon red"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></div>
                            <span class="badge badge-overdue">${s.overdue_count} vencida${s.overdue_count !== 1 ? 's' : ''}</span>
                        </div>
                        <div class="mt-16">
                            <div class="kpi-label">Monto Vencido</div>
                            <div class="kpi-value" style="color: var(--red);">${window.App.formatCurrency(s.overdue_amount)}</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="flex-between">
                            <div class="kpi-icon purple"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                            <span class="badge badge-draft">${s.total_clients} activos</span>
                        </div>
                        <div class="mt-16">
                            <div class="kpi-label">Clientes</div>
                            <div class="kpi-value">${s.total_clients}</div>
                        </div>
                    </div>
                </div>

                <!-- Quotes mini KPIs -->
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
                    <div class="card" style="padding:20px;text-align:center;">
                        <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">Cotizaciones</div>
                        <div style="font-size:24px;font-weight:700;">${s.total_quotes}</div>
                    </div>
                    <div class="card" style="padding:20px;text-align:center;">
                        <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">Tasa de Conversión</div>
                        <div style="font-size:24px;font-weight:700;color:var(--primary);">${s.conversion_rate}%</div>
                    </div>
                    <div class="card" style="padding:20px;text-align:center;">
                        <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">Cotizaciones Pendientes</div>
                        <div style="font-size:24px;font-weight:700;">${window.App.formatCurrency(s.quotes_pending)}</div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="card mb-24">
                    <div class="card-header">
                        <div class="card-title">Ingresos Mensuales</div>
                    </div>
                    <div class="card-body" style="padding:16px 24px 24px;">
                        <canvas id="revenue-chart" height="220" style="width:100%;"></canvas>
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
                                            <td><span class="badge badge-${i.status}">${statusLabel(i.status)}</span></td>
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
                                <thead><tr><th>Factura</th><th>Cliente</th><th>Vencimiento</th><th>Balance</th></tr></thead>
                                <tbody>
                                    ${data.overdue_invoices.map(i => `
                                        <tr style="cursor:pointer" onclick="window.location.hash='invoices/${i.id}'">
                                            <td class="font-semibold text-mono">${i.invoice_number}</td>
                                            <td>${i.company_name || i.contact_name}</td>
                                            <td style="color:var(--red)">${window.App.formatDate(i.due_date)}</td>
                                            <td class="font-semibold" style="color:var(--red)">${window.App.formatCurrency(i.balance, i.currency)}</td>
                                        </tr>
                                    `).join('') || `<tr><td colspan="4" class="text-center text-muted py-4">¡No hay facturas vencidas! 🎉</td></tr>`}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;

            // Render chart
            this.renderChart(data.monthly_revenue);

        } catch (e) {
            container.innerHTML = `<div class="card"><div class="card-body text-center text-red">Error al cargar el panel</div></div>`;
        }
    },

    renderChart(months) {
        const canvas = document.getElementById('revenue-chart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = 220 * dpr;
        ctx.scale(dpr, dpr);

        const W = rect.width;
        const H = 220;
        const pad = { top: 20, right: 20, bottom: 40, left: 70 };
        const chartW = W - pad.left - pad.right;
        const chartH = H - pad.top - pad.bottom;

        const maxVal = Math.max(...months.map(m => Math.max(m.revenue, m.invoiced)), 1);
        const barW = chartW / months.length;

        // Background grid
        ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim() || '#2a2a2a';
        ctx.lineWidth = 0.5;
        for (let i = 0; i <= 4; i++) {
            const y = pad.top + (chartH / 4) * i;
            ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke();
            ctx.fillStyle = '#888'; ctx.font = '11px Inter, sans-serif'; ctx.textAlign = 'right';
            ctx.fillText(window.App.formatCurrency(maxVal - (maxVal / 4) * i, ''), pad.left - 8, y + 4);
        }

        months.forEach((m, i) => {
            const x = pad.left + i * barW + barW * 0.15;
            const bw = barW * 0.3;

            // Invoiced bar (lighter)
            const ih = (m.invoiced / maxVal) * chartH;
            ctx.fillStyle = 'rgba(0, 223, 131, 0.15)';
            ctx.beginPath();
            ctx.roundRect(x, pad.top + chartH - ih, bw, ih, [4, 4, 0, 0]);
            ctx.fill();

            // Revenue bar (solid)
            const rh = (m.revenue / maxVal) * chartH;
            ctx.fillStyle = '#00DF83';
            ctx.beginPath();
            ctx.roundRect(x + bw + 2, pad.top + chartH - rh, bw, rh, [4, 4, 0, 0]);
            ctx.fill();

            // Label
            ctx.fillStyle = '#888'; ctx.font = '11px Inter, sans-serif'; ctx.textAlign = 'center';
            ctx.fillText(m.label, pad.left + i * barW + barW / 2, H - 10);
        });

        // Legend
        ctx.fillStyle = 'rgba(0, 223, 131, 0.15)';
        ctx.fillRect(pad.left, H - 18, 12, 10);
        ctx.fillStyle = '#888'; ctx.font = '10px Inter, sans-serif'; ctx.textAlign = 'left';
        ctx.fillText('Facturado', pad.left + 16, H - 9);

        ctx.fillStyle = '#00DF83';
        ctx.fillRect(pad.left + 90, H - 18, 12, 10);
        ctx.fillStyle = '#888';
        ctx.fillText('Cobrado', pad.left + 106, H - 9);
    }
};
