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

// Public Auth
Route::post('/auth/login', [AuthController::class, 'login']);

// Public debug route - TEMPORARY for SMTP testing
Route::get('/debug/mail-test', function () {
    try {
        $settings = \App\Models\Setting::getAll();
        
        // Apply SMTP config
        \App\Services\EmailService::applySmtpConfig($settings);
        
        $configInfo = [
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'username' => config('mail.mailers.smtp.username') ? '***set***' : '(empty)',
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'db_smtp_host' => $settings['smtp_host'] ?? '(not set)',
            'db_smtp_port' => $settings['smtp_port'] ?? '(not set)',
            'db_smtp_encryption' => $settings['smtp_encryption'] ?? '(not set)',
        ];
        
        // Try sending a test email
        \Illuminate\Support\Facades\Mail::raw(
            'Test email from debug endpoint at ' . now()->toDateTimeString(),
            function ($message) {
                $message->to('admin@gridbase.com.do')
                        ->subject('Debug Test - ' . now()->toDateTimeString());
            }
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Email sent successfully!',
            'config' => $configInfo,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
            'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10),
            'config' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
            ],
        ]);
    }
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/session', function (Request $request) {
        return ['authenticated' => true, 'user' => $request->user()];
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Clients
    Route::apiResource('clients', ClientController::class);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'pdf']);
    Route::post('/invoices/{id}/payment', [InvoiceController::class, 'addPayment']);
    Route::post('/invoices/{id}/send-email', [InvoiceController::class, 'sendEmail']);

    // Quotes
    Route::apiResource('quotes', QuoteController::class);
    Route::get('/quotes/{id}/pdf', [QuoteController::class, 'pdf']);
    Route::post('/quotes/{id}/convert', [QuoteController::class, 'convertToInvoice']);
    Route::post('/quotes/{id}/send-email', [QuoteController::class, 'sendEmail']);

    // Recurring Invoices
    Route::apiResource('recurring', RecurringController::class);
    Route::post('/recurring/{id}/toggle', [RecurringController::class, 'toggleStatus']);

    // Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'updateMultiple']);
    Route::post('/settings/test-smtp', [SettingController::class, 'testSmtp']);
    Route::get('/settings/diagnose-smtp', [SettingController::class, 'diagnoseSmtp']);
});
