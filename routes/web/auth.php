<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/rooms');
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
