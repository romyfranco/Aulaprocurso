<?php

use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\LaunchRevealPresentationController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ServePublicAssetController;
use App\Http\Controllers\ServeRevealAssetController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

$revealSessionMiddleware = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
];

Route::domain(config('reveal.host'))
    ->withoutMiddleware($revealSessionMiddleware)
    ->group(function (): void {
        Route::get('/p/{token}/{path?}', ServeRevealAssetController::class)
            ->where('path', '.*')
            ->middleware('throttle:'.config('reveal.rate_limit_per_minute').',1')
            ->name('reveal.assets');

        Route::any('/{path?}', fn () => abort(404))
            ->where('path', '.*');
    });

Route::get('/', function () {
    return view('landing');
});

Route::get('/verify/{certificate}', CertificateVerificationController::class)->name('certificates.verify');

Route::get('/storage/{path}', ServePublicAssetController::class)
    ->where('path', '.*')
    ->name('public.storage');

Route::middleware('auth')->get('/topics/{topic}/presentation', LaunchRevealPresentationController::class)
    ->name('topics.presentation.launch');

Route::get('/login', [LoginController::class, 'create'])->name('login');

Route::middleware('guest')->group(function (): void {
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::get('/admin/login', fn () => redirect()->route('login'));
Route::get('/instructor/login', fn () => redirect()->route('login'));
Route::get('/student/login', fn () => redirect()->route('login'));
