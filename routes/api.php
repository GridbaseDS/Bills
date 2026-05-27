<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\RecurringController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\PaymentLinkController;
use App\Http\Controllers\Api\DgiiTestUIController;
use App\Http\Controllers\Api\ReceivedInvoiceController;
use App\Http\Controllers\WhatsAppWebhookController;

// Public Auth
Route::post('/auth/login', [AuthController::class, 'login']);

// Lookups (Public or Protected, placing them here as public, but could be protected)
Route::get('/lookup/rnc/{rnc}', [LookupController::class, 'rnc']);
Route::get('/lookup/cedula/{cedula}', [LookupController::class, 'cedula']);

// WhatsApp Webhooks (must be public for Meta to access)
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'webhook']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/session', function (Request $request) {
        return ['authenticated' => true, 'user' => $request->user()];
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Invoices
    Route::post('/invoices/bulk', [InvoiceController::class, 'bulkAction']);
    Route::apiResource('invoices', InvoiceController::class);
    Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'pdf']);
    Route::post('/invoices/{id}/payment', [InvoiceController::class, 'addPayment']);
    Route::post('/invoices/{id}/send-email', [InvoiceController::class, 'sendEmail']);
    Route::post('/invoices/{id}/duplicate', [InvoiceController::class, 'duplicate']);
    Route::post('/invoices/{id}/process-ecf', [InvoiceController::class, 'processEcf']);
    Route::get('/invoices/{id}/ecf-status', [InvoiceController::class, 'checkEcfStatus']);
    Route::get('/invoices/{id}/download-xml', [InvoiceController::class, 'downloadXml']);

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

    // Items
    Route::apiResource('items', ItemController::class);

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

    // DGII Tests
    Route::post('/dgii/run-tests', [DgiiTestUIController::class, 'runTests']);
    Route::post('/dgii/diagnose', [DgiiTestUIController::class, 'diagnose']);
    Route::post('/dgii/run-aprobaciones', [DgiiTestUIController::class, 'runAprobaciones']);

    // Received Invoices (Aprobaciones Comerciales)
    Route::get('/received-invoices', [ReceivedInvoiceController::class, 'index']);
    Route::get('/received-invoices/summary', [ReceivedInvoiceController::class, 'summary']);
    Route::get('/received-invoices/{id}', [ReceivedInvoiceController::class, 'show']);
    Route::post('/received-invoices/{id}/approve', [ReceivedInvoiceController::class, 'approve']);
    Route::post('/received-invoices/{id}/reject', [ReceivedInvoiceController::class, 'reject']);
});
