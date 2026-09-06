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
                    <button class="segment-item ${this._currentTab === 'ir2' ? 'active' : ''}" data-tab="ir2" style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#8b5cf6;"></span>Declaración IR-2</button>
                    <button class="segment-item ${this._currentTab === 'itc' ? 'active' : ''}" data-tab="itc" style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#0284c7;"></span>ISC Telecom (ITC-01)</button>
                    <button class="segment-item ${this._currentTab === 'dss' ? 'active' : ''}" data-tab="dss" style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#ea580c;"></span>Seguros (DSS-07)</button>
                    <button class="segment-item ${this._currentTab === 'daf' ? 'active' : ''}" data-tab="daf" style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#0d9488;"></span>Activos Financieros (DAF)</button>
                    <button class="segment-item ${this._currentTab === 'rs1' ? 'active' : ''}" data-tab="rs1" style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#4f46e5;"></span>RST Físicas (RS1)</button>
                    <button class="segment-item ${this._currentTab === 'rs2' ? 'active' : ''}" data-tab="rs2" style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#7c3aed;"></span>RST Jurídicas (RS2)</button>
                    <button class="segment-item ${this._currentTab === 'rs3' ? 'active' : ''}" data-tab="rs3" style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#d97706;"></span>RST Compras (RS3)</button>
                    <button class="segment-item ${this._currentTab === 'rs4' ? 'active' : ''}" data-tab="rs4" style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#65a30d;"></span>RST Agropecuario (RS4)</button>
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
            const [res607, res606, res608, resIt1, resIr2, resItc, resDss, resDaf, resRs1, resRs2, resRs3, resRs4] = await Promise.all([
                App.api(`dgii/reports/607?year=${this._year}&month=${this._month}`),
                App.api(`dgii/reports/606?year=${this._year}&month=${this._month}`),
                App.api(`dgii/reports/608?year=${this._year}&month=${this._month}`),
                App.api(`dgii/reports/it1/summary?year=${this._year}&month=${this._month}`),
                App.api(`dgii/reports/ir2/summary?year=${this._year}`),
                App.api(`dgii/reports/itc/summary?year=${this._year}&month=${this._month}`),
                App.api(`dgii/reports/dss/summary?year=${this._year}&month=${this._month}`),
                App.api(`dgii/reports/daf/summary?year=${this._year}`),
                App.api(`dgii/reports/rs1/summary?year=${this._year}`),
                App.api(`dgii/reports/rs2/summary?year=${this._year}`),
                App.api(`dgii/reports/rs3/summary?year=${this._year}`),
                App.api(`dgii/reports/rs4/summary?year=${this._year}`)
            ]);

            this._records607 = res607.data || [];
            this._records606 = res606.data || [];
            this._records608 = res608.data || [];
            this._dataIt1 = resIt1.data || null;
            this._dataIr2 = resIr2.data || null;
            this._dataItc = resItc.data || null;
            this._dataDss = resDss.data || null;
            this._dataDaf = resDaf.data || null;
            this._dataRs1 = resRs1.data || null;
            this._dataRs2 = resRs2.data || null;
            this._dataRs3 = resRs3.data || null;
            this._dataRs4 = resRs4.data || null;

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
        } else if (this._currentTab === 'ir2') {
            if (btnPrevalidate) btnPrevalidate.style.display = 'none';
            if (btnExportTxt) btnExportTxt.style.display = 'none';
            if (btnExportExcel) {
                btnExportExcel.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                    Descargar Formulario Oficial IR-2 (.xls)
                `;
            }
        } else if (this._currentTab === 'itc') {
            if (btnPrevalidate) btnPrevalidate.style.display = 'none';
            if (btnExportTxt) btnExportTxt.style.display = 'none';
            if (btnExportExcel) {
                btnExportExcel.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                    Descargar Formulario Oficial ITC-01 (.xls)
                `;
            }
        } else if (this._currentTab === 'dss') {
            if (btnPrevalidate) btnPrevalidate.style.display = 'none';
            if (btnExportTxt) btnExportTxt.style.display = 'none';
            if (btnExportExcel) {
                btnExportExcel.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                    Descargar Formulario Oficial DSS-07 (.xls)
                `;
            }
        } else if (this._currentTab === 'daf') {
            if (btnPrevalidate) btnPrevalidate.style.display = 'none';
            if (btnExportTxt) btnExportTxt.style.display = 'none';
            if (btnExportExcel) {
                btnExportExcel.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                    Descargar Formulario Oficial DAF (.xls)
                `;
            }
        } else if (this._currentTab === 'rs1') {
            if (btnPrevalidate) btnPrevalidate.style.display = 'none';
            if (btnExportTxt) btnExportTxt.style.display = 'none';
            if (btnExportExcel) {
                btnExportExcel.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                    Descargar Formulario Oficial RS1 (.xlsx)
                `;
            }
        } else if (this._currentTab === 'rs2') {
            if (btnPrevalidate) btnPrevalidate.style.display = 'none';
            if (btnExportTxt) btnExportTxt.style.display = 'none';
            if (btnExportExcel) {
                btnExportExcel.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                    Descargar Formulario Oficial RS2 (.xlsx)
                `;
            }
        } else if (this._currentTab === 'rs3') {
            if (btnPrevalidate) btnPrevalidate.style.display = 'none';
            if (btnExportTxt) btnExportTxt.style.display = 'none';
            if (btnExportExcel) {
                btnExportExcel.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                    Descargar Formulario Oficial RS3 (.xlsx)
                `;
            }
        } else if (this._currentTab === 'rs4') {
            if (btnPrevalidate) btnPrevalidate.style.display = 'none';
            if (btnExportTxt) btnExportTxt.style.display = 'none';
            if (btnExportExcel) {
                btnExportExcel.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                    Descargar Formulario Oficial RS4 (.xlsx)
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
        } else if (this._currentTab === 'ir2') {
            this.renderIr2Declaration(headers, tbody, summary, refBox);
        } else if (this._currentTab === 'itc') {
            this.renderItcDeclaration(headers, tbody, summary, refBox);
        } else if (this._currentTab === 'dss') {
            this.renderDssDeclaration(headers, tbody, summary, refBox);
        } else if (this._currentTab === 'daf') {
            this.renderDafDeclaration(headers, tbody, summary, refBox);
        } else if (this._currentTab === 'rs1') {
            this.renderRs1Declaration(headers, tbody, summary, refBox);
        } else if (this._currentTab === 'rs2') {
            this.renderRs2Declaration(headers, tbody, summary, refBox);
        } else if (this._currentTab === 'rs3') {
            this.renderRs3Declaration(headers, tbody, summary, refBox);
        } else if (this._currentTab === 'rs4') {
            this.renderRs4Declaration(headers, tbody, summary, refBox);
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

    renderIr2Declaration(headers, tbody, summary, refBox) {
        const d = this._dataIr2;
        if (!d) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-24">No se pudo cargar la declaración IR-2 para este ejercicio fiscal.</td></tr>`;
            return;
        }

        const ir2 = d.ir2 || {};
        const b1 = d.b1 || {};
        const anexoJ = d.anexo_j || {};
        const jV = anexoJ.ventas || {};
        const jG = anexoJ.gastos || {};
        const a1 = d.a1 || {};

        // Banner informativo
        const bannerHtml = `
            <tr><td colspan="4" style="padding:0;border:none;">
                <div style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:1px solid #c4b5fd;border-radius:8px;padding:16px 20px;margin:12px 0;display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:36px;height:36px;border-radius:8px;background:#7c3aed;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:14px;flex-shrink:0;">
                        IR-2
                    </div>
                    <div style="font-size:13px;color:#5b21b6;line-height:1.5;flex:1;">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                            <strong>Declaración Jurada Anual del Impuesto Sobre la Renta de Sociedades (Formulario IR-2 Versión 2026)</strong>
                            <span class="badge" style="background:#7c3aed;color:#fff;font-size:11px;padding:3px 8px;border-radius:4px;">Plantilla Oficial DGII (17 Hojas y Anexos)</span>
                        </div>
                        <div style="margin-top:4px;color:#4c1d95;">
                            Contribuyente: <strong>${d.company_name}</strong> (RNC: <code>${d.tax_id}</code>) &bull; Ejercicio Fiscal: <strong>${d.period_formatted}</strong> &bull; Fecha Límite Legal: <strong>${d.deadline}</strong>
                        </div>
                    </div>
                </div>
            </td></tr>
        `;

        headers.innerHTML = `
            <th style="width:140px;">Casilla Oficial</th>
            <th>Concepto / Descripción del Formulario</th>
            <th class="text-right" style="width:200px;">Monto Anual</th>
            <th style="width:260px;">Fórmula / Base Imponible</th>
        `;

        const row = (casilla, title, amount, note, isHighlight = false, isFormula = false, isResult = false) => {
            let bg = isResult ? 'background:rgba(124,58,237,0.08);' : (isHighlight ? 'background:rgba(2,132,199,0.04);' : '');
            let fontColor = isResult ? 'color:#6d28d9;' : (isHighlight ? 'color:var(--color-primary);' : '');
            return `
                <tr style="${bg}">
                    <td>
                        <span class="badge" style="font-family:'JetBrains Mono',monospace;font-size:11px;font-weight:700;${isResult ? 'background:#7c3aed;color:#fff;' : 'background:var(--bg-hover);color:var(--color-text-primary);border:1px solid var(--color-border);'}">
                            ${casilla}
                        </span>
                    </td>
                    <td>
                        <strong style="${isResult ? 'font-size:14px;color:#6d28d9;' : ''}">${title}</strong>
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
            ${sectionHeader('I. Determinación de la Renta Neta Imponible (Viene de Anexo B-1)')}
            ${row('Casilla A', 'Total de Ingresos Brutos de Operaciones', ir2.casilla_A_total_ingresos, "Fórmula: =Casilla4_B1 ('Anexo B-1'!I35)", false, true)}
            ${row('Casilla 1', 'Beneficio o Pérdida Neta Antes de Impuesto', ir2.casilla_1_beneficio_neto, "Fórmula: =Casilla14_B1 ('Anexo B-1'!I103)", true, true)}
            ${row('Casilla 6', 'Total Ajustes Fiscales (Positivos - Negativos)', 0, "Fórmula: =+AB32-AB33-AB34-AB35", false, true)}
            ${row('Casilla 7 / 11', 'Renta Neta Imponible Sujeta al Impuesto', ir2.casilla_11_renta_imponible, "Base imponible para cálculo de ISR corporativo", true, true)}
            
            ${sectionHeader('II. Liquidación del Impuesto Sobre la Renta (Tasa 27% Legal)')}
            ${row('Casilla 12', 'Impuesto Liquidado del Ejercicio (27% Tasa Corporativa)', ir2.casilla_12_impuesto_liquidado, "Fórmula: =IF(AB41>0, AB41 * 0.27, 0)", true, true)}
            ${row('Casilla 14', 'Retenciones Efectuadas por Entidades del Estado (623)', ir2.casilla_14_retenciones_estado, "Retenciones computables del 5% del Estado", false, false)}
            ${row('Casilla 23', 'Diferencia a Pagar del Ejercicio', ir2.casilla_23_diferencia_pagar, "Fórmula: =IF(AB44-AB46>0, AB44-AB46, 0)", false, true)}
            ${row('Casilla 24', 'Saldo a Favor del Contribuyente', ir2.casilla_24_saldo_favor, "Crédito a compensar en ejercicios futuros", false, true)}
            ${row('Casilla 31', 'TOTAL IMPUESTO SOBRE LA RENTA A PAGAR (DGII)', ir2.casilla_31_total_a_pagar, "Monto definitivo a liquidar ante la DGII", false, true, true)}
        `;

        summary.innerHTML = `
            <div>
                <span>Ejercicio Fiscal: <strong>${d.year}</strong> (${d.period_formatted})</span> &bull; 
                <span>Fecha Límite Legal: <strong style="color:var(--color-danger-icon);">${d.deadline}</strong></span>
            </div>
            <div>
                Total Impuesto Liquidado (27%): <strong style="color:#7c3aed;font-size:18px;margin-left:8px;">${App.formatCurrency(ir2.casilla_31_total_a_pagar || 0, 'DOP')}</strong>
            </div>
        `;

        // Render Anexo B-1, Anexo J, Anexo A-1 and Activo in refBox
        if (refBox) {
            const act = d.activo || {};
            refBox.innerHTML = `
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(340px, 1fr));gap:16px;">
                    <!-- Card 1: Anexo B-1 Estado de Resultados -->
                    <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                        <h4 style="font-size:13px;font-weight:700;margin-bottom:12px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                            <span>Anexo B-1: Estado de Resultados Oficial</span>
                            <span class="badge" style="background:var(--bg-hover);font-size:10px;">Fórmulas I16:I104</span>
                        </h4>
                        <table style="width:100%;font-size:12px;border-collapse:collapse;">
                            <tbody>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">1.1 Ventas Locales (607):</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency(b1.ventas_locales || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">1.3 Devoluciones / Notas Crédito:</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;color:#dc2626;">-${App.formatCurrency(b1.devoluciones_ventas || 0, 'DOP')}</td>
                                </tr>
                                <tr style="background:var(--bg-hover);font-weight:700;border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Total Ingresos Netos (Casilla 4):</td>
                                    <td class="text-right" style="padding:6px 0;color:var(--color-primary);">${App.formatCurrency(b1.total_ingresos_netos || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">6.1 Sueldos y Personal (01):</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency(b1.gastos_personal || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">7.1 Honorarios Personas Físicas (02):</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency(b1.honorarios_fisicas || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">7.2 Honorarios Personas Morales (02):</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency(b1.honorarios_morales || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">7.7 Suministros y Otros Servicios:</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency(b1.otros_servicios || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">8. Arrendamientos (03):</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency(b1.arrendamientos || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">9. Activos Fijos / Reparaciones (04):</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency(b1.gastos_activos_fijos || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">10. Publicidad y Representación (05):</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency(b1.gastos_representacion || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">11. Primas de Seguros (06):</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency(b1.seguros || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">12. Gastos Financieros (07):</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency(b1.gastos_financieros || 0, 'DOP')}</td>
                                </tr>
                                <tr style="background:var(--bg-hover);font-weight:700;">
                                    <td style="padding:8px 0;">Beneficio Neto Antes Impuesto (Casilla 14):</td>
                                    <td class="text-right" style="padding:8px 0;color:#059669;">${App.formatCurrency(b1.beneficio_neto || 0, 'DOP')}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Card 2: Anexo A-1 Balance General Oficial Cuadrado -->
                    <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                        <h4 style="font-size:13px;font-weight:700;margin-bottom:12px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                            <span>Anexo A-1: Balance General Oficial</span>
                            <span class="badge" style="background:#059669;color:#fff;font-size:10px;">Balance Cuadrado ✓</span>
                        </h4>
                        <table style="width:100%;font-size:12px;border-collapse:collapse;">
                            <tbody>
                                <tr style="background:rgba(2,132,199,0.06);font-weight:700;">
                                    <td colspan="2" style="padding:4px 6px;color:var(--color-primary);">I. ACTIVOS</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:4px 6px;">1.1 Cajas y Bancos:</td>
                                    <td class="text-right font-semibold" style="padding:4px 6px;">${App.formatCurrency(a1.caja_bancos || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:4px 6px;">1.2 Cuentas por Cobrar Clientes:</td>
                                    <td class="text-right font-semibold" style="padding:4px 6px;">${App.formatCurrency(a1.cuentas_por_cobrar || 0, 'DOP')}</td>
                                </tr>
                                <tr style="background:var(--bg-hover);font-weight:700;border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 6px;">6. TOTAL ACTIVOS:</td>
                                    <td class="text-right" style="padding:5px 6px;color:var(--color-primary);">${App.formatCurrency(a1.total_activos || 0, 'DOP')}</td>
                                </tr>
                                <tr style="background:rgba(217,119,6,0.06);font-weight:700;">
                                    <td colspan="2" style="padding:4px 6px;color:#d97706;">II. PASIVOS</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:4px 6px;">7.2 Cuentas por Pagar Proveedores:</td>
                                    <td class="text-right font-semibold" style="padding:4px 6px;">${App.formatCurrency(a1.cuentas_por_pagar || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:4px 6px;">7.3 Impuestos por Pagar (ITBIS/ISR):</td>
                                    <td class="text-right font-semibold" style="padding:4px 6px;">${App.formatCurrency(a1.impuestos_por_pagar || 0, 'DOP')}</td>
                                </tr>
                                <tr style="background:var(--bg-hover);font-weight:700;border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 6px;">Total Pasivos Corrientes:</td>
                                    <td class="text-right" style="padding:5px 6px;color:#d97706;">${App.formatCurrency(a1.total_pasivos || 0, 'DOP')}</td>
                                </tr>
                                <tr style="background:rgba(124,58,237,0.06);font-weight:700;">
                                    <td colspan="2" style="padding:4px 6px;color:#7c3aed;">III. PATRIMONIO NETO</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:4px 6px;">10.1 Capital Suscrito y Pagado:</td>
                                    <td class="text-right font-semibold" style="padding:4px 6px;">${App.formatCurrency(a1.capital_social || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:4px 6px;">10.2 Reserva Legal (5%):</td>
                                    <td class="text-right font-semibold" style="padding:4px 6px;">${App.formatCurrency(a1.reserva_legal || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:4px 6px;">10.5 Beneficio del Ejercicio:</td>
                                    <td class="text-right font-semibold" style="padding:4px 6px;">${App.formatCurrency(a1.beneficio_ejercicio || 0, 'DOP')}</td>
                                </tr>
                                <tr style="background:var(--bg-hover);font-weight:700;border-top:1px solid var(--color-border);">
                                    <td style="padding:6px 6px;">11. TOTAL PASIVOS Y PATRIMONIO:</td>
                                    <td class="text-right" style="padding:6px 6px;color:#059669;font-size:13px;">${App.formatCurrency(a1.total_pasivos_patrimonio || 0, 'DOP')}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Card 3: Anexo J Resumen 607 y 606 -->
                    <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                        <h4 style="font-size:13px;font-weight:700;margin-bottom:12px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                            <span>Anexo J: Resumen Anual Comprobantes</span>
                            <span class="badge" style="background:var(--bg-hover);font-size:10px;">Ventas 607 & Compras 606</span>
                        </h4>
                        <table style="width:100%;font-size:12px;border-collapse:collapse;">
                            <tbody>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">Crédito Fiscal Emitidos (01 / 31):</td>
                                    <td class="text-right" style="padding:5px 0;font-family:'JetBrains Mono',monospace;"><strong>${(jV.counts && jV.counts['01_31']) || 0}</strong> doc(s)</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency((jV.amounts && jV.amounts['01_31']) || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">Consumo Final Emitidos (02 / 32):</td>
                                    <td class="text-right" style="padding:5px 0;font-family:'JetBrains Mono',monospace;"><strong>${(jV.counts && jV.counts['02_32']) || 0}</strong> doc(s)</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency((jV.amounts && jV.amounts['02_32']) || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">Notas Crédito Emitidas (04 / 34):</td>
                                    <td class="text-right" style="padding:5px 0;font-family:'JetBrains Mono',monospace;color:#dc2626;"><strong>${(jV.counts && jV.counts['04_34']) || 0}</strong> doc(s)</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;color:#dc2626;">-${App.formatCurrency((jV.amounts && jV.amounts['04_34']) || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">Compras con Crédito Fiscal (606):</td>
                                    <td class="text-right" style="padding:5px 0;font-family:'JetBrains Mono',monospace;"><strong>${(jG.counts && jG.counts['01_31']) || 0}</strong> doc(s)</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency((jG.amounts && jG.amounts['01_31']) || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:5px 0;">Compras a Proveedores Informales (11):</td>
                                    <td class="text-right" style="padding:5px 0;font-family:'JetBrains Mono',monospace;"><strong>${(jG.counts && jG.counts['11_41']) || 0}</strong> doc(s)</td>
                                    <td class="text-right font-semibold" style="padding:5px 0;">${App.formatCurrency((jG.amounts && jG.amounts['11_41']) || 0, 'DOP')}</td>
                                </tr>
                                <tr style="background:var(--bg-hover);font-weight:700;">
                                    <td style="padding:8px 0;" colspan="2">Total Ventas Netas Facturadas:</td>
                                    <td class="text-right" style="padding:8px 0;color:#7c3aed;">${App.formatCurrency(jV.total || 0, 'DOP')}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Card 4: Liquidación Impuesto a los Activos (1%) -->
                    <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                        <h4 style="font-size:13px;font-weight:700;margin-bottom:12px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                            <span>Hoja Activo: Impuesto Sobre los Activos</span>
                            <span class="badge" style="background:var(--bg-hover);font-size:10px;">1% Art. 401 Ley 11-92</span>
                        </h4>
                        <table style="width:100%;font-size:12px;border-collapse:collapse;">
                            <tbody>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Base Imponible de Activos (A-1):</td>
                                    <td class="text-right font-semibold" style="padding:6px 0;">${App.formatCurrency(act.total_activos || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Impuesto Liquidado 1% Activos:</td>
                                    <td class="text-right font-semibold" style="padding:6px 0;">${App.formatCurrency(act.impuesto_1pct || 0, 'DOP')}</td>
                                </tr>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:6px 0;">Impuesto Liquidado de ISR (Casilla 12):</td>
                                    <td class="text-right font-semibold" style="padding:6px 0;color:#7c3aed;">${App.formatCurrency(act.isr_liquidado || 0, 'DOP')}</td>
                                </tr>
                                <tr style="background:var(--bg-hover);font-weight:700;">
                                    <td style="padding:8px 0;">Impuesto Adicional a Pagar por Activos:</td>
                                    <td class="text-right font-bold" style="padding:8px 0;color:${(act.diferencia_pagar || 0) > 0 ? '#dc2626' : '#059669'};">
                                        ${App.formatCurrency(act.diferencia_pagar || 0, 'DOP')}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div style="margin-top:10px;font-size:11px;color:var(--color-text-muted);line-height:1.4;">
                            * El Impuesto sobre los Activos actúa como pago mínimo de ISR. Si el 27% de ISR supera el 1% de activos, no se genera diferencia adicional.
                        </div>
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
        if (this._currentTab === 'it1' || this._currentTab === 'ir2') {
            App.showToast(`La declaración ${this._currentTab.toUpperCase()} valida sus datos directamente en la planilla oficial de Excel con sus fórmulas matemáticas DGII.`, 'info');
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
        if (this._currentTab === 'ir2') {
            return this.exportIr2Excel();
        }
        if (this._currentTab === 'itc') {
            return this.exportItcExcel();
        }
        if (this._currentTab === 'dss') {
            return this.exportDssExcel();
        }
        if (this._currentTab === 'daf') {
            return this.exportDafExcel();
        }
        if (this._currentTab === 'rs1') {
            return this.exportRs1Excel();
        }
        if (this._currentTab === 'rs2') {
            return this.exportRs2Excel();
        }
        if (this._currentTab === 'rs3') {
            return this.exportRs3Excel();
        }
        if (this._currentTab === 'rs4') {
            return this.exportRs4Excel();
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
        if (this._currentTab === 'it1' || this._currentTab === 'ir2') {
            App.showToast(`La declaración ${this._currentTab.toUpperCase()} oficial se presenta en formato Excel (.xls) o directamente en la Oficina Virtual de la DGII.`, 'info');
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
    },

    async exportIr2Excel() {
        App.showToast('Generando Formulario Oficial IR-2 y Anexos en Excel DGII...', 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/ir2/export-excel?year=${this._year}`, {
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

            const rnc = (this._dataIr2 && this._dataIr2.tax_id)
                ? this._dataIr2.tax_id
                : (App.state.settings?.company_tax_id ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') : '131000000');

            a.download = `DGII_IR2_${rnc}_${this._year}.xls`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast('¡Formulario Oficial IR-2 (Excel DGII) descargado con éxito!', 'success');
        } catch (e) {
            console.error('IR-2 Excel Export error:', e);
            App.showToast('Error al generar el formulario oficial IR-2', 'error');
        }
    },

    renderItcDeclaration(headers, tbody, summary, refBox) {
        const d = this._dataItc;
        if (!d) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-24">No se pudo cargar la declaración ITC-01 para este período.</td></tr>`;
            return;
        }

        const itc = d.itc || {};

        headers.innerHTML = `
            <th style="width:130px;">Casilla Oficial</th>
            <th>Descripción / Concepto Tributario</th>
            <th class="text-right" style="width:180px;">Monto Declarado (DOP)</th>
            <th style="width:280px;">Fórmula DGII / Origen</th>
        `;

        const row = (casilla, desc, val, formula, isHeader = false, isBold = false, isHighlight = false) => `
            <tr style="${isHighlight ? 'background:rgba(2,132,199,0.08);' : ''}">
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--color-primary);">${casilla}</td>
                <td style="${isBold ? 'font-weight:700;' : ''}">${desc}</td>
                <td class="text-right ${isBold ? 'font-bold' : ''}" style="font-family:'JetBrains Mono',monospace;${isHighlight ? 'color:#0284c7;font-size:15px;' : ''}">${App.formatCurrency(val || 0, 'DOP')}</td>
                <td style="font-size:12px;color:var(--color-text-muted);">${formula}</td>
            </tr>
        `;

        tbody.innerHTML = `
            ${row('Casilla 1', 'Total de Operaciones del Período', itc.casilla_1_total_operaciones, 'Celda U24 (Total facturado neto)', false, true)}
            ${row('Casilla 2', 'Ingresos Gravados por Telecomunicaciones (Ley 253-12)', itc.casilla_2_ingresos_gravados, 'Celda U25 (Base Imponible ISC 10%)', false, true)}
            ${row('Casilla 3', 'Impuesto Determinado (Tasa 10%)', itc.casilla_3_impuesto_a_pagar, 'Fórmula Nativa DGII: =U25*0.1', false, true, true)}
            ${row('Casilla 4', 'Saldos Compensables Autorizados (Otros Impuestos)', itc.casilla_4_saldos_compensables, 'Celda U27', false, false)}
            ${row('Casilla 5', 'Saldo a Favor Anterior', itc.casilla_5_saldo_favor_anterior, 'Celda U28', false, false)}
            ${row('Casilla 6', 'Pagos Computables a Cuenta', itc.casilla_6_pagos_computables, 'Celda U29', false, false)}
            ${row('Casilla 7', 'Diferencia a Pagar', itc.casilla_7_diferencia_a_pagar, 'Fórmula Nativa DGII: =IF(U26-U27-U28-U29>0,...)', false, true)}
            ${row('Casilla 8', 'Nuevo Saldo a Favor', itc.casilla_8_nuevo_saldo_favor, 'Fórmula Nativa DGII: =IF(U26-U27-U28-U29<0,...)', false, false)}
            ${row('Casilla 12', 'TOTAL A PAGAR AL FISCO (DGII)', itc.casilla_12_total_a_pagar, 'Fórmula Nativa DGII: =U30+U33+U34+U35', false, true, true)}
        `;

        summary.innerHTML = `
            <div>
                <span>Período Fiscal: <strong>${d.period_formatted}</strong></span> &bull; 
                <span>Fecha Límite: <strong style="color:var(--color-danger-icon);">${d.deadline}</strong></span> &bull; 
                <span>Contribuyente: <strong>${d.company_name}</strong> (RNC: ${d.tax_id})</span>
            </div>
            <div>
                Total Impuesto a Pagar (ISC 10%): <strong style="color:#0284c7;font-size:18px;margin-left:8px;">${App.formatCurrency(itc.casilla_12_total_a_pagar || 0, 'DOP')}</strong>
            </div>
        `;

        if (refBox) {
            refBox.innerHTML = `
                <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                    <h4 style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                        <span>Marco Legal: Impuesto Selectivo a las Telecomunicaciones (Ley 253-12)</span>
                        <span class="badge" style="background:#0284c7;color:#fff;font-size:10px;">Formulario Oficial ITC-01</span>
                    </h4>
                    <p style="font-size:12px;color:var(--color-text-muted);margin:0;line-height:1.6;">
                        Este formulario oficial aplica a prestadores de servicios de telecomunicaciones, transmisión de voz, datos e internet conforme al Art. 21 de la Ley 253-12. La plantilla oficial de Excel prellenada por Bills preserva el 100% de las fórmulas nativas de la DGII.
                    </p>
                </div>
            `;
        }
    },

    renderDssDeclaration(headers, tbody, summary, refBox) {
        const d = this._dataDss;
        if (!d) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-24">No se pudo cargar la declaración DSS-07 para este período.</td></tr>`;
            return;
        }

        const dss = d.dss || {};
        const categories = d.categories || {};

        headers.innerHTML = `
            <th style="width:120px;">Casilla Oficial</th>
            <th>Ramo / Concepto de Seguro</th>
            <th class="text-right" style="width:120px;">Pólizas / Cant.</th>
            <th class="text-right" style="width:180px;">Valor Total (DOP)</th>
            <th style="width:260px;">Fórmula DGII / Origen</th>
        `;

        let rowsHtml = '';
        for (let i = 1; i <= 11; i++) {
            const cat = categories[i] || { label: `Ramo ${i}`, count: 0, amount: 0 };
            rowsHtml += `
                <tr>
                    <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--color-primary);">Casilla ${i}</td>
                    <td>${cat.label}</td>
                    <td class="text-right" style="font-family:'JetBrains Mono',monospace;">${cat.count > 0 ? cat.count : '—'}</td>
                    <td class="text-right font-semibold" style="font-family:'JetBrains Mono',monospace;">${App.formatCurrency(cat.amount || 0, 'DOP')}</td>
                    <td style="font-size:12px;color:var(--color-text-muted);">Celdas Y${20+i} / AB${20+i}</td>
                </tr>
            `;
        }

        const totalRow = (casilla, desc, count, val, formula, isHighlight = false) => `
            <tr style="${isHighlight ? 'background:rgba(234,88,12,0.08);' : 'background:var(--bg-hover);'}">
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--color-primary);">${casilla}</td>
                <td style="font-weight:700;">${desc}</td>
                <td class="text-right font-bold" style="font-family:'JetBrains Mono',monospace;">${count !== null ? count : '—'}</td>
                <td class="text-right font-bold" style="font-family:'JetBrains Mono',monospace;${isHighlight ? 'color:#ea580c;font-size:15px;' : ''}">${App.formatCurrency(val || 0, 'DOP')}</td>
                <td style="font-size:12px;color:var(--color-text-muted);">${formula}</td>
            </tr>
        `;

        tbody.innerHTML = `
            ${rowsHtml}
            ${totalRow('Casilla 12', 'TOTAL OPERACIONES DEL PERÍODO', null, dss.casilla_12_total_operaciones, 'Fórmula Nativa DGII: =IF(SUM(AB21:AE31)>0,...)')}
            ${totalRow('Casilla 13', 'Operaciones Exentas', null, dss.casilla_13_operaciones_exentas, 'Celda AB35')}
            ${totalRow('Casilla 14', 'Operaciones Gravadas', null, dss.casilla_14_operaciones_gravadas, 'Fórmula Nativa DGII: =IF(AB35>0,(AB32-AB35),(AB32))')}
            ${totalRow('Casilla 15', 'Impuesto a Pagar (Tasa 16%)', null, dss.casilla_15_impuesto_a_pagar, 'Fórmula Nativa DGII: =AB36*0.16', true)}
            ${totalRow('Casilla 19', 'Diferencia a Pagar', null, dss.casilla_19_diferencia_a_pagar, 'Fórmula Nativa DGII: =IF((AB37-AB38-AB39-AB40)>0,...)')}
            ${totalRow('Casilla 24', 'TOTAL A PAGAR AL FISCO (DGII)', null, dss.casilla_24_total_a_pagar, 'Fórmula Nativa DGII: =+AB41+AB45+AB46+AB47', true)}
        `;

        summary.innerHTML = `
            <div>
                <span>Período Fiscal: <strong>${d.period_formatted}</strong></span> &bull; 
                <span>Fecha Límite: <strong style="color:var(--color-danger-icon);">${d.deadline}</strong></span> &bull; 
                <span>Contribuyente: <strong>${d.company_name}</strong> (RNC: ${d.tax_id})</span>
            </div>
            <div>
                Total Impuesto a Pagar (Seguros 16%): <strong style="color:#ea580c;font-size:18px;margin-left:8px;">${App.formatCurrency(dss.casilla_24_total_a_pagar || 0, 'DOP')}</strong>
            </div>
        `;

        if (refBox) {
            refBox.innerHTML = `
                <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                    <h4 style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                        <span>Marco Legal: Impuesto Sobre Seguros en General (Ley 146-02)</span>
                        <span class="badge" style="background:#ea580c;color:#fff;font-size:10px;">Formulario Oficial DSS-07</span>
                    </h4>
                    <p style="font-size:12px;color:var(--color-text-muted);margin:0;line-height:1.6;">
                        Este formulario oficial aplica a aseguradoras y corredores de pólizas gravadas con el 16% sobre primas suscritas conforme a la Ley 146-02. La plantilla oficial de Excel prellenada por Bills preserva el 100% de las fórmulas nativas de la DGII.
                    </p>
                </div>
            `;
        }
    },

    async exportItcExcel() {
        const periodStr = `${this._year}${String(this._month).padStart(2, '0')}`;
        App.showToast('Generando Formulario Oficial ITC-01 en Excel DGII...', 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/itc/export-excel?year=${this._year}&month=${this._month}`, {
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

            const cd = response.headers.get('content-disposition');
            let filename = null;
            if (cd && cd.includes('filename=')) {
                const match = cd.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                if (match && match[1]) filename = match[1].replace(/['"]/g, '');
            }

            const rnc = (this._dataItc && this._dataItc.tax_id)
                ? this._dataItc.tax_id
                : (App.state.settings?.company_tax_id ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') : '131000000');

            a.download = filename || `DGII_ITC01_${rnc}_${periodStr}.xls`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast('¡Formulario Oficial ITC-01 (Excel DGII) descargado con éxito!', 'success');
        } catch (e) {
            console.error('ITC-01 Excel Export error:', e);
            App.showToast('Error al generar el formulario oficial ITC-01', 'error');
        }
    },

    async exportDssExcel() {
        const periodStr = `${this._year}${String(this._month).padStart(2, '0')}`;
        App.showToast('Generando Formulario Oficial DSS-07 en Excel DGII...', 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/dss/export-excel?year=${this._year}&month=${this._month}`, {
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

            const rnc = (this._dataDss && this._dataDss.tax_id)
                ? this._dataDss.tax_id
                : (App.state.settings?.company_tax_id ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') : '131000000');

            a.download = `DGII_DSS07_${rnc}_${periodStr}.xls`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast('¡Formulario Oficial DSS-07 (Excel DGII) descargado con éxito!', 'success');
        } catch (e) {
            console.error('DSS-07 Excel Export error:', e);
            App.showToast('Error al generar el formulario oficial DSS-07', 'error');
        }
    },

    renderDafDeclaration(headers, tbody, summary, refBox) {
        const d = this._dataDaf;
        if (!d) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-24">No se pudo cargar la declaración DAF para este ejercicio fiscal.</td></tr>`;
            return;
        }

        const daf = d.daf || {};

        headers.innerHTML = `
            <th style="width:130px;">Casilla Oficial</th>
            <th>Descripción / Concepto Legal (Ley 139-2011)</th>
            <th class="text-right" style="width:200px;">Monto Declarado (DOP)</th>
            <th style="width:280px;">Fórmula DGII / Origen</th>
        `;

        const row = (casilla, desc, val, formula, isHeader = false, isBold = false, isHighlight = false) => `
            <tr style="${isHighlight ? 'background:rgba(13,148,136,0.08);' : ''}">
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--color-primary);">${casilla}</td>
                <td style="${isBold ? 'font-weight:700;' : ''}">${desc}</td>
                <td class="text-right ${isBold ? 'font-bold' : ''}" style="font-family:'JetBrains Mono',monospace;${isHighlight ? 'color:#0d9488;font-size:15px;' : ''}">${App.formatCurrency(val || 0, 'DOP')}</td>
                <td style="font-size:12px;color:var(--color-text-muted);">${formula}</td>
            </tr>
        `;

        tbody.innerHTML = `
            ${row('Casilla 1', 'Total Activos Financieros Productivos Netos (Norma 09-2011)', daf.casilla_1_activos_financieros, 'Celda AC13 (Caja/Bancos y Cuentas por Cobrar)', false, true)}
            ${row('Casilla 2', 'Exención Legal (Párrafo 2 Art. 12 Ley 139-2011)', daf.casilla_2_exencion, 'Fórmula Nativa DGII: =ABS(IF((AC13+AI31)>=0,700000000,0))', false, false)}
            ${row('Casilla 3', 'Total Activos Financieros después de la Exención', daf.casilla_3_activos_despues_exencion, 'Fórmula Nativa DGII: =IF(AC13>700000000,(AC13-AC14),0)', false, true)}
            ${row('Casilla 4', 'Impuesto Liquidado sobre Activos (Tasa 0.48%)', daf.casilla_4_impuesto_liquidado, 'Fórmula Nativa DGII: =+IF(AC15>0,AC15*0.48%,0)', false, true, true)}
            ${row('Casilla 5', 'Renta Neta Imponible antes de Pérdida', daf.casilla_5_renta_neta_imponible, 'Celda AC17 (Vinculado a Casilla 7 de IR-2)', false, true)}
            ${row('Casilla 6', 'Gastos Deducibles según Ley 139-2011', daf.casilla_6_gastos_deducibles, 'Celda AC18', false, false)}
            ${row('Casilla 7', 'Renta Neta Imponible después de Gasto Deducible', daf.casilla_7_renta_despues_gasto, 'Fórmula Nativa DGII: =IF((AC17-AC18)>0,AC17-AC18,0)', false, true)}
            ${row('Casilla 8', 'Impuesto Determinado a Pagar (Menor entre Casilla 4 y 7)', daf.casilla_8_impuesto_a_pagar, 'Fórmula Nativa DGII: =IF(AC16<AC19,AC16,...)', false, true, true)}
            ${row('Casilla 13', 'Diferencia a Pagar', daf.casilla_13_diferencia_a_pagar, 'Fórmula Nativa DGII: =ABS(IF(AC20-U21-...))', false, true)}
            ${row('Casilla 17', 'TOTAL A PAGAR AL FISCO (DGII)', daf.casilla_17_total_a_pagar, 'Fórmula Nativa DGII: =IF((AC25+U27+U28)>0,...)', false, true, true)}
        `;

        summary.innerHTML = `
            <div>
                <span>Ejercicio Fiscal: <strong>${d.year}</strong> (${d.period_formatted})</span> &bull; 
                <span>Fecha Límite: <strong style="color:var(--color-danger-icon);">${d.deadline}</strong></span> &bull; 
                <span>Contribuyente: <strong>${d.company_name}</strong> (RNC: ${d.tax_id})</span>
            </div>
            <div>
                Total Impuesto a Pagar (DAF): <strong style="color:#0d9488;font-size:18px;margin-left:8px;">${App.formatCurrency(daf.casilla_17_total_a_pagar || 0, 'DOP')}</strong>
            </div>
        `;

        if (refBox) {
            refBox.innerHTML = `
                <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                    <h4 style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                        <span>Marco Legal: Impuesto a los Activos Financieros Productivos Netos (Ley 139-2011)</span>
                        <span class="badge" style="background:#0d9488;color:#fff;font-size:10px;">Formulario Oficial DAF</span>
                    </h4>
                    <p style="font-size:12px;color:var(--color-text-muted);margin:0;line-height:1.6;">
                        El Formulario DAF liquida el impuesto anual sobre activos financieros netos de entidades financieras y comerciales con inversiones productivas, contando con una exención legal de RD$ 700,000,000.00 y vinculación directa con la Renta Neta Imponible declarada en el Formulario IR-2. La plantilla oficial de Excel prellenada por Bills preserva el 100% de las fórmulas nativas de la DGII.
                    </p>
                </div>
            `;
        }
    },

    async exportDafExcel() {
        App.showToast('Generando Formulario Oficial DAF en Excel DGII...', 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/daf/export-excel?year=${this._year}`, {
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

            const rnc = (this._dataDaf && this._dataDaf.tax_id)
                ? this._dataDaf.tax_id
                : (App.state.settings?.company_tax_id ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') : '131000000');

            a.download = `DGII_DAF_${rnc}_${this._year}.xls`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast('¡Formulario Oficial DAF (Excel DGII) descargado con éxito!', 'success');
        } catch (e) {
            console.error('DAF Excel Export error:', e);
            App.showToast('Error al generar el formulario oficial DAF', 'error');
        }
    },

    renderRs1Declaration(headers, tbody, summary, refBox) {
        const d = this._dataRs1;
        if (!d) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-24">No se pudo cargar la declaración RS1 para este año.</td></tr>`;
            return;
        }

        const rs1 = d.rs1 || {};

        headers.innerHTML = `
            <th style="width:130px;">Casilla Oficial</th>
            <th>Descripción / Concepto (Decreto 265-19)</th>
            <th class="text-right" style="width:200px;">Monto Declarado (DOP)</th>
            <th style="width:280px;">Fórmula DGII / Origen</th>
        `;

        const row = (casilla, desc, val, formula, isHeader = false, isBold = false, isHighlight = false) => `
            <tr style="${isHighlight ? 'background:rgba(79,70,229,0.08);' : ''}">
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--color-primary);">${casilla}</td>
                <td style="${isBold ? 'font-weight:700;' : ''}">${desc}</td>
                <td class="text-right ${isBold ? 'font-bold' : ''}" style="font-family:'JetBrains Mono',monospace;${isHighlight ? 'color:#4f46e5;font-size:15px;' : ''}">${App.formatCurrency(val || 0, 'DOP')}</td>
                <td style="font-size:12px;color:var(--color-text-muted);">${formula}</td>
            </tr>
        `;

        tbody.innerHTML = `
            ${row('Casilla 1', 'Ingresos por Ventas de Bienes', rs1.casilla_1_ventas, 'Celda T19 (Facturación 607 Ventas)', false, false)}
            ${row('Casilla 2', 'Ingresos por Prestación de Servicios', rs1.casilla_2_servicios, 'Celda T20 (Facturación 607 Servicios)', false, false)}
            ${row('Casilla 3', 'Ingresos por Alquileres de Inmuebles', rs1.casilla_3_alquileres, 'Celda T21 (Tipo de Ingreso 04)', false, false)}
            ${row('Casilla 4', 'Honorarios Profesionales', rs1.casilla_4_honorarios, 'Celda T22', false, false)}
            ${row('Casilla 5', 'TOTAL INGRESOS BRUTOS ANUALES', rs1.casilla_5_total_ingresos, 'Fórmula Nativa DGII: =SUM(T19:T25)', false, true)}
            ${row('Casilla 8', 'Renta Neta Estimada (Base Gravable 60%)', rs1.casilla_8_renta_estimada, 'Fórmula Nativa DGII: =T26*0.60 (Exención 40% de Gastos)', false, true)}
            ${row('Casilla 11', 'Impuesto Sobre la Renta Liquidado', rs1.casilla_11_impuesto_liquidado, 'Fórmula Nativa DGII: Escala Progresiva Personas Físicas', false, true, true)}
            ${row('Casilla 16', 'TOTAL A PAGAR AL FISCO (DGII)', rs1.casilla_16_total_a_pagar, 'Fórmula Nativa DGII: Celda T57', false, true, true)}
        `;

        summary.innerHTML = `
            <div>
                <span>Ejercicio Fiscal: <strong>${d.year}</strong></span> &bull; 
                <span>Fecha Límite: <strong style="color:var(--color-danger-icon);">${d.deadline}</strong></span> &bull; 
                <span>Contribuyente: <strong>${d.company_name}</strong> (RNC/Cédula: ${d.tax_id})</span>
            </div>
            <div>
                Total Impuesto a Pagar (RS1): <strong style="color:#4f46e5;font-size:18px;margin-left:8px;">${App.formatCurrency(rs1.casilla_16_total_a_pagar || 0, 'DOP')}</strong>
            </div>
        `;

        if (refBox) {
            refBox.innerHTML = `
                <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                    <h4 style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                        <span>Marco Legal: Régimen Simplificado de Tributación (RS1 - Personas Físicas Ingresos)</span>
                        <span class="badge" style="background:#4f46e5;color:#fff;font-size:10px;">Formulario Oficial RS1 (.xlsx)</span>
                    </h4>
                    <p style="font-size:12px;color:var(--color-text-muted);margin:0;line-height:1.6;">
                        El Formulario RS1 (Decreto 265-19) calcula el impuesto para personas físicas con actividades comerciales y de servicios. Determina automáticamente la renta neta imponible deduciendo el 40% de gastos presuntos exentos y aplicando la escala progresiva del ISR. La plantilla oficial .xlsx mantiene intactas todas las fórmulas nativas de la DGII.
                    </p>
                </div>
            `;
        }
    },

    async exportRs1Excel() {
        App.showToast('Generando Formulario Oficial RS1 en Excel DGII (.xlsx)...', 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/rs1/export-excel?year=${this._year}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/octet-stream',
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

            const rnc = (this._dataRs1 && this._dataRs1.tax_id)
                ? this._dataRs1.tax_id
                : (App.state.settings?.company_tax_id ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') : '131000000');

            a.download = `DGII_RS1_${rnc}_${this._year}.xlsx`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast('¡Formulario Oficial RS1 (Excel DGII) descargado con éxito!', 'success');
        } catch (e) {
            console.error('RS1 Excel Export error:', e);
            App.showToast('Error al generar el formulario oficial RS1', 'error');
        }
    },

    renderRs2Declaration(headers, tbody, summary, refBox) {
        const d = this._dataRs2;
        if (!d) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-24">No se pudo cargar la declaración RS2 para este año.</td></tr>`;
            return;
        }

        const rs2 = d.rs2 || {};

        headers.innerHTML = `
            <th style="width:130px;">Casilla Oficial</th>
            <th>Descripción / Concepto (Decreto 265-19)</th>
            <th class="text-right" style="width:200px;">Monto Declarado (DOP)</th>
            <th style="width:280px;">Fórmula DGII / Origen</th>
        `;

        const row = (casilla, desc, val, formula, isHeader = false, isBold = false, isHighlight = false) => `
            <tr style="${isHighlight ? 'background:rgba(124,58,237,0.08);' : ''}">
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--color-primary);">${casilla}</td>
                <td style="${isBold ? 'font-weight:700;' : ''}">${desc}</td>
                <td class="text-right ${isBold ? 'font-bold' : ''}" style="font-family:'JetBrains Mono',monospace;${isHighlight ? 'color:#7c3aed;font-size:15px;' : ''}">${App.formatCurrency(val || 0, 'DOP')}</td>
                <td style="font-size:12px;color:var(--color-text-muted);">${formula}</td>
            </tr>
        `;

        tbody.innerHTML = `
            ${row('Casilla 1', 'Ingresos por Ventas de Bienes', rs2.casilla_1_ventas, 'Celda T19 (Facturación 607 Ventas)', false, false)}
            ${row('Casilla 2', 'Ingresos por Prestación de Servicios', rs2.casilla_2_servicios, 'Celda T20 (Facturación 607 Servicios)', false, false)}
            ${row('Casilla 3', 'Ingresos por Alquileres', rs2.casilla_3_alquileres, 'Celda T21', false, false)}
            ${row('Casilla 5', 'TOTAL INGRESOS BRUTOS DEL EJERCICIO', rs2.casilla_5_total_ingresos, 'Fórmula Nativa DGII: =SUM(T19:T24)', false, true)}
            ${row('Casilla 8', 'Impuesto Liquidado (Tasa Efectiva TET 7%)', rs2.casilla_8_impuesto_liquidado, 'Fórmula Nativa DGII: =L39*T25 (Celda L39 = 7.00%)', false, true, true)}
            ${row('Casilla 14', 'TOTAL A PAGAR AL FISCO (DGII)', rs2.casilla_14_total_a_pagar, 'Fórmula Nativa DGII: Celda T50', false, true, true)}
        `;

        summary.innerHTML = `
            <div>
                <span>Ejercicio Fiscal: <strong>${d.year}</strong></span> &bull; 
                <span>Fecha Límite: <strong style="color:var(--color-danger-icon);">${d.deadline}</strong></span> &bull; 
                <span>Contribuyente: <strong>${d.company_name}</strong> (RNC: ${d.tax_id})</span>
            </div>
            <div>
                Total Impuesto a Pagar (RS2): <strong style="color:#7c3aed;font-size:18px;margin-left:8px;">${App.formatCurrency(rs2.casilla_14_total_a_pagar || 0, 'DOP')}</strong>
            </div>
        `;

        if (refBox) {
            refBox.innerHTML = `
                <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                    <h4 style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                        <span>Marco Legal: Régimen Simplificado de Tributación (RS2 - Personas Jurídicas Ingresos)</span>
                        <span class="badge" style="background:#7c3aed;color:#fff;font-size:10px;">Formulario Oficial RS2 (.xlsx)</span>
                    </h4>
                    <p style="font-size:12px;color:var(--color-text-muted);margin:0;line-height:1.6;">
                        El Formulario RS2 (Decreto 265-19) aplica a personas jurídicas de servicios y comercio elegibles. Aplica directamente una Tasa Efectiva de Tributación (TET) establecida por la DGII sobre el total de ingresos brutos, prescindiendo del formato de balance general y estado de resultados ordinario de IR-2.
                    </p>
                </div>
            `;
        }
    },

    async exportRs2Excel() {
        App.showToast('Generando Formulario Oficial RS2 en Excel DGII (.xlsx)...', 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/rs2/export-excel?year=${this._year}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/octet-stream',
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

            const rnc = (this._dataRs2 && this._dataRs2.tax_id)
                ? this._dataRs2.tax_id
                : (App.state.settings?.company_tax_id ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') : '131000000');

            a.download = `DGII_RS2_${rnc}_${this._year}.xlsx`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast('¡Formulario Oficial RS2 (Excel DGII) descargado con éxito!', 'success');
        } catch (e) {
            console.error('RS2 Excel Export error:', e);
            App.showToast('Error al generar el formulario oficial RS2', 'error');
        }
    },

    renderRs3Declaration(headers, tbody, summary, refBox) {
        const d = this._dataRs3;
        if (!d) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-24">No se pudo cargar la declaración RS3 para este año.</td></tr>`;
            return;
        }

        const rs3 = d.rs3 || {};

        headers.innerHTML = `
            <th style="width:130px;">Casilla Oficial</th>
            <th>Descripción / Concepto (Decreto 265-19)</th>
            <th class="text-right" style="width:200px;">Monto Declarado (DOP)</th>
            <th style="width:280px;">Fórmula DGII / Origen</th>
        `;

        const row = (casilla, desc, val, formula, isHeader = false, isBold = false, isHighlight = false) => `
            <tr style="${isHighlight ? 'background:rgba(217,119,6,0.08);' : ''}">
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--color-primary);">${casilla}</td>
                <td style="${isBold ? 'font-weight:700;' : ''}">${desc}</td>
                <td class="text-right ${isBold ? 'font-bold' : ''}" style="font-family:'JetBrains Mono',monospace;${isHighlight ? 'color:#d97706;font-size:15px;' : ''}">${App.formatCurrency(val || 0, 'DOP')}</td>
                <td style="font-size:12px;color:var(--color-text-muted);">${formula}</td>
            </tr>
        `;

        tbody.innerHTML = `
            ${row('Casilla 1', 'Compras Locales e Importadas Registradas', rs3.casilla_1_compras, 'Celda T21 (Compras Formato 606 + Gastos)', false, false)}
            ${row('Casilla 3', 'TOTAL COMPRAS DEL EJERCICIO', rs3.casilla_3_total_compras, 'Fórmula Nativa DGII: =SUM(T21:T26)', false, true)}
            ${row('Casilla 5', 'Ventas Estimadas según Margen Comercial', rs3.casilla_5_ventas_estimadas, 'Fórmula Nativa DGII: =T27*(1+T30) (Margen colmados/comercio)', false, true)}
            ${row('Casilla 7', 'Margen Bruto de Comercialización', rs3.casilla_7_margen_bruto, 'Fórmula Nativa DGII: =T33-T27', false, true)}
            ${row('Casilla 10', 'Impuesto Sobre la Renta (ISR) Liquidado', rs3.casilla_10_isr_liquidado, 'Fórmula Nativa DGII: Celda T48 (Tasa 27% sobre margen)', false, true)}
            ${row('Casilla 11', 'ITBIS Estimado a Liquidar', rs3.casilla_11_itbis_liquidado, 'Fórmula Nativa DGII: Celda T49 (Margen x Coeficiente x 18%)', false, true)}
            ${row('Casilla 16', 'TOTAL A PAGAR AL FISCO (ISR + ITBIS)', rs3.casilla_16_total_a_pagar, 'Fórmula Nativa DGII: Celda T58', false, true, true)}
        `;

        summary.innerHTML = `
            <div>
                <span>Ejercicio Fiscal: <strong>${d.year}</strong></span> &bull; 
                <span>Fecha Límite: <strong style="color:var(--color-danger-icon);">${d.deadline}</strong></span> &bull; 
                <span>Contribuyente: <strong>${d.company_name}</strong> (RNC: ${d.tax_id})</span>
            </div>
            <div>
                Total Impuesto a Pagar (RS3): <strong style="color:#d97706;font-size:18px;margin-left:8px;">${App.formatCurrency(rs3.casilla_16_total_a_pagar || 0, 'DOP')}</strong>
            </div>
        `;

        if (refBox) {
            refBox.innerHTML = `
                <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                    <h4 style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                        <span>Marco Legal: Régimen Simplificado de Tributación (RS3 - Basado en Compras)</span>
                        <span class="badge" style="background:#d97706;color:#fff;font-size:10px;">Formulario Oficial RS3 (.xlsx)</span>
                    </h4>
                    <p style="font-size:12px;color:var(--color-text-muted);margin:0;line-height:1.6;">
                        El Formulario RS3 (Decreto 265-19) está diseñado para pequeños comerciantes y colmados. Calcula automáticamente los ingresos presuntos a partir del total de compras anuales registradas en el 606 y liquida en una única declaración simplificada el ISR anual y el ITBIS anual estimado sin necesidad de contabilidad organizada compleja.
                    </p>
                </div>
            `;
        }
    },

    async exportRs3Excel() {
        App.showToast('Generando Formulario Oficial RS3 en Excel DGII (.xlsx)...', 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/rs3/export-excel?year=${this._year}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/octet-stream',
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

            const rnc = (this._dataRs3 && this._dataRs3.tax_id)
                ? this._dataRs3.tax_id
                : (App.state.settings?.company_tax_id ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') : '131000000');

            a.download = `DGII_RS3_${rnc}_${this._year}.xlsx`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast('¡Formulario Oficial RS3 (Excel DGII) descargado con éxito!', 'success');
        } catch (e) {
            console.error('RS3 Excel Export error:', e);
            App.showToast('Error al generar el formulario oficial RS3', 'error');
        }
    },

    renderRs4Declaration(headers, tbody, summary, refBox) {
        const d = this._dataRs4;
        if (!d) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-24">No se pudo cargar la declaración RS4 para este año.</td></tr>`;
            return;
        }

        const rs4 = d.rs4 || {};

        headers.innerHTML = `
            <th style="width:130px;">Casilla Oficial</th>
            <th>Descripción / Concepto (Decreto 265-19)</th>
            <th class="text-right" style="width:200px;">Monto Declarado (DOP)</th>
            <th style="width:280px;">Fórmula DGII / Origen</th>
        `;

        const row = (casilla, desc, val, formula, isHeader = false, isBold = false, isHighlight = false) => `
            <tr style="${isHighlight ? 'background:rgba(101,163,13,0.08);' : ''}">
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--color-primary);">${casilla}</td>
                <td style="${isBold ? 'font-weight:700;' : ''}">${desc}</td>
                <td class="text-right ${isBold ? 'font-bold' : ''}" style="font-family:'JetBrains Mono',monospace;${isHighlight ? 'color:#65a30d;font-size:15px;' : ''}">${App.formatCurrency(val || 0, 'DOP')}</td>
                <td style="font-size:12px;color:var(--color-text-muted);">${formula}</td>
            </tr>
        `;

        tbody.innerHTML = `
            ${row('Casilla 1', 'Ingresos Agropecuarios del Ejercicio', rs4.casilla_1_ingresos_agropecuarios, 'Celda T21 (Facturación 607 Agropecuaria)', false, false)}
            ${row('Casilla 6', 'TOTAL INGRESOS BRUTOS AGROPECUARIOS', rs4.casilla_6_total_ingresos, 'Fórmula Nativa DGII: =SUM(T21:T25)', false, true)}
            ${row('Casilla 7', 'Impuesto Liquidado (Tasa Efectiva TET ~6.1%)', rs4.casilla_7_impuesto_liquidado, 'Fórmula Nativa DGII: =T26*L44/100 (Celda L44)', false, true, true)}
            ${row('Casilla 15', 'TOTAL A PAGAR AL FISCO (DGII)', rs4.casilla_15_total_a_pagar, 'Fórmula Nativa DGII: Celda T58', false, true, true)}
        `;

        summary.innerHTML = `
            <div>
                <span>Ejercicio Fiscal: <strong>${d.year}</strong></span> &bull; 
                <span>Fecha Límite: <strong style="color:var(--color-danger-icon);">${d.deadline}</strong></span> &bull; 
                <span>Contribuyente: <strong>${d.company_name}</strong> (RNC: ${d.tax_id})</span>
            </div>
            <div>
                Total Impuesto a Pagar (RS4): <strong style="color:#65a30d;font-size:18px;margin-left:8px;">${App.formatCurrency(rs4.casilla_15_total_a_pagar || 0, 'DOP')}</strong>
            </div>
        `;

        if (refBox) {
            refBox.innerHTML = `
                <div class="table-outer" style="padding:18px;background:var(--bg-card);border:1px solid var(--color-border);border-radius:8px;">
                    <h4 style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--color-text-primary);display:flex;align-items:center;justify-content:space-between;">
                        <span>Marco Legal: Régimen Simplificado de Tributación (RS4 - Sector Agropecuario)</span>
                        <span class="badge" style="background:#65a30d;color:#fff;font-size:10px;">Formulario Oficial RS4 (.xlsx)</span>
                    </h4>
                    <p style="font-size:12px;color:var(--color-text-muted);margin:0;line-height:1.6;">
                        El Formulario RS4 (Decreto 265-19) aplica a productores del sector agropecuario dominicano. Determina el impuesto anual a pagar liquidando la Tasa Efectiva de Tributación del sector directamente sobre los ingresos brutos anuales, simplificando radicalmente las obligaciones fiscales para el campo dominicano.
                    </p>
                </div>
            `;
        }
    },

    async exportRs4Excel() {
        App.showToast('Generando Formulario Oficial RS4 en Excel DGII (.xlsx)...', 'info');

        try {
            const token = App.state.token || localStorage.getItem('token');
            const response = await fetch(`/api/dgii/reports/rs4/export-excel?year=${this._year}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/octet-stream',
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

            const rnc = (this._dataRs4 && this._dataRs4.tax_id)
                ? this._dataRs4.tax_id
                : (App.state.settings?.company_tax_id ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') : '131000000');

            a.download = `DGII_RS4_${rnc}_${this._year}.xlsx`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast('¡Formulario Oficial RS4 (Excel DGII) descargado con éxito!', 'success');
        } catch (e) {
            console.error('RS4 Excel Export error:', e);
            App.showToast('Error al generar el formulario oficial RS4', 'error');
        }
    }
};

window.ReportsModule = ReportsModule;
export default ReportsModule;
