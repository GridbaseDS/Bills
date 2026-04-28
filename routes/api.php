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
});
