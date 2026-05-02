<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link de Pago Expirado</title>
    <style>
        :root {
            --primary: #0B484C;
            --accent: #00DF83;
            --warning: #F59E0B;
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
        
        .icon {
            background: white;
            padding: 40px;
        }
        
        .warning-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto;
            border-radius: 50%;
            background: #FEF3C7;
            position: relative;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .warning-icon::before {
            content: "!";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 60px;
            font-weight: 700;
            color: var(--warning);
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
            padding: 20px;
            margin: 20px 0;
        }
        
        .invoice-info .label {
            font-size: 12px;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .invoice-info .value {
            font-size: 20px;
            color: #111827;
            font-weight: 700;
        }
        
        .reasons {
            text-align: left;
            margin: 25px 0;
            background: #FEF3C7;
            border-left: 4px solid var(--warning);
            border-radius: 8px;
            padding: 20px;
        }
        
        .reasons h3 {
            font-size: 16px;
            color: #111827;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .reasons ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .reasons li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            color: #92400E;
            font-size: 14px;
        }
        
        .reasons li:before {
            content: "•";
            position: absolute;
            left: 10px;
            color: var(--warning);
            font-weight: bold;
            font-size: 18px;
        }
        
        .contact-info {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #F3F4F6;
        }
        
        .contact-info h2 {
            font-size: 18px;
            color: #111827;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .contact-info p {
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .contact-info strong {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Grid<span class="accent">Base</span></div>
            <div class="header-subtitle">Sistema de Facturación</div>
        </div>
        
        <div class="icon">
            <div class="warning-icon"></div>
        </div>
        
        <div class="content">
            <h1>Link de Pago Expirado</h1>
            
            <div class="invoice-info">
                <div class="label">Factura</div>
                <div class="value">#{{ $invoice->invoice_number }}</div>
            </div>
            
            <p>Este link de pago ya no es válido y no puede ser utilizado para procesar pagos.</p>
            
            <div class="reasons">
                <h3>⚠️ Posibles razones:</h3>
                <ul>
                    <li>El link ha expirado por seguridad (72 horas)</li>
                    <li>Ya se generó un nuevo link de pago</li>
                    <li>La factura fue modificada o cancelada</li>
                </ul>
            </div>
            
            <div class="contact-info">
                <h2>¿Necesita Realizar el Pago?</h2>
                <p>Por favor contacte con nosotros para obtener un <strong>nuevo link de pago</strong> actualizado.</p>
                <p><strong>Cliente:</strong> {{ $invoice->client ? $invoice->client->name : 'N/A' }}</p>
                @if($invoice->client && $invoice->client->email)
                <p><strong>Su email:</strong> {{ $invoice->client->email }}</p>
                @endif
                <p style="margin-top: 20px; color: #6B7280; font-size: 13px;">
                    Los links de pago expiran automáticamente por seguridad. 
                    Solicite uno nuevo para poder completar su pago de forma segura.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
