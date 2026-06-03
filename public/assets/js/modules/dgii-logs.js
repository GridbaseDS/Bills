export default {
    async render(container) {
        container.innerHTML = `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Auditoría DGII</h1>
                    <p class="page-subtitle">Log detallado de cada interacción con la DGII — XML, HTTP, firma, QR y verificaciones</p>
                </div>
                <div style="display:flex;gap:8px;">
                    <button id="btn-refresh-logs" class="btn btn-secondary" style="padding:8px 16px;font-size:13px;">↻ Refrescar</button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div id="dgii-log-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;"></div>

            <!-- Filters -->
            <div class="table-outer" style="margin-bottom:var(--spacing-lg);">
                <div style="padding:16px var(--spacing-xl);display:flex;flex-wrap:wrap;gap:10px;align-items:center;border-bottom:1px solid var(--color-border);">
                    <input type="text" id="log-search" placeholder="Buscar por eNCF, mensaje, trackId..." style="flex:1;min-width:180px;padding:8px 14px;border:1px solid var(--color-border);border-radius:var(--radius-md);font-size:13px;background:var(--color-bg-primary);color:var(--color-text-primary);">
                    <select id="log-level-filter" style="padding:8px 12px;border:1px solid var(--color-border);border-radius:var(--radius-md);font-size:13px;background:var(--color-bg-primary);color:var(--color-text-primary);">
                        <option value="">Todos los niveles</option>
                        <option value="info">ℹ️ Info</option>
                        <option value="warning">⚠️ Warning</option>
                        <option value="error">❌ Error</option>
                        <option value="critical">🔴 Critical</option>
                    </select>
                    <select id="log-step-filter" style="padding:8px 12px;border:1px solid var(--color-border);border-radius:var(--radius-md);font-size:13px;background:var(--color-bg-primary);color:var(--color-text-primary);max-width:200px;">
                        <option value="">Todos los pasos</option>
                        <option value="process_start">🚀 process_start</option>
                        <option value="encf_assigned">🏷️ encf_assigned</option>
                        <option value="xml_built">📄 xml_built</option>
                        <option value="xml_signed">🔏 xml_signed</option>
                        <option value="signed_saved">💾 signed_saved</option>
                        <option value="auth_">🔑 auth_*</option>
                        <option value="submit_">📤 submit_*</option>
                        <option value="post_verify">🔍 post_verify</option>
                        <option value="qr_verify">📱 qr_verify</option>
                        <option value="process_complete">✅ process_complete</option>
                        <option value="process_error">💥 process_error</option>
                    </select>
                    <input type="text" id="log-encf-filter" placeholder="eNCF" style="width:140px;padding:8px 12px;border:1px solid var(--color-border);border-radius:var(--radius-md);font-size:13px;background:var(--color-bg-primary);color:var(--color-text-primary);">
                    <button id="btn-filter-logs" class="btn btn-primary" style="padding:8px 16px;font-size:13px;">Filtrar</button>
                    <button id="btn-clear-filters" class="btn btn-ghost" style="padding:8px 12px;font-size:13px;">Limpiar</button>
                </div>

                <!-- Log Table -->
                <div style="overflow-x:auto;">
                    <table class="data-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="width:140px;">Fecha</th>
                                <th style="width:30px;">Nivel</th>
                                <th style="width:140px;">Paso</th>
                                <th style="width:120px;">eNCF</th>
                                <th>Mensaje</th>
                                <th style="width:60px;">HTTP</th>
                                <th style="width:70px;">Tiempo</th>
                                <th style="width:50px;">QR</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="dgii-logs-body">
                            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--color-text-muted);">Cargando logs...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="log-pagination" style="padding:16px var(--spacing-xl);display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--color-border);"></div>
            </div>

            <!-- Detail Modal -->
            <div id="log-detail-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);justify-content:center;align-items:center;">
                <div style="background:var(--color-bg-primary);border-radius:var(--radius-xl);width:90%;max-width:800px;max-height:85vh;overflow:hidden;box-shadow:var(--shadow-xl);display:flex;flex-direction:column;">
                    <div style="padding:20px 24px;border-bottom:1px solid var(--color-border);display:flex;justify-content:space-between;align-items:center;">
                        <h3 id="modal-title" style="margin:0;font-size:16px;font-weight:700;color:var(--color-text-primary);">Detalle del Log</h3>
                        <button id="btn-close-modal" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--color-text-muted);padding:4px;">✕</button>
                    </div>
                    <div id="modal-body" style="padding:24px;overflow-y:auto;flex:1;"></div>
                </div>
            </div>
        `;

        this.currentPage = 1;
        this.bindEvents();
        await this.loadStats();
        await this.loadLogs();
    },

    bindEvents() {
        document.getElementById('btn-refresh-logs').onclick = () => { this.loadStats(); this.loadLogs(); };
        document.getElementById('btn-filter-logs').onclick = () => { this.currentPage = 1; this.loadLogs(); };
        document.getElementById('btn-clear-filters').onclick = () => {
            document.getElementById('log-search').value = '';
            document.getElementById('log-level-filter').value = '';
            document.getElementById('log-step-filter').value = '';
            document.getElementById('log-encf-filter').value = '';
            this.currentPage = 1;
            this.loadLogs();
        };
        document.getElementById('btn-close-modal').onclick = () => {
            document.getElementById('log-detail-modal').style.display = 'none';
        };
        document.getElementById('log-detail-modal').onclick = (e) => {
            if (e.target === document.getElementById('log-detail-modal')) {
                document.getElementById('log-detail-modal').style.display = 'none';
            }
        };
        // Enter key on search
        document.getElementById('log-search').onkeydown = (e) => {
            if (e.key === 'Enter') { this.currentPage = 1; this.loadLogs(); }
        };
    },

    async loadStats() {
        try {
            const data = await App.api('dgii/logs/stats');
            const container = document.getElementById('dgii-log-stats');
            if (!data) return;

            const cards = [
                { label: 'Total Registros', value: data.total_entries || 0, icon: '📊', color: '#6366f1' },
                { label: 'Errores', value: (data.by_level?.error || 0) + (data.by_level?.critical || 0), icon: '❌', color: '#ef4444' },
                { label: 'Warnings', value: data.by_level?.warning || 0, icon: '⚠️', color: '#f59e0b' },
                { label: 'QR Verificados', value: `${data.qr_verification?.verified || 0}/${data.qr_verification?.total || 0}`, icon: '📱', color: data.qr_verification?.failed > 0 ? '#ef4444' : '#22c55e' },
            ];

            container.innerHTML = cards.map(c => `
                <div style="background:var(--color-bg-primary);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:20px;display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:var(--radius-lg);background:${c.color}15;display:flex;align-items:center;justify-content:center;font-size:20px;">${c.icon}</div>
                    <div>
                        <div style="font-size:22px;font-weight:700;color:var(--color-text-primary);line-height:1;">${c.value}</div>
                        <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px;">${c.label}</div>
                    </div>
                </div>
            `).join('');
        } catch (e) {
            console.error('Error loading stats:', e);
        }
    },

    async loadLogs() {
        const tbody = document.getElementById('dgii-logs-body');
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--color-text-muted);">Cargando...</td></tr>';

        const params = new URLSearchParams();
        params.set('page', this.currentPage);
        params.set('per_page', 50);

        const search = document.getElementById('log-search')?.value;
        const level = document.getElementById('log-level-filter')?.value;
        const step = document.getElementById('log-step-filter')?.value;
        const encf = document.getElementById('log-encf-filter')?.value;

        if (search) params.set('search', search);
        if (level) params.set('level', level);
        if (step) params.set('step', step);
        if (encf) params.set('encf', encf);

        try {
            const data = await App.api(`dgii/logs?${params.toString()}`);
            if (!data?.data?.length) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--color-text-muted);">No hay logs que coincidan con los filtros</td></tr>';
                document.getElementById('log-pagination').innerHTML = '';
                return;
            }

            tbody.innerHTML = data.data.map(log => this.renderLogRow(log)).join('');
            this.renderPagination(data);

            // Attach row click handlers
            tbody.querySelectorAll('[data-log-id]').forEach(btn => {
                btn.onclick = () => this.showDetail(btn.dataset.logId);
            });
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--color-text-muted);">Error cargando logs: ${e.message}</td></tr>`;
        }
    },

    renderLogRow(log) {
        const levelIcons = { info: '🔵', warning: '🟡', error: '🔴', critical: '⛔' };
        const levelColors = { info: '#6366f120', warning: '#f59e0b20', error: '#ef444420', critical: '#dc262620' };
        const levelBorders = { info: '#6366f140', warning: '#f59e0b40', error: '#ef444440', critical: '#dc262640' };

        const icon = levelIcons[log.level] || '⚪';
        const bgColor = levelColors[log.level] || 'transparent';
        const borderColor = levelBorders[log.level] || 'transparent';

        const date = new Date(log.created_at);
        const dateStr = date.toLocaleDateString('es-DO', { month: '2-digit', day: '2-digit' }) + ' ' + date.toLocaleTimeString('es-DO', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        const httpBadge = log.http_status
            ? `<span style="display:inline-block;padding:2px 8px;border-radius:var(--radius-sm);font-size:11px;font-weight:600;background:${log.http_status < 300 ? '#22c55e20' : '#ef444420'};color:${log.http_status < 300 ? '#22c55e' : '#ef4444'};">${log.http_status}</span>`
            : '<span style="color:var(--color-text-muted);font-size:11px;">—</span>';

        const durationStr = log.http_duration_ms
            ? `<span style="font-size:11px;color:${log.http_duration_ms > 5000 ? '#ef4444' : 'var(--color-text-muted)'};">${Math.round(log.http_duration_ms)}ms</span>`
            : '<span style="color:var(--color-text-muted);font-size:11px;">—</span>';

        let qrBadge = '<span style="color:var(--color-text-muted);font-size:11px;">—</span>';
        if (log.qr_verified === true) qrBadge = '<span style="color:#22c55e;font-weight:700;">✓</span>';
        else if (log.qr_verified === false) qrBadge = '<span style="color:#ef4444;font-weight:700;">✗</span>';

        const msgTruncated = log.message && log.message.length > 80 ? log.message.substring(0, 80) + '…' : (log.message || '');

        return `
            <tr style="background:${bgColor};border-left:3px solid ${borderColor};">
                <td style="font-size:11px;font-family:var(--font-mono,monospace);white-space:nowrap;">${dateStr}</td>
                <td style="text-align:center;">${icon}</td>
                <td><span style="font-size:11px;font-family:var(--font-mono,monospace);background:var(--color-border);padding:2px 8px;border-radius:var(--radius-sm);white-space:nowrap;">${log.step}</span></td>
                <td style="font-size:12px;font-family:var(--font-mono,monospace);font-weight:600;">${log.encf || '—'}</td>
                <td style="font-size:12px;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${this.escapeHtml(log.message || '')}">${this.escapeHtml(msgTruncated)}</td>
                <td style="text-align:center;">${httpBadge}</td>
                <td style="text-align:right;">${durationStr}</td>
                <td style="text-align:center;">${qrBadge}</td>
                <td><button data-log-id="${log.id}" class="btn btn-ghost" style="padding:4px 8px;font-size:11px;">👁️</button></td>
            </tr>
        `;
    },

    renderPagination(data) {
        const container = document.getElementById('log-pagination');
        const { current_page, last_page, total, from, to } = data;

        container.innerHTML = `
            <span style="font-size:12px;color:var(--color-text-muted);">Mostrando ${from || 0}-${to || 0} de ${total} registros</span>
            <div style="display:flex;gap:4px;">
                ${current_page > 1 ? `<button class="btn btn-ghost" style="padding:6px 12px;font-size:12px;" onclick="document.querySelector('[data-module=dgii-logs]').__module.goPage(${current_page - 1})">← Anterior</button>` : ''}
                <span style="padding:6px 12px;font-size:12px;color:var(--color-text-secondary);">Pág. ${current_page} de ${last_page}</span>
                ${current_page < last_page ? `<button class="btn btn-ghost" style="padding:6px 12px;font-size:12px;" onclick="document.querySelector('[data-module=dgii-logs]').__module.goPage(${current_page + 1})">Siguiente →</button>` : ''}
            </div>
        `;

        // Store reference for pagination
        window._dgiiLogsModule = this;
    },

    goPage(page) {
        this.currentPage = page;
        this.loadLogs();
    },

    async showDetail(logId) {
        const modal = document.getElementById('log-detail-modal');
        const body = document.getElementById('modal-body');
        const title = document.getElementById('modal-title');

        modal.style.display = 'flex';
        body.innerHTML = '<div style="text-align:center;padding:40px;color:var(--color-text-muted);">Cargando...</div>';

        try {
            const log = await App.api(`dgii/logs/${logId}`);
            title.textContent = `${log.step} — ${log.encf || 'Sin eNCF'}`;

            const levelColors = { info: '#6366f1', warning: '#f59e0b', error: '#ef4444', critical: '#dc2626' };
            const levelColor = levelColors[log.level] || '#888';

            body.innerHTML = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
                    <div style="background:var(--color-bg-secondary);border-radius:var(--radius-md);padding:12px;">
                        <div style="font-size:11px;color:var(--color-text-muted);margin-bottom:4px;">Factura</div>
                        <div style="font-size:14px;font-weight:600;">#${log.invoice_id || '—'}</div>
                    </div>
                    <div style="background:var(--color-bg-secondary);border-radius:var(--radius-md);padding:12px;">
                        <div style="font-size:11px;color:var(--color-text-muted);margin-bottom:4px;">eNCF</div>
                        <div style="font-size:14px;font-weight:600;font-family:var(--font-mono,monospace);">${log.encf || '—'}</div>
                    </div>
                    <div style="background:var(--color-bg-secondary);border-radius:var(--radius-md);padding:12px;">
                        <div style="font-size:11px;color:var(--color-text-muted);margin-bottom:4px;">Nivel</div>
                        <div style="font-size:14px;font-weight:700;color:${levelColor};text-transform:uppercase;">${log.level}</div>
                    </div>
                    <div style="background:var(--color-bg-secondary);border-radius:var(--radius-md);padding:12px;">
                        <div style="font-size:11px;color:var(--color-text-muted);margin-bottom:4px;">Fecha</div>
                        <div style="font-size:13px;">${new Date(log.created_at).toLocaleString('es-DO')}</div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Mensaje</div>
                    <div style="background:var(--color-bg-secondary);padding:14px;border-radius:var(--radius-md);font-size:13px;line-height:1.6;">${this.escapeHtml(log.message)}</div>
                </div>

                ${log.http_url ? `
                    <div style="margin-bottom:16px;">
                        <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">HTTP Request</div>
                        <div style="background:var(--color-bg-secondary);padding:14px;border-radius:var(--radius-md);font-family:var(--font-mono,monospace);font-size:12px;">
                            <div><span style="font-weight:700;color:${levelColor};">${log.http_method}</span> ${this.escapeHtml(log.http_url)}</div>
                            <div style="margin-top:4px;">Status: <span style="font-weight:700;color:${log.http_status < 300 ? '#22c55e' : '#ef4444'};">${log.http_status}</span> — ${log.http_duration_ms ? Math.round(log.http_duration_ms) + 'ms' : '—'}</div>
                        </div>
                    </div>
                ` : ''}

                ${log.http_response_body ? `
                    <div style="margin-bottom:16px;">
                        <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Response Body</div>
                        <pre style="background:#1e1e2e;color:#cdd6f4;padding:14px;border-radius:var(--radius-md);font-size:11px;overflow-x:auto;max-height:200px;margin:0;white-space:pre-wrap;word-break:break-all;">${this.escapeHtml(this.formatJson(log.http_response_body))}</pre>
                    </div>
                ` : ''}

                ${log.context ? `
                    <div style="margin-bottom:16px;">
                        <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Contexto Detallado</div>
                        <pre style="background:#1e1e2e;color:#cdd6f4;padding:14px;border-radius:var(--radius-md);font-size:11px;overflow-x:auto;max-height:300px;margin:0;white-space:pre-wrap;word-break:break-all;">${this.escapeHtml(JSON.stringify(log.context, null, 2))}</pre>
                    </div>
                ` : ''}

                ${log.qr_url ? `
                    <div style="margin-bottom:16px;">
                        <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">QR Verification</div>
                        <div style="background:var(--color-bg-secondary);padding:14px;border-radius:var(--radius-md);">
                            <div style="font-size:13px;margin-bottom:6px;">Resultado: <span style="font-weight:700;color:${log.qr_verified ? '#22c55e' : '#ef4444'};">${log.qr_verified ? '✓ Encontrada en DGII' : '✗ No encontrada en DGII'}</span></div>
                            <a href="${this.escapeHtml(log.qr_url)}" target="_blank" style="font-size:11px;color:var(--color-primary);word-break:break-all;">${this.escapeHtml(log.qr_url)}</a>
                        </div>
                    </div>
                ` : ''}

                ${log.dgii_track_id ? `
                    <div style="margin-bottom:16px;">
                        <div style="font-size:11px;font-weight:600;color:var(--color-text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">DGII Track ID</div>
                        <div style="background:var(--color-bg-secondary);padding:14px;border-radius:var(--radius-md);font-family:var(--font-mono,monospace);font-size:12px;">${log.dgii_track_id}</div>
                    </div>
                ` : ''}

                ${log.dgii_error_messages ? `
                    <div style="margin-bottom:16px;">
                        <div style="font-size:11px;font-weight:600;color:#ef4444;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Errores DGII</div>
                        <div style="background:#ef444410;border:1px solid #ef444430;padding:14px;border-radius:var(--radius-md);font-size:13px;color:#ef4444;">${this.escapeHtml(log.dgii_error_messages)}</div>
                    </div>
                ` : ''}
            `;
        } catch (e) {
            body.innerHTML = `<div style="text-align:center;padding:40px;color:#ef4444;">Error: ${e.message}</div>`;
        }
    },

    formatJson(str) {
        try {
            return JSON.stringify(JSON.parse(str), null, 2);
        } catch {
            return str || '';
        }
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
};
