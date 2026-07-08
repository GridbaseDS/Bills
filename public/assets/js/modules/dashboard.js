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

            // Expense trend
            let expTrendPct = 0;
            let isExpUp = true;
            if (prev.expense > 0) {
                const diff = cur.expense - prev.expense;
                expTrendPct = Math.round((diff / prev.expense) * 100);
                isExpUp = diff >= 0;
            }

            const firstName = (App.state?.user?.name || 'Usuario').split(' ')[0];
            const today = new Date();
            const dateStr = today.toLocaleDateString('es-DO', { day: 'numeric', month: 'short', year: 'numeric' });

            container.innerHTML = `
                <style>
                    /* ═══════════════════════════════════════════
                       DASHBOARD v5 — Reference-based Redesign
                       ═══════════════════════════════════════════ */
                    .db {
                        display: flex;
                        flex-direction: column;
                        gap: 20px;
                        width: 100%;
                        padding-bottom: 24px;
                        animation: dbIn 0.35s ease both;
                    }
                    @keyframes dbIn {
                        from { opacity: 0; transform: translateY(6px); }
                        to   { opacity: 1; transform: none; }
                    }

                    /* ── Top Header ── */
                    .db-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                    }
                    .db-welcome {
                        font-size: 22px;
                        font-weight: 700;
                        color: var(--color-text-primary);
                        letter-spacing: -0.02em;
                    }
                    .db-date {
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                        font-size: 13px;
                        font-weight: 500;
                        color: var(--color-text-secondary);
                        background: var(--bg-card);
                        border: 1px solid var(--color-border);
                        border-radius: var(--radius-md);
                        padding: 7px 14px;
                    }
                    .db-date svg { width: 15px; height: 15px; color: var(--color-text-muted); }

                    /* ── Top 3 KPI Row ── */
                    .db-kpi-row {
                        display: grid;
                        grid-template-columns: 1.4fr 1fr 1fr;
                        gap: 16px;
                    }
                    .db-kpi {
                        background: var(--bg-card);
                        border: 1px solid var(--color-border);
                        border-radius: var(--radius-xl);
                        padding: 24px;
                        box-shadow: var(--shadow-sm);
                        display: flex;
                        flex-direction: column;
                    }
                    .db-kpi-top {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        margin-bottom: 16px;
                    }
                    .db-kpi-label {
                        font-size: 13px;
                        font-weight: 500;
                        color: var(--color-text-secondary);
                    }
                    .db-kpi-amount {
                        font-size: 30px;
                        font-weight: 800;
                        color: var(--color-text-primary);
                        letter-spacing: -0.03em;
                        line-height: 1.1;
                    }
                    .db-kpi-tag {
                        display: inline-flex;
                        align-items: center;
                        gap: 3px;
                        font-size: 12px;
                        font-weight: 600;
                        padding: 3px 8px;
                        border-radius: var(--radius-full);
                        margin-left: 10px;
                        vertical-align: middle;
                    }
                    .db-kpi-tag.up {
                        background: var(--color-success-bg);
                        color: var(--color-success-text);
                    }
                    .db-kpi-tag.down {
                        background: var(--color-danger-bg);
                        color: var(--color-danger-text);
                    }
                    .db-kpi-tag svg { width: 11px; height: 11px; }
                    .db-kpi-compare {
                        font-size: 12px;
                        color: var(--color-text-muted);
                        margin-top: 8px;
                    }
                    .db-kpi-compare .trend-val { font-weight: 600; }
                    .db-kpi-compare .trend-val.up { color: var(--color-success-icon); }
                    .db-kpi-compare .trend-val.down { color: var(--color-danger-icon); }

                    /* Main KPI extra elements */
                    .db-kpi-main .db-kpi-amount { font-size: 34px; }
                    .db-kpi-main .db-kpi-meta {
                        font-size: 13px;
                        color: var(--color-text-muted);
                        margin-top: 6px;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }
                    .db-kpi-actions {
                        display: flex;
                        gap: 8px;
                        margin-top: 18px;
                    }
                    .db-kpi-btn {
                        padding: 8px 18px;
                        font-size: 13px;
                        font-weight: 600;
                        border-radius: var(--radius-md);
                        cursor: pointer;
                        transition: all 0.15s ease;
                        text-decoration: none;
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                        border: none;
                    }
                    .db-kpi-btn svg { width: 15px; height: 15px; }
                    .db-kpi-btn-primary {
                        background: #111827;
                        color: #FFFFFF;
                    }
                    [data-theme="dark"] .db-kpi-btn-primary {
                        background: #F9FAFB;
                        color: #111827;
                    }
                    .db-kpi-btn-primary:hover { opacity: 0.88; }
                    .db-kpi-btn-secondary {
                        background: var(--bg-hover);
                        color: var(--color-text-primary);
                        border: 1px solid var(--color-border);
                    }
                    .db-kpi-btn-secondary:hover { background: var(--color-border); }

                    /* ── Two-column Body ── */
                    .db-body {
                        display: grid;
                        grid-template-columns: 1fr 1.5fr;
                        gap: 16px;
                        align-items: start;
                    }
                    .db-col {
                        display: flex;
                        flex-direction: column;
                        gap: 16px;
                    }

                    /* ── Card Wrapper (shared) ── */
                    .db-card {
                        background: var(--bg-card);
                        border: 1px solid var(--color-border);
                        border-radius: var(--radius-xl);
                        box-shadow: var(--shadow-sm);
                        overflow: hidden;
                    }
                    .db-card-head {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 18px 24px 14px;
                    }
                    .db-card-title {
                        font-size: 16px;
                        font-weight: 700;
                        color: var(--color-text-primary);
                    }
                    .db-card-badge {
                        font-size: 12px;
                        font-weight: 500;
                        color: var(--color-text-muted);
                        background: var(--bg-hover);
                        padding: 4px 12px;
                        border-radius: var(--radius-full);
                        border: 1px solid var(--color-border);
                    }

                    /* ── Activity Table ── */
                    .db-activity-head {
                        display: grid;
                        grid-template-columns: 1fr auto auto;
                        gap: 16px;
                        padding: 0 24px 10px;
                        font-size: 11px;
                        font-weight: 600;
                        color: var(--color-text-muted);
                        text-transform: uppercase;
                        letter-spacing: 0.04em;
                    }
                    .db-activity-row {
                        display: grid;
                        grid-template-columns: 1fr auto auto;
                        gap: 16px;
                        align-items: center;
                        padding: 14px 24px;
                        border-top: 1px solid var(--color-border);
                        text-decoration: none;
                        color: inherit;
                        transition: background 0.12s ease;
                    }
                    .db-activity-row:hover { background: var(--bg-hover); }
                    .db-activity-who {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        min-width: 0;
                    }
                    .db-activity-avatar {
                        width: 36px; height: 36px;
                        border-radius: var(--radius-full);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: 700;
                        font-size: 13px;
                        flex-shrink: 0;
                        color: #FFFFFF;
                    }
                    .db-av-green  { background: #059669; }
                    .db-av-blue   { background: #2563EB; }
                    .db-av-purple { background: #7C3AED; }
                    .db-av-amber  { background: #D97706; }
                    .db-av-red    { background: #DC2626; }
                    .db-av-gray   { background: #6B7280; }
                    .db-activity-name {
                        font-size: 13px;
                        font-weight: 600;
                        color: var(--color-text-primary);
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    .db-activity-sub {
                        font-size: 12px;
                        color: var(--color-text-muted);
                        margin-top: 1px;
                    }
                    .db-activity-date {
                        font-size: 13px;
                        color: var(--color-text-secondary);
                        white-space: nowrap;
                    }
                    .db-activity-amount {
                        font-size: 14px;
                        font-weight: 700;
                        white-space: nowrap;
                        text-align: right;
                    }
                    .db-amount-pos { color: var(--color-success-icon); }
                    .db-amount-neg { color: var(--color-text-primary); }

                    /* ── Cashflow / Chart Card ── */
                    .db-chart-info {
                        padding: 0 24px 4px;
                    }
                    .db-chart-label {
                        font-size: 12px;
                        color: var(--color-text-muted);
                        font-weight: 500;
                    }
                    .db-chart-amount-row {
                        display: flex;
                        align-items: baseline;
                        gap: 10px;
                        margin-top: 4px;
                    }
                    .db-chart-amount {
                        font-size: 28px;
                        font-weight: 800;
                        color: var(--color-text-primary);
                        letter-spacing: -0.03em;
                    }
                    .db-chart-legend {
                        display: flex;
                        gap: 14px;
                        margin-left: auto;
                        align-self: center;
                    }
                    .db-lg-item {
                        display: flex;
                        align-items: center;
                        gap: 5px;
                        font-size: 12px;
                        font-weight: 500;
                        color: var(--color-text-secondary);
                    }
                    .db-lg-dot {
                        width: 7px; height: 7px;
                        border-radius: 50%;
                    }

                    /* ── Overdue Section ── */
                    .db-overdue-item {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 14px 24px;
                        border-top: 1px solid var(--color-border);
                        text-decoration: none;
                        color: inherit;
                        transition: background 0.12s ease;
                    }
                    .db-overdue-item:hover { background: var(--bg-hover); }
                    .db-overdue-left {
                        display: flex;
                        flex-direction: column;
                    }
                    .db-overdue-name {
                        font-size: 14px;
                        font-weight: 700;
                        color: var(--color-text-primary);
                    }
                    .db-overdue-sub {
                        font-size: 12px;
                        color: var(--color-text-muted);
                        margin-top: 1px;
                    }
                    .db-overdue-amount {
                        font-size: 15px;
                        font-weight: 800;
                        color: var(--color-text-primary);
                        letter-spacing: -0.02em;
                    }
                    .db-overdue-bar-wrap {
                        margin-top: 8px;
                        width: 100%;
                        height: 6px;
                        background: var(--bg-hover);
                        border-radius: var(--radius-full);
                        overflow: hidden;
                    }
                    .db-overdue-bar {
                        height: 100%;
                        border-radius: var(--radius-full);
                        background: var(--color-danger-icon);
                        transition: width 0.6s cubic-bezier(0.4,0,0.2,1);
                    }

                    /* Overdue split cards */
                    .db-overdue-grid {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 0;
                    }
                    .db-overdue-cell {
                        padding: 18px 20px;
                        border-top: 1px solid var(--color-border);
                        text-decoration: none;
                        color: inherit;
                        transition: background 0.12s ease;
                    }
                    .db-overdue-cell:first-child {
                        border-right: 1px solid var(--color-border);
                    }
                    .db-overdue-cell:hover { background: var(--bg-hover); }
                    .db-overdue-cell-title {
                        font-size: 13px;
                        font-weight: 700;
                        color: var(--color-text-primary);
                    }
                    .db-overdue-cell-sub {
                        font-size: 11px;
                        color: var(--color-text-muted);
                        margin-top: 1px;
                    }
                    .db-overdue-cell-amount {
                        font-size: 16px;
                        font-weight: 800;
                        color: var(--color-text-primary);
                        margin-top: 6px;
                        letter-spacing: -0.02em;
                    }

                    /* Pulse dot */
                    .db-pulse {
                        width: 7px; height: 7px;
                        border-radius: 50%;
                        background: var(--color-danger-icon);
                        display: inline-block;
                        margin-right: 6px;
                        box-shadow: 0 0 0 0 rgba(239,68,68,0.6);
                        animation: dbPulse 2s infinite;
                    }
                    @keyframes dbPulse {
                        0%  { box-shadow: 0 0 0 0 rgba(239,68,68,0.6); }
                        70% { box-shadow: 0 0 0 5px rgba(239,68,68,0); }
                        100%{ box-shadow: 0 0 0 0 rgba(239,68,68,0); }
                    }

                    .db-empty {
                        padding: 32px 24px;
                        text-align: center;
                        color: var(--color-text-muted);
                        font-size: 13px;
                    }

                    /* Mobile cards (hidden desktop) */
                    .db-mobile-cards { display: none; }

                    /* ═══ Responsive ═══ */
                    @media (max-width: 1100px) {
                        .db-kpi-row { grid-template-columns: 1fr 1fr; }
                        .db-kpi-main { grid-column: 1 / -1; }
                        .db-kpi-main .db-kpi-amount { font-size: 28px; }
                    }
                    @media (max-width: 960px) {
                        .db-body { grid-template-columns: 1fr; }
                    }

                    /* ── Mobile: iPhone 14 Pro Max (430px) and below ── */
                    @media (max-width: 640px) {
                        .db {
                            padding: 0;
                            padding-bottom: calc(80px + env(safe-area-inset-bottom));
                            gap: 12px;
                        }

                        /* Header — hidden on mobile, topbar already greets */
                        .db-header { display: none !important; }

                        /* KPI cards: main full-width, rest 2-col */
                        .db-kpi-row {
                            grid-template-columns: 1fr 1fr;
                            gap: 10px;
                        }
                        .db-kpi-main { grid-column: 1 / -1; }
                        .db-kpi {
                            padding: 16px;
                            border-radius: var(--radius-lg);
                        }
                        .db-kpi-main .db-kpi-amount { font-size: 26px; }
                        .db-kpi-amount { font-size: 19px; }
                        .db-kpi-top { margin-bottom: 10px; }
                        .db-kpi-label { font-size: 12px; }
                        .db-kpi-meta { font-size: 12px; }
                        .db-kpi-actions { gap: 8px; }
                        .db-kpi-btn { padding: 8px 14px; font-size: 12px; }
                        .db-kpi-btn svg { width: 13px; height: 13px; }
                        .db-kpi-compare { font-size: 11px; margin-top: 6px; }

                        /* Body single column */
                        .db-body { grid-template-columns: 1fr; gap: 12px; }

                        /* Cards */
                        .db-card { border-radius: var(--radius-lg); }
                        .db-card-head { padding: 14px 16px 10px; }
                        .db-card-title { font-size: 14px; }
                        .db-card-badge { font-size: 11px; padding: 3px 10px; }

                        /* Activity — hide desktop table, show mobile cards */
                        .db-activity-head { display: none !important; }
                        .db-desktop-rows { display: none !important; }
                        .db-mobile-cards {
                            display: flex !important;
                            flex-direction: column;
                        }
                        .db-activity-row {
                            padding: 12px 16px;
                            grid-template-columns: 1fr auto;
                        }
                        .db-activity-avatar { width: 32px; height: 32px; font-size: 12px; }
                        .db-activity-name { font-size: 13px; }
                        .db-activity-sub { font-size: 11px; }
                        .db-activity-amount { font-size: 13px; }

                        /* Chart */
                        .db-chart-info { padding: 0 16px 4px; }
                        .db-chart-amount { font-size: 22px; }
                        .db-chart-amount-row { flex-wrap: wrap; gap: 6px; }
                        .db-chart-legend { margin-left: 0; margin-top: 4px; width: 100%; }

                        /* Overdue */
                        .db-overdue-item { padding: 12px 16px; }
                        .db-overdue-grid { grid-template-columns: 1fr 1fr; }
                        .db-overdue-cell { padding: 14px 14px; }
                        .db-overdue-cell-title { font-size: 12px; }
                        .db-overdue-cell-amount { font-size: 14px; }
                    }

                    /* ── Extra small phones (≤375px, iPhone SE/Mini) ── */
                    @media (max-width: 375px) {
                        .db-kpi-row { grid-template-columns: 1fr; }
                        .db-kpi-main .db-kpi-amount { font-size: 24px; }
                        .db-kpi-amount { font-size: 18px; }
                        .db-overdue-grid { grid-template-columns: 1fr; }
                        .db-overdue-cell:first-child { border-right: none; }
                        .db-welcome { font-size: 17px; }
                    }
                    /* ── Tax / ITBIS Widget ── */
                    .db-tax-card {
                        background: var(--bg-card);
                        border: 1px solid var(--color-border);
                        border-radius: var(--radius-xl);
                        box-shadow: var(--shadow-sm);
                        overflow: hidden;
                    }
                    .db-tax-head {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 18px 24px 14px;
                        border-bottom: 1px solid var(--color-border);
                    }
                    .db-tax-title {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        font-size: 15px;
                        font-weight: 700;
                        color: var(--color-text-primary);
                    }
                    .db-tax-icon {
                        width: 34px; height: 34px;
                        border-radius: var(--radius-md);
                        background: #FEF3C7;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: #B45309;
                        flex-shrink: 0;
                    }
                    [data-theme="dark"] .db-tax-icon {
                        background: rgba(251,191,36,0.12);
                        color: #FCD34D;
                    }
                    .db-tax-icon svg { width: 17px; height: 17px; }
                    .db-tax-badge {
                        font-size: 11px;
                        font-weight: 600;
                        padding: 4px 10px;
                        border-radius: var(--radius-full);
                        background: #FEF3C7;
                        color: #92400E;
                        border: 1px solid #FDE68A;
                    }
                    [data-theme="dark"] .db-tax-badge {
                        background: rgba(251,191,36,0.12);
                        color: #FCD34D;
                        border-color: rgba(251,191,36,0.2);
                    }
                    .db-tax-grid {
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 0;
                    }
                    .db-tax-cell {
                        padding: 20px 24px;
                        border-right: 1px solid var(--color-border);
                    }
                    .db-tax-cell:last-child { border-right: none; }
                    .db-tax-cell-label {
                        font-size: 12px;
                        font-weight: 500;
                        color: var(--color-text-muted);
                        text-transform: uppercase;
                        letter-spacing: 0.04em;
                        margin-bottom: 6px;
                    }
                    .db-tax-cell-amount {
                        font-size: 22px;
                        font-weight: 800;
                        color: var(--color-text-primary);
                        letter-spacing: -0.02em;
                    }
                    .db-tax-cell-amount.highlight {
                        color: #D97706;
                    }
                    [data-theme="dark"] .db-tax-cell-amount.highlight {
                        color: #FCD34D;
                    }
                    .db-tax-cell-sub {
                        font-size: 11px;
                        color: var(--color-text-muted);
                        margin-top: 4px;
                    }
                    .db-tax-footer {
                        padding: 12px 24px;
                        background: var(--bg-hover);
                        font-size: 12px;
                        color: var(--color-text-muted);
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        border-top: 1px solid var(--color-border);
                    }
                    .db-tax-footer svg { width: 13px; height: 13px; flex-shrink: 0; }
                    @media (max-width: 640px) {
                        .db-tax-grid { grid-template-columns: 1fr 1fr; }
                        .db-tax-cell { padding: 16px; }
                        .db-tax-cell-amount { font-size: 18px; }
                    }
                </style>

                <div class="db">
                    <!-- Header -->
                    <div class="db-header">
                        <div class="db-welcome">Hola, ${firstName}</div>
                        <div class="db-date">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            ${dateStr}
                        </div>
                    </div>

                    <!-- Top 3 KPI Cards -->
                    <div class="db-kpi-row">
                        <!-- Main Balance Card -->
                        <div class="db-kpi db-kpi-main">
                            <div class="db-kpi-top">
                                <span class="db-kpi-label">Ganancia Este Mes</span>
                            </div>
                            <div>
                                <span class="db-kpi-amount">${App.formatCurrency(stats.revenue_this_month || 0)}</span>
                                ${trendPct !== 0 ? `
                                    <span class="db-kpi-tag ${isTrendUp ? 'up' : 'down'}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                            ${isTrendUp ? '<path d="M12 19V5m0 0 4 4m-4-4-4 4"/>' : '<path d="M12 5v14m0 0 4-4m-4 4-4-4"/>'}
                                        </svg>
                                        ${Math.abs(trendPct)}%
                                    </span>
                                ` : ''}
                            </div>
                            <div class="db-kpi-meta">${stats.invoices_this_month || 0} factura${(stats.invoices_this_month || 0) !== 1 ? 's' : ''} emitida${(stats.invoices_this_month || 0) !== 1 ? 's' : ''} este mes</div>
                            <div class="db-kpi-actions">
                                <a href="#facturas" class="db-kpi-btn db-kpi-btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    Facturas
                                </a>
                                <a href="#clientes" class="db-kpi-btn db-kpi-btn-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                    Clientes
                                </a>
                            </div>
                        </div>

                        <!-- Monthly Invoiced -->
                        <div class="db-kpi">
                            <div class="db-kpi-top">
                                <span class="db-kpi-label">Facturado Este Mes</span>
                            </div>
                            <div class="db-kpi-amount">${App.formatCurrency(stats.invoiced_amount_this_month || stats.revenue_this_month || 0)}</div>
                            <div class="db-kpi-compare">
                                ${trendPct !== 0 ? `<span class="trend-val ${isTrendUp ? 'up' : 'down'}">${isTrendUp ? '+' : ''}${trendPct}%</span>` : ''}
                                Comparado al mes pasado
                            </div>
                        </div>

                        <!-- Pending -->
                        <div class="db-kpi">
                            <div class="db-kpi-top">
                                <span class="db-kpi-label">Pendiente de Cobro</span>
                            </div>
                            <div class="db-kpi-amount">${App.formatCurrency(stats.pending_amount || 0)}</div>
                            <div class="db-kpi-compare">
                                ${stats.overdue_count > 0
                                    ? `<span class="trend-val down">${stats.overdue_count} vencida${stats.overdue_count > 1 ? 's' : ''}</span>`
                                    : '<span class="trend-val up">Al día</span>'}
                                del total pendiente
                            </div>
                        </div>
                    </div>

                    <!-- ITBIS / Tax Widget -->
                    <div class="db-tax-card">
                        <div class="db-tax-head">
                            <div class="db-tax-title">
                                <div class="db-tax-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                                    </svg>
                                </div>
                                ITBIS Recaudado
                            </div>
                            <span class="db-tax-badge">18% ITBIS</span>
                        </div>
                        <div class="db-tax-grid">
                            <div class="db-tax-cell">
                                <div class="db-tax-cell-label">Este Mes</div>
                                <div class="db-tax-cell-amount highlight">${App.formatCurrency(stats.tax_collected_this_month || 0)}</div>
                                <div class="db-tax-cell-sub">A declarar en 607</div>
                            </div>
                            <div class="db-tax-cell">
                                <div class="db-tax-cell-label">Mes Pasado</div>
                                <div class="db-tax-cell-amount">${App.formatCurrency(stats.tax_collected_last_month || 0)}</div>
                                <div class="db-tax-cell-sub">Período anterior</div>
                            </div>
                            <div class="db-tax-cell">
                                <div class="db-tax-cell-label">Total Acumulado</div>
                                <div class="db-tax-cell-amount">${App.formatCurrency(stats.tax_collected_total || 0)}</div>
                                <div class="db-tax-cell-sub">Historial completo</div>
                            </div>
                        </div>
                        ${(stats.tax_pending || 0) > 0 ? `
                        <div class="db-tax-footer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            ${App.formatCurrency(stats.tax_pending || 0)} en ITBIS de facturas aún no cobradas (pendientes de pago del cliente)
                        </div>
                        ` : ''}
                    </div>

                    <!-- Two-Column Body -->
                    <div class="db-body">

                        <!-- LEFT: Recent Activity -->
                        <div class="db-col">
                            <div class="db-card">
                                <div class="db-card-head">
                                    <span class="db-card-title">Actividad Reciente</span>
                                    <a href="#facturas" class="db-card-badge" style="text-decoration:none;">Ver todas</a>
                                </div>
                                <div class="db-activity-head">
                                    <span>Cliente</span>
                                    <span>Fecha</span>
                                    <span style="text-align:right">Monto</span>
                                </div>
                                <div class="db-desktop-rows">
                                    ${recent.length > 0 ? recent.map((inv, idx) => {
                                        const avatarColors = ['db-av-green','db-av-blue','db-av-purple','db-av-amber','db-av-red','db-av-gray'];
                                        const avColor = avatarColors[idx % avatarColors.length];
                                        const name = inv.company_name || inv.contact_name || '?';
                                        const initial = name.charAt(0).toUpperCase();
                                        const isPaid = inv.status === 'paid' || inv.status === 'accepted';
                                        return `
                                        <a href="#facturas/${inv.id}" class="db-activity-row">
                                            <div class="db-activity-who">
                                                <div class="db-activity-avatar ${avColor}">${initial}</div>
                                                <div>
                                                    <div class="db-activity-name">${name}</div>
                                                    <div class="db-activity-sub">${inv.invoice_number}</div>
                                                </div>
                                            </div>
                                            <div class="db-activity-date">${App.formatDate(inv.issue_date)}</div>
                                            <div class="db-activity-amount ${isPaid ? 'db-amount-pos' : 'db-amount-neg'}">${isPaid ? '+' : ''}${App.formatCurrency(inv.total, inv.currency)}</div>
                                        </a>`;
                                    }).join('') : '<div class="db-empty">No hay actividad reciente</div>'}
                                </div>
                                <!-- Mobile version -->
                                <div class="db-mobile-cards">
                                    ${recent.length > 0 ? recent.map((inv, idx) => {
                                        const avatarColors = ['db-av-green','db-av-blue','db-av-purple','db-av-amber','db-av-red','db-av-gray'];
                                        const avColor = avatarColors[idx % avatarColors.length];
                                        const name = inv.company_name || inv.contact_name || '?';
                                        const initial = name.charAt(0).toUpperCase();
                                        const isPaid = inv.status === 'paid' || inv.status === 'accepted';
                                        return `
                                        <a href="#facturas/${inv.id}" class="db-activity-row">
                                            <div class="db-activity-who">
                                                <div class="db-activity-avatar ${avColor}">${initial}</div>
                                                <div>
                                                    <div class="db-activity-name">${name}</div>
                                                    <div class="db-activity-sub">${inv.invoice_number}</div>
                                                </div>
                                            </div>
                                            <div class="db-activity-amount ${isPaid ? 'db-amount-pos' : 'db-amount-neg'}" style="margin-left:auto">${isPaid ? '+' : ''}${App.formatCurrency(inv.total, inv.currency)}</div>
                                        </a>`;
                                    }).join('') : '<div class="db-empty">No hay actividad reciente</div>'}
                                </div>
                            </div>

                            <!-- BPD Exchange Rates Widget -->
                            <div class="db-card" style="margin-top: 16px;">
                                <div class="db-card-head" style="border-bottom: 1px solid var(--color-border); background: var(--bg-hover);">
                                    <span class="db-card-title" style="display:flex; align-items:center; gap:8px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px; height:16px; color:var(--color-text-secondary);"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                        Tasas de Cambio actualizadas
                                    </span>
                                    <span class="badge badge-active">En Vivo</span>
                                </div>
                                <div style="padding: 18px 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                    <div style="background: var(--bg-hover); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border); text-align: center;">
                                        <div style="font-size: 11px; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.04em;">Dólar (USD)</div>
                                        <div style="font-size: 22px; font-weight: 800; color: var(--color-text-primary); letter-spacing: -0.02em;">RD$ ${(data.exchange_rates?.USD || 60.35).toFixed(2)}</div>
                                        <div style="font-size: 10px; color: var(--color-text-muted); margin-top: 2px;">Tasa de Venta BPD</div>
                                    </div>
                                    <div style="background: var(--bg-hover); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border); text-align: center;">
                                        <div style="font-size: 11px; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.04em;">Euro (EUR)</div>
                                        <div style="font-size: 22px; font-weight: 800; color: var(--color-text-primary); letter-spacing: -0.02em;">RD$ ${(data.exchange_rates?.EUR || 65.90).toFixed(2)}</div>
                                        <div style="font-size: 10px; color: var(--color-text-muted); margin-top: 2px;">Tasa de Venta BPD</div>
                                    </div>
                                </div>
                                <div style="padding: 12px 24px; background: var(--bg-hover); border-top: 1px solid var(--color-border); font-size: 11px; color: var(--color-text-muted); display: flex; align-items: center; gap: 6px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px; height:12px; flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Sincronizado vía BPD el ${data.exchange_rates?.updated_at || ''}
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT: Chart + Overdue -->
                        <div class="db-col">
                            <!-- Cashflow Chart -->
                            <div class="db-card">
                                <div class="db-card-head">
                                    <span class="db-card-title">Flujo de Caja</span>
                                    <span class="db-card-badge">Este Año</span>
                                </div>
                                <div class="db-chart-info">
                                    <div class="db-chart-label">Balance Total</div>
                                    <div class="db-chart-amount-row">
                                        <span class="db-chart-amount">${App.formatCurrency(stats.revenue_this_month || 0)}</span>
                                        ${trendPct !== 0 ? `
                                            <span class="db-kpi-tag ${isTrendUp ? 'up' : 'down'}" style="font-size:11px">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px">
                                                    ${isTrendUp ? '<path d="M12 19V5m0 0 4 4m-4-4-4 4"/>' : '<path d="M12 5v14m0 0 4-4m-4 4-4-4"/>'}
                                                </svg>
                                                ${Math.abs(trendPct)}%
                                            </span>
                                        ` : ''}
                                        <div class="db-chart-legend">
                                            <span class="db-lg-item"><span class="db-lg-dot" style="background:#3B82F6"></span>Gastos</span>
                                            <span class="db-lg-item"><span class="db-lg-dot" style="background:#F59E0B"></span>Ingresos</span>
                                        </div>
                                    </div>
                                </div>
                                <div id="area-chart" style="width:100%;min-height:220px;padding:0 12px;"></div>
                            </div>

                            <!-- Overdue / Savings-style -->
                            <div class="db-card">
                                <div class="db-card-head">
                                    <span class="db-card-title">
                                        ${overdue.length > 0 ? '<span class="db-pulse"></span>' : ''}
                                        Facturas Vencidas
                                    </span>
                                    <span class="badge badge-overdue">${overdue.length}</span>
                                </div>
                                ${overdue.length > 0 ? (overdue.length >= 2 ? `
                                    <div class="db-overdue-grid">
                                        ${overdue.slice(0, 2).map(o => `
                                            <a href="#facturas/${o.id}" class="db-overdue-cell">
                                                <div class="db-overdue-cell-title">${o.invoice_number}</div>
                                                <div class="db-overdue-cell-sub">${o.company_name || o.contact_name}</div>
                                                <div class="db-overdue-cell-amount">${App.formatCurrency(o.total, o.currency)}</div>
                                                <div class="db-overdue-bar-wrap">
                                                    <div class="db-overdue-bar" style="width:${Math.min(100, Math.max(30, Math.random() * 100))}%"></div>
                                                </div>
                                            </a>
                                        `).join('')}
                                    </div>
                                    ${overdue.length > 2 ? overdue.slice(2).map(o => `
                                        <a href="#facturas/${o.id}" class="db-overdue-item">
                                            <div class="db-overdue-left">
                                                <div style="font-weight:600;font-size:13px;color:var(--color-text-primary)">${o.invoice_number}</div>
                                                <div style="font-size:12px;color:var(--color-text-muted);margin-top:1px">${o.company_name || o.contact_name}</div>
                                            </div>
                                            <div style="text-align:right">
                                                <div style="font-weight:700;font-size:14px;color:var(--color-danger-icon)">${App.formatCurrency(o.total, o.currency)}</div>
                                                <div style="font-size:11px;color:var(--color-text-muted);margin-top:1px">${App.formatDate(o.due_date)}</div>
                                            </div>
                                        </a>
                                    `).join('') : ''}
                                ` : overdue.map(o => `
                                    <a href="#facturas/${o.id}" class="db-overdue-item">
                                        <div class="db-overdue-left">
                                            <div class="db-overdue-name">${o.invoice_number}</div>
                                            <div class="db-overdue-sub">${o.company_name || o.contact_name}</div>
                                        </div>
                                        <div>
                                            <div class="db-overdue-amount" style="color:var(--color-danger-icon)">${App.formatCurrency(o.total, o.currency)}</div>
                                        </div>
                                    </a>
                                `).join('')) : '<div class="db-empty">No hay facturas vencidas</div>'}
                            </div>
                        </div>
                    </div>


                </div>
            `;

            this.renderChart(monthlyData);
        } catch (e) {
            container.innerHTML = '<div class="text-red" style="padding:32px;text-align:center;">Error al cargar el dashboard</div>';
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
                height: 220,
                type: 'area',
                fontFamily: 'Inter, sans-serif',
                toolbar: { show: false },
                sparkline: { enabled: false },
                animations: { enabled: true, easing: 'easeinout', speed: 800 }
            },
            stroke: { curve: 'smooth', width: 2.5 },
            fill: {
                type: 'gradient',
                gradient: { opacityFrom: 0.3, opacityTo: 0.0, shadeIntensity: 1 }
            },
            grid: {
                show: true,
                strokeDashArray: 4,
                borderColor: isDark ? '#1F2937' : '#F3F4F6',
                padding: { left: 4, right: 4, top: 0, bottom: 0 }
            },
            series: [
                { name: 'Gastos', data: months.map(m => m.expense), color: '#3B82F6' },
                { name: 'Ingresos', data: months.map(m => m.revenue), color: '#F59E0B' }
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
                        if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                        return App.formatCurrency(val, '').replace(',00', '');
                    }
                }
            },
            tooltip: {
                theme: isDark ? 'dark' : 'light',
                y: { formatter: function(val) { return App.formatCurrency(val); } }
            },
            markers: { size: 0, hover: { size: 5 } },
            dataLabels: { enabled: false }
        };

        const chart = new ApexCharts(container, options);
        chart.render();
    }
};

window.DashboardModule = DashboardModule;
export default DashboardModule;
