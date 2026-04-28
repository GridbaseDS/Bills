<?php
/**
 * Gridbase Digital Solutions - API Router
 * Routes all API requests to the appropriate controller.
 */

// Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Set timezone
$appConfig = require __DIR__ . '/../config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'America/Santo_Domingo');

// CORS headers (for subdomain setup)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse the route
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/';
$pos = strpos($requestUri, $basePath);
$route = ($pos !== false) ? substr($requestUri, $pos + strlen($basePath)) : '';
$route = strtok($route, '?'); // Remove query string
$route = trim($route, '/');

$method = $_SERVER['REQUEST_METHOD'];
$segments = $route ? explode('/', $route) : [];
$resource = $segments[0] ?? '';
$id = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : null;
$action = $segments[2] ?? ($segments[1] ?? null);
if (is_numeric($action)) $action = $segments[2] ?? null;

// Get JSON body for POST/PUT
$input = [];
if (in_array($method, ['POST', 'PUT'])) {
    $rawBody = file_get_contents('php://input');
    $input = json_decode($rawBody, true) ?? [];
}

// Route map
try {
    switch ($resource) {
        case 'auth':
            $controller = new \App\Controllers\AuthController();
            match ($action) {
                'login'   => $controller->login($input),
                'logout'  => $controller->logout(),
                'session' => $controller->session(),
                default   => notFound(),
            };
            break;

        case 'dashboard':
            \App\Middleware\AuthMiddleware::authenticate();
            $controller = new \App\Controllers\DashboardController();
            match ($action) {
                null, '' , 'stats' => $controller->stats(),
                'chart'            => $controller->chart($_GET['type'] ?? 'revenue'),
                'activity'         => $controller->activity(),
                default            => notFound(),
            };
            break;

        case 'invoices':
            \App\Middleware\AuthMiddleware::authenticate();
            $controller = new \App\Controllers\InvoiceController();
            if ($method === 'GET' && !$id) { $controller->index($_GET); break; }
            if ($method === 'GET' && $id && !$action) { $controller->show($id); break; }
            if ($method === 'POST' && !$id) { $controller->store($input); break; }
            if ($method === 'PUT' && $id) { $controller->update($id, $input); break; }
            if ($method === 'DELETE' && $id) { $controller->destroy($id); break; }
            if ($method === 'POST' && $id && $action === 'send-email') { $controller->sendEmail($id); break; }
            if ($method === 'POST' && $id && $action === 'send-whatsapp') { $controller->sendWhatsApp($id); break; }
            if ($method === 'GET' && $id && $action === 'pdf') { $controller->pdf($id, $_GET['download'] ?? '0'); break; }
            if ($method === 'POST' && $id && $action === 'payment') { $controller->addPayment($id, $input); break; }
            if ($method === 'PUT' && $id && $action === 'status') { $controller->updateStatus($id, $input); break; }
            notFound();
            break;

        case 'quotes':
            \App\Middleware\AuthMiddleware::authenticate();
            $controller = new \App\Controllers\QuoteController();
            if ($method === 'GET' && !$id) { $controller->index($_GET); break; }
            if ($method === 'GET' && $id && !$action) { $controller->show($id); break; }
            if ($method === 'POST' && !$id) { $controller->store($input); break; }
            if ($method === 'PUT' && $id && !$action) { $controller->update($id, $input); break; }
            if ($method === 'DELETE' && $id) { $controller->destroy($id); break; }
            if ($method === 'POST' && $id && $action === 'send-email') { $controller->sendEmail($id); break; }
            if ($method === 'POST' && $id && $action === 'send-whatsapp') { $controller->sendWhatsApp($id); break; }
            if ($method === 'POST' && $id && $action === 'convert') { $controller->convert($id); break; }
            if ($method === 'GET' && $id && $action === 'pdf') { $controller->pdf($id); break; }
            notFound();
            break;

        case 'clients':
            \App\Middleware\AuthMiddleware::authenticate();
            $controller = new \App\Controllers\ClientController();
            if ($method === 'GET' && !$id && ($action ?? '') === 'select') { $controller->selectList(); break; }
            if ($method === 'GET' && !$id) { $controller->index($_GET); break; }
            if ($method === 'GET' && $id) { $controller->show($id); break; }
            if ($method === 'POST' && !$id) { $controller->store($input); break; }
            if ($method === 'PUT' && $id) { $controller->update($id, $input); break; }
            if ($method === 'DELETE' && $id) { $controller->destroy($id); break; }
            notFound();
            break;

        case 'recurring':
            \App\Middleware\AuthMiddleware::authenticate();
            $controller = new \App\Controllers\RecurringController();
            if ($method === 'GET' && !$id) { $controller->index($_GET); break; }
            if ($method === 'GET' && $id) { $controller->show($id); break; }
            if ($method === 'POST' && !$id) { $controller->store($input); break; }
            if ($method === 'PUT' && $id && !$action) { $controller->update($id, $input); break; }
            if ($method === 'PUT' && $id && $action === 'toggle') { $controller->toggle($id, $input); break; }
            if ($method === 'DELETE' && $id) { $controller->destroy($id); break; }
            notFound();
            break;

        case 'settings':
            \App\Middleware\AuthMiddleware::authenticate();
            $controller = new \App\Controllers\SettingsController();
            if ($method === 'GET') { $controller->index(); break; }
            if ($method === 'PUT' || $method === 'POST') { $controller->update($input); break; }
            notFound();
            break;

        default:
            notFound();
    }
} catch (\PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (\Exception $e) {
    error_log('Application error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $appConfig['debug'] ? $e->getMessage() : 'Internal server error']);
}

function notFound(): void
{
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
    exit;
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
