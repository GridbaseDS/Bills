export default {
    async render(container) {
        container.innerHTML = `
            <div class="header-actions">
                <h1 class="page-title">Pruebas de Certificación DGII</h1>
            </div>
            
            <div class="card" style="margin-top: 24px; padding: 32px;">
                <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                    <div style="margin-bottom: 24px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 16px;">
                            <polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5 12 2"></polygon>
                            <line x1="12" y1="22" x2="12" y2="15.5"></line>
                            <polyline points="22 8.5 12 15.5 2 8.5"></polyline>
                            <polyline points="2 15.5 12 8.5 22 15.5"></polyline>
                            <line x1="12" y1="2" x2="12" y2="8.5"></line>
                        </svg>
                        <h2 style="font-size: 24px; font-weight: 700; color: var(--text); margin-bottom: 8px;">Ejecutar Set de Pruebas e-CF</h2>
                        <p style="color: var(--contrast-medium); font-size: 14px; line-height: 1.6;">
                            Este proceso enviará las 30 facturas ficticias al ambiente de certificación de la DGII (CerteCF). 
                            Es un requisito obligatorio para obtener el pase a Producción. <br>
                            <strong>Nota:</strong> El proceso puede tardar entre 20 y 40 segundos en finalizar. Por favor, no cierres esta ventana.
                        </p>
                    </div>
                    
                    <button id="btn-run-tests" class="btn btn-primary" style="padding: 12px 32px; font-size: 16px; font-weight: 600;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                        Iniciar Pruebas DGII
                    </button>
                </div>
            </div>

            <!-- Virtual Console -->
            <div class="card" style="margin-top: 24px; background: #0f172a; color: #38bdf8; border: 1px solid #1e293b; display: none;" id="console-container">
                <div style="padding: 12px 16px; background: #1e293b; border-bottom: 1px solid #334155; display: flex; align-items: center; justify-content: space-between; border-radius: var(--border-radius-card) var(--border-radius-card) 0 0;">
                    <div style="font-family: monospace; font-size: 12px; font-weight: 600; color: #94a3b8;">Terminal / Salida del Servidor</div>
                    <div id="console-status" style="font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <span style="color: #fbbf24;">Esperando conexión...</span>
                    </div>
                </div>
                <div style="padding: 24px; font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.6; max-height: 500px; overflow-y: auto; white-space: pre-wrap; color: #f8fafc;" id="console-output"></div>
            </div>
        `;

        this.bindEvents();
    },

    bindEvents() {
        const btnRun = document.getElementById('btn-run-tests');
        const consoleContainer = document.getElementById('console-container');
        const consoleOutput = document.getElementById('console-output');
        const consoleStatus = document.getElementById('console-status');

        if (!btnRun) return;

        btnRun.addEventListener('click', async () => {
            // UI States
            btnRun.disabled = true;
            btnRun.innerHTML = `<span class="spinner" style="width: 18px; height: 18px; border-width: 2px; margin-right: 8px;"></span> Procesando...`;
            
            consoleContainer.style.display = 'block';
            consoleOutput.innerHTML = `<span style="color: #64748b;">[${new Date().toLocaleTimeString()}]</span> Iniciando proceso de certificación...\n<span style="color: #64748b;">[${new Date().toLocaleTimeString()}]</span> Conectando con los servidores de la DGII, por favor espera...\n`;
            consoleStatus.innerHTML = `<span class="spinner" style="width: 12px; height: 12px; border-width: 2px;"></span> <span style="color: #fbbf24;">Ejecutando</span>`;

            try {
                // Call API
                const res = await App.api('dgii/run-tests', { method: 'POST' });
                
                // Colorize the output slightly
                let coloredOutput = res.output
                    .replace(/Starting DGII Test Runner.../g, '<span style="color: #38bdf8; font-weight: bold;">$&</span>')
                    .replace(/ERROR/g, '<span style="color: #ef4444; font-weight: bold;">$&</span>')
                    .replace(/Failed/g, '<span style="color: #ef4444; font-weight: bold;">$&</span>')
                    .replace(/SUCCESS/g, '<span style="color: #22c55e; font-weight: bold;">$&</span>')
                    .replace(/Done!/g, '<span style="color: #22c55e; font-weight: bold;">$&</span>');

                consoleOutput.innerHTML += `\n${coloredOutput}`;

                if (res.success) {
                    consoleStatus.innerHTML = `<span style="color: #22c55e;">COMPLETADO</span>`;
                    App.showToast('Pruebas finalizadas con éxito', 'success');
                } else {
                    consoleStatus.innerHTML = `<span style="color: #ef4444;">ERROR O ADVERTENCIA</span>`;
                    App.showToast('Las pruebas finalizaron con errores. Revisa la consola.', 'error');
                }

            } catch (error) {
                consoleOutput.innerHTML += `\n<span style="color: #ef4444; font-weight: bold;">ERROR FATAL:</span> ${error.message}`;
                consoleStatus.innerHTML = `<span style="color: #ef4444;">ERROR FATAL</span>`;
            } finally {
                // Reset button
                btnRun.disabled = false;
                btnRun.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.59-9.21l-3.23 3.23"></path>
                    </svg>
                    Ejecutar Nuevamente
                `;
                
                // Scroll to bottom of console
                consoleOutput.scrollTop = consoleOutput.scrollHeight;
            }
        });
    }
};
