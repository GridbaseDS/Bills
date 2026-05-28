export default {
    async render(container) {
        try {
            const data = await window.App.api('settings');
            const s = data;

            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Configuración</h1>
                        <p class="page-subtitle">Ajustes del sistema y conectividad</p>
                    </div>
                </div>

                <div class="segmented-control mb-24" id="settings-tabs">
                    <button class="segment-item active" data-tab="general">General</button>
                    <button class="segment-item" data-tab="apariencia">Apariencia PDF</button>
                    <button class="segment-item" data-tab="email">Email / SMTP</button>
                    <button class="segment-item" data-tab="whatsapp">WhatsApp</button>
                    <button class="segment-item" data-tab="automation">Recordatorios</button>
                    <button class="segment-item" data-tab="integrations">Integraciones</button>
                    <button class="segment-item" data-tab="dgii">e-CF / DGII</button>
                    <button class="segment-item" data-tab="support" style="color:var(--color-danger);">Soporte</button>
                </div>

                <form id="settings-form" class="table-outer">
                    <div style="padding:var(--spacing-xl);">

                        <!-- TAB: GENERAL -->
                        <div class="tab-content" id="tab-general">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 16px;">Información de la Empresa</h3>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Nombre Comercial</label><input type="text" id="s_company_name" class="form-control" value="${s.company_name || ''}"></div>
                                <div class="form-group"><label class="form-label">Correo Oficial</label><input type="email" id="s_company_email" class="form-control" value="${s.company_email || ''}"></div>
                                <div class="form-group"><label class="form-label">Teléfono</label><input type="text" id="s_company_phone" class="form-control" value="${s.company_phone || ''}"></div>
                                <div class="form-group"><label class="form-label">RNC / Cédula</label><input type="text" id="s_company_tax_id" class="form-control" value="${s.company_tax_id || ''}"></div>
                                <div class="form-group" style="grid-column: span 2"><label class="form-label">Dirección</label><input type="text" id="s_company_address" class="form-control" value="${s.company_address || ''}"></div>
                                <div class="form-group"><label class="form-label">Ciudad</label><input type="text" id="s_company_city" class="form-control" value="${s.company_city || ''}"></div>
                                <div class="form-group"><label class="form-label">Sitio Web</label><input type="text" id="s_company_website" class="form-control" value="${s.company_website || ''}"></div>
                                <div class="form-group" style="grid-column: span 2;">
                                    <label class="form-label">Fondo de la Cápsula del Logo</label>
                                    <select id="s_logo_capsule_theme" class="form-control" style="width: 240px;">
                                        <option value="dark" ${s.logo_capsule_theme === 'dark' || !s.logo_capsule_theme ? 'selected' : ''}>Oscura (Para logos claros)</option>
                                        <option value="light" ${s.logo_capsule_theme === 'light' ? 'selected' : ''}>Clara (Para logos oscuros)</option>
                                    </select>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Define el color de fondo del recuadro protector del logotipo en la interfaz.</div>
                                </div>
                            </div>
                            <h3 style="font-size:15px;font-weight:600;margin:24px 0 16px;border-top:1px solid var(--color-border);padding-top:24px;">Ajustes de Facturación</h3>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Moneda por Defecto</label>
                                    <select id="s_default_currency" class="form-control">
                                        <option value="USD" ${s.default_currency === 'USD' ? 'selected' : ''}>USD</option>
                                        <option value="DOP" ${s.default_currency === 'DOP' ? 'selected' : ''}>DOP</option>
                                        <option value="EUR" ${s.default_currency === 'EUR' ? 'selected' : ''}>EUR</option>
                                    </select>
                                </div>
                                <div class="form-group"><label class="form-label">Impuesto por Defecto (%)</label><input type="number" id="s_default_tax_rate" class="form-control" value="${s.default_tax_rate || '18.00'}" step="0.01"></div>
                            </div>
                        </div>

                        <!-- TAB: APARIENCIA PDF -->
                        <div class="tab-content" id="tab-apariencia" style="display:none;">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 8px;">Línea Gráfica del PDF</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 24px;">Personaliza los colores, logo y estilo de tus facturas y cotizaciones en PDF.</p>
                            
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Color Primario</label>
                                    <div style="display:flex;gap:10px;align-items:center;">
                                        <input type="color" id="s_pdf_primary_color" value="${s.pdf_primary_color || '#0B484C'}" style="width:48px;height:38px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                        <input type="text" id="s_pdf_primary_color_hex" class="form-control" value="${s.pdf_primary_color || '#0B484C'}" style="width:120px;font-family:monospace;" maxlength="7">
                                    </div>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Header, footer, títulos y encabezados de sección</div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Color de Acento</label>
                                    <div style="display:flex;gap:10px;align-items:center;">
                                        <input type="color" id="s_pdf_accent_color" value="${s.pdf_accent_color || '#00DF83'}" style="width:48px;height:38px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                        <input type="text" id="s_pdf_accent_color_hex" class="form-control" value="${s.pdf_accent_color || '#00DF83'}" style="width:120px;font-family:monospace;" maxlength="7">
                                    </div>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Encabezado de tabla de ítems, etiquetas y acentos</div>
                                </div>
                                <div class="form-group" style="grid-column: span 2;">
                                    <label class="form-label">URL del Logo</label>
                                    <input type="url" id="s_pdf_logo_url" class="form-control" placeholder="https://miempresa.com/logo.png" value="${s.pdf_logo_url || ''}">
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Imagen que aparece en la esquina superior izquierda del PDF. Dejar vacío para usar el logo por defecto.</div>
                                    <div id="pdf-logo-preview" style="margin-top:10px;display:${s.pdf_logo_url ? 'block' : 'none'};">
                                        <img id="pdf-logo-preview-img" src="${s.pdf_logo_url || ''}" style="max-height:50px;max-width:200px;border:1px solid var(--color-border);border-radius:var(--radius-md);padding:6px;background:#f9f9f9;" onerror="this.style.display='none'" onload="this.style.display='inline-block'">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Mostrar Footer</label>
                                    <select id="s_pdf_show_footer" class="form-control" style="width:200px;">
                                        <option value="1" ${s.pdf_show_footer === '0' ? '' : 'selected'}>Sí — Mostrar</option>
                                        <option value="0" ${s.pdf_show_footer === '0' ? 'selected' : ''}>No — Ocultar</option>
                                    </select>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Barra con datos de contacto al pie del PDF</div>
                                </div>
                            </div>

                            <!-- Live Preview -->
                            <div style="margin-top:28px;border-top:1px solid var(--color-border);padding-top:24px;">
                                <h4 style="font-size:13px;font-weight:600;margin:0 0 14px;">Vista Previa</h4>
                                <div id="pdf-preview-card" style="border-radius:var(--radius-lg);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.10);max-width:520px;">
                                    <div id="pdf-preview-header" style="background:${s.pdf_primary_color || '#0B484C'};padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
                                        <div>
                                            <img id="pdf-preview-logo" src="${s.pdf_logo_url || 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png'}" style="height:32px;" onerror="this.style.display='none'">
                                        </div>
                                        <div style="text-align:right;">
                                            <div style="font-size:9px;letter-spacing:0.5px;"><span id="pdf-preview-label" style="color:${s.pdf_accent_color || '#00DF83'};font-weight:700;">E-NCF/</span> <span style="color:#fff;font-weight:700;">E310000000001</span></div>
                                            <div style="font-size:9px;margin-top:3px;"><span style="color:${s.pdf_accent_color || '#00DF83'};font-weight:700;">FECHA/</span> <span style="color:#fff;font-weight:700;">27/05/2026</span></div>
                                        </div>
                                    </div>
                                    <div style="padding:16px 24px;background:#fff;">
                                        <div id="pdf-preview-title" style="font-size:14px;font-weight:700;color:${s.pdf_primary_color || '#0B484C'};text-transform:uppercase;border-bottom:2px solid ${s.pdf_primary_color || '#0B484C'};display:inline-block;padding-bottom:3px;margin-bottom:12px;">FACTURA</div>
                                        <table style="width:100%;border-collapse:collapse;">
                                            <thead><tr style="background:${s.pdf_accent_color || '#00DF83'};"><th style="color:${s.pdf_primary_color || '#0B484C'};font-size:8px;text-transform:uppercase;padding:6px 8px;text-align:left;font-weight:700;">Descripción</th><th style="color:${s.pdf_primary_color || '#0B484C'};font-size:8px;text-transform:uppercase;padding:6px 8px;text-align:right;font-weight:700;">Total</th></tr></thead>
                                            <tbody><tr style="border-bottom:1px solid #E8E8E8;"><td style="padding:6px 8px;font-size:10px;color:#444;">Servicio ejemplo</td><td style="padding:6px 8px;font-size:10px;text-align:right;font-weight:600;">DOP 5,000.00</td></tr></tbody>
                                        </table>
                                    </div>
                                    <div id="pdf-preview-footer" style="background:${s.pdf_primary_color || '#0B484C'};padding:8px 24px;text-align:center;font-size:9px;color:rgba(255,255,255,0.85);${s.pdf_show_footer === '0' ? 'display:none;' : ''}">gridbase.com.do <span style="color:${s.pdf_accent_color || '#00DF83'};margin:0 6px;">•</span> info@gridbase.com.do</div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: EMAIL/SMTP -->
                        <div class="tab-content" id="tab-email" style="display:none;">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 8px;">Configuración de Correo SMTP</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 24px;">Ajustes para enviar facturas y cotizaciones por correo.</p>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Host SMTP</label><input type="text" id="s_smtp_host" class="form-control" placeholder="mail.midominio.com" value="${s.smtp_host || ''}"></div>
                                <div class="form-group"><label class="form-label">Puerto SMTP</label><input type="number" id="s_smtp_port" class="form-control" placeholder="587" value="${s.smtp_port || '587'}"></div>
                                <div class="form-group"><label class="form-label">Usuario SMTP</label><input type="text" id="s_smtp_username" class="form-control" placeholder="usuario@midominio.com" value="${s.smtp_username || ''}"></div>
                                <div class="form-group"><label class="form-label">Contraseña SMTP</label><input type="password" id="s_smtp_password" class="form-control" value="${s.smtp_password || ''}"></div>
                                <div class="form-group"><label class="form-label">Cifrado</label>
                                    <select id="s_smtp_encryption" class="form-control">
                                        <option value="none" ${!s.smtp_encryption || s.smtp_encryption === 'none' || s.smtp_encryption === 'null' ? 'selected' : ''}>Ninguno</option>
                                        <option value="tls" ${s.smtp_encryption === 'tls' ? 'selected' : ''}>TLS</option>
                                        <option value="ssl" ${s.smtp_encryption === 'ssl' ? 'selected' : ''}>SSL</option>
                                    </select>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Para localhost usa "Ninguno"</div>
                                </div>
                                <div class="form-group"><label class="form-label">Nombre de Remitente</label><input type="text" id="s_smtp_from_name" class="form-control" value="${s.smtp_from_name || ''}"></div>
                                <div class="form-group"><label class="form-label">Email de Remitente (From)</label><input type="email" id="s_smtp_from_email" class="form-control" value="${s.smtp_from_email || ''}"></div>
                            </div>
                            <div style="margin-top:24px;background:var(--bg-hover);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:20px;">
                                <h4 style="font-size:13px;font-weight:600;margin:0 0 12px;">Probar Conexión</h4>
                                <div style="display:flex;gap:12px;align-items:flex-end;">
                                    <div class="form-group" style="margin:0;flex:1;max-width:300px;"><label class="form-label">Enviar correo de prueba a:</label><input type="email" id="smtp_test_email" class="form-control" placeholder="tu@correo.com" value="${window.App.state.user?.email || ''}"></div>
                                    <button type="button" id="btn-test-smtp" class="btn btn-secondary">Probar Conexión</button>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: WHATSAPP -->
                        <div class="tab-content" id="tab-whatsapp" style="display:none;">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 8px;">API de WhatsApp Cloud</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 24px;">Conecta con Meta para enviar notificaciones vía WhatsApp.</p>
                            <div class="form-group mb-24"><label class="form-label">Habilitar Integración</label>
                                <select id="s_whatsapp_enabled" class="form-control" style="width:200px;">
                                    <option value="1" ${s.whatsapp_enabled == '1' ? 'selected' : ''}>Habilitado</option>
                                    <option value="0" ${s.whatsapp_enabled == '0' || !s.whatsapp_enabled ? 'selected' : ''}>Deshabilitado</option>
                                </select>
                            </div>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">ID del Número de Teléfono</label><input type="text" id="s_whatsapp_phone_id" class="form-control" value="${s.whatsapp_phone_id || ''}"></div>
                                <div class="form-group"><label class="form-label">ID de Cuenta Business</label><input type="text" id="s_whatsapp_business_id" class="form-control" value="${s.whatsapp_business_id || ''}"></div>
                                <div class="form-group" style="grid-column: span 2"><label class="form-label">Token de Acceso</label><input type="password" id="s_whatsapp_access_token" class="form-control" value="${s.whatsapp_access_token || ''}"></div>
                            </div>
                        </div>

                        <!-- TAB: RECORDATORIOS -->
                        <div class="tab-content" id="tab-automation" style="display:none;">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 8px;">Recordatorios Automatizados</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 24px;">Configura cuándo enviar recordatorios de pago.</p>
                            <div class="form-group mb-24"><label class="form-label">Habilitar Recordatorios</label>
                                <select id="s_reminders_enabled" class="form-control" style="width:200px;">
                                    <option value="1" ${s.reminders_enabled == '1' || !s.reminders_enabled ? 'selected' : ''}>Habilitado</option>
                                    <option value="0" ${s.reminders_enabled == '0' ? 'selected' : ''}>Deshabilitado</option>
                                </select>
                            </div>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Días Antes de Vencer</label><input type="number" id="s_reminders_days_before" class="form-control" value="${s.reminders_days_before !== undefined ? s.reminders_days_before : '3'}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Ej: 3 días antes</div></div>
                                <div class="form-group"><label class="form-label">Frecuencia al Vencer (Días)</label><input type="number" id="s_reminders_overdue_interval" class="form-control" value="${s.reminders_overdue_interval !== undefined ? s.reminders_overdue_interval : '7'}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Ej: cada 7 días</div></div>
                            </div>
                        </div>

                        <!-- TAB: INTEGRACIONES -->
                        <div class="tab-content" id="tab-integrations" style="display:none;">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 8px;">Integraciones de Pagos</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 24px;">Links de pago adjuntados a correos de facturas.</p>
                            <div class="form-group"><label class="form-label">Enlace de Pago General</label><input type="url" id="s_payment_link_general" class="form-control" placeholder="https://paypal.me/tuusuario" value="${s.payment_link_general || ''}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Se mostrará en correos y PDFs</div></div>
                            <div class="form-group"><label class="form-label">Instrucciones de Transferencia</label><textarea id="s_bank_instructions" class="form-control" rows="4" placeholder="Banco XYZ\nCuenta: 123456789">${s.bank_instructions || ''}</textarea></div>
                        </div>

                        <!-- TAB: DGII -->
                        <div class="tab-content" id="tab-dgii" style="display:none;">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 8px;">Facturación Electrónica (DGII)</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 24px;">Credenciales de firma y secuenciación e-CF.</p>
                            <div class="grid-2">
                                <div class="form-group"><label class="form-label">Entorno DGII</label>
                                    <select id="s_dgii_env" class="form-control">
                                        <option value="testing" ${s.dgii_env === 'testing' || !s.dgii_env ? 'selected' : ''}>Certificación / Pruebas</option>
                                        <option value="production" ${s.dgii_env === 'production' ? 'selected' : ''}>Producción</option>
                                    </select>
                                </div>
                                <div class="form-group"><label class="form-label">Vence Secuencia (e-NCF)</label><input type="date" id="s_dgii_ncf_expiry_date" class="form-control" value="${s.dgii_ncf_expiry_date || '2028-12-31'}"></div>
                                <div class="form-group"><label class="form-label">Certificado (.p12 / .pfx)</label><input type="text" id="s_dgii_certificate_path" class="form-control" placeholder="certificado.p12" value="${s.dgii_certificate_path || ''}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">En <code>storage/app/secure/</code></div></div>
                                <div class="form-group"><label class="form-label">Contraseña del Certificado</label><input type="password" id="s_dgii_certificate_password" class="form-control" value="${s.dgii_certificate_password || ''}"></div>
                                <div class="form-group"><label class="form-label">Próximo e-NCF Tipo 31</label><input type="number" id="s_dgii_next_e_ncf_31" class="form-control" value="${s.dgii_next_e_ncf_31 || '1'}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Ej: 1 → <code>E310000000001</code></div></div>
                                <div class="form-group"><label class="form-label">Próximo e-NCF Tipo 32</label><input type="number" id="s_dgii_next_e_ncf_32" class="form-control" value="${s.dgii_next_e_ncf_32 || '1'}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Ej: 1 → <code>E320000000001</code></div></div>
                            </div>
                        </div>

                        <!-- TAB: SUPPORT / RESET -->
                        <div class="tab-content" id="tab-support" style="display:none;">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 8px;color:#ef4444;">Soporte Técnico — Restablecer Sistema</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 24px;">Esta sección está diseñada para el mantenimiento técnico. Utiliza estas herramientas con extrema precaución.</p>
                            
                            <div style="background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);border-radius:var(--radius-lg);padding:24px;max-width:600px;">
                                <h4 style="font-size:14px;font-weight:700;color:#ef4444;margin:0 0 10px;display:flex;align-items:center;gap:8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                    Acción Altamente Destructiva
                                </h4>
                                <p style="font-size:13px;line-height:1.5;margin:0 0 16px;color:var(--color-text-secondary);">
                                    Esta acción eliminará de forma <strong>permanente e irreversible</strong> todos los datos operativos de tu sistema, incluyendo:
                                </p>
                                <ul style="font-size:13px;margin:0 0 16px 20px;padding:0;color:var(--color-text-secondary);line-height:1.6;">
                                    <li>Todas las Facturas y Cotizaciones emitidas</li>
                                    <li>Historial de Pagos y Transacciones</li>
                                    <li>Clientes y Proveedores</li>
                                    <li>Artículos y Productos en inventario</li>
                                    <li>Gastos registrados y reportes fiscales</li>
                                    <li>Configuraciones de la empresa (se restaurarán las de fábrica)</li>
                                </ul>
                                <p style="font-size:13px;font-weight:600;margin:0 0 20px;color:var(--color-text);">
                                    🔑 Tu usuario actual, tu contraseña y tu rol de administrador se conservarán para que puedas seguir accediendo al sistema vacío.
                                </p>
                                <div class="form-group" style="margin-bottom:20px;">
                                    <label class="form-label" style="font-weight:600;">Escribe tu correo de usuario para confirmar:</label>
                                    <input type="text" id="db_reset_confirm_email" class="form-control" placeholder="Ingresa tu correo actual" style="max-width:320px;margin-top:6px;">
                                </div>
                                <button type="button" id="btn-reset-db" class="btn btn-danger" style="background-color: #ef4444; color: #fff; border: none; font-weight:600; display:inline-flex; align-items:center; gap:8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"></path><path d="M16 16h5v5"></path></svg>
                                    Reiniciar Sistema desde Cero
                                </button>
                            </div>
                        </div>

                        <div style="border-top:1px solid var(--color-border);padding-top:16px;margin-top:24px;" id="settings-save-actions">
                            <button type="submit" class="btn btn-primary">Guardar Configuraciones</button>
                        </div>
                    </div>
                </form>
            `;

            // Tab navigation
            const tabs = container.querySelectorAll('#settings-tabs .segment-item');
            const contents = container.querySelectorAll('.tab-content');
            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.style.display = 'none');
                    tab.classList.add('active');
                    container.querySelector('#tab-' + tab.dataset.tab).style.display = 'block';
                });
            });

            // RNC Lookup
            document.getElementById('s_company_tax_id')?.addEventListener('input', async (e) => {
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
                            if (isRnc) { if (d.nombre) document.getElementById('s_company_name').value = d.nombre; }
                            else { const fullName = `${d.nombres} ${d.apellido1} ${d.apellido2}`.trim(); if (!document.getElementById('s_company_name').value) document.getElementById('s_company_name').value = fullName; }
                            window.App.showToast('Información autocompletada', 'success');
                        }
                    } catch (err) { window.App.showToast('RNC o Cédula no encontrada', 'error'); }
                }
            });

            // Save all settings
            document.getElementById('settings-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const settingsToUpdate = {
                    company_name: document.getElementById('s_company_name').value,
                    company_email: document.getElementById('s_company_email').value,
                    company_phone: document.getElementById('s_company_phone').value,
                    company_tax_id: document.getElementById('s_company_tax_id').value,
                    company_address: document.getElementById('s_company_address').value,
                    company_city: document.getElementById('s_company_city').value,
                    company_website: document.getElementById('s_company_website').value,
                    logo_capsule_theme: document.getElementById('s_logo_capsule_theme').value,
                    default_currency: document.getElementById('s_default_currency').value,
                    default_tax_rate: document.getElementById('s_default_tax_rate').value,
                    smtp_host: document.getElementById('s_smtp_host').value,
                    smtp_port: document.getElementById('s_smtp_port').value,
                    smtp_username: document.getElementById('s_smtp_username').value,
                    smtp_password: document.getElementById('s_smtp_password').value,
                    smtp_encryption: document.getElementById('s_smtp_encryption').value,
                    smtp_from_name: document.getElementById('s_smtp_from_name').value,
                    smtp_from_email: document.getElementById('s_smtp_from_email').value,
                    whatsapp_enabled: document.getElementById('s_whatsapp_enabled').value,
                    whatsapp_phone_id: document.getElementById('s_whatsapp_phone_id').value,
                    whatsapp_business_id: document.getElementById('s_whatsapp_business_id').value,
                    whatsapp_access_token: document.getElementById('s_whatsapp_access_token').value,
                    reminders_enabled: document.getElementById('s_reminders_enabled').value,
                    reminders_days_before: document.getElementById('s_reminders_days_before').value,
                    reminders_overdue_interval: document.getElementById('s_reminders_overdue_interval').value,
                    payment_link_general: document.getElementById('s_payment_link_general').value,
                    bank_instructions: document.getElementById('s_bank_instructions').value,
                    dgii_env: document.getElementById('s_dgii_env').value,
                    dgii_ncf_expiry_date: document.getElementById('s_dgii_ncf_expiry_date').value,
                    dgii_certificate_path: document.getElementById('s_dgii_certificate_path').value,
                    dgii_certificate_password: document.getElementById('s_dgii_certificate_password').value,
                    dgii_next_e_ncf_31: document.getElementById('s_dgii_next_e_ncf_31').value,
                    dgii_next_e_ncf_32: document.getElementById('s_dgii_next_e_ncf_32').value,
                    pdf_primary_color: document.getElementById('s_pdf_primary_color').value,
                    pdf_accent_color: document.getElementById('s_pdf_accent_color').value,
                    pdf_logo_url: document.getElementById('s_pdf_logo_url').value,
                    pdf_show_footer: document.getElementById('s_pdf_show_footer').value,
                };
                try {
                    await window.App.api('settings', { method: 'POST', body: settingsToUpdate });
                    
                    // Update global state
                    window.App.state.settings = { ...window.App.state.settings, ...settingsToUpdate };
                    
                    // Cache branding elements in localStorage
                    localStorage.setItem('logo_capsule_theme', settingsToUpdate.logo_capsule_theme);
                    
                    // Update layout immediately
                    const backdrop = document.querySelector('.logo-backdrop');
                    if (backdrop) {
                        const isLight = settingsToUpdate.logo_capsule_theme === 'light';
                        backdrop.style.background = isLight ? '#FFFFFF' : '#111827';
                        backdrop.style.border = isLight ? '1px solid rgba(0, 0, 0, 0.08)' : '1px solid rgba(255, 255, 255, 0.08)';
                    }
                    
                    window.App.showToast('Configuraciones guardadas');
                } catch(err) {}
            });

            // Test SMTP
            document.getElementById('btn-test-smtp').addEventListener('click', async () => {
                const btn = document.getElementById('btn-test-smtp');
                const testEmail = document.getElementById('smtp_test_email').value;
                if (!testEmail) return window.App.showToast('Ingresa un correo de prueba', 'error');
                const encryptionVal = document.getElementById('s_smtp_encryption').value;
                const payload = {
                    test_email: testEmail,
                    host: document.getElementById('s_smtp_host').value || 'localhost',
                    port: document.getElementById('s_smtp_port').value || '25',
                    username: document.getElementById('s_smtp_username').value,
                    password: document.getElementById('s_smtp_password').value,
                    encryption: encryptionVal === 'none' ? null : encryptionVal,
                    from_name: document.getElementById('s_smtp_from_name').value,
                    from_email: document.getElementById('s_smtp_from_email').value
                };
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> Probando...';
                btn.disabled = true;
                try { const res = await window.App.api('settings/test-smtp', { method: 'POST', body: payload }); window.App.showToast(res.message, 'success'); }
                catch(err) {}
                finally { btn.innerHTML = originalText; btn.disabled = false; }
            });

            // ── PDF Appearance: Color sync + Live Preview ──
            const updatePdfPreview = () => {
                const primary = document.getElementById('s_pdf_primary_color').value;
                const accent = document.getElementById('s_pdf_accent_color').value;
                const footerVisible = document.getElementById('s_pdf_show_footer').value === '1';

                // Header & footer backgrounds
                const header = document.getElementById('pdf-preview-header');
                const footer = document.getElementById('pdf-preview-footer');
                if (header) header.style.background = primary;
                if (footer) {
                    footer.style.background = primary;
                    footer.style.display = footerVisible ? '' : 'none';
                    // Update footer accent dot
                    const footerSpan = footer.querySelector('span');
                    if (footerSpan) footerSpan.style.color = accent;
                }

                // Title
                const title = document.getElementById('pdf-preview-title');
                if (title) {
                    title.style.color = primary;
                    title.style.borderBottomColor = primary;
                }

                // Meta labels (accent)
                document.querySelectorAll('#pdf-preview-header span[style*="font-weight:700"]').forEach(el => {
                    // Only update the label spans (E-NCF/, FECHA/), not the value spans
                    if (el.textContent.includes('/')) el.style.color = accent;
                });

                // Table header
                const thead = document.querySelector('#pdf-preview-card thead tr');
                if (thead) thead.style.background = accent;
                document.querySelectorAll('#pdf-preview-card thead th').forEach(th => {
                    th.style.color = primary;
                });
            };

            // Sync color picker <-> hex input (Primary)
            const primaryPicker = document.getElementById('s_pdf_primary_color');
            const primaryHex = document.getElementById('s_pdf_primary_color_hex');
            if (primaryPicker && primaryHex) {
                primaryPicker.addEventListener('input', () => {
                    primaryHex.value = primaryPicker.value;
                    updatePdfPreview();
                });
                primaryHex.addEventListener('input', () => {
                    if (/^#[0-9A-Fa-f]{6}$/.test(primaryHex.value)) {
                        primaryPicker.value = primaryHex.value;
                        updatePdfPreview();
                    }
                });
            }

            // Sync color picker <-> hex input (Accent)
            const accentPicker = document.getElementById('s_pdf_accent_color');
            const accentHex = document.getElementById('s_pdf_accent_color_hex');
            if (accentPicker && accentHex) {
                accentPicker.addEventListener('input', () => {
                    accentHex.value = accentPicker.value;
                    updatePdfPreview();
                });
                accentHex.addEventListener('input', () => {
                    if (/^#[0-9A-Fa-f]{6}$/.test(accentHex.value)) {
                        accentPicker.value = accentHex.value;
                        updatePdfPreview();
                    }
                });
            }

            // Logo URL preview
            const logoInput = document.getElementById('s_pdf_logo_url');
            if (logoInput) {
                logoInput.addEventListener('input', () => {
                    const url = logoInput.value.trim();
                    const previewDiv = document.getElementById('pdf-logo-preview');
                    const previewImg = document.getElementById('pdf-logo-preview-img');
                    const headerLogo = document.getElementById('pdf-preview-logo');
                    if (url) {
                        previewDiv.style.display = 'block';
                        previewImg.src = url;
                        previewImg.style.display = 'inline-block';
                        if (headerLogo) { headerLogo.src = url; headerLogo.style.display = ''; }
                    } else {
                        previewDiv.style.display = 'none';
                        if (headerLogo) {
                            headerLogo.src = 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png';
                            headerLogo.style.display = '';
                        }
                    }
                });
            }

            // Footer toggle
            const footerToggle = document.getElementById('s_pdf_show_footer');
            if (footerToggle) {
                footerToggle.addEventListener('change', updatePdfPreview);
            }

            // Database Reset
            document.getElementById('btn-reset-db')?.addEventListener('click', async () => {
                const btn = document.getElementById('btn-reset-db');
                const confirmEmail = document.getElementById('db_reset_confirm_email').value.trim();
                
                if (!confirmEmail) {
                    return window.App.showToast('Por favor, ingresa tu correo para confirmar.', 'error');
                }
                
                if (confirmEmail.toLowerCase() !== (window.App.state.user?.email || '').toLowerCase()) {
                    return window.App.showToast('El correo ingresado no coincide con tu correo de usuario.', 'error');
                }
                
                if (!confirm('¿ESTÁS ABSOLUTAMENTE SEGURO? Esta acción borrará todas las facturas, cotizaciones, clientes, artículos, gastos y configuraciones del sistema de forma irreversible. Tu usuario de acceso se conservará.')) {
                    return;
                }
                
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;border-color:#fff;margin:0 auto;"></span>';
                btn.disabled = true;
                
                try {
                    const res = await window.App.api('settings/reset-database', {
                        method: 'POST',
                        body: { confirm_email: confirmEmail }
                    });
                    
                    window.App.showToast(res.message, 'success');
                    
                    // Reload the page after 2 seconds to force the setup wizard
                    setTimeout(() => {
                        window.location.href = '/configuracion-inicial';
                    }, 2000);
                } catch(err) {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });

            // Hide/Show Save button based on active tab
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const saveActions = container.querySelector('#settings-save-actions');
                    if (saveActions) {
                        saveActions.style.display = tab.dataset.tab === 'support' ? 'none' : 'block';
                    }
                });
            });

        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar configuraciones</div>`;
        }
    }
};
