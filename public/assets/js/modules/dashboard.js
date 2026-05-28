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
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue Chart Section -->
                <div class="table-outer" style="margin-top:24px; padding: 24px;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom: 24px;">
                        <div>
                            <span style="font-size:16px; font-weight:700; color:var(--color-text-primary); display:block; margin-bottom:8px;">Análisis de Facturación</span>
                            <div style="display:flex; align-items:center; gap:16px;">
                                <span style="font-size:12px; color:var(--color-text-muted); display:inline-flex; align-items:center; gap:6px;">
                                    <span style="width:8px; height:8px; border-radius:50%; background:#10B981; display:inline-block;"></span>
                                    Ingresos
                                </span>
                                <span style="font-size:12px; color:var(--color-text-muted); display:inline-flex; align-items:center; gap:6px;">
                                    <span style="width:8px; height:8px; border-radius:50%; background:#3B82F6; display:inline-block;"></span>
                                    Gastos
                                </span>
                            </div>
                        </div>
                        <div style="display:inline-flex; align-items:center; gap:8px; padding: 8px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 12px; color: var(--color-text-primary); font-weight: 500; background: var(--color-bg-card);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-muted);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <span>Este Año</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-muted); margin-left: 4px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </div>
                    <div style="width:100%; position:relative; min-height:280px;" id="revenue-chart-container">
                        <!-- SVG Chart will be rendered here -->
                    </div>
                </div>

                <!-- Recent and Overdue Grid -->
                <div class="grid-2-1" style="margin-top:24px;">
                    <!-- Left Column: Recent Invoices -->
                    <div>
                        <div class="table-outer">
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
        this.activeMonthsData = months;
        this.drawSvgChart();

        // Re-render on window resize to ensure fluid responsiveness without any pixelation
        if (window.revenueChartResizeListener) {
            window.removeEventListener('resize', window.revenueChartResizeListener);
        }
        window.revenueChartResizeListener = () => {
            if (this.activeMonthsData) {
                this.drawSvgChart();
            }
        };
        window.addEventListener('resize', window.revenueChartResizeListener);
    },

    drawSvgChart() {
        const container = document.getElementById('revenue-chart-container');
        const months = this.activeMonthsData;
        if (!container || !months || months.length === 0) return;

        // Clear container
        container.innerHTML = '';

        // Dynamic width based on actual screen size, and a locked elegant height
        const W = container.offsetWidth || 700;
        const H = 220;
        const pad = { top: 20, right: 20, bottom: 25, left: 65 };
        const chartW = W - pad.left - pad.right;
        const chartH = H - pad.top - pad.bottom;

        // Max value for scaling
        const maxVal = Math.max(...months.map(m => Math.max(m.revenue, m.expense)), 100) * 1.15;

        // Compute coordinate points
        const pointsRev = [];
        const pointsExp = [];
        months.forEach((m, i) => {
            const x = pad.left + (i / (months.length - 1)) * chartW;
            const yRev = pad.top + chartH - (m.revenue / maxVal) * chartH;
            const yExp = pad.top + chartH - (m.expense / maxVal) * chartH;
            pointsRev.push({ x, y: yRev, value: m.revenue, label: m.label, month: m.month });
            pointsExp.push({ x, y: yExp, value: m.expense, label: m.label, month: m.month });
        });

        // Generate bezier path
        const getBezierPath = (pts) => {
            if (pts.length === 0) return '';
            let d = `M ${pts[0].x} ${pts[0].y}`;
            for (let i = 1; i < pts.length; i++) {
                const p0 = pts[i - 1];
                const p1 = pts[i];
                const cp1x = p0.x + (p1.x - p0.x) * 0.35;
                const cp1y = p0.y;
                const cp2x = p1.x - (p1.x - p0.x) * 0.35;
                const cp2y = p1.y;
                d += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p1.x} ${p1.y}`;
            }
            return d;
        };

        const getAreaPath = (pts) => {
            if (pts.length === 0) return '';
            const bezier = getBezierPath(pts);
            const bottomY = pad.top + chartH;
            return `${bezier} L ${pts[pts.length - 1].x} ${bottomY} L ${pts[0].x} ${bottomY} Z`;
        };

        const pathRev = getBezierPath(pointsRev);
        const areaRev = getAreaPath(pointsRev);
        const pathExp = getBezierPath(pointsExp);
        const areaExp = getAreaPath(pointsExp);

        // Generate SVG elements
        let svgHTML = `
            <svg viewBox="0 0 ${W} ${H}" width="100%" height="${H}" style="display:block; overflow: visible;">
                <defs>
                    <!-- Gradients for Area Fills -->
                    <linearGradient id="grad-revenue" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#10B981" stop-opacity="0.08"/>
                        <stop offset="100%" stop-color="#10B981" stop-opacity="0.0"/>
                    </linearGradient>
                    <linearGradient id="grad-expense" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.08"/>
                        <stop offset="100%" stop-color="#3B82F6" stop-opacity="0.0"/>
                    </linearGradient>
                </defs>

                <!-- Grid Lines & Y-Axis Labels -->
        `;

        // Horizontal Grid Lines
        for (let i = 0; i <= 4; i++) {
            const y = pad.top + (chartH / 4) * i;
            const gridVal = maxVal - (maxVal / 4) * i;
            svgHTML += `
                <line x1="${pad.left}" y1="${y}" x2="${W - pad.right}" y2="${y}" stroke="var(--color-border)" stroke-width="0.75" />
                <text x="${pad.left - 12}" y="${y + 4}" fill="var(--color-text-muted)" font-family="Inter, sans-serif" font-size="10px" text-anchor="end" font-weight="500">
                    ${App.formatCurrency(gridVal, '').replace(',00', '')}
                </text>
            `;
        }

        // Vertical Dotted Guide Lines for each Month
        months.forEach((m, i) => {
            const x = pad.left + (i / (months.length - 1)) * chartW;
            svgHTML += `
                <line x1="${x}" y1="${pad.top}" x2="${x}" y2="${pad.top + chartH}" stroke="var(--color-border)" stroke-width="0.75" stroke-dasharray="3,3" opacity="0.5" />
                <text x="${x}" y="${H - 10}" fill="var(--color-text-muted)" font-family="Inter, sans-serif" font-size="10px" text-anchor="middle" font-weight="500">
                    ${m.label}
                </text>
            `;
        });

        svgHTML += `
                <!-- Fills under curves -->
                <path d="${areaRev}" fill="url(#grad-revenue)" />
                <path d="${areaExp}" fill="url(#grad-expense)" />

                <!-- Main Smooth Curves -->
                <path d="${pathRev}" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" />
                <path d="${pathExp}" fill="none" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" />

                <!-- Dynamic Hover Guide Elements (Initially Hidden) -->
                <line id="chart-hover-line" x1="0" y1="${pad.top}" x2="0" y2="${pad.top + chartH}" stroke="var(--color-text-muted)" stroke-width="1" stroke-dasharray="3,3" style="display:none;" />
                <circle id="chart-hover-dot-rev" r="5.5" fill="#10B981" stroke="#FFFFFF" stroke-width="2" style="display:none; filter: drop-shadow(0 2px 4px rgba(16,185,129,0.3));" />
                <circle id="chart-hover-dot-exp" r="5.5" fill="#3B82F6" stroke="#FFFFFF" stroke-width="2" style="display:none; filter: drop-shadow(0 2px 4px rgba(59,130,246,0.3));" />
            </svg>
        `;

        // Render to container
        container.innerHTML = svgHTML;

        // Dynamic Interactive Tooltip element
        const tooltip = document.createElement('div');
        tooltip.style.position = 'absolute';
        tooltip.style.display = 'none';
        tooltip.style.background = '#1E293B';
        tooltip.style.color = '#FFFFFF';
        tooltip.style.padding = '10px 14px';
        tooltip.style.borderRadius = '8px';
        tooltip.style.fontFamily = "'Inter', sans-serif";
        tooltip.style.fontSize = '12px';
        tooltip.style.boxShadow = '0 4px 16px rgba(0,0,0,0.18)';
        tooltip.style.pointerEvents = 'none';
        tooltip.style.zIndex = '100';
        tooltip.style.transform = 'translate(-50%, -100%)';
        tooltip.style.marginTop = '-14px';
        tooltip.style.transition = 'left 0.08s ease, top 0.08s ease';
        container.appendChild(tooltip);

        // Fetch element references for interaction
        const hoverLine = document.getElementById('chart-hover-line');
        const hoverDotRev = document.getElementById('chart-hover-dot-rev');
        const hoverDotExp = document.getElementById('chart-hover-dot-exp');

        // Mouse Move Interaction Handler
        const handleInteraction = (e) => {
            const rect = container.getBoundingClientRect();
            // Get mouse position relative to container
            let clientX = e.clientX;
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
            }
            const mouseX = clientX - rect.left;
            
            // Map mouseX relative pixels to SVG viewBox W (700)
            const svgX = (mouseX / rect.width) * W;

            // Constrain search to active chart area
            if (svgX < pad.left - 15 || svgX > W - pad.right + 15) {
                hideHover();
                return;
            }

            // Find closest index
            let closestIdx = 0;
            let minDist = Infinity;
            pointsRev.forEach((p, idx) => {
                const dist = Math.abs(p.x - svgX);
                if (dist < minDist) {
                    minDist = dist;
                    closestIdx = idx;
                }
            });

            const pRev = pointsRev[closestIdx];
            const pExp = pointsExp[closestIdx];

            // Update interactive elements position
            hoverLine.setAttribute('x1', pRev.x);
            hoverLine.setAttribute('x2', pRev.x);
            hoverLine.style.display = 'block';

            hoverDotRev.setAttribute('cx', pRev.x);
            hoverDotRev.setAttribute('cy', pRev.y);
            hoverDotRev.style.display = 'block';

            hoverDotExp.setAttribute('cx', pExp.x);
            hoverDotExp.setAttribute('cy', pExp.y);
            hoverDotExp.style.display = 'block';

            // Show and position tooltip
            tooltip.style.display = 'block';
            
            // Map svg coords back to CSS client pixels
            const cssX = (pRev.x / W) * rect.width;
            
            // Use the higher point vertically for tooltip placement
            const targetY = Math.min(pRev.y, pExp.y);
            const cssY = (targetY / H) * rect.height;

            tooltip.style.left = `${cssX}px`;
            tooltip.style.top = `${cssY}px`;

            // Dynamic interactive tooltip content
            tooltip.innerHTML = `
                <div style="font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:0.06em; color:#9CA3AF; margin-bottom:6px;">${pRev.month}</div>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:20px;">
                        <span style="color:#10B981; font-weight:600; display:inline-flex; align-items:center; gap:5px; font-size:11px;">
                            <span style="width:6px; height:6px; border-radius:50%; background:#10B981; display:inline-block;"></span>
                            Ingresos:
                        </span>
                        <span style="font-weight:700; font-size:11px;">${App.formatCurrency(pRev.value)}</span>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:20px;">
                        <span style="color:#3B82F6; font-weight:600; display:inline-flex; align-items:center; gap:5px; font-size:11px;">
                            <span style="width:6px; height:6px; border-radius:50%; background:#3B82F6; display:inline-block;"></span>
                            Gastos:
                        </span>
                        <span style="font-weight:700; font-size:11px;">${App.formatCurrency(pExp.value)}</span>
                    </div>
                </div>
            `;
        };

        const hideHover = () => {
            if (hoverLine) hoverLine.style.display = 'none';
            if (hoverDotRev) hoverDotRev.style.display = 'none';
            if (hoverDotExp) hoverDotExp.style.display = 'none';
            if (tooltip) tooltip.style.display = 'none';
        };

        container.addEventListener('mousemove', handleInteraction);
        container.addEventListener('touchstart', handleInteraction, { passive: true });
        container.addEventListener('touchmove', handleInteraction, { passive: true });
        container.addEventListener('mouseleave', hideHover);
        container.addEventListener('touchend', hideHover);
    }
};

window.DashboardModule = DashboardModule;
export default DashboardModule;
