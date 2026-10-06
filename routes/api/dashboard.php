<?php

// Diisi oleh pemilik bagian B/C. Jangan diedit oleh bagian A.

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('admin/dashboard', [DashboardController::class, 'index']);
    });
