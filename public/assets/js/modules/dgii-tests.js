export default {
    async render(container) {
        container.innerHTML = `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Pruebas de Certificación DGII</h1>
                    <p class="page-subtitle">Ejecuta el set de pruebas e-CF o diagnostica el flujo de facturación</p>
                </div>
            </div>

            <!-- Diagnostic Section -->
            <div class="table-outer" style="margin-bottom:var(--spacing-xl);">
                <div style="padding:48px;max-width:800px;margin:0 auto;">
                    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
                        <div style="width:56px;height:56px;border-radius:var(--radius-xl);background:linear-gradient(135deg,#22c55e20,#10b98120);display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h2 style="font-size:20px;font-weight:700;color:var(--color-text-primary);margin:0;">Diagnóstico e-CF en Vivo</h2>
                            <p style="margin:4px 0 0 0;color:var(--color-text-secondary);font-size:13px;">Verifica que todo el flujo funcione correctamente antes de enviar facturas reales</p>
                        </div>
                    </div>
                    
                    <div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
                        <input type="text" id="diag-invoice-id" placeholder="ID de factura (opcional, usa la última si vacío)" 
                            style="flex:1;min-width:200px;padding:10px 16px;border:1px solid var(--color-border);border-radius:var(--radius-md);font-size:13px;background:var(--color-bg-primary);color:var(--color-text-primary);">
                        <button id="btn-diagnose" class="btn btn-primary" style="padding:10px 24px;font-size:14px;">
                            Ejecutar Diagnóstico
                        </button>
                        <button id="btn-diagnose-send" class="btn btn-secondary" style="padding:10px 24px;font-size:14px;" title="Igual que diagnóstico pero ENVÍA de verdad a la DGII">
                            Diagnosticar + Enviar
                        </button>
                    </div>

                    <div id="diag-results" style="display:none;"></div>
                </div>
            </div>

            <!-- Certification Test Runner Section -->
            <div class="table-outer" style="margin-bottom:var(--spacing-xl);">
                <div style="padding:24px var(--spacing-xl);border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div style="display:flex;align-items:center;gap:16px;">
                        <div style="width:48px;height:48px;border-radius:var(--radius-xl);background:linear-gradient(135deg,#3b82f620,#6366f120);display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5 12 2"></polygon>
                                <line x1="12" y1="22" x2="12" y2="15.5"></line>
                                <polyline points="22 8.5 12 15.5 2 8.5"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h2 style="font-size:18px;font-weight:700;color:var(--color-text-primary);margin:0;">Certificación e-CF (Datos Exactos)</h2>
                            <p style="margin:4px 0 0 0;color:var(--color-text-secondary);font-size:12px;">Genera XML directamente desde el set de pruebas DGII — sin usar facturas del sistema</p>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button id="btn-cert-load" class="btn btn-secondary" style="padding:8px 16px;font-size:13px;">
                            Cargar Casos
                        </button>
                        <button id="btn-cert-run-all" class="btn btn-primary" style="padding:8px 16px;font-size:13px;" disabled>
                            Ejecutar Todos
                        </button>
                    </div>
                </div>
                <div id="cert-table-container" style="padding:24px var(--spacing-xl);">
                    <div style="text-align:center;color:var(--color-text-muted);font-size:13px;padding:32px;">
                        Presiona <strong>"Cargar Casos"</strong> para ver los 25 test cases del set de pruebas DGII.
                    </div>
                </div>
                <div id="cert-summary" style="display:none;padding:16px var(--spacing-xl);border-top:1px solid var(--color-border);"></div>
            </div>

            <!-- Aprobaciones Comerciales Section -->
            <div class="table-outer" style="margin-bottom:var(--spacing-xl);">
                <div style="padding:48px;max-width:800px;margin:0 auto;text-align:center;">
                    <div style="width:56px;height:56px;border-radius:var(--radius-xl);background:linear-gradient(135deg,#8b5cf620,#7c3aed20);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <h2 style="font-size:18px;font-weight:700;color:var(--color-text-primary);margin-bottom:8px;">Paso 3: Aprobaciones Comerciales (ACECF)</h2>
                    <p style="color:var(--color-text-secondary);font-size:13px;margin-bottom:20px;">Envía las 11 Aprobaciones Comerciales del set de datos DGII. Requiere el archivo JSON en el servidor.</p>
                    <button id="btn-run-aprobaciones" class="btn btn-primary" style="padding:10px 24px;font-size:14px;">
                        Enviar Aprobaciones Comerciales
                    </button>
                </div>
            </div>

            <!-- Console -->
            <div class="table-outer" style="background:#0f172a;color:#38bdf8;border-color:#1e293b;display:none;" id="console-container">
                <div style="padding:12px var(--spacing-xl);background:#1e293b;border-bottom:1px solid #334155;display:flex;align-items:center;justify-content:space-between;">
                    <div style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;color:#94a3b8;">Terminal / Salida del Servidor</div>
                    <div id="console-status" style="font-size:12px;font-weight:600;display:flex;align-items:center;gap:8px;">
                        <span style="color:#fbbf24;">Esperando...</span>
                    </div>
                </div>
                <div style="padding:24px;font-family:'JetBrains Mono','Courier New',monospace;font-size:12px;line-height:1.7;max-height:500px;overflow-y:auto;white-space:pre-wrap;color:#f8fafc;" id="console-output"></div>
            </div>
        `;

        this.bindEvents();
    },

    bindEvents() {
        const btnDiag = document.getElementById('btn-diagnose');
        const btnDiagSend = document.getElementById('btn-diagnose-send');
        const consoleContainer = document.getElementById('console-container');
        const consoleOutput = document.getElementById('console-output');
        const consoleStatus = document.getElementById('console-status');

        // Diagnostic
        if (btnDiag) btnDiag.addEventListener('click', () => this.runDiagnose(false));
        if (btnDiagSend) btnDiagSend.addEventListener('click', () => this.runDiagnose(true));

        // Aprobaciones Comerciales
        const btnAprob = document.getElementById('btn-run-aprobaciones');
        if (btnAprob) {
            btnAprob.addEventListener('click', async () => {
                btnAprob.disabled = true;
                btnAprob.innerHTML = `<span class="spinner" style="width:16px;height:16px;border-width:2px;margin-right:8px;"></span> Procesando...`;
                consoleContainer.style.display = 'block';
                consoleOutput.innerHTML = `<span style="color:#64748b;">[${new Date().toLocaleTimeString()}]</span> Enviando Aprobaciones Comerciales a la DGII...\n`;
                consoleStatus.innerHTML = `<span class="spinner" style="width:12px;height:12px;border-width:2px;"></span> <span style="color:#fbbf24;">Ejecutando</span>`;

                try {
                    const res = await App.api('dgii/run-aprobaciones', { method: 'POST' });
                    let coloredOutput = res.output;
                    consoleOutput.innerHTML += `\n${coloredOutput}`;
                    consoleStatus.innerHTML = res.success
                        ? `<span style="color:#22c55e;">COMPLETADO</span>`
                        : `<span style="color:#ef4444;">HAY ERRORES</span>`;
                } catch (error) {
                    consoleOutput.innerHTML += `\n<span style="color:#ef4444;font-weight:bold;">ERROR:</span> ${error.message}`;
                    consoleStatus.innerHTML = `<span style="color:#ef4444;">ERROR</span>`;
                } finally {
                    btnAprob.disabled = false;
                    btnAprob.innerHTML = `Enviar Aprobaciones Comerciales`;
                    consoleOutput.scrollTop = consoleOutput.scrollHeight;
                }
            });
        }

        // Certification test runner
        const btnCertLoad = document.getElementById('btn-cert-load');
        const btnCertRunAll = document.getElementById('btn-cert-run-all');
        const certContainer = document.getElementById('cert-table-container');
        const certSummary = document.getElementById('cert-summary');

        if (btnCertLoad) {
            btnCertLoad.addEventListener('click', async () => {
                btnCertLoad.disabled = true;
                btnCertLoad.innerHTML = `<span class="spinner" style="width:14px;height:14px;border-width:2px;margin-right:6px;"></span> Cargando...`;
                try {
                    const res = await App.api('dgii/certification/list');
                    this._certCases = res.cases;
                    this.renderCertTable(certContainer, res.cases);
                    btnCertRunAll.disabled = false;
                } catch (e) {
                    certContainer.innerHTML = `<div style="padding:24px;color:#ef4444;font-weight:600;">Error: ${e.message}</div>`;
                } finally {
                    btnCertLoad.disabled = false;
                    btnCertLoad.innerHTML = 'Cargar Casos';
                }
            });
        }

        if (btnCertRunAll) {
            btnCertRunAll.addEventListener('click', async () => {
                if (!this._certCases?.length) return;
                btnCertRunAll.disabled = true;
                btnCertRunAll.innerHTML = `<span class="spinner" style="width:14px;height:14px;border-width:2px;margin-right:6px;"></span> Ejecutando...`;

                consoleContainer.style.display = 'block';
                consoleOutput.innerHTML = `<span style="color:#64748b;">[${new Date().toLocaleTimeString()}]</span> Ejecutando ${this._certCases.length} test cases de certificación...\n`;
                consoleStatus.innerHTML = `<span class="spinner" style="width:12px;height:12px;border-width:2px;"></span> <span style="color:#fbbf24;">Ejecutando</span>`;

                let passed = 0, failed = 0;
                for (const tc of this._certCases) {
                    const row = document.getElementById(`cert-row-${tc.encf}`);
                    const statusCell = document.getElementById(`cert-status-${tc.encf}`);
                    if (statusCell) statusCell.innerHTML = `<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span>`;

                    try {
                        const res = await App.api('dgii/certification/run-single', {
                            method: 'POST',
                            body: JSON.stringify({ encf: tc.encf }),
                            headers: { 'Content-Type': 'application/json' }
                        });
                        if (res.success) {
                            passed++;
                            if (statusCell) statusCell.innerHTML = `<span style="color:#22c55e;font-weight:600;">✅ ${res.status || 'Aceptado'}</span>`;
                            consoleOutput.innerHTML += `<span style="color:#22c55e;">✅ ${tc.encf} (Tipo ${tc.tipo})</span> — ${res.track_id || 'OK'}\n`;
                        } else {
                            failed++;
                            const errMsg = typeof res.errors === 'string' ? res.errors : JSON.stringify(res.errors);
                            if (statusCell) statusCell.innerHTML = `<span style="color:#ef4444;font-weight:600;" title="${errMsg}">❌ Error</span>`;
                            consoleOutput.innerHTML += `<span style="color:#ef4444;">❌ ${tc.encf} (Tipo ${tc.tipo})</span> — ${errMsg}\n`;
                        }
                    } catch (e) {
                        failed++;
                        if (statusCell) statusCell.innerHTML = `<span style="color:#ef4444;font-weight:600;">❌ ${e.message}</span>`;
                        consoleOutput.innerHTML += `<span style="color:#ef4444;">❌ ${tc.encf}</span> — ${e.message}\n`;
                    }
                    consoleOutput.scrollTop = consoleOutput.scrollHeight;
                }

                // Summary
                certSummary.style.display = 'block';
                const allPassed = failed === 0;
                certSummary.innerHTML = `
                    <div style="display:flex;align-items:center;justify-content:center;gap:16px;padding:8px;border-radius:var(--radius-md);${allPassed ? 'background:#f0fdf4;color:#16a34a;' : 'background:#fef2f2;color:#dc2626;'}">
                        <span style="font-weight:700;font-size:15px;">${allPassed ? '✅ CERTIFICACIÓN COMPLETADA' : `⚠️ ${failed} CASO(S) FALLIDOS`}</span>
                        <span style="font-size:13px;">Aprobados: ${passed} | Fallidos: ${failed} | Total: ${this._certCases.length}</span>
                    </div>`;

                consoleStatus.innerHTML = allPassed
                    ? `<span style="color:#22c55e;">COMPLETADO (${passed}/${this._certCases.length})</span>`
                    : `<span style="color:#ef4444;">ERRORES (${passed}/${this._certCases.length})</span>`;

                btnCertRunAll.disabled = false;
                btnCertRunAll.innerHTML = 'Ejecutar Todos';
            });
        }
    },

    async runDiagnose(sendForReal) {
        const resultsDiv = document.getElementById('diag-results');
        const invoiceId = document.getElementById('diag-invoice-id').value.trim();
        const btn = sendForReal ? document.getElementById('btn-diagnose-send') : document.getElementById('btn-diagnose');
        
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner" style="width:14px;height:14px;border-width:2px;margin-right:6px;"></span> Ejecutando...`;
        
        resultsDiv.style.display = 'block';
        resultsDiv.innerHTML = `<div style="padding:16px;text-align:center;color:var(--color-text-muted);"><span class="spinner" style="width:20px;height:20px;border-width:2px;"></span><br>Ejecutando diagnóstico...</div>`;

        try {
            const body = { send: sendForReal };
            if (invoiceId) body.invoice_id = invoiceId;
            
            const res = await App.api('dgii/diagnose', { method: 'POST', body: JSON.stringify(body), headers: { 'Content-Type': 'application/json' } });
            
            let html = '<div style="border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--color-border);">';
            
            for (const entry of res.log) {
                const icon = entry.status === 'ok' ? '[OK]' : entry.status === 'error' ? '[ERROR]' : entry.status === 'skip' ? '[SKIP]' : '[...]';
                const bg = entry.status === 'error' ? 'rgba(239,68,68,0.05)' : entry.status === 'ok' ? 'rgba(34,197,94,0.03)' : 'transparent';
                const borderColor = entry.status === 'error' ? '#ef4444' : entry.status === 'ok' ? '#22c55e' : '#94a3b8';
                
                html += `
                    <div style="padding:14px 20px;border-left:3px solid ${borderColor};background:${bg};border-bottom:1px solid var(--color-border);">
                        <div style="font-weight:600;font-size:14px;display:flex;align-items:center;gap:8px;">
                            ${icon} ${entry.step}
                        </div>
                        ${entry.detail ? `<div style="margin-top:6px;font-size:12px;color:var(--color-text-muted);font-family:'JetBrains Mono',monospace;word-break:break-all;">${entry.detail}</div>` : ''}
                    </div>
                `;
            }
            html += '</div>';
            
            // Overall status
            const allOk = res.log.every(e => e.status === 'ok' || e.status === 'skip');
            html += `
                <div style="margin-top:16px;padding:16px;border-radius:var(--radius-md);text-align:center;font-weight:700;font-size:15px;${
                    allOk ? 'background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;' : 'background:#fef2f2;color:#dc2626;border:1px solid #fecaca;'
                }">
                    ${allOk ? 'TODOS LOS PASOS COMPLETADOS — El sistema está listo para producción' : 'HAY ERRORES — Revisa los pasos marcados en rojo'}
                </div>
            `;
            
            resultsDiv.innerHTML = html;
        } catch (e) {
            resultsDiv.innerHTML = `<div style="padding:16px;color:#ef4444;font-weight:600;">Error: ${e.message}</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = sendForReal ? 'Diagnosticar + Enviar' : 'Ejecutar Diagnóstico';
        }
    },

    renderCertTable(container, cases) {
        const typeNames = {31:'Factura Crédito Fiscal',32:'Factura Consumo',33:'Nota Débito',34:'Nota Crédito',41:'Compras',43:'Gastos Menores',44:'Pagos Exterior',45:'Gubernamental',46:'Exportación',47:'Venta Zona Franca'};
        let html = `
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--color-border);">
                            <th style="padding:12px 16px;text-align:left;font-weight:600;color:var(--color-text-secondary);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;">#</th>
                            <th style="padding:12px 16px;text-align:left;font-weight:600;color:var(--color-text-secondary);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;">e-NCF</th>
                            <th style="padding:12px 16px;text-align:left;font-weight:600;color:var(--color-text-secondary);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;">Tipo</th>
                            <th style="padding:12px 16px;text-align:left;font-weight:600;color:var(--color-text-secondary);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;">Comprador</th>
                            <th style="padding:12px 16px;text-align:right;font-weight:600;color:var(--color-text-secondary);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;">Monto</th>
                            <th style="padding:12px 16px;text-align:center;font-weight:600;color:var(--color-text-secondary);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;">Items</th>
                            <th style="padding:12px 16px;text-align:center;font-weight:600;color:var(--color-text-secondary);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>`;

        cases.forEach((tc, i) => {
            const typeBadge = `<span style="display:inline-block;padding:2px 8px;border-radius:var(--radius-sm);font-size:11px;font-weight:600;background:rgba(99,102,241,0.08);color:#6366f1;">${tc.tipo}</span>`;
            const typeName = typeNames[tc.tipo] || 'Otro';
            const monto = tc.monto_total ? parseFloat(tc.monto_total).toLocaleString('es-DO', {minimumFractionDigits:2}) : '—';
            const items = tc.items?.join(', ') || '—';

            html += `
                <tr id="cert-row-${tc.encf}" style="border-bottom:1px solid var(--color-border);transition:background 0.15s;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:10px 16px;color:var(--color-text-muted);font-size:12px;">${i+1}</td>
                    <td style="padding:10px 16px;font-weight:600;font-family:'JetBrains Mono',monospace;font-size:12px;">${tc.encf}</td>
                    <td style="padding:10px 16px;">${typeBadge} <span style="color:var(--color-text-muted);font-size:11px;">${typeName}</span></td>
                    <td style="padding:10px 16px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${tc.razon_social_comprador || ''}">${tc.razon_social_comprador || '<span style="color:var(--color-text-muted);">—</span>'}</td>
                    <td style="padding:10px 16px;text-align:right;font-family:'JetBrains Mono',monospace;font-size:12px;">$${monto}</td>
                    <td style="padding:10px 16px;text-align:center;">
                        <span style="display:inline-block;padding:2px 8px;border-radius:var(--radius-sm);font-size:11px;background:var(--bg-hover);color:var(--color-text-secondary);" title="${items}">${tc.items_count}</span>
                    </td>
                    <td style="padding:10px 16px;text-align:center;" id="cert-status-${tc.encf}">
                        <span style="color:var(--color-text-muted);font-size:12px;">⏳ Pendiente</span>
                    </td>
                </tr>`;
        });

        html += `</tbody></table></div>`;
        container.innerHTML = html;
    }
};
