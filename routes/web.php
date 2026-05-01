<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

// Public payment routes
Route::get('/pay/{token}', [PaymentController::class, 'show'])->name('payment.show');
Route::post('/pay/{token}/create-order', [PaymentController::class, 'createOrder'])->name('payment.create-order');
Route::post('/pay/{token}/capture-order', [PaymentController::class, 'captureOrder'])->name('payment.capture-order');

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
