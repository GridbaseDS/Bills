const DashboardModule = {
    async render(container) {
        try {
            const data = await App.api('dashboard');
            const stats = data.stats || {};
            const recent = data.recent_invoices || [];
            const overdue = data.overdue_invoices || [];
            const monthlyData = data.monthly_data || [];

            // Trend calculation
            const cur = monthlyData[monthlyData.length - 1] || { revenue: 0, expense: 0 };
            const prev = monthlyData[monthlyData.length - 2] || { revenue: 0, expense: 0 };
            let trendPct = 0;
            let isTrendUp = true;
            if (prev.revenue > 0) {
                const diff = cur.revenue - prev.revenue;
                trendPct = Math.round((diff / prev.revenue) * 100);
                isTrendUp = diff >= 0;
            } else if (cur.revenue > 0) {
                trendPct = 100;
            }

            const firstName = (App.state?.user?.name || 'Usuario').split(' ')[0];

            container.innerHTML = `
                <style>
                    /* ═══════════════════════════════════════════════
                       DASHBOARD — Premium Redesign v4
                       GridBase Charcoal Design Language
                       ═══════════════════════════════════════════════ */

                    .dash {
                        display: flex;
                        flex-direction: column;
                        gap: 20px;
                        width: 100%;
                        padding-bottom: 64px;
                        animation: dashFadeIn 0.4s ease both;
                    }

                    @keyframes dashFadeIn {
                        from { opacity: 0; transform: translateY(8px); }
                        to   { opacity: 1; transform: none; }
                    }

                    /* ── Hero Banner ── */
                    .dash-hero {
                        background: var(--bg-card);
                        border: 1px solid var(--color-border);
                        border-radius: var(--radius-xl);
                        padding: 28px 32px 24px;
                        position: relative;
                        overflow: hidden;
                        box-shadow: var(--shadow-sm);
                    }
                    .dash-hero::before {
                        content: '';
                        position: absolute;
                        top: 0; left: 0; right: 0;
                        height: 3px;
                        background: linear-gradient(90deg, #111827 0%, #4B5563 50%, #9CA3AF 100%);
                        border-radius: var(--radius-xl) var(--radius-xl) 0 0;
                    }
                    [data-theme="dark"] .dash-hero::before {
                        background: linear-gradient(90deg, #F9FAFB 0%, #6B7280 50%, #374151 100%);
                    }
                    .dash-hero-top {
                        display: flex;
                        align-items: flex-start;
                        justify-content: space-between;
                        gap: 16px;
                    }
                    .dash-hero-greeting {
                        font-size: 13px;
                        font-weight: 500;
                        color: var(--color-text-muted);
                        letter-spacing: 0.02em;
                        margin-bottom: 6px;
                    }
                    .dash-hero-revenue {
                        font-size: 34px;
                        font-weight: 800;
                        color: var(--color-text-primary);
                        letter-spacing: -0.03em;
                        line-height: 1.1;
                    }
                    .dash-hero-label {
                        font-size: 13px;
                        color: var(--color-text-secondary);
                        margin-top: 6px;
                        font-weight: 500;
                    }
                    .dash-hero-trend {
                        display: inline-flex;
                        align-items: center;
                        gap: 4px;
                        padding: 5px 10px;
                        border-radius: var(--radius-full);
                        font-size: 12px;
                        font-weight: 600;
                        flex-shrink: 0;
                        margin-top: 4px;
                    }
                    .dash-hero-trend.up {
                        background: var(--color-success-bg);
                        color: var(--color-success-text);
                    }
                    .dash-hero-trend.down {
                        background: var(--color-danger-bg);
                        color: var(--color-danger-text);
                    }
                    .dash-hero-trend svg { width: 13px; height: 13px; }

                    /* ── KPI Strip ── */
                    .dash-kpis {
                        display: grid;
                        grid-template-columns: repeat(4, 1fr);
                        gap: 14px;
                    }
                    .dash-kpi {
                        background: var(--bg-card);
                        border: 1px solid var(--color-border);
                        border-radius: var(--radius-lg);
                        padding: 18px 20px;
                        display: flex;
                        align-items: flex-start;
                        gap: 12px;
                        transition: transform 0.2s cubic-bezier(0.4,0,0.2,1),
                                    box-shadow 0.2s cubic-bezier(0.4,0,0.2,1),
                                    border-color 0.2s ease;
                        box-shadow: var(--shadow-sm);
                    }
                    .dash-kpi:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 8px 20px -6px rgba(0,0,0,0.08);
                        border-color: var(--color-border-hover);
                    }
                    [data-theme="dark"] .dash-kpi:hover {
                        box-shadow: 0 8px 24px -8px rgba(0,0,0,0.5);
                    }
                    .dash-kpi-dot {
                        width: 8px;
                        height: 8px;
                        border-radius: 50%;
                        margin-top: 7px;
                        flex-shrink: 0;
                    }
                    .dash-kpi-dot.green  { background: var(--color-success-icon); }
                    .dash-kpi-dot.purple { background: var(--purple); }
                    .dash-kpi-dot.amber  { background: var(--amber); }
                    .dash-kpi-dot.red    { background: var(--color-danger-icon); }
                    .dash-kpi-value {
                        font-size: 20px;
                        font-weight: 700;
                        color: var(--color-text-primary);
                        letter-spacing: -0.02em;
                        line-height: 1.2;
                    }
                    .dash-kpi-label {
                        font-size: 12px;
                        font-weight: 500;
                        color: var(--color-text-muted);
                        margin-top: 2px;
                    }
                    .dash-kpi-badge {
                        display: inline-block;
                        margin-top: 4px;
                        font-size: 10px;
                        font-weight: 600;
                        padding: 2px 7px;
                        border-radius: var(--radius-full);
                    }
                    .dash-kpi-badge.success {
                        background: var(--color-success-bg);
                        color: var(--color-success-text);
                    }
                    .dash-kpi-badge.danger {
                        background: var(--color-danger-bg);
                        color: var(--color-danger-text);
                    }

                    /* ── Chart Section ── */
                    .dash-chart-card {
                        background: var(--bg-card);
                        border: 1px solid var(--color-border);
                        border-radius: var(--radius-xl);
                        padding: 24px 28px;
                        box-shadow: var(--shadow-sm);
                    }
                    .dash-chart-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        margin-bottom: 20px;
                    }
                    .dash-chart-title {
                        font-size: 14px;
                        font-weight: 600;
                        color: var(--color-text-primary);
                    }
                    .dash-chart-subtitle {
                        font-size: 12px;
                        color: var(--color-text-muted);
                        margin-top: 2px;
                    }
                    .dash-chart-legend {
                        display: flex;
                        gap: 16px;
                    }
                    .dash-legend-item {
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        font-size: 12px;
                        font-weight: 500;
                        color: var(--color-text-secondary);
                    }
                    .dash-legend-dot {
                        width: 8px;
                        height: 8px;
                        border-radius: 50%;
                    }

                    /* ── Bottom Two-Column Grid ── */
                    .dash-bottom {
                        display: grid;
                        grid-template-columns: 1.6fr 1fr;
                        gap: 20px;
                    }

                    /* ── Section Card (shared for recent + overdue) ── */
                    .dash-section {
                        background: var(--bg-card);
                        border: 1px solid var(--color-border);
                        border-radius: var(--radius-xl);
                        box-shadow: var(--shadow-sm);
                        overflow: hidden;
                        display: flex;
                        flex-direction: column;
                    }
                    .dash-section-head {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 16px 24px;
                        border-bottom: 1px solid var(--color-border);
                    }
                    .dash-section-title {
                        font-size: 14px;
                        font-weight: 600;
                        color: var(--color-text-primary);
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }
                    .dash-section-link {
                        font-size: 12px;
                        font-weight: 600;
                        color: var(--color-text-muted);
                        text-decoration: none;
                        display: inline-flex;
                        align-items: center;
                        gap: 4px;
                        transition: color 0.15s ease;
                    }
                    .dash-section-link:hover {
                        color: var(--color-text-primary);
                    }
                    .dash-section-link svg {
                        width: 13px; height: 13px;
                        transition: transform 0.15s ease;
                    }
                    .dash-section-link:hover svg {
                        transform: translateX(2px);
                    }

                    /* ── Recent Invoices Table ── */
                    .dash-table { width: 100%; border-collapse: collapse; }
                    .dash-table th {
                        text-align: left;
                        padding: 10px 24px;
                        font-size: 11px;
                        font-weight: 600;
                        color: var(--color-text-muted);
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        border-bottom: 1px solid var(--color-border);
                    }
                    .dash-table td {
                        padding: 12px 24px;
                        font-size: 13px;
                        color: var(--color-text-secondary);
                        border-bottom: 1px solid var(--color-border);
                        vertical-align: middle;
                    }
                    .dash-table tbody tr { transition: background 0.12s ease; }
                    .dash-table tbody tr:hover { background: var(--bg-hover); }
                    .dash-table tbody tr:last-child td { border-bottom: none; }

                    /* ── Overdue List ── */
                    .dash-overdue-item {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 14px 24px;
                        border-bottom: 1px solid var(--color-border);
                        text-decoration: none;
                        color: inherit;
                        transition: background 0.12s ease;
                    }
                    .dash-overdue-item:hover { background: var(--bg-hover); }
                    .dash-overdue-item:last-child { border-bottom: none; }

                    /* ── Pulse dot for overdue ── */
                    .dash-pulse {
                        width: 7px; height: 7px;
                        border-radius: 50%;
                        background: var(--color-danger-icon);
                        display: inline-block;
                        box-shadow: 0 0 0 0 rgba(239,68,68,0.6);
                        animation: dashPulse 2s infinite;
                    }
                    @keyframes dashPulse {
                        0%  { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239,68,68,0.6); }
                        70% { transform: scale(1);    box-shadow: 0 0 0 5px rgba(239,68,68,0); }
                        100%{ transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239,68,68,0); }
                    }

                    /* ── Empty State ── */
                    .dash-empty {
                        padding: 32px 24px;
                        text-align: center;
                        color: var(--color-text-muted);
                        font-size: 13px;
                    }

                    /* ── Mobile Card List (hidden on desktop) ── */
                    .dash-mobile-cards { display: none; }

                    /* ── Footer ── */
                    .dash-footer {
                        position: fixed;
                        bottom: 0; right: 0;
                        left: var(--sidebar-w);
                        background: var(--bg-card);
                        border-top: 1px solid var(--color-border);
                        padding: 12px 24px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 100;
                        box-shadow: 0 -1px 6px rgba(0,0,0,0.02);
                        transition: left var(--transition-normal);
                    }
                    .dash-footer-link {
                        font-size: 11px;
                        color: var(--color-text-muted);
                        text-decoration: none;
                        font-weight: 500;
                        transition: color 0.15s ease;
                    }
                    .dash-footer-link:hover { color: var(--color-text-primary); }
                    .dash-footer-link span { font-weight: 700; color: var(--color-text-secondary); }

                    /* ═══ Responsive ═══ */

                    @media (max-width: 1100px) {
                        .dash-kpis { grid-template-columns: repeat(2, 1fr); }
                    }

                    @media (max-width: 960px) {
                        .dash-bottom { grid-template-columns: 1fr; }
                    }

                    @media (max-width: 640px) {
                        .dash {
                            padding: 16px 16px 140px;
                            gap: 14px;
                        }
                        .dash-hero {
                            padding: 20px 18px 18px;
                            border-radius: var(--radius-lg);
                        }
                        .dash-hero-revenue { font-size: 26px; }
                        .dash-hero-greeting { font-size: 12px; }

                        .dash-kpis { grid-template-columns: 1fr 1fr; gap: 10px; }
                        .dash-kpi {
                            padding: 14px 14px;
                            border-radius: var(--radius-md);
                        }
                        .dash-kpi-value { font-size: 17px; }
                        .dash-kpi-label { font-size: 11px; }

                        .dash-chart-card {
                            padding: 16px 14px;
                            border-radius: var(--radius-lg);
                        }
                        .dash-chart-header { flex-direction: column; align-items: flex-start; gap: 8px; }

                        .dash-bottom { grid-template-columns: 1fr; gap: 14px; }

                        .dash-section { border-radius: var(--radius-lg); }
                        .dash-section-head { padding: 14px 16px; }
                        .dash-table th, .dash-table td { padding: 10px 16px; font-size: 12px; }

                        /* Show mobile cards, hide table */
                        .dash-table-wrap { display: none !important; }
                        .dash-mobile-cards { display: flex !important; flex-direction: column; }

                        .dash-overdue-item { padding: 12px 16px; }

                        .dash-footer {
                            left: 0;
                            bottom: calc(60px + env(safe-area-inset-bottom));
                            padding: 10px 16px;
                        }
                    }
                </style>

                <div class="dash">
                    <!-- Hero Revenue Banner -->
                    <div class="dash-hero">
                        <div class="dash-hero-top">
                            <div>
                                <div class="dash-hero-greeting">Bienvenido, ${firstName}</div>
                                <div class="dash-hero-revenue">${App.formatCurrency(stats.revenue_this_month || 0)}</div>
                                <div class="dash-hero-label">Ingresos cobrados este mes</div>
                            </div>
                            ${trendPct !== 0 ? `
                                <div class="dash-hero-trend ${isTrendUp ? 'up' : 'down'}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        ${isTrendUp
                                            ? '<path d="M12 19V5m0 0 4 4m-4-4-4 4"/>'
                                            : '<path d="M12 5v14m0 0 4-4m-4 4-4-4"/>'}
                                    </svg>
                                    ${Math.abs(trendPct)}% vs mes anterior
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <!-- KPI Strip -->
                    <div class="dash-kpis">
                        <div class="dash-kpi">
                            <span class="dash-kpi-dot green"></span>
                            <div>
                                <div class="dash-kpi-value">${App.formatCurrency(stats.revenue_this_month || 0)}</div>
                                <div class="dash-kpi-label">Cobrado</div>
                            </div>
                        </div>
                        <div class="dash-kpi">
                            <span class="dash-kpi-dot purple"></span>
                            <div>
                                <div class="dash-kpi-value">${stats.invoiced_this_month || 0}</div>
                                <div class="dash-kpi-label">Facturas emitidas</div>
                            </div>
                        </div>
                        <div class="dash-kpi">
                            <span class="dash-kpi-dot amber"></span>
                            <div>
                                <div class="dash-kpi-value">${App.formatCurrency(stats.pending_amount || 0)}</div>
                                <div class="dash-kpi-label">Pendiente de cobro</div>
                            </div>
                        </div>
                        <div class="dash-kpi">
                            <span class="dash-kpi-dot red"></span>
                            <div>
                                <div class="dash-kpi-value">${stats.overdue_count || 0}</div>
                                <div class="dash-kpi-label">Vencidas</div>
                                ${stats.overdue_count > 0
                                    ? '<span class="dash-kpi-badge danger">Atención</span>'
                                    : '<span class="dash-kpi-badge success">Al día</span>'}
                            </div>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="dash-chart-card">
                        <div class="dash-chart-header">
                            <div>
                                <div class="dash-chart-title">Análisis de Facturación</div>
                                <div class="dash-chart-subtitle">Últimos 12 meses</div>
                            </div>
                            <div class="dash-chart-legend">
                                <span class="dash-legend-item"><span class="dash-legend-dot" style="background:#10B981"></span> Ingresos</span>
                                <span class="dash-legend-item"><span class="dash-legend-dot" style="background:#3B82F6"></span> Gastos</span>
                            </div>
                        </div>
                        <div id="area-chart" style="width:100%; min-height:240px;"></div>
                    </div>

                    <!-- Bottom Two-Column Grid -->
                    <div class="dash-bottom">
                        <!-- Recent Invoices -->
                        <div class="dash-section">
                            <div class="dash-section-head">
                                <span class="dash-section-title">Facturas Recientes</span>
                                <a href="#facturas" class="dash-section-link">
                                    Ver todas
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </a>
                            </div>
                            <div class="dash-table-wrap">
                                <table class="dash-table">
                                    <thead><tr>
                                        <th>Número</th><th>Cliente</th><th>Monto</th><th>Estado</th>
                                    </tr></thead>
                                    <tbody>
                                        ${recent.length > 0 ? recent.map(i => `
                                            <tr>
                                                <td><a href="#facturas/${i.id}" class="link-id">${i.invoice_number}</a></td>
                                                <td>
                                                    <div class="user-cell">
                                                        <div class="user-avatar-sm">${(i.company_name || i.contact_name || '?').charAt(0).toUpperCase()}</div>
                                                        <span class="user-name" style="font-size:13px">${i.company_name || i.contact_name}</span>
                                                    </div>
                                                </td>
                                                <td style="font-weight:600;color:var(--color-text-primary)">${App.formatCurrency(i.total, i.currency)}</td>
                                                <td><span class="badge badge-${i.status}">${i.status}</span></td>
                                            </tr>
                                        `).join('') : '<tr><td colspan="4" class="dash-empty">No hay facturas recientes</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                            <!-- Mobile version -->
                            <div class="dash-mobile-cards">
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
                                `).join('') : '<div class="dash-empty">No hay facturas recientes</div>'}
                            </div>
                        </div>

                        <!-- Overdue Invoices -->
                        <div class="dash-section">
                            <div class="dash-section-head">
                                <span class="dash-section-title">
                                    ${overdue.length > 0 ? '<span class="dash-pulse"></span>' : ''}
                                    Vencidas
                                </span>
                                <span class="badge badge-overdue">${overdue.length}</span>
                            </div>
                            <div style="display:flex; flex-direction:column; flex:1;">
                                ${overdue.length > 0 ? overdue.map(o => `
                                    <a href="#facturas/${o.id}" class="dash-overdue-item">
                                        <div>
                                            <div style="font-weight:600;font-size:13px;color:var(--color-text-primary)">${o.invoice_number}</div>
                                            <div style="font-size:12px;color:var(--color-text-muted);margin-top:1px">${o.company_name || o.contact_name}</div>
                                        </div>
                                        <div style="text-align:right">
                                            <div style="font-weight:700;font-size:14px;color:var(--color-danger-icon)">${App.formatCurrency(o.total, o.currency)}</div>
                                            <div style="font-size:11px;color:var(--color-text-muted);margin-top:1px">${App.formatDate(o.due_date)}</div>
                                        </div>
                                    </a>
                                `).join('') : '<div class="dash-empty">No hay facturas vencidas</div>'}
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <footer class="dash-footer">
                        <a href="https://gridbase.com.do" target="_blank" class="dash-footer-link">
                            Bills by <span>GridBase Digital Solutions</span>
                        </a>
                    </footer>
                </div>
            `;

            // Render chart
            this.renderChart(monthlyData);
        } catch (e) {
            container.innerHTML = `<div class="text-red" style="padding:32px;text-align:center;">Error al cargar el dashboard</div>`;
        }
    },

    renderChart(months) {
        this.activeMonthsData = months;
        this.drawApexChart();
    },

    drawApexChart() {
        const container = document.getElementById('area-chart');
        const months = this.activeMonthsData;
        if (!container || !months || months.length === 0) return;

        if (typeof ApexCharts === 'undefined') {
            setTimeout(() => this.drawApexChart(), 100);
            return;
        }

        container.innerHTML = '';

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

        const options = {
            chart: {
                height: 240,
                type: 'area',
                fontFamily: 'Inter, sans-serif',
                toolbar: { show: false },
                sparkline: { enabled: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            stroke: {
                curve: 'smooth',
                width: 2.5
            },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.35,
                    opacityTo: 0.0,
                    shadeIntensity: 1
                }
            },
            grid: {
                show: true,
                strokeDashArray: 4,
                borderColor: isDark ? '#1F2937' : '#F3F4F6',
                padding: { left: 8, right: 8, top: 0, bottom: 0 }
            },
            series: [
                {
                    name: 'Ingresos',
                    data: months.map(m => m.revenue),
                    color: '#10B981'
                },
                {
                    name: 'Gastos',
                    data: months.map(m => m.expense),
                    color: '#3B82F6'
                }
            ],
            xaxis: {
                categories: months.map(m => m.label),
                labels: {
                    show: true,
                    style: {
                        colors: isDark ? '#6B7280' : '#9CA3AF',
                        fontFamily: 'Inter, sans-serif',
                        fontSize: '11px',
                        fontWeight: 500
                    }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    show: true,
                    style: {
                        colors: isDark ? '#6B7280' : '#9CA3AF',
                        fontFamily: 'Inter, sans-serif',
                        fontSize: '11px',
                        fontWeight: 500
                    },
                    formatter: function(val) {
                        return App.formatCurrency(val, '').replace(',00', '');
                    }
                }
            },
            tooltip: {
                theme: isDark ? 'dark' : 'light',
                y: {
                    formatter: function(val) {
                        return App.formatCurrency(val);
                    }
                }
            },
            markers: {
                size: 0,
                hover: { size: 5 }
            },
            dataLabels: {
                enabled: false
            }
        };

        const chart = new ApexCharts(container, options);
        chart.render();
    }
};

window.DashboardModule = DashboardModule;
export default DashboardModule;
