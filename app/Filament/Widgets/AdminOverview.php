<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\QuizAttempt;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Usuarios activos', User::count())->description('Todos los roles')->icon('heroicon-o-users')->color('primary'),
            Stat::make('Cursos publicados', Course::where('status', 'published')->count())->description(Course::count().' cursos en total')->icon('heroicon-o-academic-cap')->color('success'),
            Stat::make('Matrículas', Enrollment::where('status', 'active')->count())->description('Estudiantes actualmente activos')->icon('heroicon-o-user-group')->color('info'),
            Stat::make('Por calificar', QuizAttempt::where('status', 'pending_grading')->count())->description('Respuestas abiertas pendientes')->icon('heroicon-o-clock')->color('danger'),
        ];
    }
}
