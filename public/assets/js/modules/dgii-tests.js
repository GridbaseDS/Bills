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

            <!-- Certification Test Section -->
            <div class="table-outer" style="margin-bottom:var(--spacing-xl);">
                <div style="padding:48px;max-width:800px;margin:0 auto;text-align:center;">
                    <div style="width:56px;height:56px;border-radius:var(--radius-xl);background:var(--bg-hover);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5 12 2"></polygon>
                            <line x1="12" y1="22" x2="12" y2="15.5"></line>
                            <polyline points="22 8.5 12 15.5 2 8.5"></polyline>
                        </svg>
                    </div>
                    <h2 style="font-size:18px;font-weight:700;color:var(--color-text-primary);margin-bottom:8px;">Set de Pruebas DGII</h2>
                    <p style="color:var(--color-text-secondary);font-size:13px;margin-bottom:20px;">Para certificación inicial. Envía las 29 facturas ficticias al ambiente CerteCF.</p>
                    <button id="btn-run-tests" class="btn btn-secondary" style="padding:10px 24px;font-size:14px;">
                        Ejecutar Set de Pruebas
                    </button>
                </div>
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
        const btnRun = document.getElementById('btn-run-tests');
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

        // Certification tests
        if (btnRun) {
            btnRun.addEventListener('click', async () => {
                btnRun.disabled = true;
                btnRun.innerHTML = `<span class="spinner" style="width:16px;height:16px;border-width:2px;margin-right:8px;"></span> Procesando...`;
                consoleContainer.style.display = 'block';
                consoleOutput.innerHTML = `<span style="color:#64748b;">[${new Date().toLocaleTimeString()}]</span> Iniciando proceso de certificación...\n`;
                consoleStatus.innerHTML = `<span class="spinner" style="width:12px;height:12px;border-width:2px;"></span> <span style="color:#fbbf24;">Ejecutando</span>`;

                try {
                    const res = await App.api('dgii/run-tests', { method: 'POST' });
                    let coloredOutput = res.output
                        .replace(/ERROR/g, '<span style="color:#ef4444;font-weight:bold;">$&</span>')
                        .replace(/Failed/g, '<span style="color:#ef4444;font-weight:bold;">$&</span>')
                        .replace(/SUCCESS/g, '<span style="color:#22c55e;font-weight:bold;">$&</span>')
                        .replace(/Done!/g, '<span style="color:#22c55e;font-weight:bold;">$&</span>');
                    consoleOutput.innerHTML += `\n${coloredOutput}`;
                    consoleStatus.innerHTML = res.success
                        ? `<span style="color:#22c55e;">COMPLETADO</span>`
                        : `<span style="color:#ef4444;">ERROR</span>`;

                    if (res.fc250k_files && res.fc250k_files.length > 0) {
                        consoleOutput.innerHTML += `\n<span style="color:#fbbf24;font-weight:bold;">Descargando ${res.fc250k_files.length} archivos FC<250k...</span>\n`;
                        for (const file of res.fc250k_files) {
                            const bytes = atob(file.content);
                            const arr = new Uint8Array(bytes.length);
                            for (let i = 0; i < bytes.length; i++) arr[i] = bytes.charCodeAt(i);
                            const blob = new Blob([arr], { type: 'text/xml' });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url; a.download = file.name;
                            document.body.appendChild(a); a.click(); a.remove();
                            URL.revokeObjectURL(url);
                            consoleOutput.innerHTML += `<span style="color:#22c55e;">  OK: ${file.name}</span>\n`;
                            await new Promise(r => setTimeout(r, 500));
                        }
                    }
                } catch (error) {
                    consoleOutput.innerHTML += `\n<span style="color:#ef4444;font-weight:bold;">ERROR:</span> ${error.message}`;
                    consoleStatus.innerHTML = `<span style="color:#ef4444;">ERROR</span>`;
                } finally {
                    btnRun.disabled = false;
                    btnRun.innerHTML = `Ejecutar Set de Pruebas`;
                    consoleOutput.scrollTop = consoleOutput.scrollHeight;
                }
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
    }
};
