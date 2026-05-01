<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar Factura #{{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #F4F6F8;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .header {
            background: #0B484C;
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #00DF83;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .invoice-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        .items-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }
        
        .items-table thead {
            background: #f8f9fa;
        }
        
        .items-table th {
            padding: 12px;
            text-align: left;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .totals {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-bottom: 30px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            min-width: 300px;
            padding: 8px 0;
        }
        
        .total-label {
            font-size: 14px;
            color: #666;
        }
        
        .total-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }
        
        .total-row.final {
            border-top: 2px solid #667eea;
            padding-top: 12px;
            margin-top: 8px;
        }
        
        .total-row.final .total-label {
            font-size: 18px;
            color: #333;
            font-weight: 700;
        }
        
        .total-row.final .total-value {
            font-size: 24px;
            color: #667eea;
            font-weight: 700;
        }
        
        .payment-section {
            background: linear-gradient(135deg, #0B484C 0%, #094044 100%);
            padding: 40px 30px;
            border-radius: 12px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .payment-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #00DF83;
        }
        
        .payment-section h2 {
            font-size: 20px;
            color: #FFFFFF;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .payment-section p {
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        #paypal-button-container {
            max-width: 500px;
            margin: 0 auto;
            min-height: 50px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-partial {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-pending {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .loading.active {
            display: block;
        }
        
        .spinner {
            border: 4px solid #F4F6F8;
            border-top: 4px solid #0B484C;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .invoice-info {
                grid-template-columns: 1fr;
            }
            
            .total-row {
                min-width: 100%;
            }
            
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Factura #{{ $invoice->invoice_number }}</h1>
            <p>Por favor revise los detalles y proceda con el pago</p>
        </div>
        
        <div class="content">
            <div class="invoice-info">
                <div class="info-item">
                    <span class="info-label">Cliente</span>
                    <span class="info-value">{{ $invoice->client->company_name ?: $invoice->client->contact_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value">{{ $invoice->client->email }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha de Emisión</span>
                    <span class="info-value">{{ $invoice->issue_date->format('d/m/Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha de Vencimiento</span>
                    <span class="info-value">{{ $invoice->due_date->format('d/m/Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado</span>
                    <span class="info-value">
                        @if($invoice->status === 'paid')
                            <span class="status-badge status-paid">Pagada</span>
                        @elseif($invoice->status === 'partial')
                            <span class="status-badge status-partial">Pago Parcial</span>
                        @else
                            <span class="status-badge status-pending">Pendiente</span>
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Moneda</span>
                    <span class="info-value">{{ $invoice->currency }}</span>
                </div>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">Precio Unitario</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="totals">
                <div class="total-row">
                    <span class="total-label">Subtotal:</span>
                    <span class="total-value">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                
                @if($invoice->discount_amount > 0)
                <div class="total-row">
                    <span class="total-label">Descuento:</span>
                    <span class="total-value">-{{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</span>
                </div>
                @endif
                
                @if($invoice->tax_amount > 0)
                <div class="total-row">
                    <span class="total-label">Impuesto ({{ number_format($invoice->tax_rate, 2) }}%):</span>
                    <span class="total-value">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
                @endif
                
                <div class="total-row final">
                    <span class="total-label">Total:</span>
                    <span class="total-value">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</span>
                </div>
                
                @if($invoice->amount_paid > 0)
                <div class="total-row">
                    <span class="total-label">Ya Pagado:</span>
                    <span class="total-value">{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</span>
                </div>
                <div class="total-row final">
                    <span class="total-label">Saldo Restante:</span>
                    <span class="total-value">{{ $invoice->currency }} {{ number_format($invoice->getRemainingBalance(), 2) }}</span>
                </div>
                @endif
            </div>
            
            <div id="message-container"></div>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Procesando pago...</p>
            </div>
            
            @if($invoice->getRemainingBalance() > 0)
            <div class="payment-section">
                <h2>💳 Pagar con PayPal</h2>
                <p>Pague de forma segura usando PayPal o tarjeta de crédito/débito</p>
                <div id="paypal-button-container"></div>
            </div>
            @else
            <div class="alert alert-success">
                <strong>¡Factura Pagada!</strong> Esta factura ya ha sido pagada en su totalidad.
            </div>
            @endif
        </div>
    </div>
    
    @if($invoice->getRemainingBalance() > 0)
    <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $invoice->currency }}&locale=es_ES"></script>
    <script>
        paypal.Buttons({
            createOrder: function(data, actions) {
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
                    document.getElementById('loading').classList.remove('active');
                    document.getElementById('paypal-button-container').style.display = 'block';
                    showMessage('Error: ' + error.message, 'error');
                });
            },
            
            onApprove: function(data, actions) {
                document.getElementById('loading').classList.add('active');
                document.getElementById('paypal-button-container').style.display = 'none';
                
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
                        showMessage('¡Pago completado con éxito! Gracias por su pago.', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showMessage('Error: ' + (result.error || 'No se pudo completar el pago'), 'error');
                        document.getElementById('paypal-button-container').style.display = 'block';
                    }
                })
                .catch(error => {
                    document.getElementById('loading').classList.remove('active');
                    document.getElementById('paypal-button-container').style.display = 'block';
                    showMessage('Error: ' + error.message, 'error');
                });
            },
            
            onError: function(err) {
                document.getElementById('loading').classList.remove('active');
                document.getElementById('paypal-button-container').style.display = 'block';
                showMessage('Error al procesar el pago. Por favor, intente nuevamente.', 'error');
                console.error(err);
            },
            
            onCancel: function(data) {
                document.getElementById('loading').classList.remove('active');
                document.getElementById('paypal-button-container').style.display = 'block';
                showMessage('Pago cancelado. Puede intentar nuevamente cuando esté listo.', 'error');
            }
        }).render('#paypal-button-container');
        
        function showMessage(message, type) {
            const container = document.getElementById('message-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            container.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
            
            // Scroll to message
            container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    </script>
    @endif
</body>
</html>
