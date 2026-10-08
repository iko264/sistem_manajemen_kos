<?php

use Illuminate\Support\Facades\Route;

// Halaman Blade; autentikasi dicek di sisi klien (token Bearer di localStorage).
Route::view('/invoices', 'billing.invoices')->name('invoices.index');
Route::view('/payments', 'billing.payments')->name('payments.index');
Route::view('/billing', 'billing.recap')->name('billing.index');
