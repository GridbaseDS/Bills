<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configuración - Sistema de Facturación</title>
    <style>
        :root {
            --primary: #0B484C;
            --accent: #00DF83;
            --bg: #F9FAFB;
            --text: #1F2937;
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
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 8px;
        }
        
        .header p {
            color: #6B7280;
            font-size: 0.95rem;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: none;
            animation: slideIn 0.3s ease;
        }
        
        .alert.show {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #ECFDF5;
            border: 2px solid var(--success);
            color: #065F46;
        }
        
        .alert-error {
            background: #FEF2F2;
            border: 2px solid var(--error);
            color: #991B1B;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 32px;
            margin-bottom: 24px;
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--bg);
        }
        
        .card-icon {
            font-size: 2rem;
        }
        
        .card-title {
            flex: 1;
        }
        
        .card-title h2 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 4px;
        }
        
        .card-title p {
            font-size: 0.9rem;
            color: #6B7280;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0, 223, 131, 0.1);
        }
        
        .form-group small {
            display: block;
            margin-top: 6px;
            color: #6B7280;
            font-size: 0.85rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            flex: 1;
        }
        
        .btn-primary:hover {
            background: #0A3A3D;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 72, 76, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-secondary:hover {
            background: var(--bg);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-configured {
            background: #ECFDF5;
            color: #065F46;
        }
        
        .status-not-configured {
            background: #FEF2F2;
            color: #991B1B;
        }
        
        .help-box {
            background: #EFF6FF;
            border-left: 4px solid #3B82F6;
            padding: 16px;
            border-radius: 8px;
            margin-top: 24px;
        }
        
        .help-box h4 {
            color: #1E40AF;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .help-box ol {
            margin-left: 20px;
            color: #1E3A8A;
        }
        
        .help-box a {
            color: #2563EB;
            font-weight: 600;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loading-overlay">
        <div class="spinner"></div>
    </div>
    
    <div class="container">
        <div class="header">
            <h1>⚙️ Configuración del Sistema</h1>
            <p>Gestiona las configuraciones de pagos y servicios externos</p>
        </div>
        
        <div id="message-container">
            @if(session('success'))
            <div class="alert alert-success show">
                {{ session('success') }}
            </div>
            @endif
            
            @if($errors->any())
            <div class="alert alert-error show">
                <strong>Error:</strong> {{ $errors->first() }}
            </div>
            @endif
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="card-icon">💳</div>
                <div class="card-title">
                    <h2>Configuración de PayPal</h2>
                    <p>Configura las credenciales de PayPal para procesar pagos en línea</p>
                </div>
                @php
                    $isConfigured = !empty($settings['paypal_client_id']) && !empty($settings['paypal_client_secret']);
                @endphp
                <span class="status-badge {{ $isConfigured ? 'status-configured' : 'status-not-configured' }}">
                    {{ $isConfigured ? '✅ Configurado' : '⚠️ No configurado' }}
                </span>
            </div>
            
            <form method="POST" action="{{ route('settings.paypal.update') }}" id="paypal-form">
                @csrf
                
                <div class="form-group">
                    <label for="paypal_mode">Modo de Operación</label>
                    <select name="paypal_mode" id="paypal_mode" required>
                        <option value="sandbox" {{ ($settings['paypal_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>
                            🧪 Sandbox (Pruebas)
                        </option>
                        <option value="live" {{ ($settings['paypal_mode'] ?? 'sandbox') === 'live' ? 'selected' : '' }}>
                            🚀 Live (Producción)
                        </option>
                    </select>
                    <small>Usa "Sandbox" para pruebas y "Live" para transacciones reales</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="paypal_client_id">Client ID</label>
                        <input 
                            type="text" 
                            name="paypal_client_id" 
                            id="paypal_client_id" 
                            value="{{ $settings['paypal_client_id'] ?? '' }}"
                            placeholder="Ingresa tu PayPal Client ID"
                        >
                        <small>Obtenlo desde el PayPal Developer Dashboard</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="paypal_client_secret">Client Secret</label>
                        <input 
                            type="password" 
                            name="paypal_client_secret" 
                            id="paypal_client_secret" 
                            value="{{ $settings['paypal_client_secret'] ?? '' }}"
                            placeholder="Ingresa tu PayPal Client Secret"
                        >
                        <small>Mantenlo seguro y nunca lo compartas</small>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="testConnection()">
                        🔍 Probar Conexión
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Guardar Configuración
                    </button>
                </div>
            </form>
            
            <div class="help-box">
                <h4>💡 ¿Cómo obtener tus credenciales de PayPal?</h4>
                <ol>
                    <li>Ve a <a href="https://developer.paypal.com/dashboard/" target="_blank">PayPal Developer Dashboard</a></li>
                    <li>Inicia sesión con tu cuenta de PayPal</li>
                    <li>Ve a "Apps & Credentials"</li>
                    <li>En la pestaña "Sandbox" (para pruebas) o "Live" (para producción), crea o selecciona una app</li>
                    <li>Copia el "Client ID" y el "Secret"</li>
                </ol>
            </div>
        </div>
    </div>
    
    <script>
        function testConnection() {
            const clientId = document.getElementById('paypal_client_id').value;
            const clientSecret = document.getElementById('paypal_client_secret').value;
            const mode = document.getElementById('paypal_mode').value;
            
            if (!clientId || !clientSecret) {
                showMessage('Por favor, ingresa Client ID y Client Secret antes de probar la conexión', 'error');
                return;
            }
            
            const overlay = document.getElementById('loading-overlay');
            overlay.classList.add('active');
            
            fetch('{{ route("settings.paypal.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    client_id: clientId,
                    client_secret: clientSecret,
                    mode: mode
                })
            })
            .then(response => response.json())
            .then(data => {
                overlay.classList.remove('active');
                showMessage(data.message, data.success ? 'success' : 'error');
            })
            .catch(error => {
                overlay.classList.remove('active');
                showMessage('Error al probar la conexión: ' + error.message, 'error');
            });
        }
        
        function showMessage(message, type) {
            const container = document.getElementById('message-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            container.innerHTML = `<div class="alert ${alertClass} show">${message}</div>`;
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    alert.classList.remove('show');
                }
            }, 5000);
        }
        
        // Show loading overlay on form submit
        document.getElementById('paypal-form').addEventListener('submit', function() {
            document.getElementById('loading-overlay').classList.add('active');
        });
    </script>
</body>
</html>
