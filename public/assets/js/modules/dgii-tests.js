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

            <!-- Paso 4: Simulación e-CF -->
            <div class="table-outer" style="margin-bottom:var(--spacing-xl);">
                <div style="padding:24px var(--spacing-xl);border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div style="display:flex;align-items:center;gap:16px;">
                        <div style="width:48px;height:48px;border-radius:var(--radius-xl);background:linear-gradient(135deg,#f59e0b20,#f9731620);display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h2 style="font-size:18px;font-weight:700;color:var(--color-text-primary);margin:0;">Paso 4: Simulación e-CF</h2>
                            <p style="margin:4px 0 0 0;color:var(--color-text-secondary);font-size:12px;">Genera y envía facturas de simulación por tipo a la DGII en el entorno de certificación</p>
                        </div>
                    </div>
                    <button id="btn-sim-generate-all" class="btn btn-primary" style="padding:8px 20px;font-size:13px;">
                        🚀 Generar Todos
                    </button>
                </div>
                <div style="padding:24px var(--spacing-xl);">
                    <div id="sim-cards" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;"></div>
                </div>
                <div id="sim-summary" style="display:none;padding:16px var(--spacing-xl);border-top:1px solid var(--color-border);"></div>
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
        this.renderSimCards();
    },

    // Simulation type definitions matching DGII Paso 4 requirements
    SIM_TYPES: [
        { type: 31, name: 'Crédito Fiscal', qty: 4, color: '#3b82f6', icon: '🧾' },
        { type: 32, name: 'Consumo ≥250Mil', qty: 2, color: '#10b981', icon: '🛒', isRfce: false },
        { type: 33, name: 'Nota Débito', qty: 1, color: '#ef4444', icon: '📝' },
        { type: 34, name: 'Nota Crédito', qty: 2, color: '#f59e0b', icon: '📋' },
        { type: 41, name: 'Compras', qty: 2, color: '#8b5cf6', icon: '📦' },
        { type: 43, name: 'Gastos Menores', qty: 2, color: '#06b6d4', icon: '💳' },
        { type: 44, name: 'Reg. Especiales', qty: 2, color: '#ec4899', icon: '⚡' },
        { type: 45, name: 'Gubernamental', qty: 2, color: '#14b8a6', icon: '🏛️' },
        { type: 46, name: 'Exportación', qty: 2, color: '#6366f1', icon: '🌍' },
        { type: 47, name: 'Pagos al Exterior', qty: 2, color: '#a855f7', icon: '💱' },
        { type: 32, name: 'RFCE (<250Mil)', qty: 4, color: '#22c55e', icon: '📃', isRfce: true },
    ],

    renderSimCards() {
        const container = document.getElementById('sim-cards');
        if (!container) return;

        // Client ID 1 = Grupo Tecnomeca (hardcoded for certification)
        const clientId = 1;

        container.innerHTML = this.SIM_TYPES.map((t, idx) => {
            const key = t.isRfce ? `${t.type}-rfce` : `${t.type}`;
            return `
                <div class="sim-card" id="sim-card-${key}" style="border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:20px;transition:all 0.2s;position:relative;overflow:hidden;">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:${t.color};opacity:0.6;"></div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:24px;">${t.icon}</span>
                            <div>
                                <div style="font-weight:700;font-size:14px;color:var(--color-text-primary);">E${t.type}</div>
                                <div style="font-size:11px;color:var(--color-text-muted);">${t.name}</div>
                            </div>
                        </div>
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:var(--radius-sm);font-size:12px;font-weight:700;background:${t.color}15;color:${t.color};">
                            <span id="sim-count-${key}">0</span>/${t.qty}
                        </span>
                    </div>
                    <div id="sim-results-${key}" style="font-size:11px;color:var(--color-text-muted);margin-bottom:12px;min-height:20px;">
                        Pendiente de generación
                    </div>
                    <button class="btn-sim-gen btn btn-secondary" data-type="${t.type}" data-qty="${t.qty}" data-rfce="${t.isRfce ? '1' : '0'}" data-key="${key}" data-client="${clientId}"
                        style="width:100%;padding:8px;font-size:12px;font-weight:600;border-radius:var(--radius-md);transition:all 0.15s;">
                        Generar ${t.qty}x E${t.type}${t.isRfce ? ' RFCE' : ''}
                    </button>
                </div>`;
        }).join('');
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

        // Simulation individual buttons
        document.querySelectorAll('.btn-sim-gen').forEach(btn => {
            btn.addEventListener('click', () => this.runSimGenerate(btn));
        });

        // Generate All simulation
        const btnSimAll = document.getElementById('btn-sim-generate-all');
        if (btnSimAll) {
            btnSimAll.addEventListener('click', async () => {
                btnSimAll.disabled = true;
                btnSimAll.innerHTML = `<span class="spinner" style="width:14px;height:14px;border-width:2px;margin-right:6px;"></span> Generando...`;
                consoleContainer.style.display = 'block';
                consoleOutput.innerHTML = `<span style="color:#64748b;">[${new Date().toLocaleTimeString()}]</span> <span style="color:#f59e0b;font-weight:700;">━━━ PASO 4: Generando TODAS las facturas de simulación ━━━</span>\n`;
                consoleStatus.innerHTML = `<span class="spinner" style="width:12px;height:12px;border-width:2px;"></span> <span style="color:#fbbf24;">Ejecutando</span>`;

                const allBtns = document.querySelectorAll('.btn-sim-gen');
                let totalOk = 0, totalFail = 0;
                for (const b of allBtns) {
                    await this.runSimGenerate(b);
                    // Count results
                    const key = b.dataset.key;
                    const countEl = document.getElementById(`sim-count-${key}`);
                    if (countEl && parseInt(countEl.textContent) > 0) totalOk++;
                }

                const simSummary = document.getElementById('sim-summary');
                if (simSummary) {
                    simSummary.style.display = 'block';
                    simSummary.innerHTML = `
                        <div style="display:flex;align-items:center;justify-content:center;gap:16px;padding:12px;border-radius:var(--radius-md);background:#f0fdf4;color:#16a34a;font-weight:700;font-size:14px;">
                            ✅ Simulación completada — Revisa los resultados arriba y descarga los PDFs desde la lista de facturas
                        </div>`;
                }

                consoleStatus.innerHTML = `<span style="color:#22c55e;">COMPLETADO</span>`;
                btnSimAll.disabled = false;
                btnSimAll.innerHTML = '🚀 Generar Todos';
            });
        }

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
                let runPhase = '';
                for (const tc of this._certCases) {
                    // Skip Phase 4 upload-required cases in run-all
                    if (tc.upload_required) continue;
                    
                    // Log phase transitions
                    if (tc.phase && tc.phase !== runPhase) {
                        runPhase = tc.phase;
                        consoleOutput.innerHTML += `\n<span style="color:#6366f1;font-weight:700;">━━━ ${tc.phase} ━━━</span>\n`;
                    }
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
        const typeNames = {31:'Factura Crédito Fiscal',32:'Factura Consumo',33:'Nota Débito',34:'Nota Crédito',41:'Compras',43:'Gastos Menores',44:'Regímenes Especiales',45:'Gubernamental',46:'Exportación',47:'Pagos al Exterior','32-RFCE':'RFCE Resumen'};
        const phaseColors = {
            'Fase 1': { bg: 'rgba(59,130,246,0.08)', color: '#3b82f6', icon: '1️⃣' },
            'Fase 2': { bg: 'rgba(168,85,247,0.08)', color: '#a855f7', icon: '2️⃣' },
            'Fase 3': { bg: 'rgba(245,158,11,0.08)', color: '#f59e0b', icon: '3️⃣' },
            'Fase 4': { bg: 'rgba(16,185,129,0.08)', color: '#10b981', icon: '4️⃣' },
        };

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
                            <th style="padding:12px 16px;text-align:center;font-weight:600;color:var(--color-text-secondary);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>`;

        let currentPhase = '';
        cases.forEach((tc, i) => {
            // Phase separator row
            const phaseKey = (tc.phase || '').substring(0, 6); // "Fase X"
            if (tc.phase && tc.phase !== currentPhase) {
                currentPhase = tc.phase;
                const phaseCasesCount = cases.filter(c => c.phase === tc.phase).length;
                const pc = phaseColors[phaseKey] || { bg: 'rgba(100,100,100,0.08)', color: '#64748b', icon: '▶' };
                html += `
                <tr style="background:${pc.bg};">
                    <td colspan="8" style="padding:10px 16px;font-weight:700;font-size:12px;color:${pc.color};letter-spacing:0.3px;">
                        ${pc.icon} ${tc.phase} <span style="font-weight:400;opacity:0.7;">(${phaseCasesCount} casos)</span>
                    </td>
                </tr>`;
            }

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
                    <td style="padding:10px 16px;text-align:center;">
                        ${tc.upload_required
                            ? `<button class="btn-run-single" data-encf="${tc.encf}" data-tipo="${tc.tipo}" data-upload="1" style="padding:4px 12px;font-size:11px;font-weight:600;border:1px solid #f59e0b;border-radius:var(--radius-sm);background:rgba(245,158,11,0.08);color:#f59e0b;cursor:pointer;transition:all 0.15s;" onmouseover="this.style.background='#f59e0b';this.style.color='white'" onmouseout="this.style.background='rgba(245,158,11,0.08)';this.style.color='#f59e0b'">📥 Generar XML</button>`
                            : `<button class="btn-run-single" data-encf="${tc.encf}" data-tipo="${tc.tipo}" style="padding:4px 12px;font-size:11px;font-weight:600;border:1px solid var(--color-border);border-radius:var(--radius-sm);background:var(--color-bg-primary);color:var(--color-text-primary);cursor:pointer;transition:all 0.15s;" onmouseover="this.style.background='var(--color-primary)';this.style.color='white';this.style.borderColor='var(--color-primary)'" onmouseout="this.style.background='var(--color-bg-primary)';this.style.color='var(--color-text-primary)';this.style.borderColor='var(--color-border)'">▶ Ejecutar</button>`
                        }
                    </td>
                </tr>`;
        });

        html += `</tbody></table></div>`;
        container.innerHTML = html;

        // Bind individual run buttons
        container.querySelectorAll('.btn-run-single').forEach(btn => {
            btn.addEventListener('click', (e) => this.runSingleCase(e.target.closest('.btn-run-single')));
        });
    },

    async runSingleCase(btn) {
        const encf = btn.dataset.encf;
        const tipo = btn.dataset.tipo;
        const statusCell = document.getElementById(`cert-status-${encf}`);
        const consoleContainer = document.getElementById('console-container');
        const consoleOutput = document.getElementById('console-output');
        const consoleStatus = document.getElementById('console-status');

        // Show console
        consoleContainer.style.display = 'block';

        // Disable button and show spinner
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="width:12px;height:12px;border-width:2px;"></span>';
        btn.style.pointerEvents = 'none';
        if (statusCell) statusCell.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span>';

        consoleOutput.innerHTML += `<span style="color:#64748b;">[${new Date().toLocaleTimeString()}]</span> Ejecutando ${encf} (Tipo ${tipo})...\n`;
        consoleStatus.innerHTML = `<span class="spinner" style="width:12px;height:12px;border-width:2px;"></span> <span style="color:#fbbf24;">Ejecutando</span>`;

        try {
            const res = await App.api('dgii/certification/run-single', {
                method: 'POST',
                body: JSON.stringify({ encf }),
                headers: { 'Content-Type': 'application/json' }
            });

            if (res.upload_required) {
                // Phase 4: file generated, show download link
                if (statusCell) statusCell.innerHTML = `<a href="${res.download_url}" target="_blank" style="color:#f59e0b;font-weight:600;text-decoration:none;">📥 Descargar XML</a>`;
                consoleOutput.innerHTML += `<span style="color:#f59e0b;">📥 ${encf} (Tipo ${tipo})</span> — XML generado. <a href="${res.download_url}" target="_blank" style="color:#38bdf8;text-decoration:underline;">Descargar</a> y subir al portal DGII.\n`;
                consoleStatus.innerHTML = `<span style="color:#f59e0b;">ARCHIVO LISTO</span>`;
            } else if (res.success) {
                if (statusCell) statusCell.innerHTML = `<span style="color:#22c55e;font-weight:600;">✅ ${res.status || 'Aceptado'}</span>`;
                consoleOutput.innerHTML += `<span style="color:#22c55e;">✅ ${encf} (Tipo ${tipo})</span> — ${res.track_id || 'OK'}\n`;
                consoleStatus.innerHTML = `<span style="color:#22c55e;">COMPLETADO</span>`;
            } else {
                const errMsg = typeof res.errors === 'string' ? res.errors : JSON.stringify(res.errors);
                if (statusCell) statusCell.innerHTML = `<span style="color:#ef4444;font-weight:600;cursor:pointer;" title="${errMsg.replace(/"/g, '&quot;')}">❌ Error</span>`;
                consoleOutput.innerHTML += `<span style="color:#ef4444;">❌ ${encf} (Tipo ${tipo})</span> — ${errMsg}\n`;
                consoleStatus.innerHTML = `<span style="color:#ef4444;">ERROR</span>`;
            }
        } catch (e) {
            if (statusCell) statusCell.innerHTML = `<span style="color:#ef4444;font-weight:600;">❌ ${e.message}</span>`;
            consoleOutput.innerHTML += `<span style="color:#ef4444;">❌ ${encf}</span> — ${e.message}\n`;
            consoleStatus.innerHTML = `<span style="color:#ef4444;">ERROR</span>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '▶ Ejecutar';
            btn.style.pointerEvents = '';
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }
    },

    async runSimGenerate(btn) {
        const type = btn.dataset.type;
        const qty = btn.dataset.qty;
        const isRfce = btn.dataset.rfce === '1';
        const key = btn.dataset.key;
        const clientId = btn.dataset.client;
        const consoleContainer = document.getElementById('console-container');
        const consoleOutput = document.getElementById('console-output');
        const consoleStatus = document.getElementById('console-status');
        const resultsDiv = document.getElementById(`sim-results-${key}`);
        const countEl = document.getElementById(`sim-count-${key}`);
        const card = document.getElementById(`sim-card-${key}`);

        // Show console
        consoleContainer.style.display = 'block';

        // Disable and show spinner
        btn.disabled = true;
        const origText = btn.textContent;
        btn.innerHTML = `<span class="spinner" style="width:12px;height:12px;border-width:2px;margin-right:6px;"></span> Generando...`;
        if (resultsDiv) resultsDiv.innerHTML = '<span class="spinner" style="width:12px;height:12px;border-width:2px;"></span> Procesando...';

        consoleOutput.innerHTML += `<span style="color:#64748b;">[${new Date().toLocaleTimeString()}]</span> Generando ${qty}x E${type}${isRfce ? ' RFCE' : ''}...\n`;
        consoleStatus.innerHTML = `<span class="spinner" style="width:12px;height:12px;border-width:2px;"></span> <span style="color:#fbbf24;">Ejecutando</span>`;

        try {
            const res = await App.api('dgii/simulation/generate', {
                method: 'POST',
                body: JSON.stringify({
                    ecf_type: parseInt(type),
                    quantity: parseInt(qty),
                    client_id: parseInt(clientId),
                    is_rfce: isRfce,
                }),
                headers: { 'Content-Type': 'application/json' }
            });

            if (res.results && res.results.length) {
                const accepted = res.results.filter(r => r.success).length;
                const failed = res.results.filter(r => !r.success).length;

                if (countEl) countEl.textContent = accepted;

                // Update card border color
                if (card) {
                    card.style.borderColor = accepted >= parseInt(qty) ? '#22c55e' : failed > 0 ? '#ef4444' : 'var(--color-border)';
                }

                // Show results in card
                if (resultsDiv) {
                    resultsDiv.innerHTML = res.results.map(r => {
                        const icon = r.success ? '✅' : '❌';
                        const status = r.dgii_status || 'error';
                        return `<div style="margin-bottom:2px;">${icon} <code style="font-size:10px;background:var(--bg-hover);padding:1px 4px;border-radius:3px;">${r.encf}</code> <span style="color:${r.success ? '#22c55e' : '#ef4444'};">${status}</span></div>`;
                    }).join('');
                }

                // Console log
                res.results.forEach(r => {
                    if (r.success) {
                        consoleOutput.innerHTML += `<span style="color:#22c55e;">  ✅ ${r.encf}</span> → ${r.dgii_status} | $${parseFloat(r.total).toLocaleString('es-DO')}\n`;
                    } else {
                        consoleOutput.innerHTML += `<span style="color:#ef4444;">  ❌ ${r.encf}</span> → ${r.error || r.dgii_status}\n`;
                    }
                });

                consoleOutput.innerHTML += `<span style="color:#64748b;">  ─ E${type}${isRfce ? ' RFCE' : ''}: ${accepted}/${qty} aceptadas</span>\n`;
            } else {
                if (resultsDiv) resultsDiv.innerHTML = `<span style="color:#ef4444;">Error: Sin resultados</span>`;
                consoleOutput.innerHTML += `<span style="color:#ef4444;">  ❌ E${type}: Sin resultados</span>\n`;
            }
        } catch (e) {
            if (resultsDiv) resultsDiv.innerHTML = `<span style="color:#ef4444;">${e.message}</span>`;
            consoleOutput.innerHTML += `<span style="color:#ef4444;">  ❌ E${type}: ${e.message}</span>\n`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }
    }
};
