<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Setting;
use App\Services\WhatsApp\EvolutionWhatsAppDriver;

$settings = Setting::getAll();
$driver = new EvolutionWhatsAppDriver($settings);

$action = $_GET['action'] ?? null;
$messageResult = null;
$resetResult = null;

// Handle Actions
if ($action === 'send_test' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $text = $_POST['message'] ?? 'Mensaje de prueba desde Gridbase Bills Debugger';
    if (!empty($phone)) {
        $messageResult = $driver->sendTextMessage($phone, $text);
    }
}

if ($action === 'force_reset') {
    $delete = $driver->deleteInstance();
    $create = $driver->createInstance();
    $resetResult = [
        'delete' => $delete,
        'create' => $create
    ];
}

// Fetch connection state
$state = $driver->getConnectionState();
// Fetch QR code if not open
$qrCodeData = null;
if (($state['state'] ?? '') !== 'open') {
    $qrCodeData = $driver->getQrCode();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Evolution API Debugger - Gridbase</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --border-color: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-color: #3b82f6;
            --accent-success: #10b981;
            --accent-danger: #ef4444;
            --accent-warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            padding: 40px 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 40px;
        }

        h1 {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 16px;
        }

        .card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .param-group {
            margin-bottom: 15px;
        }

        .param-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }

        .param-value {
            font-size: 15px;
            font-family: monospace;
            background-color: rgba(15, 23, 42, 0.6);
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            word-break: break-all;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--accent-success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-danger {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--accent-danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge-warning {
            background-color: rgba(245, 158, 11, 0.15);
            color: var(--accent-warning);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .btn {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-danger {
            background-color: var(--accent-danger);
        }

        .btn-secondary {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background-color: var(--border-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            color: white;
            font-family: inherit;
            font-size: 15px;
            transition: border-color 0.2s;
        }

        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .response-container {
            margin-top: 20px;
            background-color: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            font-size: 13px;
            max-height: 250px;
            overflow-y: auto;
            white-space: pre-wrap;
            color: #38bdf8;
        }

        .qr-wrapper {
            text-align: center;
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            display: inline-block;
            margin-top: 15px;
        }

        .qr-wrapper img {
            width: 200px;
            height: 200px;
            display: block;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--accent-success);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--accent-danger);
        }

        .actions-bar {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>WhatsApp Evolution API - Diagnóstico</h1>
            <p class="subtitle">Herramienta interna de depuración para Gridbase Bills</p>
        </header>

        <?php if ($resetResult): ?>
            <div class="alert alert-success">
                <strong>Instancia Reiniciada:</strong><br>
                Delete: <?php echo json_encode($resetResult['delete']); ?><br>
                Create: <?php echo json_encode($resetResult['create']); ?>
            </div>
        <?php endif; ?>

        <!-- Config Card -->
        <div class="card">
            <div class="card-title">Configuración Activa</div>
            <div class="grid">
                <div>
                    <div class="param-group">
                        <div class="param-label">Habilitado en App</div>
                        <div class="param-value"><?php echo $driver->isEnabled() ? 'SÍ' : 'NO'; ?></div>
                    </div>
                    <div class="param-group">
                        <div class="param-label">URL del API</div>
                        <div class="param-value"><?php echo htmlspecialchars($settings['evolution_api_url'] ?? 'No configurada'); ?></div>
                    </div>
                </div>
                <div>
                    <div class="param-group">
                        <div class="param-label">Nombre de Instancia</div>
                        <div class="param-value"><?php echo htmlspecialchars($settings['evolution_instance'] ?? 'No configurada'); ?></div>
                    </div>
                    <div class="param-group">
                        <div class="param-label">API Key (abreviada)</div>
                        <div class="param-value">
                            <?php 
                            $key = $settings['evolution_api_key'] ?? '';
                            echo htmlspecialchars(strlen($key) > 6 ? substr($key, 0, 4) . '...' . substr($key, -3) : 'No configurada'); 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection State Card -->
        <div class="card">
            <div class="card-title">
                <span>Estado de la Sesión</span>
                <?php
                $connState = $state['state'] ?? 'Desconocido';
                if ($connState === 'open') {
                    echo '<span class="badge badge-success">Conectado (Open)</span>';
                } elseif ($connState === 'connecting') {
                    echo '<span class="badge badge-warning">Conectando (QR Listo)</span>';
                } else {
                    echo '<span class="badge badge-danger">Desconectado (' . htmlspecialchars($connState) . ')</span>';
                }
                ?>
            </div>

            <div class="param-group">
                <div class="param-label">Respuesta del Estado de Conexión</div>
                <div class="response-container"><?php echo htmlspecialchars(json_encode($state, JSON_PRETTY_PRINT)); ?></div>
            </div>

            <?php if (($state['state'] ?? '') !== 'open' && isset($qrCodeData['qr_code'])): ?>
                <div style="text-align: center; margin-top: 20px;">
                    <h3>Código QR Generado Activo</h3>
                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 10px;">Escanea este código de inmediato con tu WhatsApp</p>
                    <div class="qr-wrapper">
                        <img src="<?php echo htmlspecialchars($qrCodeData['qr_code']); ?>" alt="QR Code">
                    </div>
                </div>
            <?php elseif (($state['state'] ?? '') !== 'open'): ?>
                <div class="alert alert-danger" style="margin-top: 20px;">
                    <strong>No se pudo obtener el QR:</strong> <?php echo htmlspecialchars(json_encode($qrCodeData ?? $state)); ?>
                </div>
            <?php endif; ?>

            <div class="actions-bar">
                <a href="debug_evolution.php" class="btn">Refrescar Estado</a>
                <a href="debug_evolution.php?action=force_reset" class="btn btn-danger" onclick="return confirm('¿Seguro que deseas forzar la eliminación y recreación de la instancia? Esto invalidará el código actual.');">Forzar Recreación de Instancia</a>
            </div>
        </div>

        <!-- Message Sending Card -->
        <div class="card">
            <div class="card-title">Enviar Mensaje de Prueba</div>
            <form action="debug_evolution.php?action=send_test" method="POST">
                <div class="form-group">
                    <label for="phone">Teléfono Destinatario (con código de país, ej: 18091234567)</label>
                    <input type="text" id="phone" name="phone" placeholder="18091234567" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="message">Mensaje</label>
                    <textarea id="message" name="message" rows="3" required><?php echo htmlspecialchars($_POST['message'] ?? 'Mensaje de prueba desde Gridbase Bills Debugger'); ?></textarea>
                </div>
                <button type="submit" class="btn">Enviar Mensaje</button>
            </form>

            <?php if ($messageResult): ?>
                <div style="margin-top: 20px;">
                    <div class="param-label">Respuesta del Servidor al Enviar</div>
                    <div class="response-container"><?php echo htmlspecialchars(json_encode($messageResult, JSON_PRETTY_PRINT)); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
