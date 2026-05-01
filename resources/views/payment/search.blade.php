<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Factura - Pagar</title>
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
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 15px;
            opacity: 0.95;
            line-height: 1.5;
        }
        
        .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            text-transform: uppercase;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
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
        }
        
        .alert.show {
            display: block;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-info {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }
        
        .examples {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 13px;
            color: #666;
        }
        
        .examples strong {
            color: #333;
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
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
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
            color: #666;
            margin-top: 8px;
        }
        
        @media (max-width: 768px) {
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
                            Ingrese el número exacto de su factura tal como aparece en el documento
                        </div>
                    </div>
                    
                    <button type="submit" class="btn" id="searchBtn">
                        Buscar y Pagar
                    </button>
                </form>
                
                <div class="examples">
                    <strong>💡 Ejemplos de formatos:</strong><br>
                    • INV-2026-001<br>
                    • FACT-001<br>
                    • 2026-001
                </div>
            </div>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Buscando factura...</p>
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
