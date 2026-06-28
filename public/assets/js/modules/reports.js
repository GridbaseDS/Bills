const ReportsModule = {
    _currentTab: '606',
    _year: new Date().getFullYear(),
    _month: new Date().getMonth() + 1,
    _records606: [],
    _records607: [],

    async render(container) {
        container.innerHTML = `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Reportes Fiscales DGII</h1>
                    <p class="page-subtitle">Genera y exporta los formatos oficiales 606 y 607 para la declaración jurada</p>
                </div>
            </div>

            <!-- Period Selector Card -->
            <div class="table-outer mb-24" style="padding:20px;background:var(--bg-hover);">
                <div style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
                    <div class="form-group" style="margin:0;min-width:140px;">
                        <label class="form-label" style="margin-bottom:6px;font-size:12px;">Año</label>
                        <select id="report-year" class="form-control">
                            <option value="2026" ${this._year == 2026 ? 'selected' : ''}>2026</option>
                            <option value="2025" ${this._year == 2025 ? 'selected' : ''}>2025</option>
                            <option value="2024" ${this._year == 2024 ? 'selected' : ''}>2024</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;min-width:180px;">
                        <label class="form-label" style="margin-bottom:6px;font-size:12px;">Mes</label>
                        <select id="report-month" class="form-control">
                            <option value="1" ${this._month == 1 ? 'selected' : ''}>Enero</option>
                            <option value="2" ${this._month == 2 ? 'selected' : ''}>Febrero</option>
                            <option value="3" ${this._month == 3 ? 'selected' : ''}>Marzo</option>
                            <option value="4" ${this._month == 4 ? 'selected' : ''}>Abril</option>
                            <option value="5" ${this._month == 5 ? 'selected' : ''}>Mayo</option>
                            <option value="6" ${this._month == 6 ? 'selected' : ''}>Junio</option>
                            <option value="7" ${this._month == 7 ? 'selected' : ''}>Julio</option>
                            <option value="8" ${this._month == 8 ? 'selected' : ''}>Agosto</option>
                            <option value="9" ${this._month == 9 ? 'selected' : ''}>Septiembre</option>
                            <option value="10" ${this._month == 10 ? 'selected' : ''}>Octubre</option>
                            <option value="11" ${this._month == 11 ? 'selected' : ''}>Noviembre</option>
                            <option value="12" ${this._month == 12 ? 'selected' : ''}>Diciembre</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" id="btn-fetch-reports" style="height:38px;display:flex;align-items:center;gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        Cargar Datos
                    </button>
                </div>
            </div>

            <!-- Tabs and Action Area -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-lg);flex-wrap:wrap;gap:12px;">
                <div class="segmented-control" id="report-type-tabs">
                    <button class="segment-item ${this._currentTab === '607' ? 'active' : ''}" data-tab="607">Ventas (607)</button>
                    <button class="segment-item ${this._currentTab === '606' ? 'active' : ''}" data-tab="606">Compras/Gastos (606)</button>
                </div>
                <button class="btn btn-primary" id="btn-export-txt" style="display:flex;align-items:center;gap:8px;background:#059669;border-color:#059669;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Descargar Formato TXT
                </button>
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

        const btnExport = document.getElementById('btn-export-txt');
        if (btnExport) {
            btnExport.addEventListener('click', () => this.exportTxt());
        }
    },

    async loadData() {
        const tbody = document.getElementById('report-tbody');
        if (tbody) tbody.innerHTML = `<tr><td colspan="100" class="text-center py-24"><span class="spinner mx-auto"></span><br><small style="color:var(--color-text-muted)">Cargando registros del período...</small></td></tr>`;

        try {
            const res607 = await App.api(`dgii/reports/607?year=${this._year}&month=${this._month}`);
            this._records607 = res607.data || [];

            const res606 = await App.api(`dgii/reports/606?year=${this._year}&month=${this._month}`);
            this._records606 = res606.data || [];

            this.renderGrid();
        } catch (e) {
            if (tbody) tbody.innerHTML = `<tr><td colspan="100" class="text-center text-red py-24">Error al conectar con el servidor</td></tr>`;
        }
    },

    renderGrid() {
        const headers = document.getElementById('report-headers');
        const tbody = document.getElementById('report-tbody');
        const summary = document.getElementById('report-summary-box');
        if (!headers || !tbody) return;

        if (this._currentTab === '607') {
            // e-CF informational banner
            const bannerHtml = `
                <tr><td colspan="10" style="padding:0;border:none;">
                    <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #93c5fd;border-radius:8px;padding:16px 20px;margin:12px 0;display:flex;align-items:flex-start;gap:12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <div style="font-size:13px;color:#1e40af;line-height:1.5;">
                            <strong>Emisor Electrónico (e-CF)</strong><br>
                            Como emisor de Comprobantes Fiscales Electrónicos, <strong>no estás obligado a presentar el formato 607</strong> ante la DGII. 
                            La información de tus ventas ya fue reportada automáticamente al emitir cada e-CF. 
                            <span style="color:#6b7280;">(Ref: Norma General DGII sobre Facturación Electrónica)</span>
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
                tbody.innerHTML = bannerHtml + `<tr><td colspan="10" class="text-center text-muted py-24">No hay facturas registradas en este período</td></tr>`;
                summary.innerHTML = `<span>Total Registros: 0</span><span>Suma Total: RD$ 0.00</span>`;
                return;
            }

            // Render 607 Rows
            let sumTotal = 0;
            tbody.innerHTML = bannerHtml + this._records607.map(r => {
                sumTotal += r.monto_facturado;
                return `
                    <tr>
                        <td><strong style="font-family:'JetBrains Mono',monospace;">${r.ncf}</strong></td>
                        <td>${r.cliente_nombre}</td>
                        <td style="font-family:'JetBrains Mono',monospace;">${r.rnc_cliente || '<span class="text-muted">—</span>'}</td>
                        <td><span class="badge badge-draft" title="Ingreso por operaciones">01</span></td>
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

        } else {
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
                sumTotal += r.monto_servicios;
                return `
                    <tr>
                        <td><strong style="font-family:'JetBrains Mono',monospace;">${r.ncf}</strong></td>
                        <td>${r.proveedor_nombre}</td>
                        <td style="font-family:'JetBrains Mono',monospace;">${r.rnc_proveedor}</td>
                        <td>${this.formatDgiiDate(r.fecha_comprobante)}</td>
                        <td class="text-right font-semibold">${App.formatCurrency(r.monto_servicios + r.itbis_facturado, 'DOP')}</td>
                        <td class="text-right">${App.formatCurrency(r.itbis_facturado, 'DOP')}</td>
                        <td>
                            <select class="form-control table-select" style="font-size:12px;padding:4px 8px;height:30px;" onchange="ReportsModule.update606Field(${idx}, 'tipo_bien_servicio', this.value)">
                                <option value="01" ${r.tipo_bien_servicio === '01' ? 'selected' : ''}>01 - Personal</option>
                                <option value="02" ${r.tipo_bien_servicio === '02' || !r.tipo_bien_servicio ? 'selected' : ''}>02 - Trabajos y Servicios</option>
                                <option value="03" ${r.tipo_bien_servicio === '03' ? 'selected' : ''}>03 - Arrendamientos</option>
                                <option value="04" ${r.tipo_bien_servicio === '04' ? 'selected' : ''}>04 - Activos Fijos</option>
                                <option value="05" ${r.tipo_bien_servicio === '05' ? 'selected' : ''}>05 - Gastos de Representacion</option>
                                <option value="06" ${r.tipo_bien_servicio === '06' ? 'selected' : ''}>06 - Otras Deducciones</option>
                                <option value="07" ${r.tipo_bien_servicio === '07' ? 'selected' : ''}>07 - Financieros</option>
                                <option value="08" ${r.tipo_bien_servicio === '08' ? 'selected' : ''}>08 - Extraordinarios</option>
                                <option value="09" ${r.tipo_bien_servicio === '09' ? 'selected' : ''}>09 - Costo de Venta</option>
                                <option value="10" ${r.tipo_bien_servicio === '10' ? 'selected' : ''}>10 - Activos Fijos (Adq.)</option>
                                <option value="11" ${r.tipo_bien_servicio === '11' ? 'selected' : ''}>11 - Seguros</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-control table-select" style="font-size:12px;padding:4px 8px;height:30px;" onchange="ReportsModule.update606Field(${idx}, 'forma_pago', this.value)">
                                <option value="01" ${r.forma_pago === '01' ? 'selected' : ''}>01 - Efectivo</option>
                                <option value="02" ${r.forma_pago === '02' || !r.forma_pago ? 'selected' : ''}>02 - Cheques/Transferencia</option>
                                <option value="03" ${r.forma_pago === '03' ? 'selected' : ''}>03 - Tarjeta</option>
                                <option value="04" ${r.forma_pago === '04' ? 'selected' : ''}>04 - Credito</option>
                                <option value="05" ${r.forma_pago === '05' ? 'selected' : ''}>05 - Permuta</option>
                                <option value="06" ${r.forma_pago === '06' ? 'selected' : ''}>06 - Nota de Credito</option>
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
        }
    },

    update606Field(idx, field, value) {
        if (this._records606[idx]) {
            this._records606[idx][field] = value;
            
            // Auto update matching fields
            if (field === 'forma_pago' && value === '04') {
                // If paid via Credit, payment date can be empty according to DGII rules
                this._records606[idx]['fecha_pago'] = '';
            }
        }
    },

    formatDgiiDate(dateStr) {
        if (!dateStr || dateStr.length !== 8) return dateStr;
        // 20260527 -> 27/05/2026
        const y = dateStr.slice(0, 4);
        const m = dateStr.slice(4, 6);
        const d = dateStr.slice(6, 8);
        return `${d}/${m}/${y}`;
    },

    async exportTxt() {
        const periodStr = `${this._year}${String(this._month).padStart(2, '0')}`;
        const records = this._currentTab === '607' ? this._records607 : this._records606;

        if (records.length === 0) {
            App.showToast('No hay datos para exportar en este período', 'error');
            return;
        }

        App.showToast('Generando archivo oficial DGII...', 'info');

        try {
            // App.api automatically parses JSON. Since this endpoint returns a plain text file, we must use fetch directly.
            const response = await fetch(`/api/dgii/reports/${this._currentTab}/export`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/plain',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${App.state.token || localStorage.getItem('token')}`
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

            // Trigger file download
            const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.target = '_blank';
            
            // Retrieve company RNC
            const rnc = App.state.settings?.company_tax_id 
                ? App.state.settings.company_tax_id.replace(/[^0-9]/g, '') 
                : '131000000';
                
            a.download = `DGII_${this._currentTab}_${rnc}_${periodStr}.txt`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            App.showToast('¡Archivo descargado exitosamente!', 'success');
        } catch (e) {
            App.showToast('Error al exportar archivo de reporte', 'error');
        }
    }
};

// Local helper to match PHP pad function
function str_pad(val, size, char, direction = 'left') {
    let s = String(val);
    while (s.length < size) {
        if (direction === 'left') s = char + s;
        else s = s + char;
    }
    return s;
}

window.ReportsModule = ReportsModule;
export default ReportsModule;
