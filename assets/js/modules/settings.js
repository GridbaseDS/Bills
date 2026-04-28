export default {
    async render(container) {
        try {
            const data = await window.App.api('settings');
            // Assuming API returns key-value pairs or grouped object
            const s = data.reduce((acc, curr) => { acc[curr.setting_key] = curr.setting_value; return acc; }, {});

            container.innerHTML = `
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Configuración</h1>
                        <p class="page-subtitle">Ajustes del sistema y empresa</p>
                    </div>
                </div>
                
                <form id="settings-form" class="card mb-24">
                    <div class="card-header">
                        <div class="card-title">Información de la Empresa</div>
                    </div>
                    <div class="card-body">
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Nombre Comercial</label>
                                <input type="text" id="s_company_name" class="form-control" value="${s.company_name || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Correo Oficial (Facturas)</label>
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
                    </div>
                    
                    <div class="card-header" style="border-top: 1px solid var(--border-color)">
                        <div class="card-title">Ajustes de Facturación</div>
                    </div>
                    <div class="card-body">
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
                        <div class="mt-24">
                            <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                        </div>
                    </div>
                </form>
            `;

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
                    default_tax_rate: document.getElementById('s_default_tax_rate').value
                };

                try {
                    await window.App.api('settings', { method: 'POST', body: settingsToUpdate });
                    window.App.showToast('Configuración actualizada');
                } catch(err) {}
            });

        } catch (e) {
            container.innerHTML = `<div class="text-red">Error al cargar configuraciones</div>`;
        }
    }
};
