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
                        <button class="btn btn-primary" onclick="App.navigate('invoices/new')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Nueva Factura
                        </button>
                        <button class="btn btn-secondary" onclick="App.navigate('quotes/new')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Cotización
                        </button>
                    </div>
                </div>
                
                <!-- KPI Grid: 4 tarjetas flat con borde fino -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div class="kpi-icon green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            </div>
                            <span class="badge badge-paid">Ingresos</span>
                        </div>
                        <div style="margin-top:16px;">
                            <div class="kpi-label">Total Cobrado</div>
                            <div class="kpi-value">${window.App.formatCurrency(s.total_revenue)}</div>
                            <div style="font-size:12px;margin-top:6px;font-weight:600;color:${monthChange >= 0 ? 'var(--green)' : 'var(--red)'}">
                                ${monthChange >= 0 ? '↑' : '↓'} ${Math.abs(monthChange)}% vs mes anterior
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div class="kpi-icon amber">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                            <span class="badge badge-sent">Pendiente</span>
                        </div>
                        <div style="margin-top:16px;">
                            <div class="kpi-label">Por Cobrar</div>
                            <div class="kpi-value">${window.App.formatCurrency(s.pending_amount)}</div>
                            <div style="font-size:12px;margin-top:6px;color:var(--contrast-medium);">
                                ${s.invoices_this_month} facturas este mes
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div class="kpi-icon red">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            </div>
                            <span class="badge badge-overdue">${s.overdue_count} vencida${s.overdue_count !== 1 ? 's' : ''}</span>
                        </div>
                        <div style="margin-top:16px;">
                            <div class="kpi-label">Monto Vencido</div>
                            <div class="kpi-value" style="color: var(--red);">${window.App.formatCurrency(s.overdue_amount)}</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div class="kpi-icon purple">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                            <span class="badge badge-draft">${s.total_clients} activos</span>
                        </div>
                        <div style="margin-top:16px;">
                            <div class="kpi-label">Clientes</div>
                            <div class="kpi-value">${s.total_clients}</div>
                        </div>
                    </div>
                </div>

                <!-- Mini KPIs de Cotizaciones con diseño flat -->
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:var(--spacing-large);margin-bottom:var(--spacing-large);">
                    <div class="card" style="margin-bottom:0;">
                        <div class="card-body" style="text-align:center;">
                            <div style="font-size:11px;color:var(--contrast-low);font-weight:500;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Cotizaciones</div>
                            <div style="font-size:32px;font-weight:700;color:var(--contrast-high);letter-spacing:-0.5px;">${s.total_quotes}</div>
                        </div>
                    </div>
                    <div class="card" style="margin-bottom:0;">
                        <div class="card-body" style="text-align:center;">
                            <div style="font-size:11px;color:var(--contrast-low);font-weight:500;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Tasa de Conversión</div>
                            <div style="font-size:32px;font-weight:700;color:var(--accent);letter-spacing:-0.5px;">${s.conversion_rate}%</div>
                        </div>
                    </div>
                    <div class="card" style="margin-bottom:0;">
                        <div class="card-body" style="text-align:center;">
                            <div style="font-size:11px;color:var(--contrast-low);font-weight:500;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Cotizaciones Pendientes</div>
                            <div style="font-size:32px;font-weight:700;color:var(--contrast-high);letter-spacing:-0.5px;">${window.App.formatCurrency(s.quotes_pending)}</div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Barras -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Ingresos Mensuales</div>
                    </div>
                    <div class="card-body" style="padding:16px 24px 24px;">
                        <canvas id="revenue-chart" height="240" style="width:100%;"></canvas>
                    </div>
                </div>

                <!-- Grid asimétrico: 65% / 35% -->
                <div style="display:grid;grid-template-columns:6.5fr 3.5fr;gap:var(--spacing-large);">
                    <div class="card" style="margin-bottom:0;">
                        <div class="card-header">
                            <div class="card-title">Facturas Recientes</div>
                            <a href="/invoices" class="btn btn-ghost btn-sm">Ver todas →</a>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <table>
                                <thead><tr><th>Factura</th><th>Cliente</th><th>Monto</th><th>Estado</th></tr></thead>
                                <tbody>
                                    ${data.recent_invoices.map(i => `
                                        <tr style="cursor:pointer" onclick="App.navigate('invoices/${i.id}')">
                                            <td style="font-weight:700;font-family:'JetBrains Mono',monospace;font-size:12px;">${i.invoice_number}</td>
                                            <td>${i.company_name || i.contact_name}</td>
                                            <td style="font-weight:700;">${window.App.formatCurrency(i.total, i.currency)}</td>
                                            <td><span class="badge badge-${i.status}">${statusLabel(i.status)}</span></td>
                                        </tr>
                                    `).join('') || `<tr><td colspan="4" style="text-align:center;padding:32px;color:var(--contrast-low);">No hay facturas recientes</td></tr>`}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card" style="margin-bottom:0;">
                        <div class="card-header">
                            <div class="card-title">Vencidas</div>
                        </div>
                        <div class="card-body" style="padding:0;">
                            ${data.overdue_invoices.length > 0 ? `
                            <div style="display:flex;flex-direction:column;">
                                ${data.overdue_invoices.map(i => `
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--contrast-border);cursor:pointer;" onclick="App.navigate('invoices/${i.id}')">
                                        <div>
                                            <div style="font-weight:700;font-size:13px;color:var(--contrast-high);">${i.company_name || i.contact_name}</div>
                                            <div style="font-size:11px;color:var(--contrast-medium);margin-top:2px;">${i.invoice_number} · ${window.App.formatDate(i.due_date)}</div>
                                        </div>
                                        <div style="font-weight:700;font-size:14px;color:var(--red);">${window.App.formatCurrency(i.balance, i.currency)}</div>
                                    </div>
                                `).join('')}
                            </div>` : `
                            <div style="text-align:center;padding:32px;color:var(--contrast-low);">
                                <div style="font-size:24px;margin-bottom:8px;">🎉</div>
                                <div style="font-size:13px;">¡No hay facturas vencidas!</div>
                            </div>`}
                        </div>
                    </div>
                </div>
            `;

            // Render chart
            this.renderChart(data.monthly_revenue);

        } catch (e) {
            container.innerHTML = `<div class="card"><div class="card-body" style="text-align:center;color:var(--red);padding:48px;">Error al cargar el panel</div></div>`;
        }
    },

    renderChart(months) {
        const canvas = document.getElementById('revenue-chart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = 240 * dpr;
        ctx.scale(dpr, dpr);

        const W = rect.width;
        const H = 240;
        const pad = { top: 24, right: 24, bottom: 44, left: 72 };
        const chartW = W - pad.left - pad.right;
        const chartH = H - pad.top - pad.bottom;

        const maxVal = Math.max(...months.map(m => Math.max(m.revenue, m.invoiced)), 1);
        const barW = chartW / months.length;

        // Grid lines — flat, fine, low contrast
        const gridColor = '#E5E7EB';
        ctx.strokeStyle = gridColor;
        ctx.lineWidth = 1;
        for (let i = 0; i <= 4; i++) {
            const y = pad.top + (chartH / 4) * i;
            ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke();
            // Y-axis labels — contrast-low
            ctx.fillStyle = '#9CA3AF';
            ctx.font = '11px Inter, sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(window.App.formatCurrency(maxVal - (maxVal / 4) * i, ''), pad.left - 10, y + 4);
        }

        months.forEach((m, i) => {
            const x = pad.left + i * barW + barW * 0.15;
            const bw = barW * 0.3;

            // Invoiced bar — low opacity fill, no border, rounded top only
            const ih = (m.invoiced / maxVal) * chartH;
            ctx.fillStyle = 'rgba(17, 24, 39, 0.08)';
            ctx.beginPath();
            ctx.roundRect(x, pad.top + chartH - ih, bw, ih, [4, 4, 0, 0]);
            ctx.fill();

            // Revenue bar — solid accent fill, rounded top only
            const rh = (m.revenue / maxVal) * chartH;
            ctx.fillStyle = '#111827';
            ctx.beginPath();
            ctx.roundRect(x + bw + 2, pad.top + chartH - rh, bw, rh, [4, 4, 0, 0]);
            ctx.fill();

            // X-axis label — contrast-low, micro-copy
            ctx.fillStyle = '#9CA3AF';
            ctx.font = '11px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(m.label, pad.left + i * barW + barW / 2, H - 12);
        });

        // Legend — flat pills
        const legendY = H - 20;
        ctx.fillStyle = 'rgba(17, 24, 39, 0.08)';
        ctx.beginPath(); ctx.roundRect(pad.left, legendY, 12, 10, 2); ctx.fill();
        ctx.fillStyle = '#9CA3AF'; ctx.font = '11px Inter, sans-serif'; ctx.textAlign = 'left';
        ctx.fillText('Facturado', pad.left + 16, legendY + 9);

        ctx.fillStyle = '#111827';
        ctx.beginPath(); ctx.roundRect(pad.left + 90, legendY, 12, 10, 2); ctx.fill();
        ctx.fillStyle = '#9CA3AF';
        ctx.fillText('Cobrado', pad.left + 106, legendY + 9);
    }
};
