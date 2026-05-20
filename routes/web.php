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
Route::post('/fe/recepcion/api/ecf', [DgiiWebhookController::class, 'recepcion']);
Route::post('/fe/aprobacioncomercial/api/ecf', [DgiiWebhookController::class, 'aprobacionComercial']);
Route::get('/fe/autenticacion/api/semilla', [DgiiWebhookController::class, 'semilla']);
Route::post('/fe/autenticacion/api/validacioncertificado', [DgiiWebhookController::class, 'validacionCertificado']);

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
