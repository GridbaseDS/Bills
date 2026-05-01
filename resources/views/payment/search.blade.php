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
            background: linear-gradient(135deg, #F4F6F8 0%, #E5E7EB 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 560px;
            width: 100%;
            animation: fadeInUp 0.5s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #0B484C 0%, #094044 100%);
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
            height: 4px;
            background: linear-gradient(90deg, #00DF83 0%, #00B96B 100%);
        }
        
        .header .logo {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .header .logo .accent {
            color: #00DF83;
        }
        
        .icon {
            font-size: 48px;
            margin-bottom: 16px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .header h1 {
            font-size: 26px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .header p {
            font-size: 15px;
            opacity: 0.9;
            line-height: 1.5;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 10px;
        }
        
        label::before {
            content: '📄';
            margin-right: 8px;
            font-size: 18px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 16px 48px 16px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 17px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            font-weight: 600;
            color: #0B484C;
            letter-spacing: 0.5px;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #0B484C;
            box-shadow: 0 0 0 4px rgba(11, 72, 76, 0.1);
            transform: translateY(-2px);
        }
        
        .clear-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            opacity: 0;
            transition: all 0.2s;
            padding: 8px;
            color: #9CA3AF;
        }
        
        .clear-btn.show {
            opacity: 1;
        }
        
        .clear-btn:hover {
            color: #DC2626;
            transform: translateY(-50%) scale(1.2);
        }
        
        .btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #0B484C 0%, #094044 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-secondary {
            background: #F3F4F6;
            color: #374151;
            margin-top: 12px;
        }
        
        .btn-secondary:hover {
            background: #E5E7EB;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(11, 72, 76, 0.4);
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
            padding: 16px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
            animation: slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert.show {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #DC2626;
        }
        
        .alert-error::before {
            content: '❌';
        }
        
        .alert-info {
            background: #DBEAFE;
            color: #1E40AF;
            border-left: 4px solid #2563EB;
        }
        
        .alert-info::before {
            content: 'ℹ️';
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid #10B981;
        }
        
        .alert-success::before {
            content: '✅';
        }
        
        .examples {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
            border-radius: 10px;
            font-size: 13px;
            color: #6B7280;
            border: 1px solid #E5E7EB;
        }
        
        .examples strong {
            color: #0B484C;
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .examples div {
            line-height: 2;
            padding-left: 8px;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 30px 20px;
        }
        
        .loading.active {
            display: block;
        }
        
        .spinner {
            border: 4px solid #F3F4F6;
            border-top: 4px solid #0B484C;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .help-text {
            font-size: 13px;
            color: #6B7280;
            margin-top: 10px;
            line-height: 1.6;
            padding-left: 4px;
        }
        
        .recent-searches {
            margin-top: 20px;
            padding: 18px;
            background: white;
            border-radius: 10px;
            border: 2px dashed #E5E7EB;
        }
        
        .recent-searches h4 {
            font-size: 13px;
            color: #6B7280;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .recent-item {
            padding: 10px 14px;
            background: #F9FAFB;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }
        
        .recent-item:hover {
            background: #0B484C;
            color: white;
            transform: translateX(4px);
        }
        
        .recent-item:last-child {
            margin-bottom: 0;
        }
        
        .recent-item time {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .invoice-preview {
            display: none;
            animation: slideDown 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .invoice-preview.show {
            display: block;
        }
        
        .preview-card {
            background: linear-gradient(135deg, #F9FAFB 0%, #FFFFFF 100%);
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }
        
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px dashed #E5E7EB;
        }
        
        .preview-number {
            font-size: 20px;
            font-weight: 700;
            color: #0B484C;
            margin-bottom: 4px;
        }
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: #FEF3C7;
            color: #92400E;
        }
        
        .status-paid {
            background: #D1FAE5;
            color: #065F46;
        }
        
        .status-overdue {
            background: #FEE2E2;
            color: #991B1B;
        }
        
        .preview-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #F3F4F6;
        }
        
        .preview-row:last-child {
            border-bottom: none;
            padding-top: 16px;
            margin-top: 8px;
            border-top: 2px solid #E5E7EB;
        }
        
        .preview-label {
            color: #6B7280;
            font-size: 14px;
        }
        
        .preview-value {
            font-weight: 600;
            color: #1F2937;
            font-size: 14px;
        }
        
        .preview-total {
            font-size: 24px;
            color: #0B484C;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #6B7280;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 16px;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .back-btn:hover {
            color: #0B484C;
            transform: translateX(-4px);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header .logo {
                font-size: 28px;
            }
            
            .icon {
                font-size: 40px;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .preview-header {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">Grid<span class="accent">Base</span></div>
                <div class="icon">🔍</div>
                <h1>Buscar Factura</h1>
                <p>Ingrese el número de su factura para proceder con el pago seguro</p>
            </div>
            
            <div class="content">
                <div id="alert-container"></div>
                
                <div id="search-section">
                    <form id="invoiceSearchForm">
                        <div class="form-group">
                            <label for="invoice_number">Número de Factura</label>
                            <div class="input-wrapper">
                                <input 
                                    type="text" 
                                    id="invoice_number" 
                                    name="invoice_number" 
                                    placeholder="Ej: INV-2026-001"
                                    required
                                    autocomplete="off"
                                    autofocus
                                />
                                <button type="button" class="clear-btn" id="clearBtn">✕</button>
                            </div>
                            <div class="help-text">
                                💡 Ingrese el código exacto como aparece en su factura
                            </div>
                        </div>
                        
                        <button type="submit" class="btn" id="searchBtn">
                            <span>🔎</span>
                            <span>Buscar Factura</span>
                        </button>
                    </form>
                    
                    <div class="examples">
                        <strong>📋 Formatos de búsqueda aceptados</strong>
                        <div>
                            • INV-2026-001<br>
                            • FACT-2026-001<br>
                            • 2026-001
                        </div>
                    </div>
                    
                    <div id="recent-searches" class="recent-searches" style="display: none;">
                        <h4>🕐 Búsquedas recientes</h4>
                        <div id="recent-list"></div>
                    </div>
                </div>
                
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p style="color: #6B7280; font-weight: 500;">Buscando factura...</p>
                </div>
                
                <div id="invoice-preview" class="invoice-preview">
                    <!-- Preview content will be injected here -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const form = document.getElementById('invoiceSearchForm');
        const searchBtn = document.getElementById('searchBtn');
        const clearBtn = document.getElementById('clearBtn');
        const loading = document.getElementById('loading');
        const searchSection = document.getElementById('search-section');
        const invoicePreview = document.getElementById('invoice-preview');
        const alertContainer = document.getElementById('alert-container');
        const invoiceInput = document.getElementById('invoice_number');
        const recentSearches = document.getElementById('recent-searches');
        const recentList = document.getElementById('recent-list');
        
        // Load recent searches on page load
        loadRecentSearches();
        
        // Clear button functionality
        invoiceInput.addEventListener('input', function() {
            clearBtn.classList.toggle('show', this.value.length > 0);
        });
        
        clearBtn.addEventListener('click', function() {
            invoiceInput.value = '';
            invoiceInput.focus();
            clearBtn.classList.remove('show');
        });
        
        // Auto-uppercase and format
        invoiceInput.addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
        
        // Form submit
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const invoiceNumber = invoiceInput.value.trim();
            
            if (!invoiceNumber) {
                showAlert('Por favor ingrese un número de factura válido', 'error');
                invoiceInput.focus();
                return;
            }
            
            // Show loading
            searchBtn.disabled = true;
            searchBtn.innerHTML = '<span>⏳</span><span>Buscando...</span>';
            loading.classList.add('active');
            searchSection.style.display = 'none';
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
                
                loading.classList.remove('active');
                
                if (data.success && data.payment_url) {
                    // Save to recent searches
                    saveRecentSearch(invoiceNumber);
                    
                    // Show success and redirect
                    showAlert('✅ ¡Factura encontrada! Redirigiendo al portal de pago...', 'success');
                    setTimeout(() => {
                        window.location.href = data.payment_url;
                    }, 1500);
                } else {
                    // Show error and restore form
                    showAlert(data.message || '❌ No se encontró una factura con ese número. Verifique e intente nuevamente.', 'error');
                    searchSection.style.display = 'block';
                    searchBtn.disabled = false;
                    searchBtn.innerHTML = '<span>🔎</span><span>Buscar Factura</span>';
                    invoiceInput.select();
                }
            } catch (error) {
                loading.classList.remove('active');
                showAlert('⚠️ Error de conexión. Por favor verifique su internet e intente nuevamente.', 'error');
                searchSection.style.display = 'block';
                searchBtn.disabled = false;
                searchBtn.innerHTML = '<span>🔎</span><span>Buscar Factura</span>';
            }
        });
        
        function showAlert(message, type) {
            const alertClass = type === 'error' ? 'alert-error' : (type === 'success' ? 'alert-success' : 'alert-info');
            alertContainer.innerHTML = `<div class="alert ${alertClass} show">${message}</div>`;
        }
        
        function saveRecentSearch(invoiceNumber) {
            let recent = JSON.parse(localStorage.getItem('recentSearches') || '[]');
            recent = recent.filter(item => item.number !== invoiceNumber);
            recent.unshift({
                number: invoiceNumber,
                timestamp: new Date().toISOString()
            });
            recent = recent.slice(0, 5); // Keep only last 5
            localStorage.setItem('recentSearches', JSON.stringify(recent));
        }
        
        function loadRecentSearches() {
            const recent = JSON.parse(localStorage.getItem('recentSearches') || '[]');
            if (recent.length > 0) {
                recentSearches.style.display = 'block';
                recentList.innerHTML = recent.map(item => {
                    const date = new Date(item.timestamp);
                    const timeAgo = getTimeAgo(date);
                    return `
                        <div class="recent-item" onclick="searchRecent('${item.number}')">
                            <span>${item.number}</span>
                            <time>${timeAgo}</time>
                        </div>
                    `;
                }).join('');
            }
        }
        
        function searchRecent(invoiceNumber) {
            invoiceInput.value = invoiceNumber;
            clearBtn.classList.add('show');
            form.dispatchEvent(new Event('submit'));
        }
        
        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            const intervals = {
                'año': 31536000,
                'mes': 2592000,
                'día': 86400,
                'hora': 3600,
                'minuto': 60
            };
            
            for (const [name, value] of Object.entries(intervals)) {
                const interval = Math.floor(seconds / value);
                if (interval >= 1) {
                    return `Hace ${interval} ${name}${interval > 1 ? 's' : ''}`;
                }
            }
            return 'Hace un momento';
        }
        
        // Focus on input on page load
        setTimeout(() => invoiceInput.focus(), 300);
    </script>
</body>
</html>
