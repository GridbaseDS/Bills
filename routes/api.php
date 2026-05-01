<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\RecurringController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\PaymentLinkController;

// Public Auth
Route::post('/auth/login', [AuthController::class, 'login']);

// Lookups (Public or Protected, placing them here as public, but could be protected)
Route::get('/lookup/rnc/{rnc}', [LookupController::class, 'rnc']);
Route::get('/lookup/cedula/{cedula}', [LookupController::class, 'cedula']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/session', function (Request $request) {
        return ['authenticated' => true, 'user' => $request->user()];
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'pdf']);
    Route::post('/invoices/{id}/payment', [InvoiceController::class, 'addPayment']);
    Route::post('/invoices/{id}/send-email', [InvoiceController::class, 'sendEmail']);
    Route::post('/invoices/{id}/duplicate', [InvoiceController::class, 'duplicate']);

    // Quotes
    Route::apiResource('quotes', QuoteController::class);
    Route::get('/quotes/{id}/pdf', [QuoteController::class, 'pdf']);
    Route::post('/quotes/{id}/convert', [QuoteController::class, 'convertToInvoice']);
    Route::post('/quotes/{id}/send-email', [QuoteController::class, 'sendEmail']);
    Route::post('/quotes/{id}/duplicate', [QuoteController::class, 'duplicate']);

    // Recurring Invoices
    Route::apiResource('recurring', RecurringController::class);
    Route::post('/recurring/{id}/toggle', [RecurringController::class, 'toggleStatus']);

    // Clients
    Route::apiResource('clients', ClientController::class);
    Route::get('/clients/{id}/profile', [ClientController::class, 'profile']);

    // Payment Links
    Route::prefix('invoices/{id}/payment-link')->group(function () {
        Route::post('/generate', [PaymentLinkController::class, 'generate']);
        Route::post('/send-email', [PaymentLinkController::class, 'sendEmail']);
        Route::post('/send-whatsapp', [PaymentLinkController::class, 'sendWhatsApp']);
        Route::post('/send-both', [PaymentLinkController::class, 'sendBoth']);
        Route::post('/regenerate', [PaymentLinkController::class, 'regenerate']);
        Route::get('/check', [PaymentLinkController::class, 'checkValidity']);
        Route::get('/info', [PaymentLinkController::class, 'getPaymentInfo']);
    });

    // Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'updateMultiple']);
    Route::post('/settings/test-smtp', [SettingController::class, 'testSmtp']);
    Route::get('/settings/diagnose-smtp', [SettingController::class, 'diagnoseSmtp']);
});
