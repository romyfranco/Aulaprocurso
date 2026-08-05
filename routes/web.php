<?php

use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\DownloadCertificatePdfController;
use App\Http\Controllers\LaunchRevealPresentationController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PreviewQuizController;
use App\Http\Controllers\ServePublicAssetController;
use App\Http\Controllers\ServeRevealAssetController;
use App\Http\Controllers\ServeTopicPdfPresentationController;
use App\Http\Controllers\StudentQuizController;
use App\Http\Controllers\ViewTopicPdfPresentationController;
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

Route::middleware('auth')->group(function (): void {
    Route::get('/topics/{topic}/pdf-presentation', ViewTopicPdfPresentationController::class)
        ->name('topics.pdf-presentation.view');
    Route::get('/topics/{topic}/pdf-presentation/file', ServeTopicPdfPresentationController::class)
        ->name('topics.pdf-presentation.file');
});

Route::middleware('auth')->get('/certificates/{certificate}/download', DownloadCertificatePdfController::class)
    ->name('certificates.download');

Route::middleware('auth')->get('/evaluations/{quiz}/preview', PreviewQuizController::class)
    ->name('quizzes.preview');

Route::middleware('auth')->group(function (): void {
    Route::get('/student/evaluations/{quiz}/take', [StudentQuizController::class, 'show'])
        ->name('student.quizzes.take');
    Route::post('/student/evaluations/{quiz}/take', [StudentQuizController::class, 'submit'])
        ->name('student.quizzes.submit');
    Route::get('/student/evaluations/attempts/{attempt}/result', [StudentQuizController::class, 'result'])
        ->name('student.quizzes.result');
});

Route::get('/login', [LoginController::class, 'create'])->name('login');

Route::middleware('guest')->group(function (): void {
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::get('/admin/login', fn () => redirect()->route('login'));
Route::get('/instructor/login', fn () => redirect()->route('login'));
Route::get('/student/login', fn () => redirect()->route('login'));
