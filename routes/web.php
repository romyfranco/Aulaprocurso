<?php

use App\Http\Controllers\CertificateVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::get('/verify/{certificate}', CertificateVerificationController::class)->name('certificates.verify');
