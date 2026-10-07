<?php

use App\Http\Controllers\Api\TenancyController;
use App\Http\Controllers\Api\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Admin: semua data. Tenant: hanya miliknya (dibatasi di controller dan policy).
    Route::get('tenancies', [TenancyController::class, 'index']);
    Route::get('tenancies/{tenancy}', [TenancyController::class, 'show']);

    // Khusus admin.
    Route::middleware('role:admin')->group(function () {
        Route::get('tenants', [TenantController::class, 'index']);
        Route::post('tenancies', [TenancyController::class, 'store']);
        Route::patch('tenancies/{tenancy}/checkout', [TenancyController::class, 'checkout']);
        Route::get('rooms/{room}/tenant', [TenantController::class, 'roomTenant']);
    });
});