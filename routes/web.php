<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\WhatsAppTestController;

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

// Settings routes (protected by auth middleware)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/paypal', [SettingsController::class, 'updatePayPal'])->name('settings.paypal.update');
    Route::post('/settings/paypal/test', [SettingsController::class, 'testPayPalConnection'])->name('settings.paypal.test');
});

// WhatsApp Test routes (public for testing, consider adding auth in production)
Route::get('/whatsapp-test', [WhatsAppTestController::class, 'index'])->name('whatsapp.test');
Route::post('/whatsapp-test/send', [WhatsAppTestController::class, 'sendTest'])->name('whatsapp.test.send');
Route::post('/whatsapp-test/invoice', [WhatsAppTestController::class, 'testInvoice'])->name('whatsapp.test.invoice');
Route::post('/whatsapp-test/quote', [WhatsAppTestController::class, 'testQuote'])->name('whatsapp.test.quote');
Route::get('/whatsapp-test/status', [WhatsAppTestController::class, 'status'])->name('whatsapp.test.status');

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
