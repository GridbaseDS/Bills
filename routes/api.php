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
use App\Http\Controllers\Api\DgiiReportController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\External\ExternalInvoiceController;
use App\Http\Controllers\Api\External\ExternalQuoteController;
use App\Http\Controllers\Api\External\ExternalClientController;
use App\Http\Controllers\Api\CertificationController;
use App\Http\Controllers\Api\DgiiLogController;

// Public Auth
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/verify-2fa', [AuthController::class, 'verify2fa']);
Route::post('/auth/pin-login', [AuthController::class, 'pinLogin']);


// Lookups (Public or Protected, placing them here as public, but could be protected)
Route::get('/lookup/rnc/{rnc}', [LookupController::class, 'rnc']);
Route::get('/lookup/cedula/{cedula}', [LookupController::class, 'cedula']);

// WhatsApp Webhooks (must be public for Meta to access)
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'webhook']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/setup-pin', [AuthController::class, 'setupPin']);
    Route::get('/auth/session', function (Request $request) {
        return ['authenticated' => true, 'user' => $request->user()];
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Invoices
    Route::get('/invoices/export/csv', [InvoiceController::class, 'exportCsv']);
    Route::post('/invoices/bulk', [InvoiceController::class, 'bulkAction']);
    Route::apiResource('invoices', InvoiceController::class);
    Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'pdf']);
    Route::post('/invoices/{id}/payment', [InvoiceController::class, 'addPayment']);
    Route::post('/invoices/{id}/cancel', [InvoiceController::class, 'cancel']);
    Route::post('/invoices/{id}/send-email', [InvoiceController::class, 'sendEmail']);
    Route::post('/invoices/{id}/duplicate', [InvoiceController::class, 'duplicate']);
    Route::post('/invoices/{id}/process-ecf', [InvoiceController::class, 'processEcf']);
    Route::get('/invoices/{id}/ecf-status', [InvoiceController::class, 'checkEcfStatus']);
    Route::get('/invoices/{id}/download-xml', [InvoiceController::class, 'downloadXml']);

    // Quotes
    Route::get('/quotes/export/csv', [QuoteController::class, 'exportCsv']);
    Route::apiResource('quotes', QuoteController::class);
    Route::get('/quotes/{id}/pdf', [QuoteController::class, 'pdf']);
    Route::post('/quotes/{id}/convert', [QuoteController::class, 'convertToInvoice']);
    Route::post('/quotes/{id}/send-email', [QuoteController::class, 'sendEmail']);
    Route::post('/quotes/{id}/duplicate', [QuoteController::class, 'duplicate']);

    // Recurring Invoices
    Route::apiResource('recurring', RecurringController::class);
    Route::post('/recurring/{id}/toggle', [RecurringController::class, 'toggleStatus']);
    Route::post('/recurring/{id}/generate-now', [RecurringController::class, 'generateNow']);

    // Clients
    Route::apiResource('clients', ClientController::class);
    Route::get('/clients/{id}/profile', [ClientController::class, 'profile']);

    // Items
    Route::apiResource('items', ItemController::class);

    // Expenses (Gastos - Admin and Contador only)
    Route::apiResource('expenses', ExpenseController::class)->middleware('role:admin,contador');

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

    // Currency Exchange
    Route::get('/currency/rates', [\App\Http\Controllers\Api\CurrencyController::class, 'getRates']);

    // Settings
    Route::get('/settings', [SettingController::class, 'index']); // Read allowed for all (e.g. for general UI details)
    Route::post('/settings', [SettingController::class, 'updateMultiple'])->middleware('role:admin');
    Route::post('/settings/test-smtp', [SettingController::class, 'testSmtp'])->middleware('role:admin');
    Route::get('/settings/diagnose-smtp', [SettingController::class, 'diagnoseSmtp'])->middleware('role:admin');
    Route::post('/settings/reset-database', [SettingController::class, 'resetDatabase'])->middleware('role:admin');
    Route::post('/settings/upload-certificate', [SettingController::class, 'uploadCertificate'])->middleware('role:admin');
    Route::post('/settings/whatsapp-test', [SettingController::class, 'testWhatsapp'])->middleware('role:admin');
    Route::get('/settings/evolution-status', [SettingController::class, 'getEvolutionStatus'])->middleware('role:admin');
    Route::get('/settings/evolution-qr', [SettingController::class, 'getEvolutionQr'])->middleware('role:admin');
    Route::post('/settings/evolution-pairing-code', [SettingController::class, 'getEvolutionPairingCode'])->middleware('role:admin');

    // DGII Tests & User Management
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    Route::middleware('role:admin,contador')->group(function () {
        Route::post('/dgii/run-tests', [DgiiTestUIController::class, 'runTests']);
        Route::post('/dgii/diagnose', [DgiiTestUIController::class, 'diagnose']);
        Route::post('/dgii/run-aprobaciones', [DgiiTestUIController::class, 'runAprobaciones']);
        Route::get('/dgii/status', [DgiiTestUIController::class, 'connectionStatus']);
        Route::post('/dgii/simulation/generate', [DgiiTestUIController::class, 'generateSimulation']);

        // DGII Certification Test Runner
        Route::get('/dgii/certification/list', [CertificationController::class, 'listCases']);
        Route::post('/dgii/certification/run-single', [CertificationController::class, 'runSingle']);
        Route::post('/dgii/certification/run-all', [CertificationController::class, 'runAll']);
        Route::get('/dgii/certification/download-fc250k/{encf}', [CertificationController::class, 'downloadFc250k']);
        Route::post('/dgii/run-aprobaciones', [CertificationController::class, 'runAprobaciones']);

        // DGII Audit Logs
        Route::get('/dgii/logs', [DgiiLogController::class, 'index']);
        Route::get('/dgii/logs/stats', [DgiiLogController::class, 'stats']);
        Route::get('/dgii/logs/{id}', [DgiiLogController::class, 'show']);
        Route::get('/dgii/logs/invoice/{invoiceId}', [DgiiLogController::class, 'invoiceTimeline']);
        Route::post('/dgii/logs/purge', [DgiiLogController::class, 'purge']);

        // API Key Management
        Route::apiResource('api-keys', ApiKeyController::class);
        Route::post('/api-keys/{id}/regenerate', [ApiKeyController::class, 'regenerate']);
        Route::get('/api-keys/{id}/logs', [ApiKeyController::class, 'logs']);
    });

    // DGII Reports (Admin and Contador only)
    Route::middleware('role:admin,contador')->group(function () {
        Route::get('/dgii/reports/607', [DgiiReportController::class, 'report607']);
        Route::get('/dgii/reports/606', [DgiiReportController::class, 'report606']);
        Route::post('/dgii/reports/607/export', [DgiiReportController::class, 'export607']);
        Route::post('/dgii/reports/606/export', [DgiiReportController::class, 'export606']);
    });

    // Received Invoices (Aprobaciones Comerciales - Admin and Contador only)
    Route::middleware('role:admin,contador')->group(function () {
        Route::get('/received-invoices', [ReceivedInvoiceController::class, 'index']);
        Route::get('/received-invoices/summary', [ReceivedInvoiceController::class, 'summary']);
        Route::get('/received-invoices/{id}', [ReceivedInvoiceController::class, 'show']);
        Route::post('/received-invoices/{id}/approve', [ReceivedInvoiceController::class, 'approve']);
        Route::post('/received-invoices/{id}/reject', [ReceivedInvoiceController::class, 'reject']);
    });
});

