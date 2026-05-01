<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link de Pago Expirado</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            text-align: center;
        }
        
        .icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
        }
        
        .icon svg {
            width: 80px;
            height: 80px;
            stroke: white;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        
        .content {
            padding: 40px;
        }
        
        .content h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .content p {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .invoice-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .invoice-info .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .invoice-info .value {
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }
        
        .contact-info {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        
        .contact-info h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .contact-info p {
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .reasons {
            text-align: left;
            margin: 20px 0;
        }
        
        .reasons h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .reasons ul {
            list-style: none;
            padding: 0;
        }
        
        .reasons li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            color: #666;
            font-size: 14px;
        }
        
        .reasons li:before {
            content: "•";
            position: absolute;
            left: 10px;
            color: #667eea;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        
        <div class="content">
            <h1>Link de Pago Expirado</h1>
            
            <div class="invoice-info">
                <div class="label">Factura</div>
                <div class="value">#{{ $invoice->invoice_number }}</div>
            </div>
            
            <p>Este link de pago ya no es válido.</p>
            
            <div class="reasons">
                <h3>Posibles razones:</h3>
                <ul>
                    <li>El link ha expirado por tiempo</li>
                    <li>La factura ya fue pagada</li>
                    <li>La factura fue cancelada</li>
                </ul>
            </div>
            
            <div class="contact-info">
                <h2>¿Necesita ayuda?</h2>
                <p>Por favor contacte con nosotros para obtener un nuevo link de pago o para cualquier consulta.</p>
                <p><strong>Cliente:</strong> {{ $invoice->client->company_name ?: $invoice->client->contact_name }}</p>
                @if($invoice->client->email)
                <p><strong>Su email:</strong> {{ $invoice->client->email }}</p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
