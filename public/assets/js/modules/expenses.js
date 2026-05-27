const ExpensesModule = {
    // Classification mappings for DGII 606
    expenseTypes: {
        '01': '01 - Gastos de Personal',
        '02': '02 - Trabajos, Suministros y Servicios',
        '03': '03 - Arrendamientos',
        '04': '04 - Gastos de Activos Fijos',
        '05': '05 - Gastos de Representación',
        '06': '06 - Otras Deducciones Admitidas',
        '07': '07 - Gastos Financieros',
        '08': '08 - Gastos Extraordinarios',
        '09': '09 - Compras que forman parte del Costo',
        '10': '10 - Adquisiciones de Activos',
        '11': '11 - Gastos de Seguros'
    },

    paymentMethods: {
        '01': '01 - Efectivo',
        '02': '02 - Cheques / Transferencias',
        '03': '03 - Tarjeta de Crédito / Débito',
        '04': '04 - Compra a Crédito',
        '05': '05 - Permuta',
        '06': '06 - Notas de Crédito',
        '07': '07 - Mixto'
    },

    async render(container, id) {
        if (id === 'nuevo') { this.renderForm(container); return; }
        if (id) { this.renderForm(container, id); return; }
        this.renderList(container);
    },

    async renderList(container) {
        try {
            // Load expenses from API
            const response = await window.App.api('expenses');
            const expensesList = response.data || response;

            // Simple KPI calculation
            let totalSpent = 0;
            let totalItbis = 0;
            let thisMonthSpent = 0;
            const currentMonth = new Date().getMonth();
            const currentYear = new Date().getFullYear();

            expensesList.forEach(exp => {
                const sub = parseFloat(exp.subtotal) || 0;
                const tax = parseFloat(exp.tax_amount) || 0;
                totalSpent += sub + tax;
                totalItbis += tax;

                const expDate = new Date(exp.expense_date);
                if (expDate.getMonth() === currentMonth && expDate.getFullYear() === currentYear) {
                    thisMonthSpent += sub + tax;
                }
            });

            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Control de Gastos y Egresos</h1>
                        <p class="page-subtitle">Monitorea compras locales y egresos compatibles con reporte fiscal 606</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.App.navigate('gastos/nuevo')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Registrar Gasto
                    </button>
                </div>

                <!-- Premium KPIs -->
                <div class="grid-3" style="margin-bottom:var(--spacing-xl);">
                    <div class="stat-card">
                        <div class="stat-card-title">Total Egresado</div>
                        <div class="stat-card-value">${window.App.formatCurrency(totalSpent, 'DOP')}</div>
                        <div class="stat-card-sub" style="color:var(--color-text-muted)">Histórico acumulado</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-title">ITBIS Adelantado</div>
                        <div class="stat-card-value" style="color:var(--color-primary);">${window.App.formatCurrency(totalItbis, 'DOP')}</div>
                        <div class="stat-card-sub">Compensable en reporte 606</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-title">Gastos del Mes</div>
                        <div class="stat-card-value" style="color:var(--color-text-primary);">${window.App.formatCurrency(thisMonthSpent, 'DOP')}</div>
                        <div class="stat-card-sub">Mes en curso</div>
                    </div>
                </div>

                <div class="table-outer">
                    <div class="table-toolbar" style="display:flex; justify-content:space-between; align-items:center; gap: 12px; flex-wrap: wrap;">
                        <div class="search-wrapper" style="max-width: 320px; display: flex; width: 100%;">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="expenses-search" class="form-control" placeholder="Buscar proveedor, RNC o NCF..." style="width: 100%; padding-left: 36px;">
                        </div>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>NCF</th>
                                    <th>RNC</th>
                                    <th>Proveedor</th>
                                    <th>Tipo de Gasto</th>
                                    <th>Subtotal</th>
                                    <th>ITBIS</th>
                                    <th>Total</th>
                                    <th style="width:100px; text-align:right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="expenses-table-body">
                                ${this.generateTableRows(expensesList)}
                            </tbody>
                        </table>
                    </div>
                    <div id="expenses-mobile-list" class="mobile-card-list">${this.generateMobileCards(expensesList)}</div>
                </div>
            `;

            // Setup Search
            const searchInput = document.getElementById('expenses-search');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const q = e.target.value.toLowerCase();
                    const filtered = expensesList.filter(exp => 
                        (exp.provider_name && exp.provider_name.toLowerCase().includes(q)) ||
                        (exp.provider_tax_id && exp.provider_tax_id.toLowerCase().includes(q)) ||
                        (exp.ncf && exp.ncf.toLowerCase().includes(q))
                    );
                    document.getElementById('expenses-table-body').innerHTML = this.generateTableRows(filtered);
                    const mobileList = document.getElementById('expenses-mobile-list');
                    if (mobileList) mobileList.innerHTML = this.generateMobileCards(filtered);
                });
            }

        } catch (error) {
            container.innerHTML = `<div class="alert alert-error" style="margin-top:20px;">Error al cargar gastos.</div>`;
        }
    },

    generateTableRows(expenses) {
        if (!expenses || expenses.length === 0) {
            return `<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--color-text-muted);">No hay egresos registrados para este período</td></tr>`;
        }
        
        return expenses.map(exp => {
            const sub = parseFloat(exp.subtotal) || 0;
            const tax = parseFloat(exp.tax_amount) || 0;
            const total = sub + tax;
            const formattedDate = exp.expense_date ? exp.expense_date.split(' ')[0] : '';
            
            return `
                <tr>
                    <td><span style="font-weight:500;white-space:nowrap;">${formattedDate}</span></td>
                    <td><code style="font-family:monospace;font-size:13px;font-weight:600;color:var(--color-text-primary);">${exp.ncf || '<span style="color:var(--color-text-muted)">N/A</span>'}</code></td>
                    <td><span style="font-size:12px;font-weight:500;color:var(--color-text-secondary);">${exp.provider_tax_id || '<span style="color:var(--color-text-muted)">N/A</span>'}</span></td>
                    <td style="font-weight:500;color:var(--color-text-primary);">${exp.provider_name}</td>
                    <td><span class="badge badge-inactive" style="font-size:11px;font-weight:600;" title="${this.expenseTypes[exp.expense_type] || ''}">${this.expenseTypes[exp.expense_type] ? this.expenseTypes[exp.expense_type].substring(0, 18) + '...' : 'N/A'}</span></td>
                    <td style="font-weight:500;">${window.App.formatCurrency(sub, 'DOP')}</td>
                    <td style="font-weight:500;color:var(--color-primary);">${window.App.formatCurrency(tax, 'DOP')}</td>
                    <td style="font-weight:700;color:var(--color-text-primary);">${window.App.formatCurrency(total, 'DOP')}</td>
                    <td style="text-align:right;">
                        <div class="row-actions" style="justify-content:flex-end;">
                            <a href="#gastos/${exp.id}" class="btn-icon" style="width:28px;height:28px;" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a>
                            <button class="btn-icon" style="width:28px;height:28px;" onclick="ExpensesModule.deleteExpense(${exp.id})" title="Eliminar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger-icon)" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    },

    generateMobileCards(expenses) {
        if (!expenses || expenses.length === 0) {
            return '<div class="text-center text-muted" style="padding:32px;">No hay gastos registrados</div>';
        }
        return expenses.map(exp => {
            const sub = parseFloat(exp.subtotal) || 0;
            const tax = parseFloat(exp.tax_amount) || 0;
            const total = sub + tax;
            const formattedDate = exp.expense_date ? exp.expense_date.split(' ')[0] : '';
            return `
                <a href="#gastos/${exp.id}" class="mobile-card">
                    <div class="mobile-card-top">
                        <div class="mobile-card-id">${exp.provider_name}</div>
                        <span class="badge badge-inactive">${formattedDate}</span>
                    </div>
                    <div style="font-size:11px;font-family:monospace;color:var(--color-text-secondary);margin-top:2px;">NCF: ${exp.ncf || 'N/A'}</div>
                    <div class="mobile-card-bottom" style="margin-top:8px;">
                        <div class="mobile-card-amount">${window.App.formatCurrency(total, 'DOP')}</div>
                        <svg class="mobile-card-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </a>
            `;
        }).join('');
    },

    async renderForm(container, id) {
        let expense = { expense_date: new Date().toISOString().split('T')[0], subtotal: 0, tax_amount: 0, total: 0, expense_type: '02', payment_method: '02' };
        
        if (id) {
            try {
                expense = await window.App.api(`expenses/${id}`);
                expense.expense_date = expense.expense_date ? expense.expense_date.split(' ')[0] : '';
            } catch (e) {
                container.innerHTML = `<div class="alert alert-error">Error al cargar datos del gasto.</div>`;
                return;
            }
        }

        container.innerHTML = `
            <div style="margin-bottom:12px;">
                <a href="#gastos" style="color:var(--color-text-muted);text-decoration:none;font-size:13px;">← Control de Gastos</a>
            </div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">${id ? 'Editar Gasto' : 'Registrar Gasto (Egreso)'}</h1>
                    <p class="page-subtitle">${id ? 'Modifica los datos del gasto registrado' : 'Ingresa los datos del comprobante para alimentar tu 606 DGII automáticamente'}</p>
                </div>
                <button class="btn btn-secondary" onclick="window.App.navigate('gastos')">Cancelar</button>
            </div>

            <form id="expense-form" class="form-card">
                <div style="padding:var(--spacing-xl);">
                    <div class="grid-2">
                        
                        <div class="form-group">
                            <label class="form-label">Razón Social / Proveedor *</label>
                            <input type="text" id="e_provider_name" class="form-control" value="${expense.provider_name || ''}" placeholder="Ej: Claro Dominicana" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">RNC / Cédula Proveedor (Opcional)</label>
                            <input type="text" id="e_provider_tax_id" class="form-control" value="${expense.provider_tax_id || ''}" placeholder="Ej: 101001557" maxlength="11">
                        </div>

                        <div class="form-group">
                            <label class="form-label">NCF / e-NCF del Gasto (Opcional)</label>
                            <input type="text" id="e_ncf" class="form-control" value="${expense.ncf || ''}" placeholder="Ej: B0100000100" maxlength="13">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Fecha de Compra *</label>
                            <input type="date" id="e_date" class="form-control" value="${expense.expense_date}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tipo de Gasto (DGII 606) *</label>
                            <select id="e_type" class="form-select" required>
                                ${Object.entries(this.expenseTypes).map(([k, v]) => `
                                    <option value="${k}" ${expense.expense_type === k ? 'selected' : ''}>${v}</option>
                                `).join('')}
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Forma de Pago (DGII 606) *</label>
                            <select id="e_payment" class="form-select" required>
                                ${Object.entries(this.paymentMethods).map(([k, v]) => `
                                    <option value="${k}" ${expense.payment_method === k ? 'selected' : ''}>${v}</option>
                                `).join('')}
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Monto Subtotal (Neto) *</label>
                            <input type="number" id="e_subtotal" class="form-control" value="${expense.subtotal}" min="0" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Monto ITBIS (Impuesto) *</label>
                            <input type="number" id="e_tax" class="form-control" value="${expense.tax_amount}" min="0" step="0.01" required>
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Monto Total Compra (Cálculo Automático)</label>
                            <input type="number" id="e_total" class="form-control" value="${parseFloat(expense.subtotal || 0) + parseFloat(expense.tax_amount || 0)}" readonly style="background:var(--bg-hover); font-weight:700;">
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Notas / Concepto (Opcional)</label>
                            <textarea id="e_notes" class="form-control" rows="3" placeholder="Mensualidad de línea corporativa, compra de insumos, etc...">${expense.notes || ''}</textarea>
                        </div>
                    </div>

                    <div class="mt-24" style="display:flex; gap:12px;">
                        <button type="submit" class="btn btn-primary">${id ? 'Guardar Cambios' : 'Registrar Gasto'}</button>
                    </div>
                </div>
            </form>
        `;

        // Add auto calculation listeners
        const subtotalEl = document.getElementById('e_subtotal');
        const taxEl = document.getElementById('e_tax');
        const totalEl = document.getElementById('e_total');

        const calculateTotal = () => {
            const sub = parseFloat(subtotalEl.value) || 0;
            const tax = parseFloat(taxEl.value) || 0;
            totalEl.value = (sub + tax).toFixed(2);
        };

        subtotalEl.addEventListener('input', calculateTotal);
        taxEl.addEventListener('input', calculateTotal);

        // Submit listener
        document.getElementById('expense-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                provider_name: document.getElementById('e_provider_name').value,
                provider_tax_id: document.getElementById('e_provider_tax_id').value || null,
                ncf: document.getElementById('e_ncf').value || null,
                expense_date: document.getElementById('e_date').value,
                expense_type: document.getElementById('e_type').value,
                payment_method: document.getElementById('e_payment').value,
                subtotal: parseFloat(subtotalEl.value) || 0,
                tax_amount: parseFloat(taxEl.value) || 0,
                total: parseFloat(totalEl.value) || 0,
                notes: document.getElementById('e_notes').value || null
            };

            try {
                if (id) {
                    await window.App.api(`expenses/${id}`, { method: 'PUT', body: payload });
                    window.App.showToast('Gasto actualizado exitosamente');
                } else {
                    await window.App.api('expenses', { method: 'POST', body: payload });
                    window.App.showToast('Gasto registrado exitosamente');
                }
                window.App.navigate('gastos');
            } catch (err) {}
        });
    },

    async deleteExpense(id) {
        if (confirm('¿Estás seguro de eliminar este registro de gasto?')) {
            try {
                await window.App.api(`expenses/${id}`, { method: 'DELETE' });
                window.App.showToast('Gasto eliminado exitosamente');
                window.App.navigate('gastos');
            } catch (e) {
                // Ignore
            }
        }
    }
};

export default ExpensesModule;
