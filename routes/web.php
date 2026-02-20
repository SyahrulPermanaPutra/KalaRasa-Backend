<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';

// Route untuk halaman sukses registrasi, redirect ke / hanya jika user refresh/back (JS)
Route::view('register/success', 'pages.auth.register-success')->name('register.success');
