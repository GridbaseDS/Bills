<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar Factura #{{ $invoice->invoice_number }}</title>
    <style>
        :root {
            --primary: #0B484C;
            --primary-dark: #094044;
            --accent: #00DF83;
            --bg: #F9FAFB;
            --text: #111827;
            --text-light: #6B7280;
            --border: #E5E7EB;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }
        
        .checkout-container {
            max-width: 800px;
            margin: 0 auto;
            animation: fadeIn 0.4s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .checkout-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }
        
        .logo .accent {
            color: var(--accent);
        }
        
        .checkout-title {
            font-size: 15px;
            color: var(--text-light);
        }
        
        .steps {
            display: flex;
            justify-content: center;
            gap: 60px;
            margin-bottom: 40px;
            position: relative;
        }
        
        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 2px;
            background: var(--border);
            z-index: 0;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--border);
            color: #9CA3AF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 10px;
            transition: all 0.3s;
        }
        
        .step.active .step-circle {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 0 0 4px rgba(11, 72, 76, 0.1);
        }
        
        .step.completed .step-circle {
            background: #10B981;
            border-color: #10B981;
            color: white;
        }
        
        .step-label {
            font-size: 13px;
            color: #9CA3AF;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 32px;
            margin-bottom: 20px;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            padding-bottom: 24px;
            margin-bottom: 24px;
            border-bottom: 2px solid #F3F4F6;
        }
        
        .invoice-number-large {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 6px;
        }
        
        .invoice-subtitle {
            font-size: 14px;
            color: var(--text-light);
        }
        
        .status-badge {
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #FEF3C7; color: #92400E; }
        .status-paid { background: #D1FAE5; color: #065F46; }
        .status-overdue { background: #FEE2E2; color: #991B1B; }
        .status-partial { background: #DBEAFE; color: #1E40AF; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .info-item {
            background: #F9FAFB;
            padding: 16px;
            border-radius: 8px;
        }
        
        .info-label {
            font-size: 12px;
            color: #9CA3AF;
            text-transform: uppercase;
            margin-bottom: 6px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 15px;
            color: var(--text);
            font-weight: 600;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .items-list {
            margin-bottom: 24px;
        }
        
        .item {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid #F3F4F6;
        }
        
        .item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-description {
            font-size: 15px;
            color: var(--text);
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .item-details {
            font-size: 13px;
            color: var(--text-light);
        }
        
        .item-amount {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin-left: 20px;
            white-space: nowrap;
        }
        
        .summary {
            background: #F9FAFB;
            border-radius: 8px;
            padding: 20px;
            margin-top: 24px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
        }
        
        .summary-row:last-child {
            margin-bottom: 0;
        }
        
        .summary-label {
            color: var(--text-light);
        }
        
        .summary-value {
            font-weight: 600;
            color: var(--text);
        }
        
        .summary-divider {
            height: 1px;
            background: var(--border);
            margin: 16px 0;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 2px solid var(--primary);
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .payment-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            margin-top: 24px;
        }
        
        .payment-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .payment-amount {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 24px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .payment-description {
            font-size: 14px;
            opacity: 0.85;
            margin-bottom: 28px;
        }
        
        #paypal-button-container {
            max-width: 500px;
            margin: 0 auto;
            min-height: 50px;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
            animation: slideDown 0.3s;
        }
        
        .alert.show {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid #10B981;
        }
        
        .alert-success::before {
            content: '✅';
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #EF4444;
        }
        
        .alert-error::before {
            content: '❌';
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 40px;
        }
        
        .loading.active {
            display: block;
        }
        
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: white;
            font-weight: 500;
            font-size: 15px;
        }
        
        .secure-badge {
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .paid-notice {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
        }
        
        .paid-notice-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        
        .paid-notice-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .paid-notice-text {
            font-size: 16px;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .card {
                padding: 24px 20px;
            }
            
            .invoice-header {
                flex-direction: column;
                gap: 16px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .item {
                flex-direction: column;
                gap: 8px;
            }
            
            .item-amount {
                margin-left: 0;
                font-size: 18px;
            }
            
            .payment-amount {
                font-size: 42px;
            }
            
            .steps {
                gap: 40px;
            }
            
            .steps::before {
                width: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <div class="logo">Grid<span class="accent">Base</span></div>
            <div class="checkout-title">Portal de Pagos Seguro</div>
        </div>
        
        <div class="steps">
            <div class="step active" id="step-review">
                <div class="step-circle">1</div>
                <div class="step-label">Revisar</div>
            </div>
            <div class="step" id="step-pay">
                <div class="step-circle">2</div>
                <div class="step-label">Pagar</div>
            </div>
        </div>
        
        <div id="message-container"></div>
        
        <div class="card">
            <div class="invoice-header">
                <div>
                    <div class="invoice-number-large">{{ $invoice->invoice_number }}</div>
                    <div class="invoice-subtitle">
                        Emitida: {{ $invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : 'N/A' }} · 
                        Vence: {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}
                    </div>
                </div>
                <div>
                    @php
                        $statusClass = 'status-pending';
                        $statusText = 'Pendiente';
                        
                        if ($invoice->status === 'paid') {
                            $statusClass = 'status-paid';
                            $statusText = 'Pagada';
                        } elseif ($invoice->status === 'partial') {
                            $statusClass = 'status-partial';
                            $statusText = 'Pago Parcial';
                        } elseif ($invoice->due_date < now() && $invoice->getRemainingBalance() > 0) {
                            $statusClass = 'status-overdue';
                            $statusText = 'Vencida';
                        }
                    @endphp
                    <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Cliente</div>
                    <div class="info-value">{{ $invoice->client ? $invoice->client->name : 'Sin cliente' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value">{{ $invoice->client?->email ?? 'No disponible' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Moneda</div>
                    <div class="info-value">{{ $invoice->currency }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Método de Pago</div>
                    <div class="info-value">PayPal / Tarjeta</div>
                </div>
            </div>
            
            <div class="section-title">📋 Conceptos Facturados</div>
            
            <div class="items-list">
                @foreach($invoice->items as $item)
                <div class="item">
                    <div class="item-info">
                        <div class="item-description">{{ $item->description }}</div>
                        <div class="item-details">
                            {{ number_format($item->quantity, 0) }} × 
                            @php
                                $symbol = $invoice->currency === 'USD' ? '$' : ($invoice->currency === 'DOP' ? 'RD$' : $invoice->currency);
                            @endphp
                            {{ $symbol }}{{ number_format($item->unit_price, 2) }}
                        </div>
                    </div>
                    <div class="item-amount">
                        {{ $symbol }}{{ number_format($item->amount, 2) }}
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="summary">
                <div class="summary-row">
                    <span class="summary-label">Subtotal</span>
                    <span class="summary-value">{{ $symbol }}{{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                
                @if($invoice->discount_amount > 0)
                <div class="summary-row">
                    <span class="summary-label">Descuento</span>
                    <span class="summary-value">-{{ $symbol }}{{ number_format($invoice->discount_amount, 2) }}</span>
                </div>
                @endif
                
                @if($invoice->tax_amount > 0)
                <div class="summary-row">
                    <span class="summary-label">Impuesto ({{ number_format($invoice->tax_rate, 2) }}%)</span>
                    <span class="summary-value">{{ $symbol }}{{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
                @endif
                
                <div class="summary-divider"></div>
                
                <div class="summary-row">
                    <span class="summary-label">Total Factura</span>
                    <span class="summary-value">{{ $symbol }}{{ number_format($invoice->total, 2) }}</span>
                </div>
                
                @if($invoice->amount_paid > 0)
                <div class="summary-row">
                    <span class="summary-label">Pagos Anteriores</span>
                    <span class="summary-value">-{{ $symbol }}{{ number_format($invoice->amount_paid, 2) }}</span>
                </div>
                @endif
                
                @if($invoice->getRemainingBalance() > 0)
                <div class="summary-total">
                    <span class="summary-label">A Pagar</span>
                    <span class="summary-value">{{ $symbol }}{{ number_format($invoice->getRemainingBalance(), 2) }}</span>
                </div>
                @endif
            </div>
        </div>
        
        @if($invoice->getRemainingBalance() > 0)
        <div class="payment-card">
            <div class="payment-label">Total a pagar ahora</div>
            <div class="payment-amount">{{ $symbol }}{{ number_format($invoice->getRemainingBalance(), 2) }}</div>
            <div class="payment-description">🔒 Pago seguro procesado por PayPal • Aceptamos tarjetas de crédito y débito</div>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <div class="loading-text">Procesando pago...</div>
            </div>
            
            <div id="paypal-button-container"></div>
        </div>
        @else
        <div class="paid-notice">
            <div class="paid-notice-icon">✅</div>
            <div class="paid-notice-title">¡Factura Pagada!</div>
            <div class="paid-notice-text">Esta factura ya ha sido pagada en su totalidad. Gracias por su pago.</div>
        </div>
        @endif
        
        <div class="secure-badge">
            🔒 Conexión segura con encriptación SSL · Pagos procesados por PayPal
        </div>
    </div>
    
    @if($invoice->getRemainingBalance() > 0)
    <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $invoice->currency }}&locale=es_ES"></script>
    <script>
        const stepReview = document.getElementById('step-review');
        const stepPay = document.getElementById('step-pay');
        
        paypal.Buttons({
            createOrder: function(data, actions) {
                // Mark step 1 as completed and activate step 2
                stepReview.classList.add('completed');
                stepReview.classList.remove('active');
                stepPay.classList.add('active');
                
                document.getElementById('loading').classList.add('active');
                document.getElementById('paypal-button-container').style.display = 'none';
                
                return fetch('{{ route("payment.create-order", $invoice->payment_token) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(order => {
                    document.getElementById('loading').classList.remove('active');
                    document.getElementById('paypal-button-container').style.display = 'block';
                    
                    if (order.error) {
                        throw new Error(order.error);
                    }
                    return order.id;
                })
                .catch(error => {
                    // Reset steps on error
                    stepReview.classList.remove('completed');
                    stepReview.classList.add('active');
                    stepPay.classList.remove('active');
                    
                    document.getElementById('loading').classList.remove('active');
                    document.getElementById('paypal-button-container').style.display = 'block';
                    showMessage('Error al crear la orden: ' + error.message, 'error');
                    throw error;
                });
            },
            
            onApprove: function(data, actions) {
                document.getElementById('loading').classList.add('active');
                document.getElementById('paypal-button-container').style.display = 'none';
                
                // Mark step 2 as completed
                stepPay.classList.add('completed');
                
                return fetch('{{ route("payment.capture-order", $invoice->payment_token) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        orderID: data.orderID
                    })
                })
                .then(response => response.json())
                .then(result => {
                    document.getElementById('loading').classList.remove('active');
                    
                    if (result.success) {
                        showMessage('✅ ¡Pago completado con éxito! Gracias por su pago. La página se recargará...', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2500);
                    } else {
                        // Reset step 2 on error
                        stepPay.classList.remove('completed');
                        
                        showMessage('Error al capturar el pago: ' + (result.error || 'No se pudo completar el pago'), 'error');
                        document.getElementById('paypal-button-container').style.display = 'block';
                    }
                })
                .catch(error => {
                    stepPay.classList.remove('completed');
                    
                    document.getElementById('loading').classList.remove('active');
                    document.getElementById('paypal-button-container').style.display = 'block';
                    showMessage('Error de conexión: ' + error.message, 'error');
                });
            },
            
            onError: function(err) {
                // Reset steps
                stepReview.classList.remove('completed');
                stepReview.classList.add('active');
                stepPay.classList.remove('active');
                stepPay.classList.remove('completed');
                
                document.getElementById('loading').classList.remove('active');
                document.getElementById('paypal-button-container').style.display = 'block';
                showMessage('❌ Error al procesar el pago. Por favor, intente nuevamente.', 'error');
                console.error(err);
            },
            
            onCancel: function(data) {
                // Reset to step 1
                stepReview.classList.remove('completed');
                stepReview.classList.add('active');
                stepPay.classList.remove('active');
                
                document.getElementById('loading').classList.remove('active');
                document.getElementById('paypal-button-container').style.display = 'block';
                showMessage('⚠️ Pago cancelado. Puede intentar nuevamente cuando esté listo.', 'error');
            }
        }).render('#paypal-button-container');
        
        function showMessage(message, type) {
            const container = document.getElementById('message-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            container.innerHTML = `<div class="alert ${alertClass} show">${message}</div>`;
            
            // Scroll to message
            container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    </script>
    @endif
</body>
</html>
