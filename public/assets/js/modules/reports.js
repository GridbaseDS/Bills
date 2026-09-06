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
                    <button class="segment-item ${this._currentTab === 'it1' ? 'active' : ''}" data-tab="it1" style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#16a34a;"></span>Declaración IT-1</button>
                </div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <button class="btn" id="btn-prevalidate" style="display:flex;align-items:center;gap:8px;background:#0284c7;border-color:#0284c7;color:#fff;font-weight:600;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        Pre-validar (DGII)
                    </button>
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

        const btnPrevalidate = document.getElementById('btn-prevalidate');
        if (btnPrevalidate) {
            btnPrevalidate.addEventListener('click', () => this.prevalidate());
        }

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
            const [res607, res606, res608, resIt1] = await Promise.all([
                App.api(`dgii/reports/607?year=${this._year}&month=${this._month}`),
                App.api(`dgii/reports/606?year=${this._year}&month=${this._month}`),
                App.api(`dgii/reports/608?year=${this._year}&month=${this._month}`),
                App.api(`dgii/reports/it1/summary?year=${this._year}&month=${this._month}`)
            ]);

            this._records607 = res607.data || [];
            this._records606 = res606.data || [];
            this._records608 = res608.data || [];
            this._dataIt1 = resIt1.data || null;

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

        const btnPrevalidate = document.getElementById('btn-prevalidate');
        const btnExportTxt = document.getElementById('btn-export-txt');
        const btnExportExcel = document.getElementById('btn-export-excel');

        if (this._currentTab === 'it1') {
            if (btnPrevalidate) btnPrevalidate.style.display = 'none';
            if (btnExportTxt) btnExportTxt.style.display = 'none';
            if (btnExportExcel) {
                btnExportExcel.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                    Descargar Formulario Oficial IT-1 (.xls)
                `;
            }
        } else {
            if (btnPrevalidate) btnPrevalidate.style.display = 'flex';
            if (btnExportTxt) btnExportTxt.style.display = 'flex';
            if (btnExportExcel) {
                btnExportExcel.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                    Descargar Plantilla Excel (.xls)
                `;
            }
        }

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
        } else if (this._currentTab === 'it1') {
            this.renderIt1Declaration(headers, tbody, summary, refBox);
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

    renderIt1Declaration(headers, tbody, summary, refBox) {
        const d = this._dataIt1;
        if (!d) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-24">No se pudo cargar la declaración IT-1 para este período.</td></tr>`;
            return;
        }

        const it1 = d.it1 || {};
        const anexoA = d.anexo_a || {};
        const itbisPag = anexoA.itbis_pagado || {};
        const ncfC = anexoA.ncf_counts || {};
        const ncfA = anexoA.ncf_amounts || {};
        const fp = anexoA.formas_pago || {};

        // Banner informativo
        const bannerHtml = `
            <tr><td colspan="4" style="padding:0;border:none;">
                <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #86efac;border-radius:8px;padding:16px 20px;margin:12px 0;display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:36px;height:36px;border-radius:8px;background:#16a34a;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:14px;flex-shrink:0;">
                        IT-1
                    </div>
                    <div style="font-size:13px;color:#166534;line-height:1.5;flex:1;">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                            <strong>Declaración Jurada y Pago de ITBIS (Formulario IT-1 y Anexo A Oficial)</strong>
                            <span class="badge" style="background:#16a34a;color:#fff;font-size:11px;padding:3px 8px;border-radius:4px;">Plantilla Oficial DGII IT-1-2020.xls</span>
                        </div>
                        <div style="margin-top:4px;color:#14532d;">
                            Contribuyente: <strong>${d.company_name}</strong> (RNC: <code>${d.tax_id}</code>) &bull; Período: <strong>${d.period_formatted}</strong> &bull; Fecha Límite de Pago: <strong>${d.deadline}</strong>
                        </div>
                    </div>
                </div>
            </td></tr>
        `;

        headers.innerHTML = `
            <th style="width:140px;">Casilla Oficial</th>
            <th>Concepto / Descripción del Formulario</th>
            <th class="text-right" style="width:200px;">Monto Calculado</th>
            <th style="width:260px;">Fórmula / Base Imponible</th>
        `;

        const row = (casilla, title, amount, note, isHighlight = false, isFormula = false, isResult = false) => {
            let bg = isResult ? 'background:rgba(22,163,74,0.08);' : (isHighlight ? 'background:rgba(2,132,199,0.04);' : '');
            let fontColor = isResult ? 'color:#15803d;' : (isHighlight ? 'color:var(--color-primary);' : '');
            return `
                <tr style="${bg}">
                    <td>
                        <span class="badge" style="font-family:'JetBrains Mono',monospace;font-size:11px;font-weight:700;${isResult ? 'background:#16a34a;color:#fff;' : 'background:var(--bg-hover);color:var(--color-text-primary);border:1px solid var(--color-border);'}">
                            ${casilla}
                        </span>
                    </td>
                    <td>
                        <strong style="${isResult ? 'font-size:14px;color:#15803d;' : ''}">${title}</strong>
                        ${isFormula ? '<span style="font-size:11px;color:var(--color-text-muted);display:block;margin-top:2px;">Fórmula automática DGII</span>' : ''}
                    </td>
                    <td class="text-right font-semibold" style="${fontColor}${isResult ? 'font-size:16px;' : 'font-size:14px;'}">
                        ${App.formatCurrency(amount || 0, 'DOP')}
                    </td>
                    <td style="font-size:12px;color:var(--color-text-muted);">${note}</td>
                </tr>
            `;
        };

        const sectionHeader = (title) => `
            <tr style="background:var(--bg-hover);">
                <td colspan="4" style="padding:10px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:var(--color-text-primary);border-top:2px solid var(--color-border);border-bottom:1px solid var(--color-border);">
                    ${title}
                </td>
            </tr>
        `;

        tbody.innerHTML = bannerHtml + `
            ${sectionHeader('I. Operaciones del Período (Ingresos por Ventas 607)')}
            ${row('Casilla 1', 'Total de Operaciones del Período', it1.casilla_1_total_operaciones, "Fórmula: ='Anexo A'!W25", false, true)}
            ${row('Casilla 11', 'Operaciones Gravadas al 18% (Base Imponible)', it1.casilla_11_gravadas_18, 'Total facturado en ventas gravadas con 18%', true)}
            
            ${sectionHeader('II. Liquidación y Determinación del ITBIS (Ventas vs Compras)')}
            ${row('Casilla 16 / 21', 'Total ITBIS Cobrado en Ventas (18%)', it1.casilla_21_total_itbis_cobrado, 'Fórmula: =V26*0.18 (Casilla 11 * 18%)', true, true)}
            ${row('Casilla 22', 'ITBIS Pagado en Compras Locales Deducible (Bienes)', it1.casilla_22_itbis_bienes, "Fórmula: ='Anexo A'!O87 (De formato 606)", false, true)}
            ${row('Casilla 23', 'ITBIS Pagado por Servicios Deducibles', it1.casilla_23_itbis_servicios, "Fórmula: ='Anexo A'!P87 (De formato 606)", false, true)}
            ${row('Casilla 25', 'Total ITBIS Deducible en Compras / Gastos', it1.casilla_25_total_itbis_deducible, 'Fórmula: =V39+V40 (Bienes + Servicios)', true, true)}
            ${row('Casilla 26', 'Impuesto a Pagar (ITBIS Neto)', it1.casilla_26_impuesto_a_pagar, 'Fórmula: =IF(V38-V42>0, V38-V42, 0)', false, true)}
            ${row('Casilla 27', 'Saldo a Favor del Período', it1.casilla_27_saldo_a_favor, 'Fórmula: =IF(V42-V38>0, V42-V38, 0)', false, true)}
            ${row('Casilla 38', 'TOTAL A PAGAR AL FISCO (DGII)', it1.casilla_38_total_a_pagar, 'Monto final a liquidar en ventanilla bancaria u Oficina Virtual', false, true, true)}
        `;

        summary.innerHTML = `
            <div>
                <span>Período Fiscal: <strong>${d.period_formatted}</strong></span> &bull; 
                <span>Fecha Límite: <strong style="color:var(--color-danger-icon);">${d.deadline}</strong></span>
            </div>
            <div>
                Total Impuesto Determinado: <strong style="color:#16a34a;font-size:18px;margin-left:8px;">${App.formatCurrency(it1.casilla_38_total_a_pagar || 0, 'DOP')}</strong>
            </div>
        `;

        // Render Anexo A Reference breakdown in refBox
        if (refBox) {
            refBox.innerHTML = `
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:16px;">
                    <!-- Card 1: Comprobantes Emitidos (Renglón II) -->
                    <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                        <h4 style="font-size:13px;font-weight:700;margin-bottom:12px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                            <span>Anexo A: Comprobantes Emitidos (Renglón II)</span>
                            <span class="badge" style="background:var(--bg-hover);font-size:10px;">Fórmulas T15:W24</span>
                        </h4>
                        <table style="width:100%;font-size:12px;border-collapse:collapse;">
                            <tbody>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Crédito Fiscal (B01 / E31):</td>
                                    <td class="text-right" style="padding:6px 0;font-family:'JetBrains Mono',monospace;"><strong>${ncfC['01_31'] || 0}</strong> doc(s)</td>
                                    <td class="text-right font-semibold" style="padding:6px 0;">${App.formatCurrency(ncfA['01_31'] || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Facturas de Consumo (B02 / E32):</td>
                                    <td class="text-right" style="padding:6px 0;font-family:'JetBrains Mono',monospace;"><strong>${ncfC['02_32'] || 0}</strong> doc(s)</td>
                                    <td class="text-right font-semibold" style="padding:6px 0;">${App.formatCurrency(ncfA['02_32'] || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Notas de Crédito (B04 / E34):</td>
                                    <td class="text-right" style="padding:6px 0;font-family:'JetBrains Mono',monospace;color:#dc2626;"><strong>${ncfC['04_34'] || 0}</strong> doc(s)</td>
                                    <td class="text-right font-semibold" style="padding:6px 0;color:#dc2626;">-${App.formatCurrency(ncfA['04_34'] || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Gubernamentales / Reg. Especial:</td>
                                    <td class="text-right" style="padding:6px 0;font-family:'JetBrains Mono',monospace;"><strong>${(ncfC['14_44'] || 0) + (ncfC['15_45'] || 0)}</strong> doc(s)</td>
                                    <td class="text-right font-semibold" style="padding:6px 0;">${App.formatCurrency((ncfA['14_44'] || 0) + (ncfA['15_45'] || 0), 'DOP')}</td>
                                </tr>
                                <tr style="background:var(--bg-hover);font-weight:700;">
                                    <td style="padding:8px 0;">Total Operaciones (Casilla 11):</td>
                                    <td class="text-right" style="padding:8px 0;">—</td>
                                    <td class="text-right" style="padding:8px 0;color:var(--color-primary);">${App.formatCurrency(anexoA.total_operaciones || 0, 'DOP')}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Card 2: Formas de Pago (Renglón III) -->
                    <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                        <h4 style="font-size:13px;font-weight:700;margin-bottom:12px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                            <span>Anexo A: Formas de Pago Declaradas (Renglón III)</span>
                            <span class="badge" style="background:var(--bg-hover);font-size:10px;">W28:W34</span>
                        </h4>
                        <table style="width:100%;font-size:12px;border-collapse:collapse;">
                            <tbody>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Efectivo (Casilla 12):</td>
                                    <td class="text-right font-semibold" style="padding:6px 0;">${App.formatCurrency(fp.efectivo || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Cheques / Transferencia (Casilla 13):</td>
                                    <td class="text-right font-semibold" style="padding:6px 0;">${App.formatCurrency(fp.cheque_transferencia || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Tarjeta Débito/Crédito (Casilla 14):</td>
                                    <td class="text-right font-semibold" style="padding:6px 0;">${App.formatCurrency(fp.tarjeta || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Venta a Crédito (Casilla 15):</td>
                                    <td class="text-right font-semibold" style="padding:6px 0;color:var(--color-danger-icon);">${App.formatCurrency(fp.credito || 0, 'DOP')}</td>
                                </tr>
                                <tr style="background:var(--bg-hover);font-weight:700;">
                                    <td style="padding:8px 0;">Total Recaudado / Percibido:</td>
                                    <td class="text-right" style="padding:8px 0;color:#059669;">${App.formatCurrency((fp.efectivo || 0) + (fp.cheque_transferencia || 0) + (fp.tarjeta || 0) + (fp.credito || 0) + (fp.bonos || 0) + (fp.permuta || 0) + (fp.otras || 0), 'DOP')}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
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

    async prevalidate() {
        if (this._currentTab === 'it1') {
            App.showToast('La declaración IT-1 valida sus datos directamente en la planilla oficial de Excel con sus fórmulas matemáticas DGII.', 'info');
            return;
        }

        const periodStr = `${this._year}${String(this._month).padStart(2, '0')}`;
        const records = this.getCurrentRecords();

        if (records.length === 0) {
            App.showToast(`No hay registros de ${this._currentTab} para pre-validar en este período`, 'info');
            return;
        }

        App.showToast(`Ejecutando motor de pre-validación DGII para Formato ${this._currentTab}...`, 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/${this._currentTab}/prevalidate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    period: periodStr,
                    rnc: App.state.settings?.company_tax_id ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') : undefined,
                    records: records
                }),
                credentials: 'same-origin'
            });

            const res = await response.json();
            this.showPrevalidationModal(res);
        } catch (e) {
            console.error('Prevalidation error:', e);
            App.showToast('Error al ejecutar la pre-validación DGII', 'error');
        }
    },

    showPrevalidationModal(res) {
        const isValid = res.valid;
        const errCount = (res.errors || []).length;
        const warnCount = (res.warnings || []).length;

        let badgeHtml = '';
        if (isValid) {
            badgeHtml = `
                <div style="background:#ecfdf5;border:1px solid #10b981;border-radius:8px;padding:16px;display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                    <div style="background:#10b981;color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:18px;">✓</div>
                    <div>
                        <h4 style="margin:0;color:#065f46;font-size:15px;font-weight:700;">¡Documento 100% Válido para DGII!</h4>
                        <p style="margin:2px 0 0 0;color:#047857;font-size:12px;">Cumple con el 100% de las normas, algoritmos de dígito verificador y estructuras de comprobantes de la DGII.</p>
                    </div>
                </div>
            `;
        } else {
            badgeHtml = `
                <div style="background:#fef2f2;border:1px solid #ef4444;border-radius:8px;padding:16px;display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                    <div style="background:#ef4444;color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:18px;">✕</div>
                    <div>
                        <h4 style="margin:0;color:#991b1b;font-size:15px;font-weight:700;">Se encontraron ${errCount} errores en el reporte</h4>
                        <p style="margin:2px 0 0 0;color:#b91c1c;font-size:12px;">La DGII rechazará este archivo. Revise el detalle a continuación para corregirlos.</p>
                    </div>
                </div>
            `;
        }

        let errorsHtml = '';
        if (errCount > 0) {
            errorsHtml = `
                <div style="margin-bottom:16px;">
                    <h5 style="color:#ef4444;font-size:13px;font-weight:700;margin-bottom:8px;">Errores que bloquean el envío (${errCount}):</h5>
                    <ul style="margin:0;padding-left:20px;font-size:12px;color:#b91c1c;line-height:1.6;max-height:180px;overflow-y:auto;">
                        ${res.errors.map(err => `<li>${err}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        let warningsHtml = '';
        if (warnCount > 0) {
            warningsHtml = `
                <div style="margin-bottom:16px;">
                    <h5 style="color:#d97706;font-size:13px;font-weight:700;margin-bottom:8px;">Advertencias informativas (${warnCount}):</h5>
                    <ul style="margin:0;padding-left:20px;font-size:12px;color:#b45309;line-height:1.6;max-height:120px;overflow-y:auto;">
                        ${res.warnings.map(w => `<li>${w}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        const modalHtml = `
            <div id="modal-prevalidation" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(3px);">
                <div style="background:var(--bg-card, #fff);border-radius:12px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);width:90%;max-width:620px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;border:1px solid var(--border-color, #e2e8f0);">
                    <div style="padding:16px 20px;border-bottom:1px solid var(--border-color, #e2e8f0);display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="background:#0284c7;color:#fff;padding:4px 8px;border-radius:6px;font-size:11px;font-weight:700;">DGII ${res.format}</span>
                            <h3 style="margin:0;font-size:16px;font-weight:700;">Resultado de Pre-validación Fiscal</h3>
                        </div>
                        <button id="btn-close-preval-x" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--color-text-muted, #64748b);">&times;</button>
                    </div>
                    <div style="padding:20px;overflow-y:auto;flex:1;">
                        ${badgeHtml}
                        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:12px;background:var(--bg-hover, #f8fafc);padding:12px;border-radius:8px;margin-bottom:16px;font-size:12px;">
                            <div><span style="color:var(--color-text-muted, #64748b);display:block;">RNC Contribuyente:</span><strong>${res.rnc}</strong></div>
                            <div><span style="color:var(--color-text-muted, #64748b);display:block;">Período Fiscal:</span><strong>${res.period}</strong></div>
                            <div><span style="color:var(--color-text-muted, #64748b);display:block;">Comprobantes:</span><strong>${res.record_count} líneas</strong></div>
                        </div>
                        ${errorsHtml}
                        ${warningsHtml}
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid var(--border-color, #e2e8f0);background:var(--bg-hover, #f8fafc);display:flex;justify-content:flex-end;gap:10px;">
                        <button class="btn btn-secondary" id="btn-close-preval">Cerrar</button>
                    </div>
                </div>
            </div>
        `;

        const existingModal = document.getElementById('modal-prevalidation');
        if (existingModal) existingModal.remove();

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        const close = () => {
            const m = document.getElementById('modal-prevalidation');
            if (m) m.remove();
        };

        document.getElementById('btn-close-preval-x')?.addEventListener('click', close);
        document.getElementById('btn-close-preval')?.addEventListener('click', close);
    },

    async exportExcel() {
        if (this._currentTab === 'it1') {
            return this.exportIt1Excel();
        }

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
                    rnc: App.state.settings?.company_tax_id ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') : undefined,
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
        if (this._currentTab === 'it1') {
            App.showToast('La declaración IT-1 oficial se presenta en formato Excel (.xls) o directamente en la Oficina Virtual de la DGII.', 'info');
            return;
        }

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
    },

    async exportIt1Excel() {
        const periodStr = `${this._year}${String(this._month).padStart(2, '0')}`;
        App.showToast('Generando Formulario Oficial IT-1 y Anexo A en formato Excel DGII...', 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/it1/export-excel?year=${this._year}&month=${this._month}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/vnd.ms-excel, application/octet-stream',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${token}`
                },
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

            const rnc = (this._dataIt1 && this._dataIt1.tax_id)
                ? this._dataIt1.tax_id
                : (App.state.settings?.company_tax_id ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') : '131000000');

            a.download = `DGII_IT1_${rnc}_${periodStr}.xls`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast('¡Formulario Oficial IT-1 (Excel DGII) descargado con éxito!', 'success');
        } catch (e) {
            console.error('IT-1 Excel Export error:', e);
            App.showToast('Error al generar el formulario oficial IT-1', 'error');
        }
    }
};

window.ReportsModule = ReportsModule;
export default ReportsModule;
