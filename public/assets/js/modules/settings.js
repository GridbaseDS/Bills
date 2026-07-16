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
                    <button class="segment-item active" data-tab="general" style="display:inline-flex; align-items:center; gap:6.5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13.5" height="13.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-primary);"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        General
                    </button>
                    <button class="segment-item" data-tab="apariencia" style="display:inline-flex; align-items:center; gap:6.5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13.5" height="13.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-primary);"><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 14.7255 3.09032 17.1962 4.85857 19C5.38534 19.5371 5.43851 20.3807 5.02534 20.9729C4.54228 21.6654 4.89679 22 5.5 22H12Z"/><circle cx="7.5" cy="10.5" r="1.5"/><circle cx="11.5" cy="7.5" r="1.5"/><circle cx="16.5" cy="9.5" r="1.5"/><circle cx="15.5" cy="14.5" r="1.5"/></svg>
                        Apariencia PDF
                    </button>
                    <button class="segment-item" data-tab="email" style="display:inline-flex; align-items:center; gap:6.5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13.5" height="13.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-primary);"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        Email / SMTP
                    </button>
                    <button class="segment-item" data-tab="whatsapp" style="display:inline-flex; align-items:center; gap:6.5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13.5" height="13.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-primary);"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        WhatsApp
                    </button>
                    <button class="segment-item" data-tab="automation" style="display:inline-flex; align-items:center; gap:6.5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13.5" height="13.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-primary);"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        Recordatorios
                    </button>
                    <button class="segment-item" data-tab="integrations" style="display:inline-flex; align-items:center; gap:6.5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13.5" height="13.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-primary);"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        Integraciones
                    </button>
                    <button class="segment-item" data-tab="dgii" style="display:inline-flex; align-items:center; gap:6.5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13.5" height="13.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-primary);"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        e-CF / DGII
                    </button>
                    <button class="segment-item" data-tab="devices" style="display:inline-flex; align-items:center; gap:6.5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13.5" height="13.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-primary);"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        Dispositivos
                    </button>
                    <button class="segment-item" data-tab="apikeys" style="display:inline-flex; align-items:center; gap:6.5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13.5" height="13.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-primary);"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                        API Keys
                    </button>
                    <button class="segment-item" data-tab="support" style="display:inline-flex; align-items:center; gap:6.5px; color:var(--color-danger);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13.5" height="13.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-danger);"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/><line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/></svg>
                        Soporte
                    </button>
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
                                    <label class="form-label">Personalización del Menú Lateral</label>
                                    <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
                                        <div>
                                            <div style="font-size:12px;font-weight:500;color:var(--color-text-secondary);margin-bottom:6px;">Color de Fondo</div>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_sidebar_bg_color" value="${s.sidebar_bg_color || '#FFFFFF'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_sidebar_bg_color_hex" class="form-control" value="${s.sidebar_bg_color || '#FFFFFF'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                        <div>
                                            <div style="font-size:12px;font-weight:500;color:var(--color-text-secondary);margin-bottom:6px;">Color de Texto</div>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_sidebar_text_color" value="${s.sidebar_text_color || '#374151'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_sidebar_text_color_hex" class="form-control" value="${s.sidebar_text_color || '#374151'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                        <div>
                                            <div style="font-size:12px;font-weight:500;color:var(--color-text-secondary);margin-bottom:6px;">Color de Hover</div>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_sidebar_hover_color" value="${s.sidebar_hover_color || '#F3F4F6'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_sidebar_hover_color_hex" class="form-control" value="${s.sidebar_hover_color || '#F3F4F6'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                    </div>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:6px;">Personaliza el fondo y color de texto del menú lateral. Úsalo para adaptar el menú a tu marca.</div>
                                    <div id="sidebar-preview" style="margin-top:12px;width:180px;height:60px;border-radius:var(--radius-md);border:1px solid var(--color-border);display:flex;align-items:center;padding:0 16px;gap:10px;transition:all .2s ease;">
                                        <svg style="width:16px;height:16px;flex-shrink:0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                        <span style="font-size:13px;font-weight:500;">Vista previa</span>
                                    </div>
                                </div>
                                <div class="form-group" style="grid-column: span 2; margin-top: 16px; border-top: 1px dashed var(--color-border); padding-top: 16px;">
                                    <label class="form-label">Personalización del Menú Lateral (Modo Oscuro)</label>
                                    <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
                                        <div>
                                            <div style="font-size:12px;font-weight:500;color:var(--color-text-secondary);margin-bottom:6px;">Color de Fondo</div>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_sidebar_dark_bg_color" value="${s.sidebar_dark_bg_color || '#111827'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_sidebar_dark_bg_color_hex" class="form-control" value="${s.sidebar_dark_bg_color || '#111827'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                        <div>
                                            <div style="font-size:12px;font-weight:500;color:var(--color-text-secondary);margin-bottom:6px;">Color de Texto</div>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_sidebar_dark_text_color" value="${s.sidebar_dark_text_color || '#FFFFFF'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_sidebar_dark_text_color_hex" class="form-control" value="${s.sidebar_dark_text_color || '#FFFFFF'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                        <div>
                                            <div style="font-size:12px;font-weight:500;color:var(--color-text-secondary);margin-bottom:6px;">Color de Hover</div>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_sidebar_dark_hover_color" value="${s.sidebar_dark_hover_color || '#1F2937'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_sidebar_dark_hover_color_hex" class="form-control" value="${s.sidebar_dark_hover_color || '#1F2937'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                    </div>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:6px;">Personaliza el fondo y color de texto del menú lateral para cuando el modo oscuro está activo.</div>
                                    <div id="sidebar-dark-preview" style="margin-top:12px;width:180px;height:60px;border-radius:var(--radius-md);border:1px solid var(--color-border);display:flex;align-items:center;padding:0 16px;gap:10px;transition:all .2s ease;">
                                        <svg style="width:16px;height:16px;flex-shrink:0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                        <span style="font-size:13px;font-weight:500;">Vista previa (Oscuro)</span>
                                    </div>
                                </div>
                            </div>

                            <h3 style="font-size:15px;font-weight:600;margin:24px 0 16px;border-top:1px solid var(--color-border);padding-top:24px;">Branding</h3>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Logo de Interfaz (URL)</label>
                                    <input type="url" id="s_company_logo" class="form-control" placeholder="https://miempresa.com/logo.png" value="${s.company_logo || ''}">
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Logo que aparece en el menú lateral. Recomendado: fondo transparente (PNG/SVG).</div>
                                    <div id="logo-preview" style="margin-top:10px;display:${s.company_logo ? 'block' : 'none'};">
                                        <img id="logo-preview-img" src="${s.company_logo || ''}" style="max-height:40px;max-width:180px;border:1px solid var(--color-border);border-radius:var(--radius-md);padding:6px;background:var(--bg-hover);" onerror="this.style.display='none'" onload="this.style.display='inline-block'">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tamaño del Logo en Sidebar (Altura)</label>
                                    <div style="display:flex;align-items:center;gap:12px;margin-top:8px;">
                                        <input type="range" id="s_sidebar_logo_height" class="form-range" min="20" max="100" step="1" value="${s.sidebar_logo_height || '45'}" style="flex:1;cursor:pointer;accent-color:var(--color-primary, #0B484C);height:6px;border-radius:3px;background:var(--color-border);border:none;padding:0;">
                                        <span id="sidebar_logo_height_val" style="font-size:13px;font-weight:600;min-width:40px;text-align:right;">${s.sidebar_logo_height || '45'}px</span>
                                    </div>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Desliza para ajustar la altura del logo en el menú lateral en tiempo real.</div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Logo de Login (URL)</label>
                                    <input type="url" id="s_login_logo" class="form-control" placeholder="https://miempresa.com/login-logo.png" value="${s.login_logo || ''}">
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Logo que aparece exclusivamente en la pantalla de inicio de sesión.</div>
                                    <div id="login-logo-preview" style="margin-top:10px;display:${s.login_logo ? 'block' : 'none'};">
                                        <img id="login-logo-preview-img" src="${s.login_logo || ''}" style="max-height:40px;max-width:180px;border:1px solid var(--color-border);border-radius:var(--radius-md);padding:6px;background:var(--bg-hover);" onerror="this.style.display='none'" onload="this.style.display='inline-block'">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Favicon (URL)</label>
                                    <input type="url" id="s_company_favicon" class="form-control" placeholder="https://miempresa.com/favicon.png" value="${s.company_favicon || ''}">
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Ícono de la pestaña del navegador. Recomendado: imagen cuadrada de 180x180px.</div>
                                    <div id="favicon-preview" style="margin-top:10px;display:${s.company_favicon ? 'flex' : 'none'};align-items:center;gap:8px;">
                                        <img id="favicon-preview-img" src="${s.company_favicon || ''}" style="width:32px;height:32px;border:1px solid var(--color-border);border-radius:var(--radius-sm);object-fit:contain;" onerror="this.parentElement.style.display='none'" onload="this.parentElement.style.display='flex'">
                                        <span style="font-size:11px;color:var(--color-text-muted);">Vista previa del favicon</span>
                                    </div>
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
                                <div class="form-group" style="grid-column: span 2; margin-top: 12px; border-top: 1px dashed var(--color-border); padding-top: 16px;">
                                    <h4 style="font-size:13px;font-weight:700;margin:0 0 12px;color:var(--color-text-primary);">Colores Detallados (Opcional)</h4>
                                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">
                                        <!-- Header BG -->
                                        <div>
                                            <label class="form-label" style="font-size:12px;margin-bottom:6px;">Fondo de Cabezal</label>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_pdf_header_bg_color" value="${s.pdf_header_bg_color || s.pdf_primary_color || '#0B484C'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_pdf_header_bg_color_hex" class="form-control" value="${s.pdf_header_bg_color || s.pdf_primary_color || '#0B484C'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                        <!-- Header Text -->
                                        <div>
                                            <label class="form-label" style="font-size:12px;margin-bottom:6px;">Texto de Cabezal</label>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_pdf_header_text_color" value="${s.pdf_header_text_color || '#FFFFFF'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_pdf_header_text_color_hex" class="form-control" value="${s.pdf_header_text_color || '#FFFFFF'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                        <!-- Table Header BG -->
                                        <div>
                                            <label class="form-label" style="font-size:12px;margin-bottom:6px;">Fondo Cabecera Tabla</label>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_pdf_table_header_bg_color" value="${s.pdf_table_header_bg_color || s.pdf_accent_color || '#00DF83'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_pdf_table_header_bg_color_hex" class="form-control" value="${s.pdf_table_header_bg_color || s.pdf_accent_color || '#00DF83'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                        <!-- Table Header Text -->
                                        <div>
                                            <label class="form-label" style="font-size:12px;margin-bottom:6px;">Texto Cabecera Tabla</label>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_pdf_table_header_text_color" value="${s.pdf_table_header_text_color || s.pdf_primary_color || '#0B484C'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_pdf_table_header_text_color_hex" class="form-control" value="${s.pdf_table_header_text_color || s.pdf_primary_color || '#0B484C'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                        <!-- Footer BG -->
                                        <div>
                                            <label class="form-label" style="font-size:12px;margin-bottom:6px;">Fondo Pie de Página</label>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_pdf_footer_bg_color" value="${s.pdf_footer_bg_color || s.pdf_primary_color || '#0B484C'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_pdf_footer_bg_color_hex" class="form-control" value="${s.pdf_footer_bg_color || s.pdf_primary_color || '#0B484C'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                        <!-- Footer Text -->
                                        <div>
                                            <label class="form-label" style="font-size:12px;margin-bottom:6px;">Texto Pie de Página</label>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <input type="color" id="s_pdf_footer_text_color" value="${s.pdf_footer_text_color || '#FFFFFF'}" style="width:42px;height:34px;border:1px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;padding:2px;">
                                                <input type="text" id="s_pdf_footer_text_color_hex" class="form-control" value="${s.pdf_footer_text_color || '#FFFFFF'}" style="width:100px;font-family:monospace;font-size:12px;" maxlength="7">
                                            </div>
                                        </div>
                                    </div>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:8px;">De manera predeterminada se heredan los colores primario y acento. Si deseas personalizar áreas específicas, edita estos campos.</div>
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
                                <div class="form-group">
                                    <label class="form-label">Plantilla de Factura por Defecto</label>
                                    <select id="s_invoice_pdf_template" class="form-control" style="width:200px;">
                                        <option value="normal" ${s.invoice_pdf_template === 'thermal' ? '' : 'selected'}>Normal (Carta/A4)</option>
                                        <option value="thermal" ${s.invoice_pdf_template === 'thermal' ? 'selected' : ''}>Ticket Térmico (80mm)</option>
                                    </select>
                                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Define el formato de factura predeterminado en el sistema</div>
                                </div>
                            </div>

                            <!-- Live Preview -->
                            <div style="margin-top:28px;border-top:1px solid var(--color-border);padding-top:24px;">
                                <h4 style="font-size:13px;font-weight:600;margin:0 0 14px;">Vista Previa</h4>
                                <div id="pdf-preview-card" style="border-radius:var(--radius-lg);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.10);max-width:520px;">
                                    <div id="pdf-preview-header" style="background:${s.pdf_header_bg_color || s.pdf_primary_color || '#0B484C'};color:${s.pdf_header_text_color || '#FFFFFF'};padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
                                        <div>
                                            <img id="pdf-preview-logo" src="${s.pdf_logo_url || 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png'}" style="height:32px;" onerror="this.style.display='none'">
                                        </div>
                                        <div style="text-align:right;">
                                            <div style="font-size:9px;letter-spacing:0.5px;"><span id="pdf-preview-label" style="color:${s.pdf_accent_color || '#00DF83'};font-weight:700;">E-NCF/</span> <span style="color:${s.pdf_header_text_color || '#FFFFFF'};font-weight:700;">E310000000001</span></div>
                                            <div style="font-size:9px;margin-top:3px;"><span style="color:${s.pdf_accent_color || '#00DF83'};font-weight:700;">FECHA/</span> <span style="color:${s.pdf_header_text_color || '#FFFFFF'};font-weight:700;">27/05/2026</span></div>
                                        </div>
                                    </div>
                                    <div style="padding:16px 24px;background:#fff;">
                                        <div id="pdf-preview-title" style="font-size:14px;font-weight:700;color:${s.pdf_primary_color || '#0B484C'};text-transform:uppercase;border-bottom:2px solid ${s.pdf_primary_color || '#0B484C'};display:inline-block;padding-bottom:3px;margin-bottom:12px;">FACTURA</div>
                                        <table style="width:100%;border-collapse:collapse;">
                                            <thead><tr style="background:${s.pdf_table_header_bg_color || s.pdf_accent_color || '#00DF83'};"><th style="color:${s.pdf_table_header_text_color || s.pdf_primary_color || '#0B484C'};font-size:8px;text-transform:uppercase;padding:6px 8px;text-align:left;font-weight:700;">Descripción</th><th style="color:${s.pdf_table_header_text_color || s.pdf_primary_color || '#0B484C'};font-size:8px;text-transform:uppercase;padding:6px 8px;text-align:right;font-weight:700;">Total</th></tr></thead>
                                            <tbody><tr style="border-bottom:1px solid #E8E8E8;"><td style="padding:6px 8px;font-size:10px;color:#444;">Servicio ejemplo</td><td style="padding:6px 8px;font-size:10px;text-align:right;font-weight:600;">DOP 5,000.00</td></tr></tbody>
                                        </table>
                                    </div>
                                    <div id="pdf-preview-footer" style="background:${s.pdf_footer_bg_color || s.pdf_primary_color || '#0B484C'};color:${s.pdf_footer_text_color || '#FFFFFF'};padding:8px 24px;text-align:center;font-size:9px;${s.pdf_show_footer === '0' ? 'display:none;' : ''}">gridbase.com.do <span style="color:${s.pdf_accent_color || '#00DF83'};margin:0 6px;">•</span> info@gridbase.com.do</div>
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
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 8px;">WhatsApp</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 24px;">Selecciona el driver de envío y configura las credenciales correspondientes.</p>

                            <!-- Driver Selector -->
                            <div style="background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:20px;margin-bottom:20px;">
                                <h4 style="font-size:13px;font-weight:600;margin:0 0 12px;">Driver de Envío Activo</h4>
                                <div class="grid-2">
                                    <div class="form-group" style="margin:0;">
                                        <label class="form-label">Driver</label>
                                        <select id="s_whatsapp_driver" class="form-control" onchange="
                                            const v = this.value;
                                            document.getElementById('wa-panel-meta').style.display      = v === 'meta'      ? '' : 'none';
                                            document.getElementById('wa-panel-evolution').style.display = v === 'evolution' ? '' : 'none';
                                        ">
                                            <option value="meta"      ${(s.whatsapp_driver || 'meta') === 'meta'      ? 'selected' : ''}>Meta Cloud API (oficial)</option>
                                            <option value="evolution" ${(s.whatsapp_driver || 'meta') === 'evolution' ? 'selected' : ''}>Evolution API (self-hosted)</option>
                                        </select>
                                        <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">
                                            <strong>Meta:</strong> API oficial de WhatsApp Business (requiere aprobación de Meta).<br>
                                            <strong>Evolution:</strong> Gateway open-source auto-hospedado (sin aprobación, escaneo de QR).
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel Meta -->
                            <div id="wa-panel-meta" style="${(s.whatsapp_driver || 'meta') !== 'meta' ? 'display:none;' : ''}background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:20px;margin-bottom:20px;">
                                <h4 style="font-size:13px;font-weight:600;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.63a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                                    Meta Cloud API — Configuración
                                </h4>
                                <div class="grid-2">
                                    <div class="form-group"><label class="form-label">ID del Número de Teléfono</label><input type="text" id="s_whatsapp_phone_id" class="form-control" value="${s.whatsapp_phone_id || ''}"></div>
                                    <div class="form-group"><label class="form-label">ID de Cuenta Business</label><input type="text" id="s_whatsapp_business_id" class="form-control" value="${s.whatsapp_business_id || ''}"></div>
                                    <div class="form-group" style="grid-column: span 2"><label class="form-label">Token de Acceso</label><input type="password" id="s_whatsapp_access_token" class="form-control" value="${s.whatsapp_access_token || ''}"></div>
                                </div>
                            </div>

                            <!-- Panel Evolution API -->
                            <div id="wa-panel-evolution" style="${(s.whatsapp_driver || 'meta') !== 'evolution' ? 'display:none;' : ''}background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:20px;margin-bottom:20px;">
                                <h4 style="font-size:13px;font-weight:600;margin:0 0 6px;display:flex;align-items:center;gap:8px;">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                                    Evolution API — Configuración
                                </h4>
                                <p style="color:var(--color-text-muted);font-size:12px;margin:0 0 16px;">Gateway open-source auto-hospedado. Instala Evolution API en tu servidor y conéctala aquí.</p>
                                <div class="grid-2">
                                    <div class="form-group"><label class="form-label">URL de Evolution API</label><input type="url" id="s_evolution_api_url" class="form-control" placeholder="https://wa.gridbase.com.do" value="${s.evolution_api_url || ''}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">URL base del servidor Evolution API (sin slash final)</div></div>
                                    <div class="form-group"><label class="form-label">Nombre de Instancia</label><input type="text" id="s_evolution_instance" class="form-control" placeholder="gridbase-bills" value="${s.evolution_instance || 'gridbase-bills'}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Nombre de la instancia creada en Evolution API</div></div>
                                    <div class="form-group"><label class="form-label">API Key de Evolution</label><input type="password" id="s_evolution_api_key" class="form-control" placeholder="Tu API key del .env de Evolution" value="${s.evolution_api_key || ''}"></div>
                                    <div class="form-group"><label class="form-label">Número de WhatsApp</label><input type="tel" id="s_evolution_phone_number" class="form-control" placeholder="8495714181" value="${s.evolution_phone_number || ''}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Número asociado a la cuenta de WhatsApp (sin +, sin guiones)</div></div>
                                </div>

                                <!-- Connection Status + Pairing Code -->
                                <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                                    <button type="button" id="btn-evolution-status" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:6px;">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12.55a11 11 0 0114.08 0"/><path d="M1.42 9a16 16 0 0121.16 0"/><path d="M8.53 16.11a6 6 0 016.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                                        Verificar Conexion
                                    </button>
                                    <button type="button" id="btn-evolution-pairing" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 7h3a5 5 0 015 5 5 5 0 01-5 5h-3m-6 0H6a5 5 0 01-5-5 5 5 0 015-5h3"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                        Vincular con Codigo
                                    </button>
                                    <button type="button" id="btn-evolution-qr" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                        QR (alternativo)
                                    </button>
                                    <div id="evolution-status-badge" style="display:none;"></div>
                                </div>
                                <div id="evolution-pairing-container" style="display:none;margin-top:16px;text-align:center;"></div>
                                <div id="evolution-qr-container" style="display:none;margin-top:16px;text-align:center;"></div>
                            </div>

                            <!-- Test WhatsApp -->
                            <div style="background:var(--bg-hover);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:20px;">
                                <h4 style="font-size:13px;font-weight:600;margin:0 0 12px;">Probar Envio de Mensaje</h4>
                                <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                                    <div class="form-group" style="margin:0;flex:1;min-width:200px;max-width:300px;">
                                        <label class="form-label">Numero de WhatsApp</label>
                                        <input type="tel" id="wa_test_phone" class="form-control" placeholder="8091234567">
                                    </div>
                                    <button type="button" id="btn-test-whatsapp" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:6px;">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.63a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                                        Enviar Prueba
                                    </button>
                                </div>
                                <div id="wa-test-result" style="display:none;margin-top:12px;padding:10px 14px;border-radius:var(--radius-md);font-size:13px;"></div>
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
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 24px;">Datos fiscales del emisor, certificado digital y secuencias e-NCF para facturación electrónica.</p>

                            <!-- Preset: Datos de Prueba DGII -->
                            <div id="dgii_preset_banner" style="background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.25);border-radius:var(--radius-lg);padding:14px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:#b45309;">Preset de Certificación DGII</div>
                                    <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px;">Carga automáticamente los datos del emisor y secuencias del set de pruebas oficial (RNC 40214827087).</div>
                                </div>
                                <button type="button" id="btn_dgii_load_preset" class="btn btn-secondary" style="white-space:nowrap;display:inline-flex;align-items:center;gap:6px;border-color:rgba(245,158,11,0.4);color:#b45309;font-weight:600;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    Cargar Datos de Prueba
                                </button>
                            </div>

                            <!-- Datos Fiscales del Emisor -->
                            <div style="background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:20px;margin-bottom:24px;">
                                <h4 style="font-size:14px;font-weight:600;margin:0 0 4px;display:flex;align-items:center;gap:8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    Datos Fiscales del Emisor
                                </h4>
                                <p style="color:var(--color-text-muted);font-size:12px;margin:0 0 16px;">Estos campos se usan en el XML del e-CF. Si se dejan vacíos, se toman de la pestaña General.</p>
                                <div class="grid-2">
                                    <div class="form-group"><label class="form-label">Razón Social (DGII)</label><input type="text" id="s_dgii_razon_social" class="form-control" placeholder="Ej: EMPRESA XYZ SRL" value="${s.dgii_razon_social || ''}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Si vacío, se usa el Nombre Comercial de la pestaña General.</div></div>
                                    <div class="form-group"><label class="form-label">Nombre Comercial</label><input type="text" id="s_dgii_nombre_comercial" class="form-control" placeholder="Ej: Mi Negocio" value="${s.dgii_nombre_comercial || ''}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Nombre con el que opera comercialmente.</div></div>
                                    <div class="form-group"><label class="form-label">Código de Municipio</label><input type="text" id="s_dgii_municipio" class="form-control" placeholder="Ej: 010101" maxlength="6" value="${s.dgii_municipio || ''}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Código DGII de 6 dígitos del municipio del emisor.</div></div>
                                    <div class="form-group"><label class="form-label">Código de Provincia</label><input type="text" id="s_dgii_provincia" class="form-control" placeholder="Ej: 010000" maxlength="6" value="${s.dgii_provincia || ''}"><div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Código DGII de 6 dígitos de la provincia del emisor.</div></div>
                                </div>
                            </div>

                            <!-- Certificado y Entorno -->
                            <div style="background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:20px;margin-bottom:24px;">
                                <h4 style="font-size:14px;font-weight:600;margin:0 0 4px;display:flex;align-items:center;gap:8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    Certificado Digital y Entorno
                                </h4>
                                <p style="color:var(--color-text-muted);font-size:12px;margin:0 0 16px;">Certificado de firma digital y entorno de la DGII.</p>

                                <!-- DGII Environment Status Banner -->
                                ${(() => {
                                    const env = s.dgii_env || 'production';
                                    const envConfig = {
                                        production: {
                                            bg: 'rgba(34,197,94,0.1)', border: '#22c55e', dot: '#22c55e',
                                            label: '🟢 PRODUCCIÓN ACTIVA', sublabel: 'Las facturas se envían a ecf.dgii.gov.do — Ambiente oficial con validez fiscal.',
                                            textColor: '#16a34a'
                                        },
                                        certification: {
                                            bg: 'rgba(249,115,22,0.1)', border: '#f97316', dot: '#f97316',
                                            label: '🟠 CERTIFICACIÓN', sublabel: 'Ambiente formal de certificación (certecf). Las facturas NO tienen validez fiscal real.',
                                            textColor: '#c2410c'
                                        },
                                        testing: {
                                            bg: 'rgba(239,68,68,0.1)', border: '#ef4444', dot: '#ef4444',
                                            label: '🔴 PRE-CERTIFICACIÓN (PRUEBAS)', sublabel: '⚠️ ADVERTENCIA: Las facturas van a testecf — NO tienen validez fiscal. Cambia a Producción para facturar.',
                                            textColor: '#dc2626'
                                        }
                                    };
                                    const cfg = envConfig[env] || envConfig.production;
                                    return '<div style="background:'+cfg.bg+';border:1.5px solid '+cfg.border+';border-radius:var(--radius-md);padding:14px 16px;margin-bottom:18px;display:flex;align-items:flex-start;gap:12px;">'
                                        + '<div style="width:10px;height:10px;border-radius:50%;background:'+cfg.dot+';margin-top:3px;flex-shrink:0;box-shadow:0 0 6px '+cfg.dot+';"></div>'
                                        + '<div>'
                                        + '<div style="font-size:13px;font-weight:700;color:'+cfg.textColor+';">'+cfg.label+'</div>'
                                        + '<div style="font-size:12px;color:var(--color-text-muted);margin-top:3px;">'+cfg.sublabel+'</div>'
                                        + '</div></div>';
                                })()}


                                <div class="grid-2">
                                    <div class="form-group">
                                        <label class="form-label" style="display:flex;align-items:center;gap:6px;">
                                            Entorno DGII
                                            <span style="font-size:10px;background:var(--color-border);color:var(--color-text-muted);padding:2px 6px;border-radius:999px;">Cambia dónde se envían las facturas</span>
                                        </label>
                                        <select id="s_dgii_env" class="form-control" onchange="
                                            const bannerEl = this.closest('.form-group').closest('.grid-2').previousElementSibling;
                                            const configs = {
                                                production: { bg:'rgba(34,197,94,0.1)', border:'#22c55e', dot:'#22c55e', label:'🟢 PRODUCCIÓN ACTIVA', sub:'Las facturas se envían a ecf.dgii.gov.do — Ambiente oficial con validez fiscal.', tc:'#16a34a' },
                                                certification: { bg:'rgba(249,115,22,0.1)', border:'#f97316', dot:'#f97316', label:'🟠 CERTIFICACIÓN', sub:'Ambiente formal de certificación (certecf). Las facturas NO tienen validez fiscal real.', tc:'#c2410c' },
                                                testing: { bg:'rgba(239,68,68,0.1)', border:'#ef4444', dot:'#ef4444', label:'🔴 PRE-CERTIFICACIÓN (PRUEBAS)', sub:'⚠️ ADVERTENCIA: Las facturas van a testecf — NO tienen validez fiscal. Cambia a Producción.', tc:'#dc2626' }
                                            };
                                            const c = configs[this.value] || configs.production;
                                            bannerEl.style.background = c.bg;
                                            bannerEl.style.borderColor = c.border;
                                            bannerEl.querySelector('div[style*=border-radius]').style.background = c.dot;
                                            bannerEl.querySelector('div[style*=border-radius]').style.boxShadow = '0 0 6px ' + c.dot;
                                            bannerEl.querySelector('div[style*=font-weight]').style.color = c.tc;
                                            bannerEl.querySelector('div[style*=font-weight]').textContent = c.label;
                                            bannerEl.querySelector('div[style*=text-muted]').textContent = c.sub;
                                        ">
                                            <option value="testing" ${s.dgii_env === 'testing' ? 'selected' : ''}>Pre-Certificación (testecf) — PRUEBAS</option>
                                            <option value="certification" ${s.dgii_env === 'certification' ? 'selected' : ''}>Certificación (certecf)</option>
                                            <option value="production" ${s.dgii_env === 'production' || !s.dgii_env ? 'selected' : ''}>Producción (ecf) — FACTURAS REALES ✓</option>
                                        </select>
                                    </div>
                                    <div class="form-group"><label class="form-label">Vence Secuencia (e-NCF)</label><input type="date" id="s_dgii_ncf_expiry_date" class="form-control" value="${s.dgii_ncf_expiry_date || '2028-12-31'}"></div>
                                    <div class="form-group">
                                        <label class="form-label">Certificado (.p12 / .pfx)</label>
                                        <div style="display:flex;gap:8px;align-items:flex-start;">
                                            <input type="text" id="s_dgii_certificate_path" class="form-control" placeholder="certificado.p12" value="${s.dgii_certificate_path || ''}" style="flex:1;">
                                            <label for="dgii_cert_upload" class="btn btn-secondary" style="cursor:pointer;white-space:nowrap;margin:0;display:inline-flex;align-items:center;gap:6px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                                Subir
                                            </label>
                                            <input type="file" id="dgii_cert_upload" accept=".p12,.pfx" style="display:none;">
                                        </div>
                                        <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Almacenado en <code>storage/app/secure/</code>. Sube un .p12 o .pfx directamente.</div>
                                        <div id="cert_upload_status" style="display:none;font-size:12px;margin-top:6px;padding:8px 12px;border-radius:var(--radius-md);"></div>
                                    </div>
                                    <div class="form-group"><label class="form-label">Contraseña del Certificado</label><input type="password" id="s_dgii_certificate_password" class="form-control" value="${s.dgii_certificate_password || ''}"></div>
                                </div>
                            </div>

                            <!-- Secuencias e-NCF -->
                            <div style="background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:20px;">
                                <h4 style="font-size:14px;font-weight:600;margin:0 0 4px;display:flex;align-items:center;gap:8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                                    Secuencias e-NCF (Próximo Número)
                                </h4>
                                <p style="color:var(--color-text-muted);font-size:12px;margin:0 0 16px;">El sistema genera automáticamente el eNCF combinando el tipo + número secuencial con ceros a la izquierda.</p>
                                <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:12px;">
                                    <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:12px;">Tipo 31 — Crédito Fiscal</label><input type="number" id="s_dgii_next_e_ncf_31" class="form-control" min="1" value="${s.dgii_next_e_ncf_31 || '1'}"><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">→ <code>E31</code>0000000001</div></div>
                                    <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:12px;">Tipo 32 — Consumo</label><input type="number" id="s_dgii_next_e_ncf_32" class="form-control" min="1" value="${s.dgii_next_e_ncf_32 || '1'}"><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">→ <code>E32</code>0000000001</div></div>
                                    <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:12px;">Tipo 33 — Nota de Débito</label><input type="number" id="s_dgii_next_e_ncf_33" class="form-control" min="1" value="${s.dgii_next_e_ncf_33 || '1'}"><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">→ <code>E33</code>0000000001</div></div>
                                    <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:12px;">Tipo 34 — Nota de Crédito</label><input type="number" id="s_dgii_next_e_ncf_34" class="form-control" min="1" value="${s.dgii_next_e_ncf_34 || '1'}"><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">→ <code>E34</code>0000000001</div></div>
                                    <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:12px;">Tipo 41 — Compras</label><input type="number" id="s_dgii_next_e_ncf_41" class="form-control" min="1" value="${s.dgii_next_e_ncf_41 || '1'}"><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">→ <code>E41</code>0000000001</div></div>
                                    <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:12px;">Tipo 43 — Gastos Menores</label><input type="number" id="s_dgii_next_e_ncf_43" class="form-control" min="1" value="${s.dgii_next_e_ncf_43 || '1'}"><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">→ <code>E43</code>0000000001</div></div>
                                    <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:12px;">Tipo 44 — Regímenes Especiales</label><input type="number" id="s_dgii_next_e_ncf_44" class="form-control" min="1" value="${s.dgii_next_e_ncf_44 || '1'}"><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">→ <code>E44</code>0000000001</div></div>
                                    <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:12px;">Tipo 45 — Gubernamental</label><input type="number" id="s_dgii_next_e_ncf_45" class="form-control" min="1" value="${s.dgii_next_e_ncf_45 || '1'}"><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">→ <code>E45</code>0000000001</div></div>
                                    <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:12px;">Tipo 46 — Exportaciones</label><input type="number" id="s_dgii_next_e_ncf_46" class="form-control" min="1" value="${s.dgii_next_e_ncf_46 || '1'}"><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">→ <code>E46</code>0000000001</div></div>
                                    <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:12px;">Tipo 47 — Pagos al Exterior</label><input type="number" id="s_dgii_next_e_ncf_47" class="form-control" min="1" value="${s.dgii_next_e_ncf_47 || '1'}"><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">→ <code>E47</code>0000000001</div></div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: DEVICES -->
                        <div class="tab-content" id="tab-devices" style="display:none;">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 8px;">Dispositivos Autorizados</h3>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0 0 24px;">Administra los dispositivos (máximo 3) que tienen acceso rápido por PIN a tu cuenta. Puedes revocar el acceso de cualquiera de ellos en cualquier momento para mantener tu cuenta abierta y segura.</p>
                            
                            <div id="devices-list-container" style="display:flex; flex-direction:column; gap:16px;">
                                <div style="text-align:center;padding:40px 0;color:var(--color-text-muted);">
                                    <span class="spinner"></span>
                                    <p style="margin-top:12px;">Cargando dispositivos...</p>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: API KEYS -->
                        <div class="tab-content" id="tab-apikeys" style="display:none;">
                            <div id="api-keys-container">
                                <div style="text-align:center;padding:40px 0;color:var(--color-text-muted);">
                                    <span class="spinner"></span>
                                    <p style="margin-top:12px;">Cargando API Keys...</p>
                                </div>
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

            // Sidebar color picker sync + live preview
            const updateSidebarPreview = () => {
                const bg = document.getElementById('s_sidebar_bg_color').value;
                const text = document.getElementById('s_sidebar_text_color').value;
                const preview = document.getElementById('sidebar-preview');
                if (preview) {
                    preview.style.backgroundColor = bg;
                    preview.style.color = text;
                    preview.querySelectorAll('svg').forEach(s => s.style.color = text);
                }
            };
            // BG color
            document.getElementById('s_sidebar_bg_color')?.addEventListener('input', (e) => {
                document.getElementById('s_sidebar_bg_color_hex').value = e.target.value;
                updateSidebarPreview();
            });
            document.getElementById('s_sidebar_bg_color_hex')?.addEventListener('input', (e) => {
                if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                    document.getElementById('s_sidebar_bg_color').value = e.target.value;
                    updateSidebarPreview();
                }
            });
            // Text color
            document.getElementById('s_sidebar_text_color')?.addEventListener('input', (e) => {
                document.getElementById('s_sidebar_text_color_hex').value = e.target.value;
                updateSidebarPreview();
            });
            document.getElementById('s_sidebar_text_color_hex')?.addEventListener('input', (e) => {
                if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                    document.getElementById('s_sidebar_text_color').value = e.target.value;
                    updateSidebarPreview();
                }
            });
            // Init preview
            updateSidebarPreview();

            // Hover color sync
            document.getElementById('s_sidebar_hover_color')?.addEventListener('input', (e) => {
                document.getElementById('s_sidebar_hover_color_hex').value = e.target.value;
            });
            document.getElementById('s_sidebar_hover_color_hex')?.addEventListener('input', (e) => {
                if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                    document.getElementById('s_sidebar_hover_color').value = e.target.value;
                }
            });

            // Sidebar (Dark) color picker sync + live preview
            const updateSidebarDarkPreview = () => {
                const bg = document.getElementById('s_sidebar_dark_bg_color').value;
                const text = document.getElementById('s_sidebar_dark_text_color').value;
                const preview = document.getElementById('sidebar-dark-preview');
                if (preview) {
                    preview.style.backgroundColor = bg;
                    preview.style.color = text;
                    preview.querySelectorAll('svg').forEach(s => s.style.color = text);
                }
            };
            // Dark BG color
            document.getElementById('s_sidebar_dark_bg_color')?.addEventListener('input', (e) => {
                document.getElementById('s_sidebar_dark_bg_color_hex').value = e.target.value;
                updateSidebarDarkPreview();
            });
            document.getElementById('s_sidebar_dark_bg_color_hex')?.addEventListener('input', (e) => {
                if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                    document.getElementById('s_sidebar_dark_bg_color').value = e.target.value;
                    updateSidebarDarkPreview();
                }
            });
            // Dark Text color
            document.getElementById('s_sidebar_dark_text_color')?.addEventListener('input', (e) => {
                document.getElementById('s_sidebar_dark_text_color_hex').value = e.target.value;
                updateSidebarDarkPreview();
            });
            document.getElementById('s_sidebar_dark_text_color_hex')?.addEventListener('input', (e) => {
                if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                    document.getElementById('s_sidebar_dark_text_color').value = e.target.value;
                    updateSidebarDarkPreview();
                }
            });
            // Init dark preview
            updateSidebarDarkPreview();

            // Dark Hover color sync
            document.getElementById('s_sidebar_dark_hover_color')?.addEventListener('input', (e) => {
                document.getElementById('s_sidebar_dark_hover_color_hex').value = e.target.value;
            });
            document.getElementById('s_sidebar_dark_hover_color_hex')?.addEventListener('input', (e) => {
                if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                    document.getElementById('s_sidebar_dark_hover_color').value = e.target.value;
                }
            });

            // Logo URL live preview
            document.getElementById('s_company_logo')?.addEventListener('input', (e) => {
                const url = e.target.value.trim();
                const preview = document.getElementById('logo-preview');
                const img = document.getElementById('logo-preview-img');
                if (url && img) { img.src = url; preview.style.display = 'block'; }
                else if (preview) { preview.style.display = 'none'; }
            });

            // Sidebar logo height live preview
            document.getElementById('s_sidebar_logo_height')?.addEventListener('input', (e) => {
                const val = e.target.value;
                const display = document.getElementById('sidebar_logo_height_val');
                if (display) display.textContent = `${val}px`;
                
                const sidebarImg = document.querySelector('#sidebar-logo-img');
                if (sidebarImg) sidebarImg.style.height = `${val}px`;
            });

            // Favicon URL live preview
            document.getElementById('s_company_favicon')?.addEventListener('input', (e) => {
                const url = e.target.value.trim();
                const preview = document.getElementById('favicon-preview');
                const img = document.getElementById('favicon-preview-img');
                if (url && img) { img.src = url; preview.style.display = 'flex'; }
                else if (preview) { preview.style.display = 'none'; }
            });

            // Certificate upload handler
            document.getElementById('dgii_cert_upload')?.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;
                const statusEl = document.getElementById('cert_upload_status');
                statusEl.style.display = 'block';
                statusEl.style.background = 'rgba(59,130,246,0.08)';
                statusEl.style.color = 'var(--color-primary)';
                statusEl.textContent = `Subiendo ${file.name}...`;
                try {
                    const formData = new FormData();
                    formData.append('certificate', file);
                    const token = document.querySelector('meta[name="csrf-token"]')?.content 
                        || window.App.state?.csrfToken || '';
                    const resp = await fetch('/api/settings/upload-certificate', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                        body: formData,
                        credentials: 'same-origin'
                    });
                    const data = await resp.json();
                    if (data.success) {
                        document.getElementById('s_dgii_certificate_path').value = data.filename;
                        statusEl.style.background = 'rgba(34,197,94,0.08)';
                        statusEl.style.color = '#16a34a';
                        statusEl.textContent = `Certificado "${data.filename}" subido correctamente.`;
                        window.App.showToast('Certificado subido', 'success');
                    } else {
                        throw new Error(data.error || 'Error al subir');
                    }
                } catch (err) {
                    statusEl.style.background = 'rgba(239,68,68,0.08)';
                    statusEl.style.color = '#ef4444';
                    statusEl.textContent = `Error: ${err.message}`;
                    window.App.showToast('Error al subir certificado', 'error');
                }
                e.target.value = '';
            });

            // DGII Test Preset handler
            document.getElementById('btn_dgii_load_preset')?.addEventListener('click', () => {
                if (!confirm('¿Cargar los datos del set de pruebas DGII?\n\nEsto sobreescribirá los campos del emisor (General + DGII) y reiniciará las secuencias e-NCF a 1.\n\nLos cambios no se guardarán hasta que presiones "Guardar Configuraciones".')) return;

                // General tab fields (Emisor data from Excel)
                const generalFields = {
                    's_company_tax_id': '40214827087',
                    's_company_name': 'DOCUMENTOS ELECTRONICOS DE 02',
                    's_company_email': 'pruebas@facturaelectronica.com',
                    's_company_phone': '809-472-7676',
                    's_company_address': 'AVE. ISABEL AGUIAR NO. 269, ZONA INDUSTRIAL DE HERRERA',
                    's_company_city': 'Santo Domingo',
                    's_company_website': 'www.facturaelectronica.com',
                };

                // DGII tab fields
                const dgiiFields = {
                    's_dgii_razon_social': 'DOCUMENTOS ELECTRONICOS DE 02',
                    's_dgii_nombre_comercial': 'DOCUMENTOS ELECTRONICOS DE 02',
                    's_dgii_municipio': '010101',
                    's_dgii_provincia': '010000',
                    's_dgii_env': 'production',
                    's_dgii_ncf_expiry_date': '2028-12-31',
                };

                // e-NCF sequences — all reset to 1
                const ncfFields = {
                    's_dgii_next_e_ncf_31': '1',
                    's_dgii_next_e_ncf_32': '1',
                    's_dgii_next_e_ncf_33': '1',
                    's_dgii_next_e_ncf_34': '1',
                    's_dgii_next_e_ncf_41': '1',
                    's_dgii_next_e_ncf_43': '1',
                    's_dgii_next_e_ncf_44': '1',
                    's_dgii_next_e_ncf_45': '1',
                    's_dgii_next_e_ncf_46': '1',
                    's_dgii_next_e_ncf_47': '1',
                };

                // Apply all fields
                const allFields = { ...generalFields, ...dgiiFields, ...ncfFields };
                let filled = 0;
                for (const [id, val] of Object.entries(allFields)) {
                    const el = document.getElementById(id);
                    if (el) { el.value = val; filled++; }
                }

                // Visual feedback on the banner
                const banner = document.getElementById('dgii_preset_banner');
                if (banner) {
                    banner.style.background = 'rgba(34,197,94,0.08)';
                    banner.style.borderColor = 'rgba(34,197,94,0.3)';
                    banner.querySelector('div > div:first-child').style.color = '#16a34a';
                    banner.querySelector('div > div:first-child').textContent = 'Datos de prueba cargados';
                    banner.querySelector('div > div:last-child').textContent = `${filled} campos actualizados. Presiona "Guardar Configuraciones" para aplicar.`;
                    setTimeout(() => {
                        banner.style.background = 'rgba(245,158,11,0.06)';
                        banner.style.borderColor = 'rgba(245,158,11,0.25)';
                        banner.querySelector('div > div:first-child').style.color = '#b45309';
                        banner.querySelector('div > div:first-child').textContent = 'Preset de Certificación DGII';
                        banner.querySelector('div > div:last-child').textContent = 'Carga automáticamente los datos del emisor y secuencias del set de pruebas oficial (RNC 40214827087).';
                    }, 5000);
                }

                window.App.showToast(`${filled} campos cargados con datos de prueba DGII`, 'success');
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
                    logo_capsule_theme: 'custom',
                    sidebar_bg_color: document.getElementById('s_sidebar_bg_color').value,
                    sidebar_text_color: document.getElementById('s_sidebar_text_color').value,
                    sidebar_hover_color: document.getElementById('s_sidebar_hover_color').value,
                    sidebar_dark_bg_color: document.getElementById('s_sidebar_dark_bg_color').value,
                    sidebar_dark_text_color: document.getElementById('s_sidebar_dark_text_color').value,
                    sidebar_dark_hover_color: document.getElementById('s_sidebar_dark_hover_color').value,
                    company_logo: document.getElementById('s_company_logo').value,
                    sidebar_logo_height: document.getElementById('s_sidebar_logo_height').value,
                    login_logo: document.getElementById('s_login_logo').value,
                    company_favicon: document.getElementById('s_company_favicon').value,
                    default_currency: document.getElementById('s_default_currency').value,
                    default_tax_rate: document.getElementById('s_default_tax_rate').value,
                    smtp_host: document.getElementById('s_smtp_host').value,
                    smtp_port: document.getElementById('s_smtp_port').value,
                    smtp_username: document.getElementById('s_smtp_username').value,
                    smtp_password: document.getElementById('s_smtp_password').value,
                    smtp_encryption: document.getElementById('s_smtp_encryption').value,
                    smtp_from_name: document.getElementById('s_smtp_from_name').value,
                    smtp_from_email: document.getElementById('s_smtp_from_email').value,
                    whatsapp_driver: document.getElementById('s_whatsapp_driver')?.value || 'meta',
                    whatsapp_phone_id: document.getElementById('s_whatsapp_phone_id')?.value || '',
                    whatsapp_business_id: document.getElementById('s_whatsapp_business_id')?.value || '',
                    whatsapp_access_token: document.getElementById('s_whatsapp_access_token')?.value || '',
                    evolution_api_url: document.getElementById('s_evolution_api_url')?.value || '',
                    evolution_api_key: document.getElementById('s_evolution_api_key')?.value || '',
                    evolution_instance: document.getElementById('s_evolution_instance')?.value || 'gridbase-bills',
                    evolution_phone_number: document.getElementById('s_evolution_phone_number')?.value || '',
                    reminders_enabled: document.getElementById('s_reminders_enabled').value,
                    reminders_days_before: document.getElementById('s_reminders_days_before').value,
                    reminders_overdue_interval: document.getElementById('s_reminders_overdue_interval').value,
                    payment_link_general: document.getElementById('s_payment_link_general').value,
                    bank_instructions: document.getElementById('s_bank_instructions').value,
                    dgii_razon_social: document.getElementById('s_dgii_razon_social').value,
                    dgii_nombre_comercial: document.getElementById('s_dgii_nombre_comercial').value,
                    dgii_municipio: document.getElementById('s_dgii_municipio').value,
                    dgii_provincia: document.getElementById('s_dgii_provincia').value,
                    dgii_env: document.getElementById('s_dgii_env').value,
                    dgii_ncf_expiry_date: document.getElementById('s_dgii_ncf_expiry_date').value,
                    dgii_certificate_path: document.getElementById('s_dgii_certificate_path').value,
                    dgii_certificate_password: document.getElementById('s_dgii_certificate_password').value,
                    dgii_next_e_ncf_31: document.getElementById('s_dgii_next_e_ncf_31').value,
                    dgii_next_e_ncf_32: document.getElementById('s_dgii_next_e_ncf_32').value,
                    dgii_next_e_ncf_33: document.getElementById('s_dgii_next_e_ncf_33').value,
                    dgii_next_e_ncf_34: document.getElementById('s_dgii_next_e_ncf_34').value,
                    dgii_next_e_ncf_41: document.getElementById('s_dgii_next_e_ncf_41').value,
                    dgii_next_e_ncf_43: document.getElementById('s_dgii_next_e_ncf_43').value,
                    dgii_next_e_ncf_44: document.getElementById('s_dgii_next_e_ncf_44').value,
                    dgii_next_e_ncf_45: document.getElementById('s_dgii_next_e_ncf_45').value,
                    dgii_next_e_ncf_46: document.getElementById('s_dgii_next_e_ncf_46').value,
                    dgii_next_e_ncf_47: document.getElementById('s_dgii_next_e_ncf_47').value,
                    pdf_primary_color: document.getElementById('s_pdf_primary_color').value,
                    pdf_accent_color: document.getElementById('s_pdf_accent_color').value,
                    pdf_header_bg_color: document.getElementById('s_pdf_header_bg_color').value,
                    pdf_header_text_color: document.getElementById('s_pdf_header_text_color').value,
                    pdf_table_header_bg_color: document.getElementById('s_pdf_table_header_bg_color').value,
                    pdf_table_header_text_color: document.getElementById('s_pdf_table_header_text_color').value,
                    pdf_footer_bg_color: document.getElementById('s_pdf_footer_bg_color').value,
                    pdf_footer_text_color: document.getElementById('s_pdf_footer_text_color').value,
                    pdf_logo_url: document.getElementById('s_pdf_logo_url').value,
                    pdf_show_footer: document.getElementById('s_pdf_show_footer').value,
                    invoice_pdf_template: document.getElementById('s_invoice_pdf_template').value,
                };
                try {
                    await window.App.api('settings', { method: 'POST', body: settingsToUpdate });
                    
                    // Update global state
                    window.App.state.settings = { ...window.App.state.settings, ...settingsToUpdate };
                    
                    // Cache branding elements in localStorage
                    if (settingsToUpdate.sidebar_bg_color) localStorage.setItem('sidebar_bg_color', settingsToUpdate.sidebar_bg_color);
                    if (settingsToUpdate.sidebar_text_color) localStorage.setItem('sidebar_text_color', settingsToUpdate.sidebar_text_color);
                    if (settingsToUpdate.sidebar_hover_color) localStorage.setItem('sidebar_hover_color', settingsToUpdate.sidebar_hover_color);
                    if (settingsToUpdate.sidebar_dark_bg_color) localStorage.setItem('sidebar_dark_bg_color', settingsToUpdate.sidebar_dark_bg_color);
                    if (settingsToUpdate.sidebar_dark_text_color) localStorage.setItem('sidebar_dark_text_color', settingsToUpdate.sidebar_dark_text_color);
                    if (settingsToUpdate.sidebar_dark_hover_color) localStorage.setItem('sidebar_dark_hover_color', settingsToUpdate.sidebar_dark_hover_color);
                    
                    // Apply sidebar colors immediately via centralized method
                    window.App.applySidebarColors();
                    
                    window.App.showToast('Configuraciones guardadas');
                    
                    // Update logo + favicon immediately
                    if (settingsToUpdate.company_logo !== undefined) {
                        localStorage.setItem('company_logo', settingsToUpdate.company_logo);
                        const sidebarImg = document.querySelector('.sidebar-logo img');
                        if (sidebarImg) sidebarImg.src = settingsToUpdate.company_logo;
                    }
                    if (settingsToUpdate.sidebar_logo_height !== undefined) {
                        localStorage.setItem('sidebar_logo_height', settingsToUpdate.sidebar_logo_height);
                        const sidebarImg = document.querySelector('.sidebar-logo img');
                        if (sidebarImg) sidebarImg.style.height = `${settingsToUpdate.sidebar_logo_height}px`;
                    }
                    if (settingsToUpdate.login_logo !== undefined) {
                        localStorage.setItem('login_logo', settingsToUpdate.login_logo);
                    }
                    if (settingsToUpdate.company_favicon !== undefined) {
                        localStorage.setItem('company_favicon', settingsToUpdate.company_favicon);
                        window.App.updateFavicon();
                    }
                    window.App.updateTitle();
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

            // ── WhatsApp Test ──
            document.getElementById('btn-test-whatsapp')?.addEventListener('click', async () => {
                const btn = document.getElementById('btn-test-whatsapp');
                const phone = document.getElementById('wa_test_phone')?.value?.trim();
                const result = document.getElementById('wa-test-result');
                if (!phone) return window.App.showToast('Ingresa un número de WhatsApp', 'error');
                const orig = btn.innerHTML;
                btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> Enviando...';
                btn.disabled = true;
                result.style.display = 'none';
                try {
                    const res = await window.App.api('settings/whatsapp-test', { method: 'POST', body: { phone } });
                    result.style.display = 'block';
                    if (res.success) {
                        result.style.background = 'rgba(34,197,94,0.08)';
                        result.style.border = '1px solid rgba(34,197,94,0.3)';
                        result.style.color = '#16a34a';
                        result.innerHTML = `Mensaje enviado correctamente (driver: <strong>${res.driver}</strong>)`;
                    } else {
                        result.style.background = 'rgba(239,68,68,0.08)';
                        result.style.border = '1px solid rgba(239,68,68,0.3)';
                        result.style.color = '#dc2626';
                        result.innerHTML = `Error: ${res.error || 'desconocido'}`;
                    }
                } catch(err) {
                    result.style.display = 'block';
                    result.style.background = 'rgba(239,68,68,0.08)';
                    result.style.border = '1px solid rgba(239,68,68,0.3)';
                    result.style.color = '#dc2626';
                    result.textContent = 'Error al enviar el mensaje.';
                } finally { btn.innerHTML = orig; btn.disabled = false; }
            });

            // ── Evolution API Status ──
            document.getElementById('btn-evolution-status')?.addEventListener('click', async () => {
                const badge = document.getElementById('evolution-status-badge');
                badge.style.display = 'inline-flex';
                badge.style.alignItems = 'center';
                badge.style.gap = '6px';
                badge.style.padding = '4px 10px';
                badge.style.borderRadius = '999px';
                badge.style.fontSize = '12px';
                badge.style.fontWeight = '600';
                badge.style.background = 'var(--color-border)';
                badge.style.color = 'var(--color-text-muted)';
                badge.textContent = 'Verificando...';
                try {
                    const res = await window.App.api('settings/evolution-status');
                    if (res.connected) {
                        badge.style.background = 'rgba(34,197,94,0.12)';
                        badge.style.color = '#16a34a';
                        badge.textContent = 'Conectado';
                    } else {
                        badge.style.background = 'rgba(239,68,68,0.12)';
                        badge.style.color = '#dc2626';
                        badge.textContent = res.message || res.state || 'Desconectado';
                    }
                } catch(err) {
                    badge.style.background = 'rgba(239,68,68,0.12)';
                    badge.style.color = '#dc2626';
                    badge.textContent = 'Error de conexion';
                }
            });

            // ── Evolution API QR Code ──
            document.getElementById('btn-evolution-qr')?.addEventListener('click', async () => {
                const qrContainer = document.getElementById('evolution-qr-container');
                qrContainer.style.display = 'block';
                document.getElementById('evolution-pairing-container').style.display = 'none';
                qrContainer.innerHTML = '<div style="padding:20px;color:var(--color-text-muted);font-size:13px;">Obteniendo QR...</div>';
                try {
                    const res = await window.App.api('settings/evolution-qr');
                    if (res.success && res.qr_code) {
                        qrContainer.innerHTML = `<div style="padding:12px;background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-lg);display:inline-block;">
                            <img src="${res.qr_code}" style="width:220px;height:220px;display:block;" alt="QR WhatsApp">
                            <div style="font-size:11px;color:var(--color-text-muted);margin-top:8px;text-align:center;">Escanea con WhatsApp → Dispositivos Vinculados → Vincular dispositivo</div>
                        </div>`;
                    } else if (res.success) {
                        qrContainer.innerHTML = '<div style="padding:12px 16px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.3);border-radius:var(--radius-md);color:#16a34a;font-size:13px;">Ya conectado — no se requiere escanear QR.</div>';
                    } else {
                        qrContainer.innerHTML = `<div style="padding:12px 16px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.3);border-radius:var(--radius-md);color:#dc2626;font-size:13px;">${res.message || 'Error al obtener QR'}</div>`;
                    }
                } catch(err) {
                    qrContainer.innerHTML = '<div style="color:#dc2626;font-size:13px;">Error al obtener QR. Verifica la URL y API Key.</div>';
                }
            });

            // ── Evolution API Pairing Code ──
            document.getElementById('btn-evolution-pairing')?.addEventListener('click', async () => {
                const container = document.getElementById('evolution-pairing-container');
                const phoneInput = document.getElementById('s_evolution_phone_number');
                const phone = phoneInput?.value?.trim();

                if (!phone) {
                    window.App.toast('Ingresa el número de WhatsApp primero', 'error');
                    phoneInput?.focus();
                    return;
                }

                container.style.display = 'block';
                document.getElementById('evolution-qr-container').style.display = 'none';
                container.innerHTML = `<div style="padding:24px;background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-lg);">
                    <div style="display:flex;align-items:center;justify-content:center;gap:8px;color:var(--color-text-muted);font-size:14px;">
                        <svg style="animation:spin 1s linear infinite;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                        Generando código de vinculación...
                    </div>
                </div>`;

                try {
                    const res = await window.App.api('settings/evolution-pairing-code', {
                        method: 'POST',
                        body: JSON.stringify({ phone_number: phone }),
                    });

                    if (res.success && res.pairing_code) {
                        const formatted = res.formatted || res.pairing_code;
                        container.innerHTML = `<div style="padding:24px;background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-lg);">
                            <div style="font-size:13px;font-weight:600;margin-bottom:12px;color:var(--color-text);text-align:center;">Código de Vinculación</div>
                            <div style="font-size:36px;font-weight:800;letter-spacing:6px;font-family:'JetBrains Mono',monospace;color:var(--color-primary);text-align:center;padding:16px 0;">${formatted}</div>
                            <div style="font-size:12px;color:var(--color-text-muted);text-align:center;line-height:1.6;margin-top:8px;">
                                <strong>Pasos:</strong><br>
                                1. Abre WhatsApp → ⋮ → Dispositivos vinculados<br>
                                2. Toca "Vincular un dispositivo"<br>
                                3. Toca <strong>"Vincular con el número de teléfono"</strong> (link azul abajo)<br>
                                4. Introduce tu número y luego este código<br>
                                <div style="margin-top:8px;padding:6px 10px;background:rgba(234,179,8,0.1);border:1px solid rgba(234,179,8,0.3);border-radius:var(--radius-sm);color:#a16207;">
                                    ⏱ El código expira en ~60 segundos. Si expira, presiona el botón de nuevo.
                                </div>
                            </div>
                        </div>`;
                    } else {
                        container.innerHTML = `<div style="padding:16px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.3);border-radius:var(--radius-md);color:#dc2626;font-size:13px;text-align:center;">${res.message || 'Error al generar código'}</div>`;
                    }
                } catch(err) {
                    container.innerHTML = '<div style="padding:16px;color:#dc2626;font-size:13px;text-align:center;">Error al generar código. Verifica la configuración.</div>';
                }
            });

            // ── PDF Appearance: Color sync + Live Preview ──
            const updatePdfPreview = () => {
                const primary = document.getElementById('s_pdf_primary_color').value;
                const accent = document.getElementById('s_pdf_accent_color').value;
                const headerBg = document.getElementById('s_pdf_header_bg_color').value;
                const headerText = document.getElementById('s_pdf_header_text_color').value;
                const tableHeaderBg = document.getElementById('s_pdf_table_header_bg_color').value;
                const tableHeaderTxt = document.getElementById('s_pdf_table_header_text_color').value;
                const footerBg = document.getElementById('s_pdf_footer_bg_color').value;
                const footerText = document.getElementById('s_pdf_footer_text_color').value;
                const footerVisible = document.getElementById('s_pdf_show_footer').value === '1';

                // Header & footer backgrounds
                const header = document.getElementById('pdf-preview-header');
                const footer = document.getElementById('pdf-preview-footer');
                if (header) {
                    header.style.background = headerBg;
                    header.style.color = headerText;
                }
                if (footer) {
                    footer.style.background = footerBg;
                    footer.style.color = footerText;
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

                // Meta labels (accent) / value labels (white in preview if background is dark, otherwise primary)
                document.querySelectorAll('#pdf-preview-header span[style*="font-weight:700"]').forEach(el => {
                    if (el.textContent.includes('/')) {
                        el.style.color = accent;
                    } else {
                        el.style.color = headerText;
                    }
                });

                // Table header
                const thead = document.querySelector('#pdf-preview-card thead tr');
                if (thead) thead.style.background = tableHeaderBg;
                document.querySelectorAll('#pdf-preview-card thead th').forEach(th => {
                    th.style.color = tableHeaderTxt;
                });
            };

            // Sync helper
            const syncColorPair = (pickerId, hexId) => {
                const picker = document.getElementById(pickerId);
                const hex = document.getElementById(hexId);
                if (picker && hex) {
                    picker.addEventListener('input', () => {
                        hex.value = picker.value.toUpperCase();
                        updatePdfPreview();
                    });
                    hex.addEventListener('input', () => {
                        if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) {
                            picker.value = hex.value;
                            updatePdfPreview();
                        }
                    });
                }
            };

            // Sync color picker <-> hex input (Primary)
            const primaryPicker = document.getElementById('s_pdf_primary_color');
            const primaryHex = document.getElementById('s_pdf_primary_color_hex');
            if (primaryPicker && primaryHex) {
                primaryPicker.addEventListener('input', () => {
                    const val = primaryPicker.value;
                    const oldVal = primaryHex.value;
                    primaryHex.value = val.toUpperCase();
                    
                    // If header bg, table header text, or footer bg were matching the old primary color, update them too
                    ['s_pdf_header_bg_color', 's_pdf_table_header_text_color', 's_pdf_footer_bg_color'].forEach(id => {
                        const picker = document.getElementById(id);
                        const hex = document.getElementById(id + '_hex');
                        if (picker && hex && picker.value.toUpperCase() === oldVal.toUpperCase()) {
                            picker.value = val;
                            hex.value = val.toUpperCase();
                        }
                    });
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
                    const val = accentPicker.value;
                    const oldVal = accentHex.value;
                    accentHex.value = val.toUpperCase();
                    
                    // If table header bg matches old accent, update it too
                    const tableHeaderBgPicker = document.getElementById('s_pdf_table_header_bg_color');
                    const tableHeaderBgHex = document.getElementById('s_pdf_table_header_bg_color_hex');
                    if (tableHeaderBgPicker && tableHeaderBgHex && tableHeaderBgPicker.value.toUpperCase() === oldVal.toUpperCase()) {
                        tableHeaderBgPicker.value = val;
                        tableHeaderBgHex.value = val.toUpperCase();
                    }
                    updatePdfPreview();
                });
                accentHex.addEventListener('input', () => {
                    if (/^#[0-9A-Fa-f]{6}$/.test(accentHex.value)) {
                        accentPicker.value = accentHex.value;
                        updatePdfPreview();
                    }
                });
            }

            // Sync detailed colors
            syncColorPair('s_pdf_header_bg_color', 's_pdf_header_bg_color_hex');
            syncColorPair('s_pdf_header_text_color', 's_pdf_header_text_color_hex');
            syncColorPair('s_pdf_table_header_bg_color', 's_pdf_table_header_bg_color_hex');
            syncColorPair('s_pdf_table_header_text_color', 's_pdf_table_header_text_color_hex');
            syncColorPair('s_pdf_footer_bg_color', 's_pdf_footer_bg_color_hex');
            syncColorPair('s_pdf_footer_text_color', 's_pdf_footer_text_color_hex');

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

            // User devices management helper
            const loadUserDevices = async () => {
                const containerEl = document.getElementById('devices-list-container');
                if (!containerEl) return;
                
                try {
                    const devices = await window.App.api('auth/devices');
                    if (devices.length === 0) {
                        containerEl.innerHTML = `
                            <div style="text-align:center;padding:32px;color:var(--color-text-muted);border:1px dashed var(--color-border);border-radius:var(--radius-lg);">
                                No hay dispositivos autorizados para acceso por PIN en esta cuenta.
                            </div>
                        `;
                        return;
                    }
                    
                    containerEl.innerHTML = devices.map(d => {
                        const lastUsed = d.last_used_at ? new Date(d.last_used_at).toLocaleString('es-DO', { day: 'numeric', month: 'short', hour: '2-digit', minute:'2-digit' }) : 'Nunca';
                        const isCurrent = d.device_token === localStorage.getItem('device_token');
                        
                        return `
                            <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:var(--bg-card); border:1px solid var(--color-border); border-radius:var(--radius-lg); gap:16px; box-shadow:var(--shadow-sm); margin-bottom: 12px;">
                                <div style="display:flex; align-items:center; gap:16px; min-width:0; flex:1;">
                                    <div style="width:40px; height:40px; border-radius:50%; background:var(--bg-hover); display:flex; align-items:center; justify-content:center; color:var(--color-text-primary); border:1px solid var(--color-border); flex-shrink:0;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                    </div>
                                    <div style="min-width:0; flex:1;">
                                        <div style="font-size:14px; font-weight:700; color:var(--color-text-primary); display:flex; align-items:center; gap:8px;">
                                            ${d.device_name || 'Dispositivo desconocido'}
                                            ${isCurrent ? '<span class="badge badge-active">Este Dispositivo</span>' : ''}
                                        </div>
                                        <div style="font-size:12px; color:var(--color-text-muted); margin-top:2px;">Último uso: ${lastUsed}</div>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-danger btn-revoke-device" data-id="${d.id}" style="background:#FEE2E2; color:#DC2626; border:1px solid #FCA5A5; font-size:12px; padding:6px 12px; font-weight:600; border-radius:var(--radius-md); cursor:pointer; transition: all 0.15s ease;">
                                        Revocar Acceso
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('');
                    
                    // Attach click handlers to revoke buttons
                    containerEl.querySelectorAll('.btn-revoke-device').forEach(btn => {
                        btn.addEventListener('click', async (e) => {
                            const deviceId = btn.dataset.id;
                            if (confirm('¿Estás seguro de que deseas revocar el acceso PIN para este dispositivo? Tendrás que iniciar sesión con contraseña la próxima vez.')) {
                                btn.disabled = true;
                                btn.innerHTML = '<span class="spinner"></span>';
                                try {
                                    await window.App.api(`auth/devices/${deviceId}`, { method: 'DELETE' });
                                    window.App.showToast('Acceso revocado con éxito', 'success');
                                    const isCurrent = devices.find(x => x.id == deviceId)?.device_token === localStorage.getItem('device_token');
                                    if (isCurrent) {
                                        localStorage.removeItem('device_token');
                                    }
                                    loadUserDevices();
                                } catch (err) {
                                    window.App.showToast(err.message, 'error');
                                    loadUserDevices();
                                }
                            }
                        });
                    });
                    
                } catch (error) {
                    containerEl.innerHTML = `
                        <div style="color:var(--color-danger); text-align:center; padding:16px;">
                            Error al cargar dispositivos: ${error.message}
                        </div>
                    `;
                }
            };

            // Hide/Show Save button based on active tab + load API Keys tab lazily
            let apiKeysLoaded = false;
            tabs.forEach(tab => {
                tab.addEventListener('click', async () => {
                    const saveActions = container.querySelector('#settings-save-actions');
                    const hideSaveTabs = ['support', 'apikeys', 'devices'];
                    if (saveActions) {
                        saveActions.style.display = hideSaveTabs.includes(tab.dataset.tab) ? 'none' : 'block';
                    }
                    // Lazy-load API Keys module
                    if (tab.dataset.tab === 'apikeys' && !apiKeysLoaded) {
                        apiKeysLoaded = true;
                        try {
                            const mod = await import('./api-keys.js?v=58');
                            mod.default.render(document.getElementById('api-keys-container'));
                        } catch (e) {
                            document.getElementById('api-keys-container').innerHTML = '<div class="text-red">Error al cargar módulo de API Keys</div>';
                            apiKeysLoaded = false;
                        }
                    }
                    // Load user devices dynamically
                    if (tab.dataset.tab === 'devices') {
                        loadUserDevices();
                    }
                });
            });

        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar configuraciones</div>`;
        }
    }
};
