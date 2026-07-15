<?php

namespace App\Filament\Student\Widgets;

use App\Models\Quiz;
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
            Stat::make('Mis cursos', $enrollments->count())->description($enrollments->where('status', 'completed')->count().' completados')->icon('heroicon-o-book-open')->color('info'),
            Stat::make('Evaluaciones disponibles', Quiz::whereHas('topic.courses.enrollments', fn ($q) => $q->where('student_id', auth()->id()))->count())->icon('heroicon-o-clipboard-document-list')->color('warning'),
            Stat::make('Certificados', auth()->user()->certificates()->count())->description('Logros verificables con QR')->icon('heroicon-o-trophy')->color('success'),
        ];
    }
}
