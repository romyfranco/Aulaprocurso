<?php

namespace App\Filament\Instructor\Widgets;

use Filament\Widgets\ChartWidget;

class CourseProgressChart extends ChartWidget
{
    protected ?string $heading = 'Progreso promedio por curso';

    protected function getData(): array
    {
        $courses = auth()->user()->coursesAsInstructor()->withAvg('enrollments', 'progress_percentage')->get();

        return ['datasets' => [[
            'label' => 'Progreso promedio',
            'data' => $courses->pluck('enrollments_avg_progress_percentage')->map(fn ($value) => round((float) $value, 1)),
            'backgroundColor' => '#14B8A6',
            'borderColor' => '#0D9488',
        ]], 'labels' => $courses->pluck('title')];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
