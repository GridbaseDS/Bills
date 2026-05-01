<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago de Factura - Gridbase Bills</title>
    <link rel="icon" type="image/png" href="https://gridbase.com.do/wp-content/uploads/2026/03/cropped-imagen_2026-03-18_101800374-180x180.png">
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
            max-width: 700px;
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
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--border);
            z-index: 0;
        }
        
        .step {
            flex: 1;
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
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--primary);
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(11, 72, 76, 0.1);
        }
        
        .input-hint {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 6px;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(11, 72, 76, 0.2);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }
        
        .alert.show {
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #EF4444;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid #10B981;
        }
        
        .invoice-preview {
            display: none;
        }
        
        .invoice-preview.show {
            display: block;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 2px solid #F3F4F6;
        }
        
        .invoice-number-large {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .invoice-date {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 4px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #FEF3C7; color: #92400E; }
        .status-paid { background: #D1FAE5; color: #065F46; }
        .status-overdue { background: #FEE2E2; color: #991B1B; }
        
        .client-info {
            background: #F9FAFB;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .client-label {
            font-size: 12px;
            color: #9CA3AF;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        
        .client-name {
            font-size: 16px;
            font-weight: 600;
        }
        
        .section-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #F3F4F6;
        }
        
        .item:last-child {
            border-bottom: none;
        }
        
        .item-description {
            flex: 1;
            font-size: 14px;
        }
        
        .item-qty {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 4px;
        }
        
        .item-amount {
            font-size: 14px;
            font-weight: 600;
            margin-left: 20px;
        }
        
        .summary {
            border-top: 2px solid var(--border);
            padding-top: 16px;
            margin-top: 16px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .summary-label {
            color: var(--text-light);
        }
        
        .summary-value {
            font-weight: 600;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 16px;
            margin-top: 12px;
            border-top: 2px solid var(--primary);
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .payment-box {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            margin-top: 24px;
        }
        
        .payment-amount {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        
        .payment-total {
            font-size: 40px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .btn-pay {
            background: white;
            color: var(--primary);
            width: 100%;
            padding: 18px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .btn-back {
            display: inline-block;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 14px;
            margin-top: 16px;
            transition: all 0.2s;
        }
        
        .btn-back:hover {
            color: white;
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
            border: 4px solid #F3F4F6;
            border-top: 4px solid var(--primary);
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
        
        .secure-badge {
            text-align: center;
            padding: 16px;
            font-size: 13px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .card { padding: 24px 20px; }
            .steps { margin-bottom: 30px; }
            .invoice-header { flex-direction: column; gap: 12px; }
            .payment-total { font-size: 36px; }
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
            <div class="step active" id="step1">
                <div class="step-circle">1</div>
                <div class="step-label">Buscar</div>
            </div>
            <div class="step" id="step2">
                <div class="step-circle">2</div>
                <div class="step-label">Revisar</div>
            </div>
            <div class="step" id="step3">
                <div class="step-circle">3</div>
                <div class="step-label">Pagar</div>
            </div>
        </div>
        
        <div id="alert-container"></div>
        
        <!-- Step 1: Search Form -->
        <div id="search-form" class="card">
            <div class="card-title">🔍 Encuentra tu factura</div>
            <form id="invoiceSearchForm">
                <div class="form-group">
                    <label for="invoice_number">Número de Factura</label>
                    <input 
                        type="text" 
                        id="invoice_number" 
                        name="invoice_number" 
                        placeholder="INV-2026-001"
                        required
                        autofocus
                    />
                    <div class="input-hint">
                        Ingrese el número exacto como aparece en su factura
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" id="searchBtn">
                    <span>🔎</span>
                    <span>Buscar Factura</span>
                </button>
            </form>
        </div>
        
        <!-- Loading -->
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="color: var(--text-light); font-weight: 500;">Buscando factura...</p>
        </div>
        
        <!-- Step 2: Invoice Preview -->
        <div id="invoice-preview" class="invoice-preview">
            <div class="card">
                <div class="invoice-header">
                    <div>
                        <div class="invoice-number-large" id="preview-number"></div>
                        <div class="invoice-date" id="preview-date"></div>
                    </div>
                    <div class="status-badge" id="preview-status"></div>
                </div>
                
                <div class="client-info">
                    <div class="client-label">Facturado a</div>
                    <div class="client-name" id="preview-client"></div>
                </div>
                
                <div class="items-section">
                    <div class="section-title">Conceptos</div>
                    <div id="preview-items"></div>
                </div>
                
                <div class="summary">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value" id="preview-subtotal"></span>
                    </div>
                    <div class="summary-row" id="tax-row">
                        <span class="summary-label">Impuesto (<span id="preview-tax-rate"></span>%)</span>
                        <span class="summary-value" id="preview-tax"></span>
                    </div>
                    <div class="summary-row" id="paid-row" style="display: none;">
                        <span class="summary-label">Pagado</span>
                        <span class="summary-value" id="preview-paid"></span>
                    </div>
                    <div class="summary-total">
                        <span class="summary-label">A Pagar</span>
                        <span class="summary-value" id="preview-remaining"></span>
                    </div>
                </div>
                
                <div class="payment-box">
                    <div class="payment-amount">Total a pagar ahora</div>
                    <div class="payment-total" id="payment-total"></div>
                    <button class="btn-pay" id="proceedBtn">
                        <span>🔒</span>
                        <span>Proceder al Pago Seguro</span>
                    </button>
                    <a href="#" class="btn-back" id="backBtn">← Buscar otra factura</a>
                </div>
            </div>
        </div>
        
        <div class="secure-badge">
            🔒 Conexión segura · Pagos procesados por PayPal
        </div>
    </div>
    
    <script>
        const form = document.getElementById('invoiceSearchForm');
        const searchBtn = document.getElementById('searchBtn');
        const loading = document.getElementById('loading');
        const searchForm = document.getElementById('search-form');
        const invoicePreview = document.getElementById('invoice-preview');
        const alertContainer = document.getElementById('alert-container');
        const invoiceInput = document.getElementById('invoice_number');
        
        const step1 = document.getElementById('step1');
        const step2 = document.getElementById('step2');
        const step3 = document.getElementById('step3');
        
        let currentInvoiceUrl = '';
        
        // Auto-uppercase
        invoiceInput.addEventListener('input', function() {
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
            
            searchBtn.disabled = true;
            searchBtn.innerHTML = '<span>⏳</span><span>Buscando...</span>';
            loading.classList.add('active');
            searchForm.style.display = 'none';
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
                
                if (data.success && data.invoice) {
                    displayInvoice(data.invoice, data.payment_url);
                    step1.classList.add('completed');
                    step1.classList.remove('active');
                    step2.classList.add('active');
                } else {
                    showAlert(data.message || 'No se encontró una factura con ese número', 'error');
                    searchForm.style.display = 'block';
                    searchBtn.disabled = false;
                    searchBtn.innerHTML = '<span>🔎</span><span>Buscar Factura</span>';
                    invoiceInput.select();
                }
            } catch (error) {
                loading.classList.remove('active');
                showAlert('Error de conexión. Por favor intente nuevamente.', 'error');
                searchForm.style.display = 'block';
                searchBtn.disabled = false;
                searchBtn.innerHTML = '<span>🔎</span><span>Buscar Factura</span>';
            }
        });
        
        function displayInvoice(invoice, paymentUrl) {
            currentInvoiceUrl = paymentUrl;
            
            document.getElementById('preview-number').textContent = invoice.number;
            document.getElementById('preview-date').textContent = 'Fecha: ' + formatDate(invoice.date) + ' · Vence: ' + formatDate(invoice.due_date);
            document.getElementById('preview-client').textContent = invoice.client.name;
            
            const statusBadge = document.getElementById('preview-status');
            const statusMap = {
                pending: 'Pendiente',
                paid: 'Pagada',
                overdue: 'Vencida',
                partial: 'Pago Parcial'
            };
            statusBadge.textContent = statusMap[invoice.status] || invoice.status;
            statusBadge.className = 'status-badge status-' + invoice.status;
            
            const itemsHtml = invoice.items.map(item => `
                <div class="item">
                    <div>
                        <div class="item-description">${item.description}</div>
                        <div class="item-qty">${item.quantity} × ${formatCurrency(item.price, invoice.currency)}</div>
                    </div>
                    <div class="item-amount">${formatCurrency(item.total, invoice.currency)}</div>
                </div>
            `).join('');
            document.getElementById('preview-items').innerHTML = itemsHtml;
            
            document.getElementById('preview-subtotal').textContent = formatCurrency(invoice.subtotal, invoice.currency);
            document.getElementById('preview-tax-rate').textContent = invoice.tax_rate;
            document.getElementById('preview-tax').textContent = formatCurrency(invoice.tax, invoice.currency);
            
            if (invoice.amount_paid > 0) {
                document.getElementById('paid-row').style.display = 'flex';
                document.getElementById('preview-paid').textContent = '-' + formatCurrency(invoice.amount_paid, invoice.currency);
            }
            
            document.getElementById('preview-remaining').textContent = formatCurrency(invoice.remaining, invoice.currency);
            document.getElementById('payment-total').textContent = formatCurrency(invoice.remaining, invoice.currency);
            
            invoicePreview.classList.add('show');
        }
        
        document.getElementById('proceedBtn').addEventListener('click', function() {
            if (currentInvoiceUrl) {
                step2.classList.add('completed');
                step2.classList.remove('active');
                step3.classList.add('active');
                
                setTimeout(() => {
                    window.location.href = currentInvoiceUrl;
                }, 500);
            }
        });
        
        document.getElementById('backBtn').addEventListener('click', function(e) {
            e.preventDefault();
            invoicePreview.classList.remove('show');
            searchForm.style.display = 'block';
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<span>🔎</span><span>Buscar Factura</span>';
            invoiceInput.value = '';
            invoiceInput.focus();
            alertContainer.innerHTML = '';
            
            step1.classList.add('active');
            step1.classList.remove('completed');
            step2.classList.remove('active');
            step2.classList.remove('completed');
            step3.classList.remove('active');
        });
        
        function formatCurrency(amount, currency) {
            const symbols = { USD: '$', DOP: 'RD$', EUR: '€' };
            return (symbols[currency] || currency + ' ') + parseFloat(amount).toFixed(2);
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-DO', { year: 'numeric', month: 'short', day: 'numeric' });
        }
        
        function showAlert(message, type) {
            const alertClass = type === 'error' ? 'alert-error' : 'alert-success';
            alertContainer.innerHTML = `<div class="alert ${alertClass} show">${message}</div>`;
        }
        
        setTimeout(() => invoiceInput.focus(), 300);
    </script>
</body>
</html>
