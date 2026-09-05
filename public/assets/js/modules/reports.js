const ReportsModule = {
    _currentTab: '607',
    _year: new Date().getFullYear(),
    _month: new Date().getMonth() + 1,
    _records606: [],
    _records607: [],
    _records608: [],

    async render(container) {
        container.innerHTML = `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Reportes Fiscales DGII</h1>
                    <p class="page-subtitle">Genera y descarga las plantillas oficiales en Excel (.xlsx) y archivos TXT para Formatos 606, 607 y 608</p>
                </div>
            </div>

            <!-- Period Selector Card -->
            <div class="table-outer mb-24" style="padding:20px;background:var(--bg-hover);">
                <div style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
                    <div class="form-group" style="margin:0;min-width:140px;">
                        <label class="form-label" style="margin-bottom:6px;font-size:12px;">Año Fiscal</label>
                        <select id="report-year" class="form-control">
                            <option value="2026" ${this._year == 2026 ? 'selected' : ''}>2026</option>
                            <option value="2025" ${this._year == 2025 ? 'selected' : ''}>2025</option>
                            <option value="2024" ${this._year == 2024 ? 'selected' : ''}>2024</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;min-width:180px;">
                        <label class="form-label" style="margin-bottom:6px;font-size:12px;">Mes del Período</label>
                        <select id="report-month" class="form-control">
                            <option value="1" ${this._month == 1 ? 'selected' : ''}>01 - Enero</option>
                            <option value="2" ${this._month == 2 ? 'selected' : ''}>02 - Febrero</option>
                            <option value="3" ${this._month == 3 ? 'selected' : ''}>03 - Marzo</option>
                            <option value="4" ${this._month == 4 ? 'selected' : ''}>04 - Abril</option>
                            <option value="5" ${this._month == 5 ? 'selected' : ''}>05 - Mayo</option>
                            <option value="6" ${this._month == 6 ? 'selected' : ''}>06 - Junio</option>
                            <option value="7" ${this._month == 7 ? 'selected' : ''}>07 - Julio</option>
                            <option value="8" ${this._month == 8 ? 'selected' : ''}>08 - Agosto</option>
                            <option value="9" ${this._month == 9 ? 'selected' : ''}>09 - Septiembre</option>
                            <option value="10" ${this._month == 10 ? 'selected' : ''}>10 - Octubre</option>
                            <option value="11" ${this._month == 11 ? 'selected' : ''}>11 - Noviembre</option>
                            <option value="12" ${this._month == 12 ? 'selected' : ''}>12 - Diciembre</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" id="btn-fetch-reports" style="height:38px;display:flex;align-items:center;gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        Cargar Período
                    </button>
                </div>
            </div>

            <!-- Tabs and Action Area -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-lg);flex-wrap:wrap;gap:12px;">
                <div class="segmented-control" id="report-type-tabs">
                    <button class="segment-item ${this._currentTab === '607' ? 'active' : ''}" data-tab="607">Ventas (607)</button>
                    <button class="segment-item ${this._currentTab === '606' ? 'active' : ''}" data-tab="606">Compras / Gastos (606)</button>
                    <button class="segment-item ${this._currentTab === '608' ? 'active' : ''}" data-tab="608">Anulaciones (608)</button>
                </div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <button class="btn" id="btn-export-excel" style="display:flex;align-items:center;gap:8px;background:#107c41;border-color:#107c41;color:#fff;font-weight:600;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                        Descargar Plantilla Excel (.xls)
                    </button>
                    <button class="btn btn-secondary" id="btn-export-txt" style="display:flex;align-items:center;gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Descargar Formato TXT
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="table-outer">
                <div class="table-wrapper" style="overflow-x:auto;">
                    <table class="data-table" id="report-table">
                        <thead><tr id="report-headers"></tr></thead>
                        <tbody id="report-tbody"></tbody>
                    </table>
                </div>
                <div id="report-summary-box" style="padding:16px 24px;border-top:1px solid var(--color-border);background:var(--bg-hover);display:flex;justify-content:space-between;align-items:center;font-weight:600;font-size:14px;color:var(--color-text-primary);"></div>
            </div>

            <!-- Reference Legend Container for 608 -->
            <div id="report-reference-box" style="margin-top:20px;"></div>
        `;

        this.bindEvents();
        await this.loadData();
    },

    bindEvents() {
        const btnFetch = document.getElementById('btn-fetch-reports');
        if (btnFetch) {
            btnFetch.addEventListener('click', async () => {
                this._year = document.getElementById('report-year').value;
                this._month = document.getElementById('report-month').value;
                await this.loadData();
            });
        }

        const tabs = document.querySelectorAll('#report-type-tabs .segment-item');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                this._currentTab = tab.dataset.tab;
                this.renderGrid();
            });
        });

        const btnExportExcel = document.getElementById('btn-export-excel');
        if (btnExportExcel) {
            btnExportExcel.addEventListener('click', () => this.exportExcel());
        }

        const btnExportTxt = document.getElementById('btn-export-txt');
        if (btnExportTxt) {
            btnExportTxt.addEventListener('click', () => this.exportTxt());
        }
    },

    async loadData() {
        const tbody = document.getElementById('report-tbody');
        if (tbody) tbody.innerHTML = `<tr><td colspan="100" class="text-center py-24"><span class="spinner mx-auto"></span><br><small style="color:var(--color-text-muted)">Cargando registros fiscales del período...</small></td></tr>`;

        try {
            const [res607, res606, res608] = await Promise.all([
                App.api(`dgii/reports/607?year=${this._year}&month=${this._month}`),
                App.api(`dgii/reports/606?year=${this._year}&month=${this._month}`),
                App.api(`dgii/reports/608?year=${this._year}&month=${this._month}`)
            ]);

            this._records607 = res607.data || [];
            this._records606 = res606.data || [];
            this._records608 = res608.data || [];

            this.renderGrid();
        } catch (e) {
            if (tbody) tbody.innerHTML = `<tr><td colspan="100" class="text-center text-red py-24">Error al conectar con el servidor para cargar los reportes</td></tr>`;
        }
    },

    renderGrid() {
        const headers = document.getElementById('report-headers');
        const tbody = document.getElementById('report-tbody');
        const summary = document.getElementById('report-summary-box');
        const refBox = document.getElementById('report-reference-box');
        if (!headers || !tbody) return;

        if (refBox) refBox.innerHTML = '';

        if (this._currentTab === '607') {
            // e-CF informational banner
            const bannerHtml = `
                <tr><td colspan="10" style="padding:0;border:none;">
                    <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #93c5fd;border-radius:8px;padding:16px 20px;margin:12px 0;display:flex;align-items:flex-start;gap:12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <div style="font-size:13px;color:#1e40af;line-height:1.5;">
                            <strong>Formato 607 - Ventas de Bienes y Servicios (Norma 07-2018)</strong><br>
                            Los emisores electrónicos (e-CF) reportan sus ventas en tiempo real a la DGII. Esta plantilla prellenada en Excel refleja los 23 campos oficiales requeridos por la DGII, incluyendo el desglose de formas de pago.
                        </div>
                    </div>
                </td></tr>
            `;

            // Render 607 Headers
            headers.innerHTML = `
                <th>Comprobante (eNCF)</th>
                <th>Cliente</th>
                <th>RNC/Cédula</th>
                <th>Tipo Ingreso</th>
                <th>Fecha Emisión</th>
                <th class="text-right">Monto Facturado</th>
                <th class="text-right">ITBIS Facturado</th>
                <th class="text-right">Efectivo</th>
                <th class="text-right">Transferencia</th>
                <th class="text-right">Crédito</th>
            `;

            if (this._records607.length === 0) {
                tbody.innerHTML = bannerHtml + `<tr><td colspan="10" class="text-center text-muted py-24">No hay facturas emitidas registradas en este período</td></tr>`;
                summary.innerHTML = `<span>Total Registros: 0</span><span>Suma Total: RD$ 0.00</span>`;
                return;
            }

            // Render 607 Rows
            let sumTotal = 0;
            tbody.innerHTML = bannerHtml + this._records607.map(r => {
                sumTotal += (r.monto_facturado || 0);
                return `
                    <tr>
                        <td><strong style="font-family:'JetBrains Mono',monospace;">${r.ncf}</strong></td>
                        <td>${r.cliente_nombre}</td>
                        <td style="font-family:'JetBrains Mono',monospace;">${r.rnc_cliente || '<span class="text-muted">—</span>'}</td>
                        <td><span class="badge badge-draft" title="Ingreso por operaciones">${r.tipo_ingreso || '01'}</span></td>
                        <td>${this.formatDgiiDate(r.fecha_comprobante)}</td>
                        <td class="text-right font-semibold">${App.formatCurrency(r.monto_facturado, 'DOP')}</td>
                        <td class="text-right">${App.formatCurrency(r.itbis_facturado, 'DOP')}</td>
                        <td class="text-right">${App.formatCurrency(r.efectivo, 'DOP')}</td>
                        <td class="text-right">${App.formatCurrency(r.bancos, 'DOP')}</td>
                        <td class="text-right" style="color:var(--color-danger-icon);">${App.formatCurrency(r.credito, 'DOP')}</td>
                    </tr>
                `;
            }).join('');

            summary.innerHTML = `
                <span>Total Comprobantes Emitidos: <strong>${this._records607.length}</strong></span>
                <span>Facturado Neto: <strong style="color:var(--color-primary);font-size:16px;">${App.formatCurrency(sumTotal, 'DOP')}</strong></span>
            `;

        } else if (this._currentTab === '606') {
            // Render 606 Headers
            headers.innerHTML = `
                <th>Comprobante (eNCF)</th>
                <th>Proveedor</th>
                <th>RNC/Cédula</th>
                <th>Fecha Emisión</th>
                <th class="text-right">Total Facturado</th>
                <th class="text-right">ITBIS Facturado</th>
                <th style="min-width: 220px;">Tipo de Gasto (606)</th>
                <th style="min-width: 180px;">Forma de Pago</th>
            `;

            if (this._records606.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-24">No hay compras ni gastos registrados en este período</td></tr>`;
                summary.innerHTML = `<span>Total Comprobantes Recibidos: 0</span><span>Suma Total: RD$ 0.00</span>`;
                return;
            }

            // Render 606 Rows with Editable Dropdowns
            let sumTotal = 0;
            tbody.innerHTML = this._records606.map((r, idx) => {
                sumTotal += (r.monto_servicios || 0);
                return `
                    <tr>
                        <td><strong style="font-family:'JetBrains Mono',monospace;">${r.ncf}</strong></td>
                        <td>${r.proveedor_nombre}</td>
                        <td style="font-family:'JetBrains Mono',monospace;">${r.rnc_proveedor}</td>
                        <td>${this.formatDgiiDate(r.fecha_comprobante)}</td>
                        <td class="text-right font-semibold">${App.formatCurrency((r.monto_servicios || 0) + (r.itbis_facturado || 0), 'DOP')}</td>
                        <td class="text-right">${App.formatCurrency(r.itbis_facturado, 'DOP')}</td>
                        <td>
                            <select class="form-control table-select" style="font-size:12px;padding:4px 8px;height:30px;" onchange="ReportsModule.update606Field(${idx}, 'tipo_bien_servicio', this.value)">
                                <option value="01" ${r.tipo_bien_servicio === '01' ? 'selected' : ''}>01 - Personal</option>
                                <option value="02" ${r.tipo_bien_servicio === '02' || !r.tipo_bien_servicio ? 'selected' : ''}>02 - Trabajos y Servicios</option>
                                <option value="03" ${r.tipo_bien_servicio === '03' ? 'selected' : ''}>03 - Arrendamientos</option>
                                <option value="04" ${r.tipo_bien_servicio === '04' ? 'selected' : ''}>04 - Activos Fijos</option>
                                <option value="05" ${r.tipo_bien_servicio === '05' ? 'selected' : ''}>05 - Gastos Representación</option>
                                <option value="06" ${r.tipo_bien_servicio === '06' ? 'selected' : ''}>06 - Otras Deducciones</option>
                                <option value="07" ${r.tipo_bien_servicio === '07' ? 'selected' : ''}>07 - Financieros</option>
                                <option value="08" ${r.tipo_bien_servicio === '08' ? 'selected' : ''}>08 - Extraordinarios</option>
                                <option value="09" ${r.tipo_bien_servicio === '09' ? 'selected' : ''}>09 - Costo de Venta</option>
                                <option value="10" ${r.tipo_bien_servicio === '10' ? 'selected' : ''}>10 - Adquisición Activos</option>
                                <option value="11" ${r.tipo_bien_servicio === '11' ? 'selected' : ''}>11 - Seguros</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-control table-select" style="font-size:12px;padding:4px 8px;height:30px;" onchange="ReportsModule.update606Field(${idx}, 'forma_pago', this.value)">
                                <option value="01" ${r.forma_pago === '01' ? 'selected' : ''}>01 - Efectivo</option>
                                <option value="02" ${r.forma_pago === '02' || !r.forma_pago ? 'selected' : ''}>02 - Cheques/Transferencia</option>
                                <option value="03" ${r.forma_pago === '03' ? 'selected' : ''}>03 - Tarjeta</option>
                                <option value="04" ${r.forma_pago === '04' ? 'selected' : ''}>04 - Crédito</option>
                                <option value="05" ${r.forma_pago === '05' ? 'selected' : ''}>05 - Permuta</option>
                                <option value="06" ${r.forma_pago === '06' ? 'selected' : ''}>06 - Nota de Crédito</option>
                                <option value="07" ${r.forma_pago === '07' ? 'selected' : ''}>07 - Mixto</option>
                            </select>
                        </td>
                    </tr>
                `;
            }).join('');

            summary.innerHTML = `
                <span>Total Comprobantes Recibidos: <strong>${this._records606.length}</strong></span>
                <span>Gasto Neto: <strong style="color:#059669;font-size:16px;">${App.formatCurrency(sumTotal, 'DOP')}</strong></span>
            `;

        } else if (this._currentTab === '608') {
            // Formato 608 Banner
            const bannerHtml = `
                <tr><td colspan="7" style="padding:0;border:none;">
                    <div style="background:linear-gradient(135deg,#fef2f2,#fee2e2);border:1px solid #fca5a5;border-radius:8px;padding:16px 20px;margin:12px 0;display:flex;align-items:flex-start;gap:12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <div style="font-size:13px;color:#991b1b;line-height:1.5;">
                            <strong>Formato 608 - Comprobantes Fiscales Anulados</strong><br>
                            Reporta los comprobantes fiscales y e-NCF anulados durante este período indicando el motivo oficial de anulación (códigos 01 al 09 de la DGII).
                        </div>
                    </div>
                </td></tr>
            `;

            // Render 608 Headers
            headers.innerHTML = `
                <th>Comprobante Fiscal (NCF)</th>
                <th>Cliente / Razón Social</th>
                <th>Fecha Comprobante</th>
                <th>Fecha Anulación</th>
                <th style="min-width: 240px;">Tipo de Anulación (DGII)</th>
                <th class="text-right">Monto Facturado</th>
                <th>Estatus</th>
            `;

            if (this._records608.length === 0) {
                tbody.innerHTML = bannerHtml + `<tr><td colspan="7" class="text-center text-muted py-24">No hay comprobantes anulados en este período</td></tr>`;
                summary.innerHTML = `<span>Total Comprobantes Anulados: 0</span><span>Suma Total: RD$ 0.00</span>`;
                this.render608CatalogLegend(refBox);
                return;
            }

            // Render 608 Rows
            let sumTotal = 0;
            tbody.innerHTML = bannerHtml + this._records608.map((r, idx) => {
                sumTotal += (r.monto || 0);
                return `
                    <tr>
                        <td><strong style="font-family:'JetBrains Mono',monospace;color:#dc2626;">${r.ncf}</strong></td>
                        <td>${r.cliente_nombre || 'Cliente General'}</td>
                        <td>${this.formatDgiiDate(r.fecha_comprobante)}</td>
                        <td>${this.formatDgiiDate(r.fecha_anulacion)}</td>
                        <td>
                            <select class="form-control table-select" style="font-size:12px;padding:4px 8px;height:30px;" onchange="ReportsModule.update608Field(${idx}, 'tipo_anulacion', this.value)">
                                <option value="01" ${r.tipo_anulacion === '01' ? 'selected' : ''}>01 - Deterioro de Factura Pre-Impresa</option>
                                <option value="02" ${r.tipo_anulacion === '02' ? 'selected' : ''}>02 - Errores de Impresión</option>
                                <option value="03" ${r.tipo_anulacion === '03' ? 'selected' : ''}>03 - Impresión Defectuosa</option>
                                <option value="04" ${r.tipo_anulacion === '04' ? 'selected' : ''}>04 - Duplicidad de Factura</option>
                                <option value="05" ${r.tipo_anulacion === '05' || !r.tipo_anulacion ? 'selected' : ''}>05 - Corrección de la Información</option>
                                <option value="06" ${r.tipo_anulacion === '06' ? 'selected' : ''}>06 - Cambio de Productos</option>
                                <option value="07" ${r.tipo_anulacion === '07' ? 'selected' : ''}>07 - Devolución de Productos</option>
                                <option value="08" ${r.tipo_anulacion === '08' ? 'selected' : ''}>08 - Omisión de Productos</option>
                                <option value="09" ${r.tipo_anulacion === '09' ? 'selected' : ''}>09 - Errores en Secuencia de NCF</option>
                            </select>
                        </td>
                        <td class="text-right font-semibold">${App.formatCurrency(r.monto, 'DOP')}</td>
                        <td><span class="badge badge-cancelled" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;">Anulada</span></td>
                    </tr>
                `;
            }).join('');

            summary.innerHTML = `
                <span>Total Comprobantes Anulados: <strong>${this._records608.length}</strong></span>
                <span>Monto Anulado: <strong style="color:#dc2626;font-size:16px;">${App.formatCurrency(sumTotal, 'DOP')}</strong></span>
            `;

            this.render608CatalogLegend(refBox);
        }
    },

    render608CatalogLegend(container) {
        if (!container) return;
        container.innerHTML = `
            <div class="table-outer" style="padding:20px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                <h3 style="font-size:14px;font-weight:700;margin-bottom:12px;color:var(--color-text-primary);display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                    Catálogo Oficial DGII — Tipos de Anulación (Formato 608)
                </h3>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:10px;font-size:12px;">
                    <div style="padding:8px 12px;background:var(--bg-hover);border-radius:6px;"><strong>01:</strong> Deterioro de Factura Pre-Impresa</div>
                    <div style="padding:8px 12px;background:var(--bg-hover);border-radius:6px;"><strong>02:</strong> Errores de Impresión (Factura Pre-Impresa)</div>
                    <div style="padding:8px 12px;background:var(--bg-hover);border-radius:6px;"><strong>03:</strong> Impresión Defectuosa</div>
                    <div style="padding:8px 12px;background:var(--bg-hover);border-radius:6px;"><strong>04:</strong> Duplicidad de Factura</div>
                    <div style="padding:8px 12px;background:var(--bg-hover);border-radius:6px;"><strong>05:</strong> Corrección de la Información</div>
                    <div style="padding:8px 12px;background:var(--bg-hover);border-radius:6px;"><strong>06:</strong> Cambio de Productos</div>
                    <div style="padding:8px 12px;background:var(--bg-hover);border-radius:6px;"><strong>07:</strong> Devolución de Productos</div>
                    <div style="padding:8px 12px;background:var(--bg-hover);border-radius:6px;"><strong>08:</strong> Omisión de Productos</div>
                    <div style="padding:8px 12px;background:var(--bg-hover);border-radius:6px;"><strong>09:</strong> Errores en Secuencias de NCF</div>
                </div>
            </div>
        `;
    },

    update606Field(idx, field, value) {
        if (this._records606[idx]) {
            this._records606[idx][field] = value;
            if (field === 'forma_pago' && value === '04') {
                this._records606[idx]['fecha_pago'] = '';
            }
        }
    },

    update608Field(idx, field, value) {
        if (this._records608[idx]) {
            this._records608[idx][field] = value;
        }
    },

    formatDgiiDate(dateStr) {
        if (!dateStr || dateStr.length !== 8) return dateStr || '—';
        const y = dateStr.slice(0, 4);
        const m = dateStr.slice(4, 6);
        const d = dateStr.slice(6, 8);
        return `${d}/${m}/${y}`;
    },

    getCurrentRecords() {
        if (this._currentTab === '607') return this._records607;
        if (this._currentTab === '606') return this._records606;
        if (this._currentTab === '608') return this._records608;
        return [];
    },

    async exportExcel() {
        const periodStr = `${this._year}${String(this._month).padStart(2, '0')}`;
        const records = this.getCurrentRecords();

        if (records.length === 0) {
            App.showToast(`No hay registros de ${this._currentTab} para exportar en este período`, 'error');
            return;
        }

        App.showToast(`Generando plantilla oficial Excel DGII ${this._currentTab}...`, 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/${this._currentTab}/export-excel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/vnd.ms-excel, application/octet-stream',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    period: periodStr,
                    records: records
                }),
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errText = await response.text();
                throw new Error(`Error del servidor (${response.status}): ${errText}`);
            }

            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.target = '_blank';

            const rnc = App.state.settings?.company_tax_id
                ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '')
                : '131000000';

            a.download = `DGII_${this._currentTab}_${rnc}_${periodStr}.xls`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast(`¡Plantilla Excel oficial DGII ${this._currentTab} descargada con éxito!`, 'success');
        } catch (e) {
            console.error('Excel Export error:', e);
            App.showToast('Error al generar la plantilla Excel DGII', 'error');
        }
    },

    async exportTxt() {
        const periodStr = `${this._year}${String(this._month).padStart(2, '0')}`;
        const records = this.getCurrentRecords();

        if (records.length === 0) {
            App.showToast(`No hay registros de ${this._currentTab} para exportar en este período`, 'error');
            return;
        }

        App.showToast(`Generando archivo de texto DGII ${this._currentTab}...`, 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/${this._currentTab}/export`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/plain',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    period: periodStr,
                    records: records
                }),
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`Error del servidor: ${response.status}`);
            }

            const text = await response.text();
            const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.target = '_blank';

            const rnc = App.state.settings?.company_tax_id
                ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '')
                : '131000000';

            a.download = `DGII_${this._currentTab}_${rnc}_${periodStr}.txt`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast(`¡Archivo TXT DGII ${this._currentTab} descargado exitosamente!`, 'success');
        } catch (e) {
            console.error('TXT Export error:', e);
            App.showToast('Error al exportar archivo de reporte TXT', 'error');
        }
    }
};

window.ReportsModule = ReportsModule;
export default ReportsModule;