// ═══════════════════════════════════════════════════════════════════════
// External API v1 — Authenticated via API Key (Bearer Token)
// ═══════════════════════════════════════════════════════════════════════
Route::prefix('v1')->middleware(['api.log', 'api.key', 'api.throttle'])->group(function () {

    // Invoices
    Route::get('/invoices', [ExternalInvoiceController::class, 'index'])
        ->middleware('api.permission:invoices.read');
    Route::post('/invoices', [ExternalInvoiceController::class, 'store'])
        ->middleware('api.permission:invoices.create');
    Route::get('/invoices/{id}', [ExternalInvoiceController::class, 'show'])
        ->middleware('api.permission:invoices.read');
    Route::get('/invoices/{id}/pdf', [ExternalInvoiceController::class, 'pdf'])
        ->middleware('api.permission:invoices.read');
    Route::put('/invoices/{id}', [ExternalInvoiceController::class, 'update'])
        ->middleware('api.permission:invoices.create');

    // Quotes
    Route::get('/quotes', [ExternalQuoteController::class, 'index'])
        ->middleware('api.permission:quotes.read');
    Route::post('/quotes', [ExternalQuoteController::class, 'store'])
        ->middleware('api.permission:quotes.create');
    Route::get('/quotes/{id}', [ExternalQuoteController::class, 'show'])
        ->middleware('api.permission:quotes.read');
    Route::get('/quotes/{id}/pdf', [ExternalQuoteController::class, 'pdf'])
        ->middleware('api.permission:quotes.read');
    Route::put('/quotes/{id}', [ExternalQuoteController::class, 'update'])
        ->middleware('api.permission:quotes.create');
    Route::post('/quotes/{id}/convert', [ExternalQuoteController::class, 'convertToInvoice'])
        ->middleware('api.permission:quotes.convert');

    // Clients
    Route::get('/clients', [ExternalClientController::class, 'index'])
        ->middleware('api.permission:clients.read');
    Route::post('/clients', [ExternalClientController::class, 'store'])
        ->middleware('api.permission:clients.create');
    Route::get('/clients/{id}', [ExternalClientController::class, 'show'])
        ->middleware('api.permission:clients.read');
});

