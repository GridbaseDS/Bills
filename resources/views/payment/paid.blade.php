<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Pagada - #{{ $invoice->invoice_number }}</title>
    <style>
        :root {
            --primary: #0B484C;
            --accent: #00DF83;
            --success: #10B981;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, #064347 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            text-align: center;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, #064347 100%);
            padding: 30px 40px;
            color: white;
        }
        
        .logo {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .logo .accent {
            color: var(--accent);
        }
        
        .header-subtitle {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .success-icon {
            background: white;
            padding: 40px;
            position: relative;
        }
        
        .checkmark {
            width: 100px;
            height: 100px;
            margin: 0 auto;
            position: relative;
        }
        
        .checkmark-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: var(--success);
            animation: scaleIn 0.5s ease-out;
        }
        
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        
        .checkmark-check {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(45deg);
            width: 30px;
            height: 50px;
            border: solid white;
            border-width: 0 5px 5px 0;
            animation: checkmark 0.5s 0.3s ease-out forwards;
            opacity: 0;
        }
        
        @keyframes checkmark {
            to { opacity: 1; }
        }
        
        .content {
            padding: 40px;
        }
        
        .content h1 {
            font-size: 28px;
            color: #111827;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .content p {
            font-size: 16px;
            color: #6B7280;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .invoice-info {
            background: #F3F4F6;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            text-align: left;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .info-row:last-child {
            border-bottom: none;
            padding-top: 20px;
            margin-top: 10px;
            border-top: 2px solid var(--primary);
        }
        
        .info-label {
            font-size: 14px;
            color: #6B7280;
        }
        
        .info-value {
            font-size: 16px;
            color: #111827;
            font-weight: 600;
        }
        
        .info-value.highlight {
            font-size: 24px;
            color: var(--success);
            font-weight: 700;
        }
        
        .footer-note {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #F3F4F6;
            font-size: 14px;
            color: #6B7280;
        }
        
        .footer-note strong {
            color: #111827;
        }
        
        .paid-badge {
            display: inline-block;
            background: var(--success);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Grid<span class="accent">Base</span></div>
            <div class="header-subtitle">Sistema de Facturación</div>
        </div>
        
        <div class="success-icon">
            <div class="checkmark">
                <div class="checkmark-circle"></div>
                <div class="checkmark-check"></div>
            </div>
        </div>
        
        <div class="content">
            <h1>¡Factura Pagada!</h1>
            <div class="paid-badge">✓ COMPLETAMENTE PAGADA</div>
            <p>Esta factura ya ha sido pagada en su totalidad. No se requiere ninguna acción adicional.</p>
            
            <div class="invoice-info">
                <div class="info-row">
                    <div class="info-label">Número de Factura</div>
                    <div class="info-value">#{{ $invoice->invoice_number }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Cliente</div>
                    <div class="info-value">{{ $invoice->client ? $invoice->client->name : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha de Emisión</div>
                    <div class="info-value">{{ $invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : 'N/A' }}</div>
                </div>
                @if($invoice->paid_at)
                <div class="info-row">
                    <div class="info-label">Fecha de Pago</div>
                    <div class="info-value">{{ $invoice->paid_at->format('d/m/Y H:i') }}</div>
                </div>
                @endif
                <div class="info-row">
                    <div class="info-label">Total Pagado</div>
                    <div class="info-value highlight">
                        @if($invoice->currency === 'DOP')
                            RD${{ number_format($invoice->total, 2) }}
                        @else
                            {{ $invoice->currency }} {{ number_format($invoice->total, 2) }}
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="footer-note">
                <strong>¿Necesita ayuda?</strong><br>
                Si tiene alguna pregunta sobre esta factura, por favor contacte con nuestro equipo de soporte.
                @if($invoice->client && $invoice->client->email)
                <br><br><strong>Su email registrado:</strong> {{ $invoice->client->email }}
                @endif
            </div>
        </div>
    </div>
</body>
</html>
