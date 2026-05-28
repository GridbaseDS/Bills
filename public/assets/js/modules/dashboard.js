const DashboardModule = {
    async render(container) {
        try {
            const data = await App.api('dashboard');
            const stats = data.stats || {};
            const recent = data.recent_invoices || [];
            const overdue = data.overdue_invoices || [];

            // Calculate monthly trend dynamically
            const monthlyData = data.monthly_data || [];
            const currentMonthData = monthlyData[monthlyData.length - 1] || { revenue: 0, expense: 0 };
            const prevMonthData = monthlyData[monthlyData.length - 2] || { revenue: 0, expense: 0 };
            let trendPct = 0;
            let isTrendUp = true;
            if (prevMonthData.revenue > 0) {
                const diff = currentMonthData.revenue - prevMonthData.revenue;
                trendPct = Math.round((diff / prevMonthData.revenue) * 100);
                isTrendUp = diff >= 0;
            } else if (currentMonthData.revenue > 0) {
                trendPct = 100;
                isTrendUp = true;
            }

            const trendBadge = trendPct !== 0 ? `
                <div style="display:flex; align-items:center; gap:4px; padding:4px 8px; border-radius:6px; font-weight:600; font-size:13px; color:${isTrendUp ? '#10B981' : '#EF4444'}; background:${isTrendUp ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'};">
                    <svg style="width:14px; height:14px;" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        ${isTrendUp 
                            ? '<path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0 4 4m-4-4-4 4"/>' 
                            : '<path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0 4-4m-4 4-4-4"/>'}
                    </svg>
                    <span>${Math.abs(trendPct)}%</span>
                </div>
            ` : '';

            const firstName = (App.state?.user?.name || 'Usuario').split(' ')[0];

            container.innerHTML = `
                <style>
                    /* Premium Custom Styling & Dynamic Transitions for Tenant Dashboard */
                    .dashboard-card-hover {
                        transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1), 
                                    box-shadow 0.22s cubic-bezier(0.4, 0, 0.2, 1), 
                                    border-color 0.22s cubic-bezier(0.4, 0, 0.2, 1) !important;
                    }
                    .dashboard-card-hover:hover {
                        transform: translateY(-4px);
                        box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.08), 0 4px 12px -2px rgba(0, 0, 0, 0.03) !important;
                        border-color: rgba(17, 24, 39, 0.12) !important;
                    }
                    [data-theme="dark"] .dashboard-card-hover:hover {
                        border-color: rgba(255, 255, 255, 0.15) !important;
                        box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.5), 0 4px 12px -2px rgba(0, 0, 0, 0.3) !important;
                    }
                    
                    /* Accentuated visual response on hover for metric icons */
                    .metric-card .metric-card-icon {
                        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
                    }
                    .metric-card:hover .metric-card-icon {
                        transform: scale(1.12) rotate(6deg);
                    }
                    
                    /* Red pulse dot for overdue invoices indicator */
                    .pulse-dot {
                        width: 8px;
                        height: 8px;
                        border-radius: 50%;
                        background-color: #EF4444;
                        display: inline-block;
                        margin-right: 8px;
                        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
                        animation: pulse-red 2s infinite;
                    }
                    @keyframes pulse-red {
                        0% {
                            transform: scale(0.95);
                            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
                        }
                        70% {
                            transform: scale(1);
                            box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
                        }
                        100% {
                            transform: scale(0.95);
                            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
                        }
                    }
                </style>

                <div class="page-header">
                    <div>
                        <h1 class="page-title">Hola, ${firstName}</h1>
                        <p class="page-subtitle">Resumen general de tu negocio</p>
                    </div>
                </div>

                <!-- Metric Cards -->
                <div class="grid-metrics">
                    <div class="metric-card dashboard-card-hover">
                        <div class="metric-body">
                            <span class="metric-value">${App.formatCurrency(stats.revenue_this_month || 0)}</span>
                        </div>
                        <div class="metric-title">Cobrado Este Mes</div>
                        <div class="metric-card-icon green">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                    </div>
                    <div class="metric-card dashboard-card-hover">
                        <div class="metric-body">
                            <span class="metric-value">${stats.invoiced_this_month || 0}</span>
                        </div>
                        <div class="metric-title">Facturas Este Mes</div>
                        <div class="metric-card-icon purple">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric-card dashboard-card-hover">
                        <div class="metric-body">
                            <span class="metric-value">${App.formatCurrency(stats.pending_amount || 0)}</span>
                        </div>
                        <div class="metric-title">Pendiente de Cobro</div>
                        <div class="metric-card-icon amber">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric-card dashboard-card-hover">
                        <div class="metric-body">
                            <span class="metric-value">${stats.overdue_count || 0}</span>
                            ${stats.overdue_count > 0 ? '<span class="metric-trend down">Vencidas</span>' : '<span class="metric-trend up">Al día</span>'}
                        </div>
                        <div class="metric-title">Facturas Vencidas</div>
                        <div class="metric-card-icon red">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue Chart Section -->
                <div class="table-outer dashboard-card-hover" style="margin-top:24px; padding: 24px;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom: 16px;">
                        <div>
                            <span style="font-size:12px; color:var(--color-text-muted); display:block; text-transform:uppercase; font-weight:600; letter-spacing:0.05em; margin-bottom:4px;">Análisis de Facturación</span>
                            <h5 style="font-size:24px; font-weight:700; color:var(--color-text-primary); margin:0;">${App.formatCurrency(stats.revenue_this_month || 0)}</h5>
                            <p style="font-size:12px; color:var(--color-text-muted); margin:4px 0 0 0;">Cobrado Este Mes</p>
                        </div>
                        ${trendBadge}
                    </div>
                    <div id="area-chart" style="width:100%; min-height:220px; margin: 12px 0;"></div>
                    <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--color-border); padding-top:16px; margin-top:8px;">
                        <button id="dropdownDefaultButton" class="btn-ghost" style="font-size:13px; font-weight:600; color:var(--color-text-muted); display:inline-flex; align-items:center; gap:6px; border:none; background:none; cursor:pointer;" type="button">
                            Este Año
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-muted);"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <a href="#reportes" style="font-size:13px; font-weight:600; color:var(--color-primary); text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            Ver Reportes
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </a>
                    </div>
                </div>

                <!-- Recent and Overdue Grid -->
                <div class="dashboard-grid" style="margin-top:24px;">
                    <!-- Left Column: Recent Invoices -->
                    <div>
                        <div class="table-outer dashboard-card-hover">
                            <div class="table-toolbar">
                                <span style="font-size:14px;font-weight:600;">Facturas Recientes</span>
                                <a href="#facturas" class="btn btn-ghost btn-sm btn-view-all">Ver todas <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-arrow-icon"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
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
                        <div class="table-outer dashboard-card-hover">
                            <div class="table-toolbar">
                                <div style="display:flex;align-items:center;">
                                    ${overdue.length > 0 ? '<span class="pulse-dot"></span>' : ''}
                                    <span style="font-size:14px;font-weight:600;">Vencidas</span>
                                </div>
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

        // Clear container
        container.innerHTML = '';

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

        const options = {
            chart: {
                height: 220,
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
                width: 3.5
            },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.45,
                    opacityTo: 0.0,
                    shadeIntensity: 1
                }
            },
            grid: {
                show: true,
                strokeDashArray: 4,
                borderColor: isDark ? '#334155' : '#E2E8F0',
                padding: {
                    left: 10,
                    right: 10,
                    top: 0,
                    bottom: 0
                }
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
                        colors: 'var(--color-text-muted)',
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
                        colors: 'var(--color-text-muted)',
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
                hover: {
                    size: 6
                }
            }
        };

        const chart = new ApexCharts(container, options);
        chart.render();
    }
};

window.DashboardModule = DashboardModule;
export default DashboardModule;
