/**
 * API Keys Management Module
 * Renders inside the Settings > API Keys tab.
 */
const PERMISSION_LABELS = {
    'invoices.create': { label: 'Crear Facturas', icon: '📝', group: 'Facturas' },
    'invoices.read':   { label: 'Ver Facturas',   icon: '👁️', group: 'Facturas' },
    'quotes.create':   { label: 'Crear Cotizaciones', icon: '📋', group: 'Cotizaciones' },
    'quotes.read':     { label: 'Ver Cotizaciones',   icon: '👁️', group: 'Cotizaciones' },
    'quotes.convert':  { label: 'Convertir a Factura', icon: '🔄', group: 'Cotizaciones' },
    'clients.create':  { label: 'Crear Clientes', icon: '👤', group: 'Clientes' },
    'clients.read':    { label: 'Ver Clientes',   icon: '👁️', group: 'Clientes' },
};

const ALL_PERMISSIONS = Object.keys(PERMISSION_LABELS);

function formatDate(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('es-DO', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function renderPermissionBadges(permissions) {
    if (!permissions || permissions.length === 0) return '<span style="color:var(--color-text-muted);font-size:12px;">Sin permisos</span>';
    if (permissions.includes('*')) return '<span class="badge" style="background:var(--color-primary);color:#fff;font-size:11px;padding:2px 8px;border-radius:12px;">Todos</span>';
    return permissions.map(p => {
        const info = PERMISSION_LABELS[p] || { label: p, icon: '🔒' };
        return `<span class="badge" style="background:var(--bg-hover);color:var(--color-text);font-size:11px;padding:2px 8px;border-radius:12px;margin:2px;">${info.icon} ${info.label}</span>`;
    }).join('');
}

function renderPermissionsCheckboxes(selected = [], idPrefix = 'perm') {
    const groups = {};
    ALL_PERMISSIONS.forEach(p => {
        const info = PERMISSION_LABELS[p];
        if (!groups[info.group]) groups[info.group] = [];
        groups[info.group].push(p);
    });

    let html = '';
    for (const [group, perms] of Object.entries(groups)) {
        html += `<div style="margin-bottom:12px;">
            <div style="font-size:12px;font-weight:600;color:var(--color-text-secondary);margin-bottom:6px;">${group}</div>`;
        perms.forEach(p => {
            const info = PERMISSION_LABELS[p];
            const checked = selected.includes(p) || selected.includes('*') ? 'checked' : '';
            html += `<label style="display:flex;align-items:center;gap:8px;margin-bottom:4px;cursor:pointer;font-size:13px;">
                <input type="checkbox" class="${idPrefix}-checkbox" value="${p}" ${checked} style="accent-color:var(--color-primary);">
                ${info.icon} ${info.label}
            </label>`;
        });
        html += '</div>';
    }
    return html;
}

export default {
    async render(container) {
        try {
            const res = await window.App.api('api-keys');
            const keys = res.data || [];

            container.innerHTML = `
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
                    <div>
                        <h3 style="font-size:15px;font-weight:600;margin:0 0 6px;">API Keys para Integración Externa</h3>
                        <p style="color:var(--color-text-muted);font-size:13px;margin:0;">
                            Permite que sistemas externos (sitios web, e-commerce, ERP) creen facturas y cotizaciones automáticamente.
                        </p>
                    </div>
                    <button type="button" id="btn-create-api-key" class="btn btn-primary" style="white-space:nowrap;display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Nueva API Key
                    </button>
                </div>

                ${keys.length === 0 ? `
                    <div style="text-align:center;padding:48px 20px;background:var(--bg-hover);border-radius:var(--radius-lg);border:1px dashed var(--color-border);">
                        <div style="font-size:48px;margin-bottom:12px;">🔑</div>
                        <p style="font-size:15px;font-weight:600;margin:0 0 6px;">Sin API Keys</p>
                        <p style="font-size:13px;color:var(--color-text-muted);margin:0;">Crea una API key para permitir integraciones externas con tu sistema de facturación.</p>
                    </div>
                ` : `
                    <div style="display:flex;flex-direction:column;gap:12px;" id="api-keys-list">
                        ${keys.map(k => this.renderKeyCard(k)).join('')}
                    </div>
                `}

                <!-- Documentation Section -->
                <div style="margin-top:32px;border-top:1px solid var(--color-border);padding-top:24px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                        <div>
                            <h4 style="font-size:14px;font-weight:600;margin:0 0 6px;display:flex;align-items:center;gap:8px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                Documentación Completa de la API
                            </h4>
                            <p style="color:var(--color-text-muted);font-size:13px;margin:0;">Guía de instalación con ejemplos en cURL, JavaScript, PHP, Python, C# y WordPress/WooCommerce.</p>
                        </div>
                        <a href="/api-docs" target="_blank" class="btn btn-secondary" style="white-space:nowrap;display:flex;align-items:center;gap:6px;text-decoration:none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            Ver Documentación
                        </a>
                    </div>
                    <div style="background:var(--bg-hover);border-radius:var(--radius-lg);padding:20px;font-size:13px;margin-top:16px;">
                        <p style="margin:0 0 12px;"><strong>Base URL:</strong> <code style="background:var(--color-primary);color:#fff;padding:2px 8px;border-radius:4px;">${window.location.origin}/api/v1</code></p>
                        <p style="margin:0 0 12px;"><strong>Autenticación:</strong> Header <code>Authorization: Bearer gb_tu_token_aqui</code></p>
                        <p style="margin:0;"><strong>Ejemplo rápido:</strong></p>
                        <pre style="background:#1e1e2e;color:#cdd6f4;padding:16px;border-radius:var(--radius-md);overflow-x:auto;font-size:12px;line-height:1.5;margin:8px 0 0;"><code>curl -X POST ${window.location.origin}/api/v1/invoices \\
  -H "Authorization: Bearer gb_tu_token..." \\
  -H "Content-Type: application/json" \\
  -d '{"client":{"tax_id":"131456789","company_name":"Mi Empresa"},"items":[{"description":"Servicio","quantity":1,"unit_price":5000}],"currency":"DOP","tax_rate":18}'</code></pre>
                    </div>
                </div>
            `;

            // ── Event Listeners ──

            // Create API Key
            container.querySelector('#btn-create-api-key')?.addEventListener('click', () => this.showCreateModal(container));

            // Card actions (edit, toggle, regenerate, delete)
            container.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const action = e.currentTarget.dataset.action;
                    const id = e.currentTarget.dataset.id;
                    if (action === 'edit') this.showEditModal(container, id);
                    else if (action === 'toggle') this.toggleKey(container, id);
                    else if (action === 'regenerate') this.regenerateKey(container, id);
                    else if (action === 'delete') this.deleteKey(container, id);
                });
            });

        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar API Keys: ${e.message}</div>`;
        }
    },

    renderKeyCard(k) {
        const statusColor = k.is_active ? '#22c55e' : '#ef4444';
        const statusText = k.is_active ? 'Activa' : 'Revocada';
        const isExpired = k.expires_at && new Date(k.expires_at) < new Date();

        return `
        <div style="background:var(--bg-card);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:20px;${!k.is_active ? 'opacity:0.6;' : ''}">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <span style="font-size:15px;font-weight:600;">${k.name}</span>
                        <span style="background:${statusColor}22;color:${statusColor};font-size:11px;padding:2px 10px;border-radius:12px;font-weight:600;">${statusText}</span>
                        ${isExpired ? '<span style="background:#ef444422;color:#ef4444;font-size:11px;padding:2px 10px;border-radius:12px;font-weight:600;">Expirada</span>' : ''}
                    </div>
                    <div style="font-family:monospace;font-size:13px;color:var(--color-text-secondary);margin-bottom:10px;">${k.prefix}${'•'.repeat(20)}</div>
                    <div style="margin-bottom:10px;">${renderPermissionBadges(k.permissions)}</div>
                    <div style="display:flex;gap:20px;font-size:12px;color:var(--color-text-muted);">
                        <span>📊 ${k.rate_limit} req/min</span>
                        <span>🕐 Último uso: ${formatDate(k.last_used_at)}</span>
                        <span>📅 Creada: ${formatDate(k.created_at)}</span>
                        ${k.expires_at ? `<span>⏳ Expira: ${formatDate(k.expires_at)}</span>` : ''}
                        <span>👤 ${k.created_by}</span>
                    </div>
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0;">
                    <button type="button" class="btn btn-ghost btn-sm" data-action="edit" data-id="${k.id}" title="Editar" style="padding:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm" data-action="toggle" data-id="${k.id}" title="${k.is_active ? 'Desactivar' : 'Activar'}" style="padding:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="${k.is_active ? '#ef4444' : '#22c55e'}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm" data-action="regenerate" data-id="${k.id}" title="Regenerar token" style="padding:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm" data-action="delete" data-id="${k.id}" title="Eliminar" style="padding:6px;color:#ef4444;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </div>
            </div>
        </div>`;
    },

    showCreateModal(container) {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:1000;display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML = `
        <div style="background:var(--bg-card);border-radius:var(--radius-xl);padding:28px;width:520px;max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <h3 style="margin:0 0 20px;font-size:17px;font-weight:700;">🔑 Nueva API Key</h3>
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Nombre</label>
                <input type="text" id="ak-name" class="form-control" placeholder="Ej: Mi Tienda Web, ERP, WooCommerce...">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Permisos</label>
                <div style="background:var(--bg-hover);border-radius:var(--radius-md);padding:16px;border:1px solid var(--color-border);">
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;cursor:pointer;font-size:13px;font-weight:600;">
                        <input type="checkbox" id="ak-select-all" style="accent-color:var(--color-primary);"> Seleccionar todos
                    </label>
                    <div style="border-top:1px solid var(--color-border);padding-top:12px;">
                        ${renderPermissionsCheckboxes([], 'ak')}
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:16px;margin-bottom:16px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Rate Limit (req/min)</label>
                    <input type="number" id="ak-rate-limit" class="form-control" value="60" min="1" max="1000">
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Fecha de Expiración</label>
                    <input type="date" id="ak-expires" class="form-control">
                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px;">Dejar vacío = sin expiración</div>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--color-border);padding-top:16px;">
                <button type="button" id="ak-cancel" class="btn btn-secondary">Cancelar</button>
                <button type="button" id="ak-create" class="btn btn-primary">Crear API Key</button>
            </div>
        </div>`;
        document.body.appendChild(overlay);

        // Select all toggle
        overlay.querySelector('#ak-select-all').addEventListener('change', (e) => {
            overlay.querySelectorAll('.ak-checkbox').forEach(cb => cb.checked = e.target.checked);
        });

        overlay.querySelector('#ak-cancel').addEventListener('click', () => overlay.remove());
        overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });

        overlay.querySelector('#ak-create').addEventListener('click', async () => {
            const name = overlay.querySelector('#ak-name').value.trim();
            if (!name) return window.App.showToast('Ingresa un nombre para la API key', 'error');

            const permissions = Array.from(overlay.querySelectorAll('.ak-checkbox:checked')).map(cb => cb.value);
            if (permissions.length === 0) return window.App.showToast('Selecciona al menos un permiso', 'error');

            const rateLimit = parseInt(overlay.querySelector('#ak-rate-limit').value) || 60;
            const expiresAt = overlay.querySelector('#ak-expires').value || null;

            try {
                const res = await window.App.api('api-keys', {
                    method: 'POST',
                    body: { name, permissions, rate_limit: rateLimit, expires_at: expiresAt }
                });

                overlay.remove();
                this.showTokenModal(res.data.token, res.data.name);
                this.render(container); // Refresh the list
            } catch (e) {
                // Error handled by App.api
            }
        });
    },

    showTokenModal(token, name) {
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:1001;display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML = `
        <div style="background:var(--bg-card);border-radius:var(--radius-xl);padding:28px;width:560px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="text-align:center;margin-bottom:20px;">
                <div style="font-size:48px;margin-bottom:8px;">✅</div>
                <h3 style="margin:0;font-size:17px;font-weight:700;">API Key Creada: ${name}</h3>
            </div>
            <div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:var(--radius-md);padding:14px;margin-bottom:20px;">
                <div style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:6px;">⚠️ IMPORTANTE — Copia este token ahora</div>
                <div style="font-size:12px;color:#92400e;">Este token no se mostrará de nuevo. Si lo pierdes, tendrás que regenerarlo.</div>
            </div>
            <div style="position:relative;margin-bottom:20px;">
                <input type="text" id="token-display" value="${token}" readonly style="width:100%;font-family:monospace;font-size:13px;padding:12px;background:#1e1e2e;color:#cdd6f4;border:1px solid var(--color-border);border-radius:var(--radius-md);padding-right:80px;">
                <button type="button" id="btn-copy-token" style="position:absolute;right:4px;top:50%;transform:translateY(-50%);background:var(--color-primary);color:#fff;border:none;padding:6px 14px;border-radius:var(--radius-sm);cursor:pointer;font-size:12px;font-weight:600;">Copiar</button>
            </div>
            <div style="text-align:center;">
                <button type="button" id="btn-close-token" class="btn btn-primary">Entendido, ya lo copié</button>
            </div>
        </div>`;
        document.body.appendChild(overlay);

        overlay.querySelector('#btn-copy-token').addEventListener('click', () => {
            navigator.clipboard.writeText(token);
            const btn = overlay.querySelector('#btn-copy-token');
            btn.textContent = '✓ Copiado';
            setTimeout(() => btn.textContent = 'Copiar', 2000);
        });

        overlay.querySelector('#btn-close-token').addEventListener('click', () => overlay.remove());
    },

    async showEditModal(container, id) {
        try {
            const res = await window.App.api(`api-keys/${id}`);
            const k = res.data;

            const overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:1000;display:flex;align-items:center;justify-content:center;';
            overlay.innerHTML = `
            <div style="background:var(--bg-card);border-radius:var(--radius-xl);padding:28px;width:520px;max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <h3 style="margin:0 0 20px;font-size:17px;font-weight:700;">✏️ Editar API Key</h3>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Nombre</label>
                    <input type="text" id="ek-name" class="form-control" value="${k.name}">
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Permisos</label>
                    <div style="background:var(--bg-hover);border-radius:var(--radius-md);padding:16px;border:1px solid var(--color-border);">
                        ${renderPermissionsCheckboxes(k.permissions, 'ek')}
                    </div>
                </div>
                <div style="display:flex;gap:16px;margin-bottom:16px;">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Rate Limit (req/min)</label>
                        <input type="number" id="ek-rate-limit" class="form-control" value="${k.rate_limit}" min="1" max="1000">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Expiración</label>
                        <input type="date" id="ek-expires" class="form-control" value="${k.expires_at ? k.expires_at.split('T')[0] : ''}">
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--color-border);padding-top:16px;">
                    <button type="button" id="ek-cancel" class="btn btn-secondary">Cancelar</button>
                    <button type="button" id="ek-save" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </div>`;
            document.body.appendChild(overlay);

            overlay.querySelector('#ek-cancel').addEventListener('click', () => overlay.remove());
            overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });

            overlay.querySelector('#ek-save').addEventListener('click', async () => {
                const name = overlay.querySelector('#ek-name').value.trim();
                const permissions = Array.from(overlay.querySelectorAll('.ek-checkbox:checked')).map(cb => cb.value);
                const rateLimit = parseInt(overlay.querySelector('#ek-rate-limit').value) || 60;
                const expiresAt = overlay.querySelector('#ek-expires').value || null;

                try {
                    await window.App.api(`api-keys/${id}`, {
                        method: 'PUT',
                        body: { name, permissions, rate_limit: rateLimit, expires_at: expiresAt }
                    });
                    overlay.remove();
                    window.App.showToast('API Key actualizada', 'success');
                    this.render(container);
                } catch (e) { /* handled */ }
            });
        } catch (e) { /* handled */ }
    },

    async toggleKey(container, id) {
        try {
            const res = await window.App.api(`api-keys/${id}`);
            const newState = !res.data.is_active;
            const action = newState ? 'activar' : 'desactivar';

            if (!confirm(`¿Estás seguro de ${action} esta API key?`)) return;

            await window.App.api(`api-keys/${id}`, {
                method: 'PUT',
                body: { is_active: newState }
            });
            window.App.showToast(`API Key ${newState ? 'activada' : 'desactivada'}`, 'success');
            this.render(container);
        } catch (e) { /* handled */ }
    },

    async regenerateKey(container, id) {
        if (!confirm('¿Regenerar el token? El token anterior dejará de funcionar inmediatamente.')) return;

        try {
            const res = await window.App.api(`api-keys/${id}/regenerate`, { method: 'POST' });
            this.showTokenModal(res.data.token, res.data.name);
            this.render(container);
        } catch (e) { /* handled */ }
    },

    async deleteKey(container, id) {
        if (!confirm('¿Eliminar esta API key de forma permanente? Esta acción no se puede deshacer.')) return;

        try {
            await window.App.api(`api-keys/${id}`, { method: 'DELETE' });
            window.App.showToast('API Key eliminada', 'success');
            this.render(container);
        } catch (e) { /* handled */ }
    }
};
