<?php

use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::get('/verify/{certificate}', CertificateVerificationController::class)->name('certificates.verify');

Route::get('/login', [LoginController::class, 'create'])->name('login');

Route::middleware('guest')->group(function (): void {
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::get('/admin/login', fn () => redirect()->route('login'));
Route::get('/instructor/login', fn () => redirect()->route('login'));
Route::get('/student/login', fn () => redirect()->route('login'));
