<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // DashboardController adalah controller satu aksi (__invoke).
    Route::get('admin/dashboard', DashboardController::class);
});