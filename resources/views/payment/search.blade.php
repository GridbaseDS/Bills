<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Factura - Gridbase Bills</title>
    <link rel="icon" type="image/png" href="https://gridbase.com.do/wp-content/uploads/2026/03/cropped-imagen_2026-03-18_101800374-180x180.png">
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
            max-width: 520px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .header {
            background: #0B484C;
            color: white;
            padding: 40px 30px;
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
        
        .header .logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .header .logo .accent {
            color: #00DF83;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.5;
        }
        
        .icon {
            font-size: 40px;
            margin-bottom: 12px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 8px;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s;
            text-transform: uppercase;
            font-weight: 600;
            color: #0B484C;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #0B484C;
            box-shadow: 0 0 0 3px rgba(11, 72, 76, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: #0B484C;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            background: #094044;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(11, 72, 76, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert.show {
            display: block;
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #DC2626;
        }
        
        .alert-info {
            background: #DBEAFE;
            color: #1E40AF;
            border-left: 4px solid #2563EB;
        }
        
        .examples {
            margin-top: 20px;
            padding: 16px;
            background: #F4F6F8;
            border-radius: 8px;
            font-size: 13px;
            color: #6B7280;
            border: 1px solid #E5E7EB;
        }
        
        .examples strong {
            color: #0B484C;
            display: block;
            margin-bottom: 8px;
        }
        
        .examples div {
            line-height: 1.8;
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
        
        .help-text {
            font-size: 13px;
            color: #6B7280;
            margin-top: 8px;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                margin: 10px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Grid<span class="accent">Base</span></div>
            <div class="icon">🔍</div>
            <h1>Buscar Factura</h1>
            <p>Ingrese el número de su factura para proceder con el pago</p>
        </div>
        
        <div class="content">
            <div id="alert-container"></div>
            
            <div id="search-form">
                <form id="invoiceSearchForm">
                    <div class="form-group">
                        <label for="invoice_number">Número de Factura</label>
                        <input 
                            type="text" 
                            id="invoice_number" 
                            name="invoice_number" 
                            placeholder="Ej: INV-2026-001"
                            required
                            autocomplete="off"
                        />
                        <div class="help-text">
                            Ingrese el número exacto tal como aparece en su factura
                        </div>
                    </div>
                    
                    <button type="submit" class="btn" id="searchBtn">
                        Buscar y Pagar
                    </button>
                </form>
                
                <div class="examples">
                    <strong>💡 Ejemplos de formatos:</strong>
                    <div>
                        • INV-2026-001<br>
                        • FACT-001<br>
                        • 2026-001
                    </div>
                </div>
            </div>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p style="color: #6B7280;">Buscando factura...</p>
            </div>
        </div>
    </div>
    
    <script>
        const form = document.getElementById('invoiceSearchForm');
        const searchBtn = document.getElementById('searchBtn');
        const loading = document.getElementById('loading');
        const searchForm = document.getElementById('search-form');
        const alertContainer = document.getElementById('alert-container');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const invoiceNumber = document.getElementById('invoice_number').value.trim();
            
            if (!invoiceNumber) {
                showAlert('Por favor ingrese un número de factura', 'error');
                return;
            }
            
            // Show loading
            searchBtn.disabled = true;
            searchBtn.textContent = 'Buscando...';
            loading.classList.add('active');
            alertContainer.innerHTML = '';
            
            try {
                const response = await fetch('/buscar-factura', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ invoice_number: invoiceNumber })
                });
                
                const data = await response.json();
                
                if (data.success && data.payment_url) {
                    showAlert('¡Factura encontrada! Redirigiendo...', 'info');
                    setTimeout(() => {
                        window.location.href = data.payment_url;
                    }, 1000);
                } else {
                    showAlert(data.message || 'No se encontró una factura con ese número', 'error');
                    searchBtn.disabled = false;
                    searchBtn.textContent = 'Buscar y Pagar';
                    loading.classList.remove('active');
                }
            } catch (error) {
                showAlert('Error al buscar la factura. Por favor intente nuevamente.', 'error');
                searchBtn.disabled = false;
                searchBtn.textContent = 'Buscar y Pagar';
                loading.classList.remove('active');
            }
        });
        
        function showAlert(message, type) {
            const alertClass = type === 'error' ? 'alert-error' : 'alert-info';
            alertContainer.innerHTML = `<div class="alert ${alertClass} show">${message}</div>`;
        }
        
        // Auto-uppercase
        document.getElementById('invoice_number').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>
