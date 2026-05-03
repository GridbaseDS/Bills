<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp API Test - GridBase Bills</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #2d3748;
            font-size: 28px;
            margin-bottom: 8px;
        }
        .status {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-top: 16px;
            padding: 12px;
            background: #f7fafc;
            border-radius: 8px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-badge.success {
            background: #c6f6d5;
            color: #22543d;
        }
        .status-badge.error {
            background: #fed7d7;
            color: #742a2a;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card h2 {
            color: #2d3748;
            font-size: 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 14px;
        }
        input, textarea {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 16px;
            font-size: 14px;
            display: none;
        }
        .alert.success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        .alert.error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        .alert.show {
            display: block;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }
        .info-item {
            background: #f7fafc;
            padding: 12px;
            border-radius: 8px;
        }
        .info-label {
            font-size: 12px;
            color: #718096;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 14px;
            color: #2d3748;
            font-weight: 600;
            word-break: break-all;
        }
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        .tab {
            padding: 12px 20px;
            background: none;
            border: none;
            color: #718096;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .sample-data {
            background: #f7fafc;
            padding: 12px;
            border-radius: 8px;
            margin-top: 12px;
            font-size: 13px;
            color: #4a5568;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 WhatsApp API Test Center</h1>
            <p style="color: #718096; margin-top: 8px;">Prueba el envío de mensajes de WhatsApp sin crear facturas reales</p>
            
            <div class="status">
                @if($config['enabled'])
                    <span class="status-badge success">
                        <span class="status-dot"></span>
                        WhatsApp Habilitado
                    </span>
                @else
                    <span class="status-badge error">
                        <span class="status-dot"></span>
                        WhatsApp Deshabilitado
                    </span>
                @endif
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Phone ID</div>
                    <div class="info-value">{{ $config['phone_id'] }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Business Account</div>
                    <div class="info-value">{{ $config['business_account_id'] }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Access Token</div>
                    <div class="info-value">{{ $config['has_token'] ? '✓ Configurado' : '✗ No configurado' }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('text')">📝 Mensaje Simple</button>
                <button class="tab" onclick="switchTab('invoice')">📄 Notificación de Factura</button>
                <button class="tab" onclick="switchTab('quote')">💼 Notificación de Cotización</button>
            </div>

            <!-- Tab 1: Simple Text Message -->
            <div id="tab-text" class="tab-content active">
                <h2>📝 Enviar Mensaje de Texto</h2>
                <form id="form-text" onsubmit="sendTextMessage(event)">
                    <div class="form-group">
                        <label for="text-phone">Número de WhatsApp *</label>
                        <input type="text" id="text-phone" placeholder="18091234567" required>
                        <div class="sample-data">
                            💡 Formato: código de país + número (ej: 18091234567 para RD)
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="text-message">Mensaje *</label>
                        <textarea id="text-message" placeholder="Escribe tu mensaje aquí..." required>Hola! Este es un mensaje de prueba desde GridBase Bills. 🚀</textarea>
                    </div>
                    <button type="submit" class="btn">Enviar Mensaje</button>
                    <div id="alert-text" class="alert"></div>
                </form>
            </div>

            <!-- Tab 2: Invoice Notification -->
            <div id="tab-invoice" class="tab-content">
                <h2>📄 Notificación de Factura</h2>
                <form id="form-invoice" onsubmit="sendInvoiceNotification(event)">
                    <div class="form-group">
                        <label for="invoice-phone">Número de WhatsApp *</label>
                        <input type="text" id="invoice-phone" placeholder="18091234567" required>
                    </div>
                    <div class="form-group">
                        <label for="invoice-client">Nombre del Cliente *</label>
                        <input type="text" id="invoice-client" value="Juan Pérez" required>
                    </div>
                    <div class="form-group">
                        <label for="invoice-number">Número de Factura *</label>
                        <input type="text" id="invoice-number" value="FAC-001" required>
                    </div>
                    <div class="form-group">
                        <label for="invoice-total">Total *</label>
                        <input type="number" id="invoice-total" value="2500.00" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="invoice-currency">Moneda *</label>
                        <input type="text" id="invoice-currency" value="RD$" maxlength="5" required>
                    </div>
                    <div class="form-group">
                        <label for="invoice-link">Link de Pago (Opcional)</label>
                        <input type="url" id="invoice-link" placeholder="https://bills.gridbase.com.do/pay/abc123">
                    </div>
                    <button type="submit" class="btn">Enviar Notificación</button>
                    <div id="alert-invoice" class="alert"></div>
                </form>
            </div>

            <!-- Tab 3: Quote Notification -->
            <div id="tab-quote" class="tab-content">
                <h2>💼 Notificación de Cotización</h2>
                <form id="form-quote" onsubmit="sendQuoteNotification(event)">
                    <div class="form-group">
                        <label for="quote-phone">Número de WhatsApp *</label>
                        <input type="text" id="quote-phone" placeholder="18091234567" required>
                    </div>
                    <div class="form-group">
                        <label for="quote-client">Nombre del Cliente *</label>
                        <input type="text" id="quote-client" value="María García" required>
                    </div>
                    <div class="form-group">
                        <label for="quote-number">Número de Cotización *</label>
                        <input type="text" id="quote-number" value="COT-045" required>
                    </div>
                    <div class="form-group">
                        <label for="quote-total">Total *</label>
                        <input type="number" id="quote-total" value="3500.00" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="quote-currency">Moneda *</label>
                        <input type="text" id="quote-currency" value="USD" maxlength="5" required>
                    </div>
                    <button type="submit" class="btn">Enviar Notificación</button>
                    <div id="alert-quote" class="alert"></div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function showAlert(elementId, message, type) {
            const alert = document.getElementById(elementId);
            alert.textContent = message;
            alert.className = 'alert ' + type + ' show';
            setTimeout(() => {
                alert.classList.remove('show');
            }, 5000);
        }

        async function sendTextMessage(event) {
            event.preventDefault();
            const btn = event.target.querySelector('.btn');
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            try {
                const response = await fetch('/whatsapp-test/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        phone: document.getElementById('text-phone').value,
                        message: document.getElementById('text-message').value
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    showAlert('alert-text', '✓ ' + data.message, 'success');
                } else {
                    showAlert('alert-text', '✗ ' + data.message, 'error');
                }
            } catch (error) {
                showAlert('alert-text', '✗ Error de red: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Enviar Mensaje';
            }
        }

        async function sendInvoiceNotification(event) {
            event.preventDefault();
            const btn = event.target.querySelector('.btn');
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            try {
                const response = await fetch('/whatsapp-test/invoice', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        phone: document.getElementById('invoice-phone').value,
                        client_name: document.getElementById('invoice-client').value,
                        invoice_number: document.getElementById('invoice-number').value,
                        total: document.getElementById('invoice-total').value,
                        currency: document.getElementById('invoice-currency').value,
                        payment_link: document.getElementById('invoice-link').value || null
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    showAlert('alert-invoice', '✓ ' + data.message, 'success');
                } else {
                    showAlert('alert-invoice', '✗ ' + data.message, 'error');
                }
            } catch (error) {
                showAlert('alert-invoice', '✗ Error de red: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Enviar Notificación';
            }
        }

        async function sendQuoteNotification(event) {
            event.preventDefault();
            const btn = event.target.querySelector('.btn');
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            try {
                const response = await fetch('/whatsapp-test/quote', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        phone: document.getElementById('quote-phone').value,
                        client_name: document.getElementById('quote-client').value,
                        quote_number: document.getElementById('quote-number').value,
                        total: document.getElementById('quote-total').value,
                        currency: document.getElementById('quote-currency').value
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    showAlert('alert-quote', '✓ ' + data.message, 'success');
                } else {
                    showAlert('alert-quote', '✗ ' + data.message, 'error');
                }
            } catch (error) {
                showAlert('alert-quote', '✗ Error de red: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Enviar Notificación';
            }
        }
    </script>
</body>
</html>
