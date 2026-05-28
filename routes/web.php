<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\WhatsAppTestController;
use App\Http\Controllers\Api\DgiiWebhookController;

// Public invoice search and payment routes
Route::get('/buscar-factura', [PaymentController::class, 'searchPage'])->name('payment.search');
Route::post('/buscar-factura', [PaymentController::class, 'searchInvoice'])->name('payment.search.submit');

// Public payment routes
Route::get('/pay/{token}', [PaymentController::class, 'show'])->name('payment.show');
Route::post('/pay/{token}/create-order', [PaymentController::class, 'createOrder'])->name('payment.create-order');
Route::post('/pay/{token}/capture-order', [PaymentController::class, 'captureOrder'])->name('payment.capture-order');

// Diagnostics routes (public for troubleshooting)
Route::get('/diagnostics', [DiagnosticsController::class, 'index'])->name('diagnostics.index');
Route::post('/diagnostics/test-order', [DiagnosticsController::class, 'testOrderCreation'])->name('diagnostics.test-order');
Route::get('/diagnostics/problematic-payments', [DiagnosticsController::class, 'listProblematicPayments'])->name('diagnostics.problematic-payments');
Route::post('/diagnostics/fix-payment', [DiagnosticsController::class, 'fixPayment'])->name('diagnostics.fix-payment');

// The /settings routes are now fully managed dynamically by the SPA and api.php.
// We remove the conflicting legacy /settings web routes so the SPA catch-all can render the frontend settings view correctly.

// WhatsApp Test routes (public for testing, consider adding auth in production)
Route::get('/whatsapp-test', [WhatsAppTestController::class, 'index'])->name('whatsapp.test');
Route::post('/whatsapp-test/send', [WhatsAppTestController::class, 'sendTest'])->name('whatsapp.test.send');
Route::post('/whatsapp-test/invoice', [WhatsAppTestController::class, 'testInvoice'])->name('whatsapp.test.invoice');
Route::post('/whatsapp-test/quote', [WhatsAppTestController::class, 'testQuote'])->name('whatsapp.test.quote');
Route::get('/whatsapp-test/status', [WhatsAppTestController::class, 'status'])->name('whatsapp.test.status');

// DGII Electronic Invoicing Webhook Reception Endpoints (For Certification & Interchange)
// Per DGII spec: "Servicios no sensitivos a mayúsculas y minúsculas" — accept any method
Route::any('/fe/recepcion/api/ecf', [DgiiWebhookController::class, 'recepcion']);
Route::any('/fe/aprobacioncomercial/api/ecf', [DgiiWebhookController::class, 'aprobacionComercial']);
Route::any('/fe/autenticacion/api/semilla', [DgiiWebhookController::class, 'semilla']);
Route::any('/fe/autenticacion/api/Semilla', [DgiiWebhookController::class, 'semilla']);
Route::any('/fe/autenticacion/api/validacioncertificado', [DgiiWebhookController::class, 'validacionCertificado']);
Route::any('/fe/autenticacion/api/ValidacionCertificado', [DgiiWebhookController::class, 'validacionCertificado']);

// Catch-all for any /fe/* URL we might be missing
Route::any('/fe/{path}', function (\Illuminate\Http\Request $request, $path) {
    \Illuminate\Support\Facades\Log::warning("DGII Webhook CATCH-ALL: {$request->method()} /fe/{$path}");
    \Illuminate\Support\Facades\Log::warning("DGII Webhook CATCH-ALL Headers: " . json_encode($request->headers->all()));
    // Try to route to semilla or validacioncertificado based on path
    if (stripos($path, 'semilla') !== false) {
        return app()->call([app(DgiiWebhookController::class), 'semilla']);
    }
    if (stripos($path, 'validacion') !== false) {
        return app()->call([app(DgiiWebhookController::class), 'validacionCertificado'], ['request' => $request]);
    }
    return response()->json(['error' => 'Endpoint not found', 'path' => "/fe/{$path}"], 404);
})->where('path', '.*');

// FC<250k file downloads for DGII certification portal upload
Route::get('/dgii-fc250k', function () {
    $dir = storage_path('app/dgii_tests/fc_250k_upload');
    if (!is_dir($dir)) return 'No hay archivos. Ejecuta las pruebas primero.';
    $files = glob("$dir/*.xml");
    if (empty($files)) return 'No hay archivos. Ejecuta las pruebas primero.';
    $html = '<h2>Archivos FC&lt;250k para subir al portal DGII</h2><ul>';
    foreach ($files as $f) {
        $name = basename($f);
        $html .= "<li><a href='/dgii-fc250k/$name' download>📥 $name</a></li>";
    }
    $html .= '</ul><p>Descarga todos y súbelos al portal DGII → "Facturas de consumo &lt; 250Mil"</p>';
    return $html;
});
Route::get('/dgii-fc250k/{filename}', function ($filename) {
    $path = storage_path("app/dgii_tests/fc_250k_upload/$filename");
    if (!file_exists($path)) abort(404);
    return response()->download($path);
});

// API Documentation (public)
Route::get('/api-docs', function () {
    return view('api-docs');
})->name('api.docs');

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
