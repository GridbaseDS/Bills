/**
 * GridBase Bills - Support & Ticketing Module
 * Communication channel for users and support team (soporte@gridbase.com.do)
 */

const SupportModule = {
    categories: {
        'general': 'Consulta General',
        'facturacion': 'Facturación y Cobros',
        'dgii': 'Integración DGII / e-CF',
        'tecnico': 'Incidencia Técnica',
        'configuracion': 'Configuración de Empresa',
        'otro': 'Otro Asunto'
    },

    priorities: {
        'baja': { label: 'Baja', bg: 'rgba(100,116,139,0.12)', color: 'var(--color-text-muted)', border: 'rgba(100,116,139,0.25)' },
        'media': { label: 'Media', bg: 'rgba(59,130,246,0.12)', color: '#3b82f6', border: 'rgba(59,130,246,0.25)' },
        'alta': { label: 'Alta', bg: 'rgba(245,158,11,0.12)', color: '#f59e0b', border: 'rgba(245,158,11,0.25)' },
        'urgente': { label: 'Urgente', bg: 'rgba(239,68,68,0.12)', color: '#ef4444', border: 'rgba(239,68,68,0.25)' }
    },

    statuses: {
        'abierto': { label: 'Abierto', class: 'notice-info', bg: 'rgba(37,99,235,0.12)', color: '#2563eb', border: 'rgba(37,99,235,0.25)' },
        'en_proceso': { label: 'En Proceso', class: 'notice-warning', bg: 'rgba(245,158,11,0.12)', color: '#d97706', border: 'rgba(245,158,11,0.25)' },
        'resuelto': { label: 'Resuelto', class: 'notice-success', bg: 'rgba(16,185,129,0.12)', color: '#16a34a', border: 'rgba(16,185,129,0.25)' },
        'cerrado': { label: 'Cerrado', class: 'notice-default', bg: 'rgba(100,116,139,0.12)', color: '#64748b', border: 'rgba(100,116,139,0.25)' }
    },

    async render(container, id) {
        if (id === 'nuevo' || id === 'new') {
            this.renderNewTicketForm(container);
            return;
        }
        if (id) {
            this.renderTicketDetail(container, id);
            return;
        }
        this.renderList(container);
    },

    /**
     * Render Ticket List
     */
    async renderList(container) {
        container.innerHTML = `<div class="text-center mt-24"><div class="spinner mx-auto"></div></div>`;

        try {
            const res = await window.App.api('support/tickets');
            const tickets = res.data || [];
            const stats = res.stats || { total: 0, open: 0, in_progress: 0, resolved: 0 };
            const isAdmin = res.is_admin || false;
            const supportEmail = res.support_email || 'soporte@gridbase.com.do';

            container.innerHTML = `
                <div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:20px;">
                    <div>
                        <h1 class="page-title" style="font-size:22px;font-weight:700;margin:0 0 4px 0;">Centro de Soporte y Asistencia</h1>
                        <p class="page-subtitle" style="color:var(--color-text-muted);font-size:13px;margin:0;">
                            Canal directo de contacto con el equipo de soporte técnico y fiscal
                        </p>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <a href="https://docs.gridbase.com.do/index.php?doc=gridbase-bills" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                            Documentación
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.6;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        </a>
                        <button class="btn btn-primary" onclick="window.App.navigate('soporte/nuevo')" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Abrir Nuevo Ticket
                        </button>
                    </div>
                </div>

                <!-- Support Official Banner -->
                <div class="notice-banner notice-info" style="margin-bottom:20px;">
                    <svg class="notice-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>
                    <div class="notice-content">
                        <strong class="notice-title">Canal Oficial: ${supportEmail}</strong><br>
                        Cada ticket registrado se notifica al equipo de soporte y las respuestas se envían directamente a tu bandeja de correo y a esta plataforma. Horario de atención: Lunes a Viernes 8:00 AM - 6:00 PM.
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid-metrics" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;margin-bottom:20px;">
                    <div class="workspace-panel" style="padding:16px;border:1px solid var(--color-border);border-radius:var(--radius-lg);background:var(--color-bg-primary);">
                        <div style="font-size:12px;color:var(--color-text-muted);font-weight:600;text-transform:uppercase;">Total Tickets</div>
                        <div style="font-size:24px;font-weight:700;color:var(--color-text-primary);margin-top:4px;">${stats.total}</div>
                    </div>
                    <div class="workspace-panel" style="padding:16px;border:1px solid var(--color-border);border-radius:var(--radius-lg);background:var(--color-bg-primary);">
                        <div style="font-size:12px;color:#2563eb;font-weight:600;text-transform:uppercase;">Abiertos</div>
                        <div style="font-size:24px;font-weight:700;color:#2563eb;margin-top:4px;">${stats.open}</div>
                    </div>
                    <div class="workspace-panel" style="padding:16px;border:1px solid var(--color-border);border-radius:var(--radius-lg);background:var(--color-bg-primary);">
                        <div style="font-size:12px;color:#d97706;font-weight:600;text-transform:uppercase;">En Proceso</div>
                        <div style="font-size:24px;font-weight:700;color:#d97706;margin-top:4px;">${stats.in_progress}</div>
                    </div>
                    <div class="workspace-panel" style="padding:16px;border:1px solid var(--color-border);border-radius:var(--radius-lg);background:var(--color-bg-primary);">
                        <div style="font-size:12px;color:#16a34a;font-weight:600;text-transform:uppercase;">Resueltos</div>
                        <div style="font-size:24px;font-weight:700;color:#16a34a;margin-top:4px;">${stats.resolved}</div>
                    </div>
                </div>

                <!-- Table Container with Filter Toolbar -->
                <div class="table-outer" style="background:var(--color-bg-primary);border:1px solid var(--color-border);border-radius:var(--radius-lg);overflow:hidden;">
                    <div class="table-toolbar" style="padding:14px 16px;border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                        <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:240px;">
                            <div class="search-wrapper" style="position:relative;width:100%;max-width:320px;">
                                <input type="text" id="tickets-search" class="form-control" placeholder="Buscar por número o asunto..." style="padding-left:36px;font-size:13px;height:38px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--color-text-muted);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </div>
                            <select id="tickets-filter-status" class="form-control" style="max-width:160px;font-size:13px;height:38px;">
                                <option value="all">Todos los estados</option>
                                <option value="abierto">Abiertos</option>
                                <option value="en_proceso">En Proceso</option>
                                <option value="resuelto">Resueltos</option>
                                <option value="cerrado">Cerrados</option>
                            </select>
                            <select id="tickets-filter-priority" class="form-control" style="max-width:160px;font-size:13px;height:38px;">
                                <option value="all">Toda prioridad</option>
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-wrapper" style="overflow-x:auto;">
                        <table class="data-table" style="width:100%;border-collapse:collapse;font-size:13px;">
                            <thead>
                                <tr style="border-bottom:1px solid var(--color-border);background:var(--color-bg-secondary);text-align:left;">
                                    <th style="padding:12px 16px;width:120px;">Ticket</th>
                                    <th style="padding:12px 16px;">Asunto</th>
                                    ${isAdmin ? `<th style="padding:12px 16px;width:180px;">Usuario / Creador</th>` : ''}
                                    <th style="padding:12px 16px;width:150px;">Categoría</th>
                                    <th style="padding:12px 16px;width:110px;">Prioridad</th>
                                    <th style="padding:12px 16px;width:120px;">Estado</th>
                                    <th style="padding:12px 16px;width:140px;">Última Actividad</th>
                                    <th style="padding:12px 16px;width:100px;text-align:right;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tickets-table-body">
                                ${this.renderTicketRows(tickets, isAdmin)}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            // Attach search and filter listeners
            const searchInput = container.querySelector('#tickets-search');
            const statusFilter = container.querySelector('#tickets-filter-status');
            const priorityFilter = container.querySelector('#tickets-filter-priority');

            const refreshTable = async () => {
                const search = searchInput.value.trim();
                const status = statusFilter.value;
                const priority = priorityFilter.value;
                const query = new URLSearchParams();
                if (search) query.append('search', search);
                if (status !== 'all') query.append('status', status);
                if (priority !== 'all') query.append('priority', priority);

                const freshRes = await window.App.api(`support/tickets?${query.toString()}`);
                const tbody = container.querySelector('#tickets-table-body');
                if (tbody) {
                    tbody.innerHTML = this.renderTicketRows(freshRes.data || [], isAdmin);
                }
            };

            let searchTimeout = null;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(refreshTable, 300);
            });
            statusFilter.addEventListener('change', refreshTable);
            priorityFilter.addEventListener('change', refreshTable);

        } catch (e) {
            container.innerHTML = `
                <div class="notice-banner notice-danger">
                    <div class="notice-content">
                        <strong>Error al cargar tickets de soporte:</strong> ${e.message || 'Error de conexión'}
                    </div>
                </div>
            `;
        }
    },

    renderTicketRows(tickets, isAdmin) {
        if (!tickets || tickets.length === 0) {
            const cols = isAdmin ? 8 : 7;
            return `
                <tr>
                    <td colspan="${cols}" style="text-align:center;padding:48px 16px;color:var(--color-text-muted);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;opacity:0.6;"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <div style="font-weight:600;font-size:14px;color:var(--color-text-primary);">No hay tickets de soporte registrados</div>
                        <div style="font-size:12px;margin-top:4px;">Si necesitas asistencia, haz clic en el botón "Abrir Nuevo Ticket" superior.</div>
                    </td>
                </tr>
            `;
        }

        return tickets.map(t => {
            const prio = this.priorities[t.priority] || this.priorities.media;
            const st = this.statuses[t.status] || this.statuses.abierto;
            const catLabel = this.categories[t.category] || t.category;
            const updatedDate = t.updated_at ? new Date(t.updated_at).toLocaleDateString('es-DO', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—';

            return `
                <tr style="border-bottom:1px solid var(--color-border);transition:background .15s ease;" class="hover-row">
                    <td style="padding:12px 16px;">
                        <span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700;color:var(--color-primary);">${t.ticket_number}</span>
                    </td>
                    <td style="padding:12px 16px;">
                        <div style="font-weight:600;color:var(--color-text-primary);cursor:pointer;" onclick="window.App.navigate('soporte/${t.ticket_number}')">${this.escapeHtml(t.subject)}</div>
                        ${t.latest_message ? `<div style="font-size:11.5px;color:var(--color-text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:320px;margin-top:2px;">${this.escapeHtml(t.latest_message.message)}</div>` : ''}
                    </td>
                    ${isAdmin ? `
                    <td style="padding:12px 16px;">
                        <div style="font-size:12px;font-weight:600;color:var(--color-text-primary);">${t.user ? this.escapeHtml(t.user.name) : 'Usuario'}</div>
                        <div style="font-size:11px;color:var(--color-text-muted);">${t.user ? this.escapeHtml(t.user.email) : '—'}</div>
                    </td>
                    ` : ''}
                    <td style="padding:12px 16px;">
                        <span style="font-size:12px;color:var(--color-text-secondary);background:var(--color-bg-secondary);padding:3px 8px;border-radius:4px;border:1px solid var(--color-border);">${catLabel}</span>
                    </td>
                    <td style="padding:12px 16px;">
                        <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:600;background:${prio.bg};color:${prio.color};border:1px solid ${prio.border};">
                            ${prio.label}
                        </span>
                    </td>
                    <td style="padding:12px 16px;">
                        <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:600;background:${st.bg};color:${st.color};border:1px solid ${st.border};">
                            ${st.label}
                        </span>
                    </td>
                    <td style="padding:12px 16px;color:var(--color-text-muted);font-size:12px;">
                        ${updatedDate}
                    </td>
                    <td style="padding:12px 16px;text-align:right;">
                        <button class="btn btn-secondary btn-sm" onclick="window.App.navigate('soporte/${t.ticket_number}')" style="padding:4px 10px;font-size:12px;font-weight:600;">
                            Ver
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    },

    /**
     * Render Form to Create New Support Ticket
     */
    renderNewTicketForm(container) {
        container.innerHTML = `
            <div style="max-width:760px;margin:0 auto;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                    <button class="btn btn-secondary btn-sm" onclick="window.App.navigate('soporte')" style="display:inline-flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Volver a Tickets
                    </button>
                    <span style="color:var(--color-text-muted);font-size:13px;">/ Abrir Nueva Solicitud</span>
                </div>

                <div class="workspace-panel" style="background:var(--color-bg-primary);border:1px solid var(--color-border);border-radius:var(--radius-xl);padding:28px;box-shadow:var(--shadow-sm);">
                    <div style="border-bottom:1px solid var(--color-border);padding-bottom:16px;margin-bottom:20px;">
                        <h2 style="font-size:18px;font-weight:700;margin:0 0 6px 0;color:var(--color-text-primary);">Abrir Ticket de Soporte</h2>
                        <p style="font-size:13px;color:var(--color-text-muted);margin:0;">
                            Describe tu consulta o inconveniente. Nuestro equipo de soporte técnico te atenderá con la mayor brevedad.
                        </p>
                    </div>

                    <div class="notice-banner notice-info" style="margin-bottom:20px;padding:12px 16px;">
                        <svg class="notice-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <div class="notice-content" style="font-size:12.5px;">
                            Al enviar esta solicitud se generará un número de ticket único y se notificará automáticamente al equipo de soporte en <strong>soporte@gridbase.com.do</strong>.
                        </div>
                    </div>

                    <form id="new-ticket-form">
                        <div class="form-group" style="margin-bottom:16px;">
                            <label class="form-label" style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">Asunto o Título <span style="color:#ef4444;">*</span></label>
                            <input type="text" id="ticket-subject" class="form-control" placeholder="Ej: Error al emitir factura e-CF hacia DGII" required style="width:100%;font-size:13px;">
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <div class="form-group">
                                <label class="form-label" style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">Categoría <span style="color:#ef4444;">*</span></label>
                                <select id="ticket-category" class="form-control" required style="width:100%;font-size:13px;">
                                    <option value="general">Consulta General</option>
                                    <option value="facturacion">Facturación y Cobros</option>
                                    <option value="dgii">Integración DGII / e-CF</option>
                                    <option value="tecnico">Incidencia Técnica</option>
                                    <option value="configuracion">Configuración de Empresa</option>
                                    <option value="otro">Otro Asunto</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">Prioridad</label>
                                <select id="ticket-priority" class="form-control" style="width:100%;font-size:13px;">
                                    <option value="baja">Baja - Consulta general</option>
                                    <option value="media" selected>Media - Operación normal</option>
                                    <option value="alta">Alta - Afecta facturación</option>
                                    <option value="urgente">Urgente - Sistema detenido</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:24px;">
                            <label class="form-label" style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">Descripción Detallada <span style="color:#ef4444;">*</span></label>
                            <textarea id="ticket-message" class="form-control" rows="6" placeholder="Describe paso a paso lo que sucede, incluyendo comprobante afectado, mensaje de error o detalles pertinentes..." required style="width:100%;font-size:13px;line-height:1.5;resize:vertical;"></textarea>
                        </div>

                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;">
                            <button type="button" class="btn btn-secondary" onclick="window.App.navigate('soporte')">Cancelar</button>
                            <button type="submit" id="btn-submit-ticket" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;font-weight:600;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                Enviar Solicitud a Soporte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        const form = container.querySelector('#new-ticket-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = container.querySelector('#btn-submit-ticket');
            btn.disabled = true;
            btn.textContent = 'Enviando solicitud...';

            try {
                const payload = {
                    subject: container.querySelector('#ticket-subject').value.trim(),
                    category: container.querySelector('#ticket-category').value,
                    priority: container.querySelector('#ticket-priority').value,
                    message: container.querySelector('#ticket-message').value.trim(),
                };

                const res = await window.App.api('support/tickets', {
                    method: 'POST',
                    body: payload
                });

                const ticketNum = res?.data?.ticket_number || res?.ticket_number;
                if (ticketNum) {
                    window.App.showToast(`Ticket ${ticketNum} creado con éxito.`, 'success');
                    window.App.navigate(`soporte/${ticketNum}`);
                } else {
                    window.App.showToast('Ticket de soporte creado con éxito.', 'success');
                    window.App.navigate('soporte');
                }
            } catch (err) {
                window.App.showToast(err.message || 'Error al crear ticket', 'error');
                btn.disabled = false;
                btn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    Enviar Solicitud a Soporte
                `;
            }
        });
    },

    /**
     * Render Ticket Conversation Thread
     */
    async renderTicketDetail(container, id) {
        container.innerHTML = `<div class="text-center mt-24"><div class="spinner mx-auto"></div></div>`;

        try {
            const res = await window.App.api(`support/tickets/${id}`);
            const ticket = res.data;
            const isAdmin = res.is_admin || false;
            const supportEmail = res.support_email || 'soporte@gridbase.com.do';
            const prio = this.priorities[ticket.priority] || this.priorities.media;
            const st = this.statuses[ticket.status] || this.statuses.abierto;
            const catLabel = this.categories[ticket.category] || ticket.category;
            const isClosed = ticket.status === 'cerrado';

            container.innerHTML = `
                <div style="max-width:880px;margin:0 auto;">
                    <!-- Back navigation -->
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
                        <button class="btn btn-secondary btn-sm" onclick="window.App.navigate('soporte')" style="display:inline-flex;align-items:center;gap:6px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                            Volver a Lista de Tickets
                        </button>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:12px;color:var(--color-text-muted);">Estado:</span>
                            <select id="detail-ticket-status" class="form-control" style="width:auto;padding:4px 10px;font-size:12px;font-weight:600;height:32px;">
                                <option value="abierto" ${ticket.status === 'abierto' ? 'selected' : ''}>Abierto</option>
                                <option value="en_proceso" ${ticket.status === 'en_proceso' ? 'selected' : ''}>En Proceso</option>
                                <option value="resuelto" ${ticket.status === 'resuelto' ? 'selected' : ''}>Resuelto</option>
                                <option value="cerrado" ${ticket.status === 'cerrado' ? 'selected' : ''}>Cerrado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Ticket Header Box -->
                    <div class="workspace-panel" style="background:var(--color-bg-primary);border:1px solid var(--color-border);border-radius:var(--radius-xl);padding:22px;margin-bottom:20px;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;border-bottom:1px solid var(--color-border);padding-bottom:14px;margin-bottom:14px;">
                            <div>
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
                                    <span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;color:var(--color-primary);background:var(--color-primary-soft, rgba(59,130,246,0.1));padding:3px 10px;border-radius:6px;">
                                        ${ticket.ticket_number}
                                    </span>
                                    <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:600;background:${prio.bg};color:${prio.color};border:1px solid ${prio.border};">
                                        Prioridad: ${prio.label}
                                    </span>
                                    <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:600;background:${st.bg};color:${st.color};border:1px solid ${st.border};">
                                        ${st.label}
                                    </span>
                                </div>
                                <h2 style="font-size:19px;font-weight:700;margin:0;color:var(--color-text-primary);">${this.escapeHtml(ticket.subject)}</h2>
                            </div>
                            <div style="text-align:right;font-size:12px;color:var(--color-text-muted);">
                                <div>Creado: <strong>${new Date(ticket.created_at).toLocaleDateString('es-DO', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</strong></div>
                                <div style="margin-top:2px;">Categoría: <strong>${catLabel}</strong></div>
                                ${ticket.user ? `<div style="margin-top:2px;">Remitente: <strong>${this.escapeHtml(ticket.user.name)}</strong> (${this.escapeHtml(ticket.user.email)})</div>` : ''}
                            </div>
                        </div>

                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--color-text-muted);flex-wrap:wrap;gap:8px;">
                            <div>Canal oficial de respuesta: <strong style="color:var(--color-primary);">${supportEmail}</strong></div>
                            ${ticket.status === 'resuelto' ? `<span style="color:#16a34a;font-weight:600;">Este ticket fue marcado como resuelto. Puedes responder si necesitas reabrirlo.</span>` : ''}
                        </div>
                    </div>

                    <!-- Conversation Thread -->
                    <div id="thread-messages" style="display:flex;flex-direction:column;gap:16px;margin-bottom:24px;">
                        ${(ticket.messages || []).map(msg => this.renderMessageBubble(msg, ticket)).join('')}
                    </div>

                    <!-- Reply Box -->
                    <div class="workspace-panel" style="background:var(--color-bg-primary);border:1px solid var(--color-border);border-radius:var(--radius-xl);padding:20px;box-shadow:var(--shadow-sm);">
                        <div style="font-size:14px;font-weight:700;color:var(--color-text-primary);margin-bottom:10px;display:flex;align-items:center;gap:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            ${isAdmin ? 'Responder como Soporte Gridbase' : 'Escribir una Respuesta al Ticket'}
                        </div>

                        <form id="reply-form">
                            <textarea id="reply-message" class="form-control" rows="4" placeholder="${isAdmin ? 'Escribe la respuesta formal para el usuario...' : 'Escribe tu mensaje adicional o aclaratoria...'}" required style="width:100%;font-size:13px;line-height:1.5;margin-bottom:14px;resize:vertical;"></textarea>

                            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                                <div style="font-size:12px;color:var(--color-text-muted);">
                                    Se enviará notificación por correo a ${isAdmin ? 'el usuario' : supportEmail}.
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    ${isAdmin ? `
                                    <select id="reply-next-status" class="form-control" style="font-size:12px;height:34px;width:auto;">
                                        <option value="en_proceso">Mantener En Proceso</option>
                                        <option value="resuelto">Marcar como Resuelto</option>
                                        <option value="abierto">Marcar como Abierto</option>
                                    </select>
                                    ` : ''}
                                    <button type="submit" id="btn-submit-reply" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;font-weight:600;font-size:13px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                        Enviar Respuesta
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            // Status dropdown change listener
            const statusSelect = container.querySelector('#detail-ticket-status');
            statusSelect.addEventListener('change', async () => {
                const newStatus = statusSelect.value;
                try {
                    await window.App.api(`support/tickets/${ticket.id}/status`, {
                        method: 'PATCH',
                        body: { status: newStatus }
                    });
                    window.App.showToast(`Estado cambiado a ${newStatus}`, 'success');
                    this.renderTicketDetail(container, ticket.ticket_number);
                } catch (err) {
                    window.App.showToast(err.message || 'Error al actualizar estado', 'error');
                }
            });

            // Reply submission listener
            const replyForm = container.querySelector('#reply-form');
            replyForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = container.querySelector('#btn-submit-reply');
                const replyText = container.querySelector('#reply-message').value.trim();
                const nextStatusInput = container.querySelector('#reply-next-status');
                const nextStatus = nextStatusInput ? nextStatusInput.value : undefined;

                btn.disabled = true;
                btn.textContent = 'Enviando...';

                try {
                    await window.App.api(`support/tickets/${ticket.id}/reply`, {
                        method: 'POST',
                        body: {
                            message: replyText,
                            status: nextStatus
                        }
                    });
                    window.App.showToast('Respuesta enviada correctamente', 'success');
                    this.renderTicketDetail(container, ticket.ticket_number);
                } catch (err) {
                    window.App.showToast(err.message || 'Error al enviar respuesta', 'error');
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        Enviar Respuesta
                    `;
                }
            });

        } catch (e) {
            container.innerHTML = `
                <div class="notice-banner notice-danger">
                    <div class="notice-content">
                        <strong>Error al cargar el ticket:</strong> ${e.message || 'No encontrado'}
                    </div>
                </div>
            `;
        }
    },

    renderMessageBubble(msg, ticket) {
        const isSupport = msg.sender_type === 'support';
        const isSystem = msg.sender_type === 'system';
        const dateStr = msg.created_at ? new Date(msg.created_at).toLocaleDateString('es-DO', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';

        if (isSystem) {
            return `
                <div style="text-align:center;margin:8px 0;">
                    <span style="display:inline-block;background:var(--color-bg-secondary);border:1px solid var(--color-border);padding:4px 12px;border-radius:var(--radius-full);font-size:11.5px;color:var(--color-text-muted);">
                        ${this.escapeHtml(msg.message)} &bull; ${dateStr}
                    </span>
                </div>
            `;
        }

        const bubbleBg = isSupport ? 'var(--color-bg-secondary)' : 'var(--color-bg-primary)';
        const borderColor = isSupport ? 'rgba(59,130,246,0.35)' : 'var(--color-border)';
        const avatarBg = isSupport ? '#2563eb' : '#64748b';
        const initial = (msg.sender_name || 'U').charAt(0).toUpperCase();

        return `
            <div style="display:flex;align-items:flex-start;gap:12px;background:${bubbleBg};border:1px solid ${borderColor};border-radius:var(--radius-lg);padding:16px 18px;position:relative;">
                <div style="width:34px;height:34px;border-radius:50%;background:${avatarBg};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;">
                    ${initial}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;flex-wrap:wrap;gap:6px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <strong style="font-size:13.5px;color:var(--color-text-primary);">${this.escapeHtml(msg.sender_name)}</strong>
                            ${isSupport ? `<span style="background:#2563eb;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;">Equipo de Soporte</span>` : ''}
                            <span style="font-size:11.5px;color:var(--color-text-muted);">(${this.escapeHtml(msg.sender_email)})</span>
                        </div>
                        <span style="font-size:11.5px;color:var(--color-text-muted);">${dateStr}</span>
                    </div>
                    <div style="font-size:13.5px;line-height:1.6;color:var(--color-text-primary);white-space:pre-wrap;word-break:break-word;">${this.escapeHtml(msg.message)}</div>
                </div>
            </div>
        `;
    },

    escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
};

export default SupportModule;
