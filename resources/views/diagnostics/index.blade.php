<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Diagnósticos PayPal - Sistema de Facturación</title>
    <style>
        :root {
            --primary: #0B484C;
            --accent: #00DF83;
            --success: #10B981;
            --error: #EF4444;
            --warning: #F59E0B;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #F9FAFB;
            color: #1F2937;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 24px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: var(--primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .status-item {
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #E5E7EB;
        }
        
        .status-item.success {
            background: #ECFDF5;
            border-left-color: var(--success);
        }
        
        .status-item.error {
            background: #FEF2F2;
            border-left-color: var(--error);
        }
        
        .status-item.warning {
            background: #FFFBEB;
            border-left-color: var(--warning);
        }
        
        .status-label {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .status-value {
            font-size: 0.95rem;
            color: #6B7280;
        }
        
        .test-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 12px;
            margin-top: 16px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group select {
            padding: 10px;
            border: 2px solid #E5E7EB;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            align-self: flex-end;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0A3A3D;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .result-box {
            margin-top: 20px;
            padding: 16px;
            border-radius: 8px;
            display: none;
        }
        
        .result-box.show {
            display: block;
        }
        
        .result-box.success {
            background: #ECFDF5;
            border: 2px solid var(--success);
            color: #065F46;
        }
        
        .result-box.error {
            background: #FEF2F2;
            border: 2px solid var(--error);
            color: #991B1B;
        }
        
        .result-title {
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        
        pre {
            background: #F3F4F6;
            padding: 12px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 0.85rem;
            margin-top: 8px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #ECFDF5;
            color: #065F46;
        }
        
        .badge-error {
            background: #FEF2F2;
            color: #991B1B;
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnósticos de PayPal</h1>
        <p style="color: #6B7280; margin-bottom: 30px;">Herramienta para diagnosticar problemas de integración con PayPal</p>
        
        <!-- System Status -->
        <div class="card">
            <h2>📊 Estado del Sistema</h2>
            <div class="status-grid">
                <div class="status-item {{ $diagnostics['paypal_configured'] ? 'success' : 'error' }}">
                    <div class="status-label">Estado PayPal</div>
                    <div class="status-value">
                        @if($diagnostics['paypal_configured'])
                            ✅ Configurado
                        @else
                            ❌ No configurado
                        @endif
                    </div>
                </div>
                
                <div class="status-item {{ $diagnostics['client_id_set'] ? 'success' : 'error' }}">
                    <div class="status-label">Client ID</div>
                    <div class="status-value">{{ $diagnostics['client_id_preview'] }}</div>
                </div>
                
                <div class="status-item {{ $diagnostics['client_secret_set'] ? 'success' : 'error' }}">
                    <div class="status-label">Client Secret</div>
                    <div class="status-value">{{ $diagnostics['client_secret_set'] ? '✅ Configurado' : '❌ No configurado' }}</div>
                </div>
                
                <div class="status-item success">
                    <div class="status-label">Modo</div>
                    <div class="status-value">{{ strtoupper($diagnostics['mode']) }}</div>
                </div>
                
                <div class="status-item {{ $diagnostics['curl_enabled'] ? 'success' : 'error' }}">
                    <div class="status-label">cURL</div>
                    <div class="status-value">{{ $diagnostics['curl_enabled'] ? '✅ Habilitado v' . $diagnostics['curl_version'] : '❌ Deshabilitado' }}</div>
                </div>
                
                <div class="status-item success">
                    <div class="status-label">PHP</div>
                    <div class="status-value">{{ $diagnostics['php_version'] }}</div>
                </div>
            </div>
        </div>
        
        <!-- Invoice Sample -->
        @if($diagnostics['sample_invoice'])
        <div class="card">
            <h2>📄 Ejemplo de Factura</h2>
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-label">Número</div>
                    <div class="status-value">{{ $diagnostics['sample_invoice']->invoice_number }}</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Moneda</div>
                    <div class="status-value">{{ $diagnostics['sample_invoice']->currency }}</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Total</div>
                    <div class="status-value">{{ number_format($diagnostics['sample_invoice']->total, 2) }} {{ $diagnostics['sample_invoice']->currency }}</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Pendiente</div>
                    <div class="status-value">{{ number_format($diagnostics['sample_invoice']->getRemainingBalance(), 2) }} {{ $diagnostics['sample_invoice']->currency }}</div>
                </div>
            </div>
            
            @if($diagnostics['sample_invoice']->currency !== 'USD' && !in_array($diagnostics['sample_invoice']->currency, ['EUR', 'GBP', 'CAD', 'AUD']))
            <div class="result-box error show" style="margin-top: 16px;">
                <div class="result-title">⚠️ Advertencia de Moneda</div>
                <p>La moneda <strong>{{ $diagnostics['sample_invoice']->currency }}</strong> puede no estar soportada por PayPal.</p>
                <p>Monedas soportadas comúnmente: USD, EUR, GBP, CAD, AUD, JPY, MXN, etc.</p>
                <p>Para República Dominicana, se recomienda usar <strong>USD</strong> en lugar de DOP.</p>
            </div>
            @endif
        </div>
        @endif
        
        <!-- Test Order Creation -->
        <div class="card">
            <h2>🧪 Prueba de Creación de Orden</h2>
            <p style="color: #6B7280; margin-bottom: 16px;">Crea una orden de prueba para verificar la integración</p>
            
            <form id="test-form" class="test-form">
                <div class="form-group">
                    <label>Monto</label>
                    <input type="number" name="amount" value="10.00" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label>Moneda</label>
                    <select name="currency" required>
                        <option value="USD">USD - Dólar Estadounidense</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="GBP">GBP - Libra Esterlina</option>
                        <option value="CAD">CAD - Dólar Canadiense</option>
                        <option value="MXN">MXN - Peso Mexicano</option>
                        <option value="DOP">DOP - Peso Dominicano</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" id="test-btn">
                    Probar Orden
                </button>
            </form>
            
            <div id="test-result" class="result-box"></div>
        </div>
        
        <!-- Fix Problematic Payments -->
        <div class="card">
            <h2>🔧 Corregir Pagos con Problemas de Conversión</h2>
            <p style="color: #6B7280; margin-bottom: 16px;">Encuentra y corrige pagos de PayPal donde el monto se registró en la moneda incorrecta</p>
            
            <button type="button" class="btn btn-primary" onclick="loadProblematicPayments()" style="margin-bottom: 20px;">
                🔍 Buscar Pagos Problemáticos
            </button>
            
            <div id="payments-container" style="display: none;">
                <div id="payments-list"></div>
            </div>
            
            <div id="payments-result" class="result-box"></div>
        </div>
        
        <!-- Instructions -->
        <div class="card">
            <h2>📝 Pasos para Resolver Problemas</h2>
            <ol style="line-height: 1.8; margin-left: 20px;">
                <li>Verifica que las credenciales de PayPal estén configuradas en <a href="/settings" style="color: var(--primary); font-weight: 600;">Settings</a></li>
                <li>Asegúrate de usar credenciales de <strong>Sandbox</strong> para pruebas</li>
                <li>Verifica que la moneda de tus facturas sea soportada por PayPal (USD recomendado)</li>
                <li>Prueba crear una orden con el formulario de arriba</li>
                <li>Si tienes pagos con conversión incorrecta, usa la herramienta de corrección</li>
                <li>Si el error persiste, revisa los logs en <code>storage/logs/laravel.log</code></li>
                <li>Verifica que cURL esté habilitado y pueda hacer peticiones HTTPS</li>
            </ol>
        </div>
    </div>
    
    <script>
        document.getElementById('test-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('test-btn');
            const resultBox = document.getElementById('test-result');
            const formData = new FormData(this);
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Probando...';
            resultBox.classList.remove('show', 'success', 'error');
            
            try {
                const response = await fetch('/diagnostics/test-order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        amount: formData.get('amount'),
                        currency: formData.get('currency')
                    })
                });
                
                const data = await response.json();
                
                resultBox.classList.add('show');
                
                if (data.success) {
                    resultBox.classList.add('success');
                    resultBox.innerHTML = `
                        <div class="result-title">${data.message}</div>
                        <p><strong>Order ID:</strong> ${data.order_id}</p>
                        <p><strong>Status:</strong> ${data.status}</p>
                        <p><strong>Amount:</strong> ${data.amount} ${data.currency}</p>
                    `;
                } else {
                    resultBox.classList.add('error');
                    let errorHtml = `<div class="result-title">❌ Error: ${data.message}</div>`;
                    
                    if (data.step) {
                        errorHtml += `<p><strong>Paso fallido:</strong> ${data.step}</p>`;
                    }
                    
                    if (data.response) {
                        errorHtml += `<p><strong>Respuesta de PayPal:</strong></p>`;
                        errorHtml += `<pre>${JSON.stringify(data.response, null, 2)}</pre>`;
                    }
                    
                    if (data.request_data) {
                        errorHtml += `<p><strong>Datos enviados:</strong></p>`;
                        errorHtml += `<pre>${JSON.stringify(data.request_data, null, 2)}</pre>`;
                    }
                    
                    resultBox.innerHTML = errorHtml;
                }
            } catch (error) {
                resultBox.classList.add('show', 'error');
                resultBox.innerHTML = `
                    <div class="result-title">❌ Error de red</div>
                    <p>${error.message}</p>
                `;
            } finally {
                btn.disabled = false;
                btn.textContent = 'Probar Orden';
            }
        });
        
        async function loadProblematicPayments() {
            const container = document.getElementById('payments-container');
            const listDiv = document.getElementById('payments-list');
            const resultBox = document.getElementById('payments-result');
            
            listDiv.innerHTML = '<p>Cargando...</p>';
            container.style.display = 'block';
            resultBox.classList.remove('show');
            
            try {
                const response = await fetch('/diagnostics/problematic-payments');
                const data = await response.json();
                
                if (data.success && data.payments.length > 0) {
                    let html = `
                        <div style="background: #FEF2F2; border: 2px solid #EF4444; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                            <strong style="color: #991B1B;">⚠️ ${data.payments.length} pago(s) con posibles problemas encontrados</strong>
                        </div>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #F3F4F6; text-align: left;">
                                    <th style="padding: 12px; border-bottom: 2px solid #E5E7EB;">Factura</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #E5E7EB;">Registrado</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #E5E7EB;">Debería ser</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #E5E7EB;">Diferencia</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #E5E7EB;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    data.payments.forEach(payment => {
                        html += `
                            <tr style="border-bottom: 1px solid #E5E7EB;">
                                <td style="padding: 12px;">
                                    <strong>${payment.invoice_number}</strong><br>
                                    <small style="color: #6B7280;">Total: ${payment.invoice_total.toFixed(2)} ${payment.invoice_currency}</small>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="color: #EF4444; font-weight: 600;">${payment.payment_amount.toFixed(2)} ${payment.invoice_currency}</span>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="color: #10B981; font-weight: 600;">${payment.estimated_original.toFixed(2)} ${payment.invoice_currency}</span>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="color: #F59E0B; font-weight: 600;">+${payment.difference.toFixed(2)}</span>
                                </td>
                                <td style="padding: 12px;">
                                    <button 
                                        onclick="fixPayment(${payment.payment_id}, ${payment.estimated_original})"
                                        class="btn btn-primary"
                                        style="padding: 8px 16px; font-size: 0.9rem;">
                                        ✅ Corregir
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += `
                            </tbody>
                        </table>
                    `;
                    
                    listDiv.innerHTML = html;
                } else {
                    listDiv.innerHTML = `
                        <div style="background: #ECFDF5; border: 2px solid #10B981; border-radius: 12px; padding: 16px; text-align: center;">
                            <strong style="color: #065F46;">✅ No se encontraron pagos con problemas</strong>
                        </div>
                    `;
                }
            } catch (error) {
                listDiv.innerHTML = `
                    <div style="background: #FEF2F2; border: 2px solid #EF4444; border-radius: 12px; padding: 16px;">
                        <strong style="color: #991B1B;">❌ Error al cargar pagos</strong><br>
                        ${error.message}
                    </div>
                `;
            }
        }
        
        async function fixPayment(paymentId, correctAmount) {
            if (!confirm(`¿Estás seguro de corregir este pago a ${correctAmount.toFixed(2)} DOP?`)) {
                return;
            }
            
            const resultBox = document.getElementById('payments-result');
            resultBox.classList.remove('show', 'success', 'error');
            
            try {
                const response = await fetch('/diagnostics/fix-payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        payment_id: paymentId,
                        correct_amount: correctAmount
                    })
                });
                
                const data = await response.json();
                
                resultBox.classList.add('show');
                
                if (data.success) {
                    resultBox.classList.add('success');
                    resultBox.innerHTML = `
                        <div class="result-title">${data.message}</div>
                        <p><strong>Monto anterior:</strong> ${data.old_amount} DOP</p>
                        <p><strong>Monto corregido:</strong> ${data.new_amount} DOP</p>
                        <p><strong>Estado factura:</strong> ${data.invoice_status}</p>
                        <p><strong>Total pagado:</strong> ${data.invoice_amount_paid} DOP</p>
                    `;
                    
                    // Reload payments list
                    setTimeout(() => loadProblematicPayments(), 2000);
                } else {
                    resultBox.classList.add('error');
                    resultBox.innerHTML = `
                        <div class="result-title">❌ Error</div>
                        <p>${data.message}</p>
                    `;
                }
            } catch (error) {
                resultBox.classList.add('show', 'error');
                resultBox.innerHTML = `
                    <div class="result-title">❌ Error de red</div>
                    <p>${error.message}</p>
                `;
            }
        }
    </script>
</body>
</html>
