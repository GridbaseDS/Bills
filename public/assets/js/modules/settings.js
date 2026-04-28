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
                
                <div class="tabs mb-24">
                    <button class="tab active" data-tab="general">General y Facturación</button>
                    <button class="tab" data-tab="email">Email / SMTP</button>
                    <button class="tab" data-tab="whatsapp">WhatsApp API</button>
                    <button class="tab" data-tab="automation">Recordatorios</button>
                    <button class="tab" data-tab="integrations">Integraciones</button>
                </div>

                <form id="settings-form" class="card mb-24">
                    <div class="card-body">
                        
                        <!-- TAB: GENERAL -->
                        <div class="tab-content" id="tab-general">
                            <h3 class="mb-16">Información de la Empresa</h3>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Nombre Comercial</label>
                                    <input type="text" id="s_company_name" class="form-control" value="${s.company_name || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Correo Oficial</label>
                                    <input type="email" id="s_company_email" class="form-control" value="${s.company_email || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" id="s_company_phone" class="form-control" value="${s.company_phone || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">RNC / Cédula</label>
                                    <input type="text" id="s_company_tax_id" class="form-control" value="${s.company_tax_id || ''}">
                                </div>
                                <div class="form-group" style="grid-column: span 2">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" id="s_company_address" class="form-control" value="${s.company_address || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Ciudad</label>
                                    <input type="text" id="s_company_city" class="form-control" value="${s.company_city || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Sitio Web</label>
                                    <input type="text" id="s_company_website" class="form-control" value="${s.company_website || ''}">
                                </div>
                            </div>
                            
                            <h3 class="mb-16 mt-24" style="border-top: 1px solid var(--border); padding-top: 24px;">Ajustes de Facturación</h3>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Moneda por Defecto</label>
                                    <select id="s_default_currency" class="form-control">
                                        <option value="USD" ${s.default_currency === 'USD' ? 'selected' : ''}>USD</option>
                                        <option value="DOP" ${s.default_currency === 'DOP' ? 'selected' : ''}>DOP</option>
                                        <option value="EUR" ${s.default_currency === 'EUR' ? 'selected' : ''}>EUR</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Impuesto por Defecto (%)</label>
                                    <input type="number" id="s_default_tax_rate" class="form-control" value="${s.default_tax_rate || '18.00'}" step="0.01">
                                </div>
                            </div>
                        </div>

                        <!-- TAB: EMAIL/SMTP -->
                        <div class="tab-content" id="tab-email" style="display:none;">
                            <h3 class="mb-16">Configuración de Correo SMTP</h3>
                            <p class="text-muted mb-24">Ajustes para enviar facturas y cotizaciones por correo.</p>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Host SMTP</label>
                                    <input type="text" id="s_smtp_host" class="form-control" placeholder="mail.midominio.com" value="${s.smtp_host || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Puerto SMTP</label>
                                    <input type="number" id="s_smtp_port" class="form-control" placeholder="587" value="${s.smtp_port || '587'}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Usuario SMTP</label>
                                    <input type="text" id="s_smtp_username" class="form-control" placeholder="usuario@midominio.com" value="${s.smtp_username || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contraseña SMTP</label>
                                    <input type="password" id="s_smtp_password" class="form-control" value="${s.smtp_password || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Cifrado</label>
                                    <select id="s_smtp_encryption" class="form-control">
                                        <option value="tls" ${s.smtp_encryption === 'tls' ? 'selected' : ''}>TLS</option>
                                        <option value="ssl" ${s.smtp_encryption === 'ssl' ? 'selected' : ''}>SSL</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nombre de Remitente</label>
                                    <input type="text" id="s_smtp_from_name" class="form-control" value="${s.smtp_from_name || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email de Remitente (From)</label>
                                    <input type="email" id="s_smtp_from_email" class="form-control" value="${s.smtp_from_email || ''}">
                                </div>
                            </div>
                        </div>

                        <!-- TAB: WHATSAPP -->
                        <div class="tab-content" id="tab-whatsapp" style="display:none;">
                            <h3 class="mb-16">API de WhatsApp Cloud</h3>
                            <p class="text-muted mb-24">Conecta con Meta para enviar notificaciones automáticas vía WhatsApp.</p>
                            <div class="form-group mb-24">
                                <label class="form-label">Habilitar Integración de WhatsApp</label>
                                <select id="s_whatsapp_enabled" class="form-control" style="width:200px;">
                                    <option value="1" ${s.whatsapp_enabled == '1' ? 'selected' : ''}>Habilitado</option>
                                    <option value="0" ${s.whatsapp_enabled == '0' || !s.whatsapp_enabled ? 'selected' : ''}>Deshabilitado</option>
                                </select>
                            </div>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">ID del Número de Teléfono</label>
                                    <input type="text" id="s_whatsapp_phone_id" class="form-control" value="${s.whatsapp_phone_id || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ID de Cuenta de WhatsApp Business</label>
                                    <input type="text" id="s_whatsapp_business_id" class="form-control" value="${s.whatsapp_business_id || ''}">
                                </div>
                                <div class="form-group" style="grid-column: span 2">
                                    <label class="form-label">Token de Acceso (Permanente)</label>
                                    <input type="password" id="s_whatsapp_access_token" class="form-control" value="${s.whatsapp_access_token || ''}">
                                </div>
                            </div>
                        </div>

                        <!-- TAB: RECORDATORIOS -->
                        <div class="tab-content" id="tab-automation" style="display:none;">
                            <h3 class="mb-16">Recordatorios Automatizados (Cron)</h3>
                            <p class="text-muted mb-24">Configura cuándo el sistema debe enviar recordatorios de pago.</p>
                            <div class="form-group mb-24">
                                <label class="form-label">Habilitar Recordatorios</label>
                                <select id="s_reminders_enabled" class="form-control" style="width:200px;">
                                    <option value="1" ${s.reminders_enabled == '1' || !s.reminders_enabled ? 'selected' : ''}>Habilitado</option>
                                    <option value="0" ${s.reminders_enabled == '0' ? 'selected' : ''}>Deshabilitado</option>
                                </select>
                            </div>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Días Antes de Vencer (Aviso previo)</label>
                                    <input type="number" id="s_reminders_days_before" class="form-control" value="${s.reminders_days_before !== undefined ? s.reminders_days_before : '3'}">
                                    <div class="form-hint">Ej: 3 (Enviará un aviso 3 días antes de que venza)</div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Frecuencia al Vencer (Días)</label>
                                    <input type="number" id="s_reminders_overdue_interval" class="form-control" value="${s.reminders_overdue_interval !== undefined ? s.reminders_overdue_interval : '7'}">
                                    <div class="form-hint">Ej: 7 (Enviará un aviso cada 7 días si está vencida)</div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: INTEGRACIONES -->
                        <div class="tab-content" id="tab-integrations" style="display:none;">
                            <h3 class="mb-16">Integraciones de Pagos Online</h3>
                            <p class="text-muted mb-24">Agrega links de pago rápidos que se adjuntarán a los correos de las facturas.</p>
                            <div class="form-group">
                                <label class="form-label">Enlace de Pago General (PayPal.Me, Stripe Link, etc.)</label>
                                <input type="url" id="s_payment_link_general" class="form-control" placeholder="https://paypal.me/tuusuario" value="${s.payment_link_general || ''}">
                                <div class="form-hint">Este link se mostrará en los correos y PDFs como tu método de pago online principal.</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Instrucciones de Transferencia Bancaria</label>
                                <textarea id="s_bank_instructions" class="form-control" rows="4" placeholder="Banco XYZ\nCuenta: 123456789\nNombre: Empresa SRL">${s.bank_instructions || ''}</textarea>
                            </div>
                        </div>

                        <div class="mt-24" style="border-top: 1px solid var(--border); padding-top: 16px;">
                            <button type="submit" class="btn btn-primary">Guardar Todas las Configuraciones</button>
                        </div>
                    </div>
                </form>
            `;

            // Setup Details Tabs Navigation
            const tabs = container.querySelectorAll('.tab');
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
                    default_currency: document.getElementById('s_default_currency').value,
                    default_tax_rate: document.getElementById('s_default_tax_rate').value,
                    
                    // SMTP
                    smtp_host: document.getElementById('s_smtp_host').value,
                    smtp_port: document.getElementById('s_smtp_port').value,
                    smtp_username: document.getElementById('s_smtp_username').value,
                    smtp_password: document.getElementById('s_smtp_password').value,
                    smtp_encryption: document.getElementById('s_smtp_encryption').value,
                    smtp_from_name: document.getElementById('s_smtp_from_name').value,
                    smtp_from_email: document.getElementById('s_smtp_from_email').value,

                    // WhatsApp
                    whatsapp_enabled: document.getElementById('s_whatsapp_enabled').value,
                    whatsapp_phone_id: document.getElementById('s_whatsapp_phone_id').value,
                    whatsapp_business_id: document.getElementById('s_whatsapp_business_id').value,
                    whatsapp_access_token: document.getElementById('s_whatsapp_access_token').value,

                    // Reminders
                    reminders_enabled: document.getElementById('s_reminders_enabled').value,
                    reminders_days_before: document.getElementById('s_reminders_days_before').value,
                    reminders_overdue_interval: document.getElementById('s_reminders_overdue_interval').value,

                    // Integrations
                    payment_link_general: document.getElementById('s_payment_link_general').value,
                    bank_instructions: document.getElementById('s_bank_instructions').value,
                };

                try {
                    await window.App.api('settings', { method: 'POST', body: settingsToUpdate });
                    window.App.showToast('Configuraciones guardadas y aplicadas');
                } catch(err) {}
            });

        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar configuraciones</div>`;
        }
    }
};
