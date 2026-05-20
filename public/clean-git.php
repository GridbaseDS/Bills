<?php
/**
 * GridBase Bills — Git Cleanup Tool for cPanel Deployment
 * This script runs diagnostic and cleanup commands inside the server's Git repository
 * to remove any uncommitted changes blocking cPanel's "Deploy HEAD Commit" button.
 */

// Basic authentication (optional: you can remove this or enter a simple passcode if needed)
$passcode = request_query('passcode') ?? '';

function request_query($key) {
    return isset($_GET[$key]) ? $_GET[$key] : null;
}

$repoPath = '/home/grupaqgl/repositories/Bills';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Git Cleanup Tool — GridBase Bills</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0B0F19;
            --bg-card: #151D30;
            --primary: #3B82F6;
            --primary-hover: #2563EB;
            --success: #10B981;
            --error: #EF4444;
            --text: #F3F4F6;
            --text-muted: #9CA3AF;
            --border: #24324F;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .container {
            width: 100%;
            max-width: 650px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .logo {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo img {
            max-height: 48px;
        }

        h1 {
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            margin: 0 0 8px 0;
        }

        .subtitle {
            color: var(--text-muted);
            text-align: center;
            font-size: 14px;
            margin-bottom: 32px;
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 24px;
            border: 1px solid transparent;
        }

        .alert-info {
            background-color: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.3);
            color: #93C5FD;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 24px;
        }

        .btn {
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .console {
            background: #070A13;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            color: #34D399;
            overflow-x: auto;
            max-height: 300px;
            white-space: pre-wrap;
            box-shadow: inset 0 4px 12px rgba(0,0,0,0.5);
        }

        .console-title {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        .footer {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="logo">
        <img src="https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-18_101800374-180x180.png" alt="GridBase">
    </div>
    <h1>Limpieza de Git en Servidor</h1>
    <div class="subtitle">Resuelve el bloqueo de despliegue en cPanel</div>

    <div class="alert alert-info">
        <strong>¿Por qué ocurre este bloqueo?</strong> cPanel desactiva el botón "Deploy HEAD Commit" si detecta archivos modificados directamente en el servidor (ej. logs, cachés o cambios manuales) que no están en el repositorio remoto. Esta herramienta limpia y restaura de forma segura el repositorio local del servidor para reactivar el botón.
    </div>

    <div class="actions">
        <a href="?action=status" class="btn btn-secondary">🔍 Ver Estado de Git</a>
        <a href="?action=clean" class="btn btn-primary" onclick="return confirm('¿Seguro que deseas limpiar todos los cambios locales en el servidor? Se descartarán archivos temporales no subidos.');">⚡ Limpiar y Desbloquear cPanel</a>
    </div>

    <?php
    $action = request_query('action');
    if ($action):
        echo '<div class="console-title">';
        echo '<span>Resultado del Comando</span>';
        echo '<span>Path: ' . htmlspecialchars($repoPath) . '</span>';
        echo '</div>';
        echo '<div class="console">';

        if (!is_dir($repoPath)) {
            echo "Error: No se encontró el repositorio Git en la ruta especificada:\n" . htmlspecialchars($repoPath);
        } else {
            if ($action === 'status') {
                echo "Ejecutando: git status\n\n";
                $output = shell_exec("cd " . escapeshellarg($repoPath) . " && git status 2>&1");
                echo htmlspecialchars($output);
            } elseif ($action === 'clean') {
                echo "1. Descartando cambios locales (git reset --hard HEAD)...\n";
                $reset = shell_exec("cd " . escapeshellarg($repoPath) . " && git reset --hard HEAD 2>&1");
                echo htmlspecialchars($reset) . "\n";

                echo "2. Eliminando archivos sin seguimiento (git clean -fd)...\n";
                $clean = shell_exec("cd " . escapeshellarg($repoPath) . " && git clean -fd 2>&1");
                echo htmlspecialchars($clean) . "\n";

                echo "3. Consultando estado final (git status)...\n";
                $status = shell_exec("cd " . escapeshellarg($repoPath) . " && git status 2>&1");
                echo htmlspecialchars($status);
                
                echo "\n\n✨ ¡LISTO! El repositorio en el servidor está 100% limpio.\n";
                echo "Por favor, regresa a la pestaña 'Pull or Deploy' en cPanel y recarga la página.\n";
                echo "El botón 'Deploy HEAD Commit' ya debería estar activo y funcional.";
            }
        }
        echo '</div>';
    endif;
    ?>

    <div class="footer">
        Desarrollado para GridBase Bills • Creado con ⚡ por Antigravity
    </div>
</div>

</body>
</html>
