<?php

namespace App\Filament\Student\Resources\Enrollments\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('course.title')->label('Curso')->icon('heroicon-o-book-open')->searchable()->description(fn ($record) => $record->course->estimated_duration_hours.' horas estimadas'),
            TextColumn::make('progress_percentage')->label('Progreso')->suffix('%')->badge()->color(fn ($state) => $state >= 100 ? 'success' : ($state < 40 ? 'danger' : 'primary'))->sortable(),
            TextColumn::make('topics_progress')->label('Temas completados')->state(function ($record) {
                $total = $record->course->topics()->count();
                $done = $record->attempts()->where('status', 'graded')->whereHas('quiz', fn ($q) => $q->whereHas('topic', fn ($t) => $t->whereHas('courses', fn ($c) => $c->whereKey($record->course_id))))->with('quiz:id,topic_id')->get()->pluck('quiz.topic_id')->unique()->count();

                return "{$done} de {$total}";
            })->icon('heroicon-o-check-circle'),
            TextColumn::make('status')->label('Estado')->badge()->color(fn ($state) => $state === 'completed' ? 'success' : 'info')->formatStateUsing(fn ($state) => $state === 'completed' ? 'Completado' : 'En curso'),
        ])->recordActions([ViewAction::make()->label('Ver progreso')]);
    }
}
