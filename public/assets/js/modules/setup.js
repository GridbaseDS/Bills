/**
 * GridBase Bills — Setup Wizard Module
 * Premium Multi-step Onboarding Interface
 */

export default {
    async render(container) {
        try {
            const data = await window.App.api('settings');
            const s = data;

            container.innerHTML = `
                <div class="setup-wizard-container" style="max-width:800px;margin:40px auto;padding:0 var(--spacing-md);">
                    <!-- Wizard Header -->
                    <div class="text-center mb-32">
                        <img src="https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png" alt="GridBase" style="height:36px;margin-bottom:16px;">
                        <h1 style="font-size:24px;font-weight:700;color:var(--color-text-primary);margin:0 0 8px;">Configuración Inicial del Sistema</h1>
                        <p style="color:var(--color-text-muted);font-size:14px;margin:0;">Completa los detalles de tu empresa para empezar a utilizar Gridbase Bills.</p>
                    </div>

                    <!-- Steps Progress Bar -->
                    <div class="setup-progress-bar mb-32" style="display:flex;justify-content:space-between;position:relative;margin:0 20px;">
                        <div style="position:absolute;top:15px;left:0;right:0;height:2px;background:var(--color-border);z-index:1;"></div>
                        <div id="setup-progress-line" style="position:absolute;top:15px;left:0;width:0%;height:2px;background:var(--color-primary);z-index:1;transition:width 0.3s ease;"></div>
                        
                        <div class="step-indicator active" data-step="1" style="position:relative;z-index:2;text-align:center;">
                            <div class="step-num" style="width:32px;height:32px;border-radius:50%;background:var(--color-primary);color:white;display:flex;align-items:center;justify-content:center;font-weight:600;margin:0 auto 8px;border:2px solid var(--color-background);transition:all 0.3s ease;">1</div>
                            <span style="font-size:11px;font-weight:600;color:var(--color-text-primary);">Empresa</span>
                        </div>
                        <div class="step-indicator" data-step="2" style="position:relative;z-index:2;text-align:center;">
                            <div class="step-num" style="width:32px;height:32px;border-radius:50%;background:var(--color-border);color:var(--color-text-muted);display:flex;align-items:center;justify-content:center;font-weight:600;margin:0 auto 8px;border:2px solid var(--color-background);transition:all 0.3s ease;">2</div>
                            <span style="font-size:11px;font-weight:600;color:var(--color-text-muted);">Finanzas</span>
                        </div>
                        <div class="step-indicator" data-step="3" style="position:relative;z-index:2;text-align:center;">
                            <div class="step-num" style="width:32px;height:32px;border-radius:50%;background:var(--color-border);color:var(--color-text-muted);display:flex;align-items:center;justify-content:center;font-weight:600;margin:0 auto 8px;border:2px solid var(--color-background);transition:all 0.3s ease;">3</div>
                            <span style="font-size:11px;font-weight:600;color:var(--color-text-muted);">Correo</span>
                        </div>
                        <div class="step-indicator" data-step="4" style="position:relative;z-index:2;text-align:center;">
                            <div class="step-num" style="width:32px;height:32px;border-radius:50%;background:var(--color-border);color:var(--color-text-muted);display:flex;align-items:center;justify-content:center;font-weight:600;margin:0 auto 8px;border:2px solid var(--color-background);transition:all 0.3s ease;">4</div>
                            <span style="font-size:11px;font-weight:600;color:var(--color-text-muted);">Notificaciones</span>
                        </div>
                        <div class="step-indicator" data-step="5" style="position:relative;z-index:2;text-align:center;">
                            <div class="step-num" style="width:32px;height:32px;border-radius:50%;background:var(--color-border);color:var(--color-text-muted);display:flex;align-items:center;justify-content:center;font-weight:600;margin:0 auto 8px;border:2px solid var(--color-background);transition:all 0.3s ease;">5</div>
                            <span style="font-size:11px;font-weight:600;color:var(--color-text-muted);">Integración / e-CF</span>
                        </div>
                    </div>

                    <!-- Wizard Form Container -->
                    <form id="setup-form" class="table-outer" style="background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-xl);box-shadow:var(--shadow-lg);padding:var(--spacing-xl);">
                        
                        <!-- STEP 1: EMPRESA -->
                        <div class="wizard-step" id="step-content-1">
                            <h3 style="font-size:16px;font-weight:600;margin:0 0 20px;display:flex;align-items:center;gap:8px;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>Identidad de la Empresa</h3>
                            
                            <!-- Logos Upload Section -->
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;background:var(--bg-hover);padding:16px;border-radius:var(--radius-lg);border:1px solid var(--color-border);">
                                <div>
                                    <label class="form-label" style="font-weight:600;">Logo para Interfaz</label>
                                    <input type="file" id="setup_logo" class="form-control" accept="image/*" style="padding:4px;">
                                    <div style="margin-top:8px;display:flex;align-items:center;gap:12px;">
                                        <div id="setup_logo_preview" style="height:40px;width:120px;border:1px dashed var(--color-border);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;background:${s.logo_capsule_theme === 'light' ? '#FFFFFF' : '#111827'};overflow:hidden;padding:4px;">
                                            ${s.company_logo ? `<img src="${s.company_logo}" style="max-height:100%;max-width:100%;object-fit:contain;">` : `<span style="font-size:10px;color:${s.logo_capsule_theme === 'light' ? 'var(--color-text-muted)' : '#9CA3AF'};">Sin logo</span>`}
                                        </div>
                                        <span style="font-size:10px;color:var(--color-text-muted);">Recomendado: PNG horizontal</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label" style="font-weight:600;">Favicon de Empresa</label>
                                    <input type="file" id="setup_favicon" class="form-control" accept="image/*" style="padding:4px;">
                                    <div style="margin-top:8px;display:flex;align-items:center;gap:12px;">
                                        <div id="setup_favicon_preview" style="height:40px;width:40px;border:1px dashed var(--color-border);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;background:${s.logo_capsule_theme === 'light' ? '#FFFFFF' : '#111827'};overflow:hidden;padding:4px;">
                                            ${s.company_favicon ? `<img src="${s.company_favicon}" style="max-height:100%;max-width:100%;object-fit:contain;">` : `<span style="font-size:10px;color:${s.logo_capsule_theme === 'light' ? 'var(--color-text-muted)' : '#9CA3AF'};">Sin fav</span>`}
                                        </div>
                                        <span style="font-size:10px;color:var(--color-text-muted);">Recomendado: 32x32px PNG</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Nombre Comercial <span style="color:var(--color-danger);">*</span></label><input type="text" id="setup_company_name" class="form-control" value="${s.company_name || ''}" required></div>
                                <div class="form-group"><label class="form-label">RNC / Cédula <span style="color:var(--color-danger);">*</span></label><input type="text" id="setup_company_tax_id" class="form-control" value="${s.company_tax_id || ''}" placeholder="Ej: 131234567" required></div>
                                <div class="form-group"><label class="form-label">Correo Oficial <span style="color:var(--color-danger);">*</span></label><input type="email" id="setup_company_email" class="form-control" value="${s.company_email || ''}" required></div>
                                <div class="form-group"><label class="form-label">Teléfono</label><input type="text" id="setup_company_phone" class="form-control" value="${s.company_phone || ''}"></div>
                                <div class="form-group" style="grid-column: span 2"><label class="form-label">Dirección</label><input type="text" id="setup_company_address" class="form-control" value="${s.company_address || ''}"></div>
                                <div class="form-group"><label class="form-label">Ciudad</label><input type="text" id="setup_company_city" class="form-control" value="${s.company_city || ''}"></div>
                                <div class="form-group"><label class="form-label">Sitio Web</label><input type="text" id="setup_company_website" class="form-control" value="${s.company_website || ''}"></div>
                                <div class="form-group" style="grid-column: span 2;">
                                    <label class="form-label" style="font-weight:600;">Fondo de la Cápsula del Logo</label>
                                    <select id="setup_logo_capsule_theme" class="form-control" style="width:240px;">
                                        <option value="dark" ${s.logo_capsule_theme === 'dark' || !s.logo_capsule_theme ? 'selected' : ''}>Oscura (Para logos claros)</option>
                                        <option value="light" ${s.logo_capsule_theme === 'light' ? 'selected' : ''}>Clara (Para logos oscuros)</option>
                                    </select>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Elige el fondo del recuadro del logo en la barra lateral y login para garantizar un contraste perfecto.</div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: FINANZAS -->
                        <div class="wizard-step" id="step-content-2" style="display:none;">
                            <h3 style="font-size:16px;font-weight:600;margin:0 0 20px;display:flex;align-items:center;gap:8px;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>Preferencias Financieras</h3>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Moneda por Defecto</label>
                                    <select id="setup_default_currency" class="form-control">
                                        <option value="DOP" ${s.default_currency === 'DOP' || !s.default_currency ? 'selected' : ''}>DOP - Peso Dominicano</option>
                                        <option value="USD" ${s.default_currency === 'USD' ? 'selected' : ''}>USD - Dólar Estadounidense</option>
                                        <option value="EUR" ${s.default_currency === 'EUR' ? 'selected' : ''}>EUR - Euro</option>
                                    </select>
                                </div>
                                <div class="form-group"><label class="form-label">Impuesto por Defecto (%)</label><input type="number" id="setup_default_tax_rate" class="form-control" value="${s.default_tax_rate || '18.00'}" step="0.01"></div>
                            </div>
                        </div>

                        <!-- STEP 3: CORREO SMTP -->
                        <div class="wizard-step" id="step-content-3" style="display:none;">
                            <h3 style="font-size:16px;font-weight:600;margin:0 0 8px;display:flex;align-items:center;gap:8px;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>Servidor de Correo SMTP</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 20px;">Configura tu servidor de correo para que las facturas y recordatorios salgan desde tu dominio oficial.</p>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Host SMTP</label><input type="text" id="setup_smtp_host" class="form-control" placeholder="mail.miempresa.com" value="${s.smtp_host || ''}"></div>
                                <div class="form-group"><label class="form-label">Puerto SMTP</label><input type="number" id="setup_smtp_port" class="form-control" placeholder="587" value="${s.smtp_port || '587'}"></div>
                                <div class="form-group"><label class="form-label">Usuario SMTP</label><input type="text" id="setup_smtp_username" class="form-control" placeholder="hola@miempresa.com" value="${s.smtp_username || ''}"></div>
                                <div class="form-group"><label class="form-label">Contraseña SMTP</label><input type="password" id="setup_smtp_password" class="form-control" value="${s.smtp_password || ''}"></div>
                                <div class="form-group"><label class="form-label">Cifrado</label>
                                    <select id="setup_smtp_encryption" class="form-control">
                                        <option value="none" ${!s.smtp_encryption || s.smtp_encryption === 'none' || s.smtp_encryption === 'null' ? 'selected' : ''}>Ninguno</option>
                                        <option value="tls" ${s.smtp_encryption === 'tls' || !s.smtp_encryption ? 'selected' : ''}>TLS (Recomendado)</option>
                                        <option value="ssl" ${s.smtp_encryption === 'ssl' ? 'selected' : ''}>SSL</option>
                                    </select>
                                </div>
                                <div class="form-group"><label class="form-label">Nombre de Remitente</label><input type="text" id="setup_smtp_from_name" class="form-control" placeholder="Ej: Facturación Grupo" value="${s.smtp_from_name || ''}"></div>
                                <div class="form-group" style="grid-column: span 2;"><label class="form-label">Email de Remitente (From)</label><input type="email" id="setup_smtp_from_email" class="form-control" placeholder="facturacion@miempresa.com" value="${s.smtp_from_email || ''}"></div>
                            </div>
                            <div style="margin-top:20px;background:var(--bg-hover);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:16px;">
                                <h4 style="font-size:13px;font-weight:600;margin:0 0 10px;">Probar Conexión SMTP</h4>
                                <div style="display:flex;gap:12px;align-items:flex-end;">
                                    <div class="form-group" style="margin:0;flex:1;max-width:300px;"><label class="form-label" style="font-size:11px;">Enviar correo de prueba a:</label><input type="email" id="setup_smtp_test_email" class="form-control" placeholder="tu@correo.com" value="${window.App.state.user?.email || ''}"></div>
                                    <button type="button" id="setup_btn-test-smtp" class="btn btn-secondary">Probar Conexión</button>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 4: WHATSAPP Y RECORDATORIOS -->
                        <div class="wizard-step" id="step-content-4" style="display:none;">
                            <h3 style="font-size:16px;font-weight:600;margin:0 0 8px;display:flex;align-items:center;gap:8px;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>WhatsApp Cloud y Recordatorios</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 20px;">Configura el envío de alertas automáticas vía WhatsApp Cloud API y recordatorios automáticos de facturas vencidas.</p>
                            
                            <h4 style="font-size:14px;font-weight:600;margin:0 0 12px;color:var(--color-text-primary);">WhatsApp Cloud API (Meta)</h4>
                            <div class="form-group mb-16"><label class="form-label">Habilitar Integración</label>
                                <select id="setup_whatsapp_enabled" class="form-control" style="width:200px;">
                                    <option value="1" ${s.whatsapp_enabled == '1' ? 'selected' : ''}>Habilitado</option>
                                    <option value="0" ${s.whatsapp_enabled == '0' || !s.whatsapp_enabled ? 'selected' : ''}>Deshabilitado</option>
                                </select>
                            </div>
                            <div class="grid-2 mb-24">
                                <div class="form-group"><label class="form-label">ID del Número de Teléfono</label><input type="text" id="setup_whatsapp_phone_id" class="form-control" value="${s.whatsapp_phone_id || ''}"></div>
                                <div class="form-group"><label class="form-label">ID de Cuenta Business</label><input type="text" id="setup_whatsapp_business_id" class="form-control" value="${s.whatsapp_business_id || ''}"></div>
                                <div class="form-group" style="grid-column: span 2"><label class="form-label">Token de Acceso Permanente</label><input type="password" id="setup_whatsapp_access_token" class="form-control" value="${s.whatsapp_access_token || ''}"></div>
                            </div>

                            <h4 style="font-size:14px;font-weight:600;margin:24px 0 12px;border-top:1px solid var(--color-border);padding-top:20px;color:var(--color-text-primary);">Recordatorios de Pago Automáticos</h4>
                            <div class="form-group mb-16"><label class="form-label">Habilitar Recordatorios</label>
                                <select id="setup_reminders_enabled" class="form-control" style="width:200px;">
                                    <option value="1" ${s.reminders_enabled == '1' || !s.reminders_enabled ? 'selected' : ''}>Habilitado</option>
                                    <option value="0" ${s.reminders_enabled == '0' ? 'selected' : ''}>Deshabilitado</option>
                                </select>
                            </div>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Días Antes de Vencer</label><input type="number" id="setup_reminders_days_before" class="form-control" value="${s.reminders_days_before !== undefined ? s.reminders_days_before : '3'}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Ej: 3 días antes de la fecha de vencimiento</div></div>
                                <div class="form-group"><label class="form-label">Frecuencia al Vencer (Días)</label><input type="number" id="setup_reminders_overdue_interval" class="form-control" value="${s.reminders_overdue_interval !== undefined ? s.reminders_overdue_interval : '7'}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Ej: Cada 7 días después de estar vencida</div></div>
                            </div>
                        </div>

                        <!-- STEP 5: INTEGRACIONES Y e-CF / DGII -->
                        <div class="wizard-step" id="step-content-5" style="display:none;">
                            <h3 style="font-size:16px;font-weight:600;margin:0 0 8px;display:flex;align-items:center;gap:8px;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>Integraciones y Factura Electrónica e-CF (DGII)</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 20px;">Configura tus pasarelas de pago y las firmas e-CF para emitir facturación electrónica dominicana homologada.</p>
                            
                            <h4 style="font-size:14px;font-weight:600;margin:0 0 12px;color:var(--color-text-primary);">Integración de Cobros</h4>
                            <div class="form-group"><label class="form-label">Enlace de Pago General</label><input type="url" id="setup_payment_link_general" class="form-control" placeholder="https://paypal.me/miempresa" value="${s.payment_link_general || ''}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Enlace de cobro (Paypal, Stripe) que irá en los correos y PDFs.</div></div>
                            <div class="form-group"><label class="form-label">Instrucciones de Transferencia Bancaria</label><textarea id="setup_bank_instructions" class="form-control" rows="3" placeholder="Ej: Transferir a Banco XYZ\nCuenta corriente: 123456789\nRNC: 131-23456-7">${s.bank_instructions || ''}</textarea></div>

                            <h4 style="font-size:14px;font-weight:600;margin:24px 0 12px;border-top:1px solid var(--color-border);padding-top:20px;color:var(--color-text-primary);">Firma Digital y Conectividad DGII (e-CF)</h4>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Entorno DGII</label>
                                    <select id="setup_dgii_env" class="form-control">
                                        <option value="testing" ${s.dgii_env === 'testing' || !s.dgii_env ? 'selected' : ''}>Pre-Certificación (testecf)</option>
                                        <option value="certification" ${s.dgii_env === 'certification' ? 'selected' : ''}>Certificación (certecf)</option>
                                        <option value="production" ${s.dgii_env === 'production' ? 'selected' : ''}>Producción (ecf)</option>
                                    </select>
                                </div>
                                <div class="form-group"><label class="form-label">Vence Secuencia (e-NCF)</label><input type="date" id="setup_dgii_ncf_expiry_date" class="form-control" value="${s.dgii_ncf_expiry_date || '2028-12-31'}"></div>
                                <div class="form-group"><label class="form-label">Archivo Certificado (.p12 / .pfx)</label><input type="text" id="setup_dgii_certificate_path" class="form-control" placeholder="certificado.p12" value="${s.dgii_certificate_path || ''}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Nombre del certificado que subirás a <code>storage/app/secure/</code></div></div>
                                <div class="form-group"><label class="form-label">Contraseña del Certificado</label><input type="password" id="setup_dgii_certificate_password" class="form-control" value="${s.dgii_certificate_password || ''}"></div>
                                <div class="form-group"><label class="form-label">Próximo e-NCF Tipo 31 (Facturas de Crédito)</label><input type="number" id="setup_dgii_next_e_ncf_31" class="form-control" value="${s.dgii_next_e_ncf_31 || '1'}"></div>
                                <div class="form-group"><label class="form-label">Próximo e-NCF Tipo 32 (Facturas de Consumo)</label><input type="number" id="setup_dgii_next_e_ncf_32" class="form-control" value="${s.dgii_next_e_ncf_32 || '1'}"></div>
                            </div>
                        </div>

                        <!-- Wizard Footer Actions -->
                        <div style="border-top:1px solid var(--color-border);padding-top:20px;margin-top:32px;display:flex;justify-content:space-between;align-items:center;">
                            <button type="button" id="wizard-prev" class="btn btn-secondary" style="visibility:hidden;min-width:100px;">Atrás</button>
                            <button type="button" id="wizard-next" class="btn btn-primary" style="min-width:120px;">Siguiente</button>
                            <button type="submit" id="wizard-submit" class="btn btn-success" style="display:none;min-width:140px;">Finalizar Configuración</button>
                        </div>
                    </form>
                </div>
            `;

            // Active State
            let currentStep = 1;
            const totalSteps = 5;

            // Form references
            const form = container.querySelector('#setup-form');
            const prevBtn = container.querySelector('#wizard-prev');
            const nextBtn = container.querySelector('#wizard-next');
            const submitBtn = container.querySelector('#wizard-submit');
            const progressLine = container.querySelector('#setup-progress-line');
            const indicators = container.querySelectorAll('.step-indicator');

            // Handle Logo & Favicon Files and convert them to base64
            let companyLogoBase64 = s.company_logo || '';
            let companyFaviconBase64 = s.company_favicon || '';

            const handleFile = (inputEl, previewEl, callback) => {
                inputEl.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (!file) return;
                    
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const base64 = event.target.result;
                        previewEl.innerHTML = `<img src="${base64}" style="max-height:100%;max-width:100%;object-fit:contain;">`;
                        callback(base64);
                    };
                    reader.readAsDataURL(file);
                });
            };

            handleFile(
                container.querySelector('#setup_logo'),
                container.querySelector('#setup_logo_preview'),
                (base64) => { companyLogoBase64 = base64; }
            );

            handleFile(
                container.querySelector('#setup_favicon'),
                container.querySelector('#setup_favicon_preview'),
                (base64) => { companyFaviconBase64 = base64; }
            );

            // Handle Capsule Theme Change and instantly update previews
            const capsuleThemeSelect = container.querySelector('#setup_logo_capsule_theme');
            capsuleThemeSelect?.addEventListener('change', (e) => {
                const isLight = e.target.value === 'light';
                const bg = isLight ? '#FFFFFF' : '#111827';
                const borderColor = isLight ? 'var(--color-border)' : 'rgba(255,255,255,0.08)';
                const labelColor = isLight ? 'var(--color-text-muted)' : '#9CA3AF';
                
                const logoPreview = container.querySelector('#setup_logo_preview');
                if (logoPreview) {
                    logoPreview.style.background = bg;
                    logoPreview.style.borderColor = borderColor;
                    const span = logoPreview.querySelector('span');
                    if (span) span.style.color = labelColor;
                }
                const favPreview = container.querySelector('#setup_favicon_preview');
                if (favPreview) {
                    favPreview.style.background = bg;
                    favPreview.style.borderColor = borderColor;
                    const span = favPreview.querySelector('span');
                    if (span) span.style.color = labelColor;
                }
            });

            // RNC Lookup in step 1
            const rncInput = container.querySelector('#setup_company_tax_id');
            rncInput?.addEventListener('input', async (e) => {
                const val = e.target.value.replace(/[^0-9]/g, '');
                if (val.length === 9 || val.length === 11) {
                    if (e.target.dataset.lastFetch === val) return;
                    e.target.dataset.lastFetch = val;
                    const isRnc = val.length === 9;
                    const endpoint = isRnc ? 'rnc' : 'cedula';
                    try {
                        window.App.showToast('Buscando identificación...', 'info');
                        const res = await window.App.api(`lookup/${endpoint}/${val}`);
                        if (res.found && res.data) {
                            const d = res.data;
                            if (isRnc) {
                                if (d.nombre) container.querySelector('#setup_company_name').value = d.nombre;
                            } else {
                                const fullName = `${d.nombres} ${d.apellido1} ${d.apellido2}`.trim();
                                if (!container.querySelector('#setup_company_name').value) {
                                    container.querySelector('#setup_company_name').value = fullName;
                                }
                            }
                            window.App.showToast('Información autocompletada', 'success');
                        }
                    } catch (err) {
                        window.App.showToast('Identificación no encontrada en DGII', 'error');
                    }
                }
            });

            // Update Progress UI
            const updateProgress = () => {
                // Show/hide steps
                for (let i = 1; i <= totalSteps; i++) {
                    const stepEl = container.querySelector(`#step-content-${i}`);
                    if (stepEl) stepEl.style.display = (i === currentStep) ? 'block' : 'none';
                }

                // Progress indicators
                indicators.forEach(ind => {
                    const stepNum = parseInt(ind.dataset.step);
                    const circle = ind.querySelector('.step-num');
                    ind.classList.remove('active');
                    
                    if (stepNum < currentStep) {
                        circle.style.background = 'var(--color-success)';
                        circle.style.borderColor = 'var(--color-success)';
                        circle.style.color = 'white';
                        circle.innerHTML = '✓';
                    } else if (stepNum === currentStep) {
                        ind.classList.add('active');
                        circle.style.background = 'var(--color-primary)';
                        circle.style.borderColor = 'var(--color-primary)';
                        circle.style.color = 'white';
                        circle.innerHTML = stepNum;
                    } else {
                        circle.style.background = 'var(--color-border)';
                        circle.style.borderColor = 'var(--color-border)';
                        circle.style.color = 'var(--color-text-muted)';
                        circle.innerHTML = stepNum;
                    }
                });

                // Progress line
                const percent = ((currentStep - 1) / (totalSteps - 1)) * 100;
                progressLine.style.width = `${percent}%`;

                // Footer Buttons
                prevBtn.style.visibility = (currentStep === 1) ? 'hidden' : 'visible';
                if (currentStep === totalSteps) {
                    nextBtn.style.display = 'none';
                    submitBtn.style.display = 'block';
                } else {
                    nextBtn.style.display = 'block';
                    submitBtn.style.display = 'none';
                }
            };

            // Button Event Handlers
            nextBtn.addEventListener('click', () => {
                // Perform simple validation of the current step fields
                if (currentStep === 1) {
                    const name = container.querySelector('#setup_company_name').value.trim();
                    const taxId = container.querySelector('#setup_company_tax_id').value.trim();
                    const email = container.querySelector('#setup_company_email').value.trim();
                    if (!name || !taxId || !email) {
                        return window.App.showToast('Por favor, rellena los campos requeridos (*)', 'error');
                    }
                }

                if (currentStep < totalSteps) {
                    currentStep++;
                    updateProgress();
                }
            });

            prevBtn.addEventListener('click', () => {
                if (currentStep > 1) {
                    currentStep--;
                    updateProgress();
                }
            });

            // Test SMTP in step 3
            container.querySelector('#setup_btn-test-smtp').addEventListener('click', async () => {
                const btn = container.querySelector('#setup_btn-test-smtp');
                const testEmail = container.querySelector('#setup_smtp_test_email').value;
                if (!testEmail) return window.App.showToast('Ingresa un correo de prueba', 'error');
                const encryptionVal = container.querySelector('#setup_smtp_encryption').value;
                const payload = {
                    test_email: testEmail,
                    host: container.querySelector('#setup_smtp_host').value || 'localhost',
                    port: container.querySelector('#setup_smtp_port').value || '25',
                    username: container.querySelector('#setup_smtp_username').value,
                    password: container.querySelector('#setup_smtp_password').value,
                    encryption: encryptionVal === 'none' ? null : encryptionVal,
                    from_name: container.querySelector('#setup_smtp_from_name').value,
                    from_email: container.querySelector('#setup_smtp_from_email').value
                };
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span>';
                btn.disabled = true;
                try {
                    const res = await window.App.api('settings/test-smtp', { method: 'POST', body: payload });
                    window.App.showToast(res.message, 'success');
                } catch(err) {
                    // Toast error shown automatically by API handler
                } finally {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });

            // Handle Wizard Submit and Save all settings
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const settingsToUpdate = {
                    company_name: container.querySelector('#setup_company_name').value,
                    company_email: container.querySelector('#setup_company_email').value,
                    company_phone: container.querySelector('#setup_company_phone').value,
                    company_tax_id: container.querySelector('#setup_company_tax_id').value,
                    company_address: container.querySelector('#setup_company_address').value,
                    company_city: container.querySelector('#setup_company_city').value,
                    company_website: container.querySelector('#setup_company_website').value,
                    company_logo: companyLogoBase64,
                    company_favicon: companyFaviconBase64,
                    logo_capsule_theme: container.querySelector('#setup_logo_capsule_theme').value,
                    default_currency: container.querySelector('#setup_default_currency').value,
                    default_tax_rate: container.querySelector('#setup_default_tax_rate').value,
                    smtp_host: container.querySelector('#setup_smtp_host').value,
                    smtp_port: container.querySelector('#setup_smtp_port').value,
                    smtp_username: container.querySelector('#setup_smtp_username').value,
                    smtp_password: container.querySelector('#setup_smtp_password').value,
                    smtp_encryption: container.querySelector('#setup_smtp_encryption').value,
                    smtp_from_name: container.querySelector('#setup_smtp_from_name').value,
                    smtp_from_email: container.querySelector('#setup_smtp_from_email').value,
                    whatsapp_enabled: container.querySelector('#setup_whatsapp_enabled').value,
                    whatsapp_phone_id: container.querySelector('#setup_whatsapp_phone_id').value,
                    whatsapp_business_id: container.querySelector('#setup_whatsapp_business_id').value,
                    whatsapp_access_token: container.querySelector('#setup_whatsapp_access_token').value,
                    reminders_enabled: container.querySelector('#setup_reminders_enabled').value,
                    reminders_days_before: container.querySelector('#setup_reminders_days_before').value,
                    reminders_overdue_interval: container.querySelector('#setup_reminders_overdue_interval').value,
                    payment_link_general: container.querySelector('#setup_payment_link_general').value,
                    bank_instructions: container.querySelector('#setup_bank_instructions').value,
                    dgii_env: container.querySelector('#setup_dgii_env').value,
                    dgii_ncf_expiry_date: container.querySelector('#setup_dgii_ncf_expiry_date').value,
                    dgii_certificate_path: container.querySelector('#setup_dgii_certificate_path').value,
                    dgii_certificate_password: container.querySelector('#setup_dgii_certificate_password').value,
                    dgii_next_e_ncf_31: container.querySelector('#setup_dgii_next_e_ncf_31').value,
                    dgii_next_e_ncf_32: container.querySelector('#setup_dgii_next_e_ncf_32').value,
                    is_installed: '1' // MARK AS INSTALLED!
                };

                try {
                    window.App.showToast('Guardando configuración inicial...', 'info');
                    await window.App.api('settings', { method: 'POST', body: settingsToUpdate });
                    
                    // Cache branding elements in localStorage
                    localStorage.setItem('logo_capsule_theme', settingsToUpdate.logo_capsule_theme);
                    if (settingsToUpdate.company_logo) localStorage.setItem('company_logo', settingsToUpdate.company_logo);
                    if (settingsToUpdate.company_favicon) localStorage.setItem('company_favicon', settingsToUpdate.company_favicon);
                    
                    window.App.showToast('¡Configuración completada con éxito!', 'success');
                    
                    // Restart app checkAuth to reload interface and boot normal shell!
                    setTimeout(() => {
                        window.location.href = '/inicio';
                    }, 1000);
                } catch(err) {
                    window.App.showToast('Error al guardar la configuración: ' + err.message, 'error');
                }
            });

            // Initialize Progress
            updateProgress();

        } catch (e) {
            container.innerHTML = `<div class="text-red" style="text-align:center;padding:40px;">Error al cargar el asistente de configuración.</div>`;
        }
    }
};
