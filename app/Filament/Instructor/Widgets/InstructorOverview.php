<?php

namespace App\Filament\Instructor\Widgets;

use App\Models\Enrollment;
use App\Models\QuizAttempt;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InstructorOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Mis cursos', auth()->user()->coursesAsInstructor()->count())->icon('heroicon-o-book-open')->color('primary'),
            Stat::make('Estudiantes', Enrollment::whereHas('course.instructors', fn ($q) => $q->whereKey(auth()->id()))->distinct('student_id')->count('student_id'))->icon('heroicon-o-user-group')->color('info'),
            Stat::make('Por calificar', QuizAttempt::where('status', 'pending_grading')->whereHas('quiz.topic.courses.instructors', fn ($q) => $q->whereKey(auth()->id()))->count())->description('Requieren revisión manual')->icon('heroicon-o-clock')->color('danger'),
            Stat::make('Necesitan atención', Enrollment::where('progress_percentage', '<', 40)->whereHas('course.instructors', fn ($q) => $q->whereKey(auth()->id()))->count())->description('Progreso menor al 40%')->icon('heroicon-o-exclamation-triangle')->color('warning'),
        ];
    }
}
