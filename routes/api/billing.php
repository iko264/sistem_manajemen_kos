<?php

use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // admin: semua; tenant: miliknya (dibatasi di controller + Policy)
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);

    // tenant pemilik invoice (multipart: proof, amount)
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->middleware('role:tenant');

    Route::middleware('role:admin')->group(function () {
        Route::post('invoices/generate', [InvoiceController::class, 'generate']);
        Route::get('payments', [PaymentController::class, 'index']);
        Route::patch('payments/{payment}/verify', [PaymentController::class, 'verify']);
        Route::get('billing/tenants', [BillingController::class, 'tenants']);
        Route::get('billing/rooms', [BillingController::class, 'rooms']);
    });
});
