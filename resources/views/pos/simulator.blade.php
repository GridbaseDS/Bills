<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador SmartPOS — Gridbase Bills</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0A0A0A;
            --bg-card: #171717;
            --bg-inner: #1C1C1E;
            --primary: #FAFAFA;
            --secondary: #A1A1AA;
            --green: #10B981;
            --red: #EF4444;
            --border: #262626;
            --glow-green: rgba(16, 185, 129, 0.15);
            --glow-red: rgba(239, 68, 68, 0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        /* Verifone Terminal Body Wrapper */
        .terminal-container {
            width: 100%;
            max-width: 380px;
            background: linear-gradient(145deg, #1C1C1E, #121214);
            border: 2px solid #2C2C2E;
            border-radius: 40px;
            padding: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-bottom: 8px solid #0F0F10;
        }

        /* TPV Contactless Icon Area */
        .contactless-area {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            gap: 6px;
            color: var(--secondary);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 600;
        }

        .contactless-waves {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .wave {
            width: 3px;
            height: 12px;
            background-color: var(--secondary);
            border-radius: 2px;
            animation: wave-pulse 1.6s infinite ease-in-out;
        }

        .wave:nth-child(2) { height: 16px; animation-delay: 0.2s; }
        .wave:nth-child(3) { height: 20px; animation-delay: 0.4s; }
        .wave:nth-child(4) { height: 24px; animation-delay: 0.6s; }

        @keyframes wave-pulse {
            0%, 100% { opacity: 0.3; transform: scaleY(0.8); }
            50% { opacity: 1; transform: scaleY(1.1); color: var(--green); background-color: var(--green); }
        }

        /* Screen Wrapper */
        .terminal-screen {
            width: 100%;
            background-color: #000000;
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.8);
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .screen-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #555;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .screen-logo {
            font-weight: 700;
            color: #FAFAFA;
            letter-spacing: 0.05em;
            font-size: 12px;
        }

        .screen-status-badge {
            background-color: rgba(255, 255, 255, 0.05);
            padding: 2px 8px;
            border-radius: 10px;
            color: var(--secondary);
        }

        .amount-display {
            font-size: 34px;
            font-weight: 700;
            color: #FFFFFF;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
            display: flex;
            align-items: baseline;
        }

        .currency {
            font-size: 16px;
            color: var(--secondary);
            margin-left: 4px;
            font-weight: 500;
        }

        .invoice-details {
            font-size: 12px;
            color: var(--secondary);
            text-align: center;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 8px 16px;
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.02);
            line-height: 1.6;
        }

        .invoice-details strong {
            color: var(--primary);
        }

        /* Virtual Card Tapping Simulator */
        .card-container {
            width: 100%;
            height: 100px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin-bottom: 10px;
        }

        .virtual-card {
            width: 140px;
            height: 85px;
            background: linear-gradient(135deg, #2A2A2E 0%, #1A1A1C 100%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            position: relative;
            box-shadow: 0 8px 20px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 10px;
            cursor: pointer;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: card-hover-loop 3s infinite ease-in-out;
        }

        .virtual-card:active {
            transform: scale(0.92) translateY(5px);
        }

        @keyframes card-hover-loop {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(-1deg); }
        }

        .card-chip {
            width: 18px;
            height: 14px;
            background-color: #E2B755;
            border-radius: 2px;
        }

        .card-logo {
            align-self: flex-end;
            font-size: 8px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.3);
            text-transform: uppercase;
        }

        .card-no {
            font-size: 9px;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 0.1em;
        }

        /* Status screen message overlay */
        .terminal-status-screen {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #000000;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            padding: 20px;
            text-align: center;
            animation: fade-in 0.3s ease-out;
        }

        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .status-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .status-icon.approved {
            background-color: var(--glow-green);
            color: var(--green);
            border: 2px solid var(--green);
            box-shadow: 0 0 20px var(--glow-green);
        }

        .status-icon.declined {
            background-color: var(--glow-red);
            color: var(--red);
            border: 2px solid var(--red);
            box-shadow: 0 0 20px var(--glow-red);
        }

        .status-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .status-desc {
            font-size: 12px;
            color: var(--secondary);
            line-height: 1.4;
        }

        /* Physical style keypads */
        .terminal-actions {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-tpv {
            width: 100%;
            height: 48px;
            border-radius: 14px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform var(--transition-fast) ease, filter 0.2s ease;
        }

        .btn-tpv:active {
            transform: scale(0.98);
        }

        .btn-approve {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
        }

        .btn-approve:hover {
            filter: brightness(1.1);
        }

        .btn-decline {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3);
        }

        .btn-decline:hover {
            filter: brightness(1.1);
        }

        .merchant-footer {
            margin-top: 24px;
            font-size: 11px;
            color: #555;
            text-align: center;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div class="terminal-container">
        
        <!-- Contactless Indicator -->
        <div class="contactless-area">
            <span>Contactless / Chip</span>
            <div class="contactless-waves">
                <div class="wave"></div>
                <div class="wave"></div>
                <div class="wave"></div>
                <div class="wave"></div>
            </div>
        </div>

        <!-- Smart Screen -->
        <div class="terminal-screen">
            
            <div class="screen-header">
                <span class="screen-logo">GRIDBASE TPV</span>
                <span class="screen-status-badge">ONLINE</span>
            </div>

            <!-- Amount -->
            <div class="amount-display">
                <span>${{ number_format($invoice->total, 2) }}</span>
                <span class="currency">DOP</span>
            </div>

            <!-- Details -->
            <div class="invoice-details">
                Factura: <strong>{{ $invoice->invoice_number }}</strong><br>
                Cliente: <strong>{{ $invoice->client->company_name }}</strong>
            </div>

            <!-- Interactive card mockup -->
            <div class="card-container">
                <div class="virtual-card" id="virtual-card">
                    <div class="card-chip"></div>
                    <span class="card-logo">Gridbase Pay</span>
                    <span class="card-no">•••• •••• •••• 1040</span>
                </div>
            </div>

            <!-- Overlay for transaction completion status -->
            <div class="terminal-status-screen" id="status-screen">
                <div class="status-icon" id="status-icon-div">
                    <!-- Icon replaced dynamically -->
                    <span id="status-svg-icon"></span>
                </div>
                <div class="status-title" id="status-text-title">Aprobado</div>
                <div class="status-desc" id="status-text-desc">Código de Autorización: 987654</div>
            </div>

        </div>

        <!-- Buttons simulating input/cancellation -->
        <div class="terminal-actions">
            <button class="btn-tpv btn-approve" id="btn-simulate-approve">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Aprobar Transacción
            </button>
            <button class="btn-tpv btn-decline" id="btn-simulate-decline">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                Declinar Transacción
            </button>
        </div>

        <div class="merchant-footer">
            COMERCIO: {{ \App\Models\Setting::get('company_name', 'GRIDBASE DIGITAL SOLUTIONS') }}
        </div>

    </div>

    <script>
        const invoiceId = "{{ $invoice->id }}";
        const cardEl = document.getElementById('virtual-card');
        const statusScreen = document.getElementById('status-screen');
        const statusIconDiv = document.getElementById('status-icon-div');
        const statusSvgIcon = document.getElementById('status-svg-icon');
        const statusTextTitle = document.getElementById('status-text-title');
        const statusTextDesc = document.getElementById('status-text-desc');

        // Helper to update status on backend
        async function submitStatus(status) {
            let authCode = '';
            let cardNo = '489952******1040';
            let cardType = 'VISA';
            let message = '';

            if (status === 'approved') {
                authCode = Math.floor(100000 + Math.random() * 900000).toString();
                message = 'APROBADA';
            } else {
                authCode = '000000';
                message = 'DECLINADA - FONDOS INSUFICIENTES';
            }

            try {
                const response = await fetch('/api/pos/update-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        invoice_id: invoiceId,
                        status: status,
                        auth_code: authCode,
                        card_number: cardNo,
                        card_type: cardType,
                        message: message
                    })
                });

                const resData = await response.json();
                if (resData.success) {
                    showStatusUI(status, authCode, message);
                } else {
                    alert('Error actualizando estado: ' + resData.message);
                }
            } catch(err) {
                alert('Fallo de red: ' + err.message);
            }
        }

        // Render status on terminal screen
        function showStatusUI(status, authCode, message) {
            statusScreen.style.display = 'flex';
            statusIconDiv.className = 'status-icon ' + status;

            if (status === 'approved') {
                statusSvgIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
                statusTextTitle.textContent = 'Transacción Aprobada';
                statusTextDesc.innerHTML = `AUT: <strong>${authCode}</strong><br>Tarjeta: VISA 1040<br><span style="color:var(--green);font-size:11px;font-weight:600;">PAGO COMPLETADO</span>`;
            } else {
                statusSvgIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`;
                statusTextTitle.textContent = 'Transacción Declinada';
                statusTextDesc.innerHTML = `<span style="color:var(--red);">${message}</span><br>Favor use otra tarjeta`;
            }
        }

        // Bind clicks
        document.getElementById('btn-simulate-approve').addEventListener('click', () => {
            // Animate card tapping
            cardEl.style.transform = 'translateY(-120px) scale(0.6)';
            setTimeout(() => submitStatus('approved'), 600);
        });

        document.getElementById('btn-simulate-decline').addEventListener('click', () => {
            cardEl.style.transform = 'translateY(-120px) scale(0.6)';
            setTimeout(() => submitStatus('declined'), 600);
        });

        cardEl.addEventListener('click', () => {
            // Tap shortcut
            cardEl.style.transform = 'translateY(-120px) scale(0.6)';
            setTimeout(() => submitStatus('approved'), 600);
        });
    </script>
</body>
</html>
