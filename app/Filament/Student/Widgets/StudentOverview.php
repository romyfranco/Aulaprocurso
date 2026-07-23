<?php

namespace App\Filament\Student\Widgets;

use App\Filament\Student\Resources\Certificates\CertificateResource;
use App\Filament\Student\Resources\Enrollments\EnrollmentResource;
use App\Filament\Student\Resources\Quizzes\QuizResource;
use App\Services\StudentQuizService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $enrollments = auth()->user()->enrollments()->with('course')->get();
        $average = round((float) $enrollments->avg('progress_percentage'));

        return [
            Stat::make('Progreso general', $average.'%')->description('Promedio de todos tus cursos')->icon('heroicon-o-chart-pie')->color('primary'),
            Stat::make('Mis cursos', $enrollments->count())->description($enrollments->where('status', 'completed')->count().' completados')->icon('heroicon-o-book-open')->color('info')->url(EnrollmentResource::getUrl('index')),
            Stat::make('Evaluaciones pendientes', app(StudentQuizService::class)->pendingCount(auth()->user()))->icon('heroicon-o-clipboard-document-list')->color('warning')->url(QuizResource::getUrl('index')),
            Stat::make('Certificados', auth()->user()->certificates()->count())->description('Logros verificables con QR')->icon('heroicon-o-trophy')->color('success')->url(CertificateResource::getUrl('index')),
        ];
    }
}
