const DashboardModule = {
    async render(container) {
        try {
            const data = await App.api('dashboard');
            const stats = data.stats || {};
            const recent = data.recent_invoices || [];
            const overdue = data.overdue_invoices || [];

            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Dashboard</h1>
                        <p class="page-subtitle">Resumen general de tu negocio</p>
                    </div>
                </div>

                <!-- Metric Cards -->
                <div class="grid-metrics">
                    <div class="metric-card">
                        <div class="metric-body">
                            <span class="metric-value">${App.formatCurrency(stats.revenue_this_month || 0)}</span>
                        </div>
                        <div class="metric-title">Cobrado Este Mes</div>
                        <div class="metric-card-icon green">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-body">
                            <span class="metric-value">${stats.invoiced_this_month || 0}</span>
                        </div>
                        <div class="metric-title">Facturas Este Mes</div>
                        <div class="metric-card-icon purple">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-body">
                            <span class="metric-value">${App.formatCurrency(stats.pending_amount || 0)}</span>
                        </div>
                        <div class="metric-title">Pendiente de Cobro</div>
                        <div class="metric-card-icon amber">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-body">
                            <span class="metric-value">${stats.overdue_count || 0}</span>
                            ${stats.overdue_count > 0 ? '<span class="metric-trend down">Vencidas</span>' : '<span class="metric-trend up">Al día</span>'}
                        </div>
                        <div class="metric-title">Facturas Vencidas</div>
                        <div class="metric-card-icon red">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        </div>
                    </div>
                </div>

                <!-- Charts & Tables Grid -->
                <div class="dashboard-grid">
                    <div>
                        <!-- Revenue Chart -->
                        <div class="table-outer" style="margin-bottom: var(--spacing-xl);">
                            <div class="table-toolbar">
                                <span style="font-size:14px;font-weight:600;">Facturación Mensual</span>
                            </div>
                            <div style="padding: var(--spacing-xl);">
                                <canvas id="revenue-chart" width="700" height="260" style="width:100%;height:260px;"></canvas>
                            </div>
                        </div>

                        <!-- Recent Invoices Table -->
                        <div class="table-outer">
                            <div class="table-toolbar">
                                <span style="font-size:14px;font-weight:600;">Facturas Recientes</span>
                                <a href="/facturas" class="btn btn-ghost btn-sm btn-view-all">Ver todas <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-arrow-icon"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
                            </div>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead><tr>
                                        <th>Número</th><th>Cliente</th><th>Fecha</th><th>Monto</th><th>Estado</th>
                                    </tr></thead>
                                    <tbody>
                                        ${recent.length > 0 ? recent.map(i => `
                                            <tr>
                                                <td><a href="#facturas/${i.id}" class="link-id">${i.invoice_number}</a></td>
                                                <td>
                                                    <div class="user-cell">
                                                        <div class="user-avatar-sm">${(i.company_name || i.contact_name || '?').charAt(0).toUpperCase()}</div>
                                                        <div class="user-details">
                                                            <span class="user-name">${i.company_name || i.contact_name}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>${App.formatDate(i.issue_date)}</td>
                                                <td style="font-weight:600;color:var(--color-text-primary)">${App.formatCurrency(i.total, i.currency)}</td>
                                                <td><span class="badge badge-${i.status}">${i.status}</span></td>
                                            </tr>
                                        `).join('') : '<tr><td colspan="5" class="text-center text-muted" style="padding:32px;">No hay facturas recientes</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                            <div class="mobile-card-list">
                                ${recent.length > 0 ? recent.map(i => `
                                    <a href="#facturas/${i.id}" class="mobile-card">
                                        <div class="mobile-card-middle">
                                            <div class="mobile-card-avatar">${(i.company_name || i.contact_name || '?').charAt(0).toUpperCase()}</div>
                                            <div class="mobile-card-info">
                                                <div class="mobile-card-name">${i.invoice_number}</div>
                                                <div class="mobile-card-sub">${i.company_name || i.contact_name}</div>
                                            </div>
                                            <div style="text-align:right">
                                                <div class="mobile-card-amount" style="font-size:15px">${App.formatCurrency(i.total, i.currency)}</div>
                                                <span class="badge badge-${i.status}" style="margin-top:4px">${i.status}</span>
                                            </div>
                                        </div>
                                    </a>
                                `).join('') : '<div class="text-center text-muted" style="padding:32px;">No hay facturas recientes</div>'}
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Overdue -->
                    <div>
                        <div class="table-outer">
                            <div class="table-toolbar">
                                <span style="font-size:14px;font-weight:600;">Vencidas</span>
                                <span class="badge badge-overdue">${overdue.length}</span>
                            </div>
                            <div style="padding: 0;">
                                ${overdue.length > 0 ? overdue.map(o => `
                                    <a href="#facturas/${o.id}" style="display:flex;align-items:center;justify-content:space-between;padding:14px var(--spacing-xl);border-bottom:1px solid var(--color-border);text-decoration:none;color:inherit;transition:background var(--transition-fast);" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">
                                        <div>
                                            <div style="font-weight:600;font-size:13px;color:var(--color-text-primary)">${o.invoice_number}</div>
                                            <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px">${o.company_name || o.contact_name}</div>
                                        </div>
                                        <div style="text-align:right">
                                            <div style="font-weight:700;font-size:14px;color:var(--color-danger-icon)">${App.formatCurrency(o.total, o.currency)}</div>
                                            <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px">${App.formatDate(o.due_date)}</div>
                                        </div>
                                    </a>
                                `).join('') : '<div style="padding:32px;text-align:center;color:var(--color-text-muted);font-size:13px;">No hay facturas vencidas</div>'}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Render chart
            this.renderChart(data.monthly_data || []);
        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar el dashboard</div>`;
        }
    },

    renderChart(months) {
        const canvas = document.getElementById('revenue-chart');
        if (!canvas || months.length === 0) return;

        const ctx = canvas.getContext('2d');
        const W = canvas.width = canvas.offsetWidth * 2;
        const H = canvas.height = 520;
        ctx.scale(1, 1);

        const pad = { top: 24, right: 20, bottom: 50, left: 80 };
        const chartW = W - pad.left - pad.right;
        const chartH = H - pad.top - pad.bottom;

        const maxVal = Math.max(...months.map(m => Math.max(m.invoiced, m.revenue)), 1000) * 1.15;
        const barW = chartW / months.length;

        // Dynamic Computed Styles
        const style = getComputedStyle(document.documentElement);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = style.getPropertyValue('--color-border').trim() || '#E5E7EB';
        const textColor = style.getPropertyValue('--color-text-muted').trim() || '#9CA3AF';
        const primaryColor = style.getPropertyValue('--color-text-primary').trim() || '#111827';

        // Grid lines
        ctx.strokeStyle = gridColor;
        ctx.lineWidth = 1;
        for (let i = 0; i <= 4; i++) {
            const y = pad.top + (chartH / 4) * i;
            ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke();
            ctx.fillStyle = textColor;
            ctx.font = '11px Inter, sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(window.App.formatCurrency(maxVal - (maxVal / 4) * i, ''), pad.left - 10, y + 4);
        }

        months.forEach((m, i) => {
            const x = pad.left + i * barW + barW * 0.15;
            const bw = barW * 0.3;

            // Invoiced bar with linear gradient
            const ih = (m.invoiced / maxVal) * chartH;
            const yStartInv = pad.top + chartH - ih;
            const yEndInv = pad.top + chartH;
            const invGrad = ctx.createLinearGradient(0, yStartInv, 0, yEndInv);
            invGrad.addColorStop(0, isDark ? 'rgba(255, 255, 255, 0.18)' : 'rgba(17, 24, 39, 0.12)');
            invGrad.addColorStop(1, isDark ? 'rgba(255, 255, 255, 0.02)' : 'rgba(17, 24, 39, 0.02)');

            ctx.fillStyle = invGrad;
            ctx.beginPath();
            ctx.roundRect(x, yStartInv, bw, ih, [4, 4, 0, 0]);
            ctx.fill();

            // Revenue bar with linear gradient
            const rh = (m.revenue / maxVal) * chartH;
            const yStartRev = pad.top + chartH - rh;
            const yEndRev = pad.top + chartH;
            const revGrad = ctx.createLinearGradient(0, yStartRev, 0, yEndRev);
            revGrad.addColorStop(0, primaryColor);
            revGrad.addColorStop(1, isDark ? 'rgba(255, 255, 255, 0.25)' : 'rgba(17, 24, 39, 0.3)');

            ctx.fillStyle = revGrad;
            ctx.beginPath();
            ctx.roundRect(x + bw + 2, yStartRev, bw, rh, [4, 4, 0, 0]);
            ctx.fill();

            // X-axis label
            ctx.fillStyle = textColor;
            ctx.font = '11px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(m.label, pad.left + i * barW + barW / 2, H - 12);
        });

        // Legend matched to gradients
        const legendY = H - 20;
        ctx.fillStyle = isDark ? 'rgba(255, 255, 255, 0.18)' : 'rgba(17, 24, 39, 0.12)';
        ctx.beginPath(); ctx.roundRect(pad.left, legendY, 12, 10, 2); ctx.fill();
        ctx.fillStyle = textColor; ctx.font = '11px Inter, sans-serif'; ctx.textAlign = 'left';
        ctx.fillText('Facturado', pad.left + 16, legendY + 9);

        ctx.fillStyle = primaryColor;
        ctx.beginPath(); ctx.roundRect(pad.left + 90, legendY, 12, 10, 2); ctx.fill();
        ctx.fillStyle = textColor;
        ctx.fillText('Cobrado', pad.left + 106, legendY + 9);
    }
};

window.DashboardModule = DashboardModule;
export default DashboardModule;
