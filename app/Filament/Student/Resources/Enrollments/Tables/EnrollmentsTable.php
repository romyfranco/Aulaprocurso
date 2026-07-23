<?php

namespace App\Filament\Student\Resources\Enrollments\Tables;

use App\Models\Enrollment;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentsTable
{
    private static function completedTopicsCount(Enrollment $enrollment): int
    {
        $enrollment->loadMissing('attempts.quiz:id,topic_id');

        return $enrollment->attempts
            ->where('status', 'graded')
            ->pluck('quiz.topic_id')
            ->filter()
            ->unique()
            ->count();
    }

    private static function sortByCompletedTopics(Builder $query, string $direction): Builder
    {
        $enrollments = Enrollment::query()
            ->where('student_id', auth()->id())
            ->with('attempts.quiz:id,topic_id')
            ->get();

        $ordered = $direction === 'desc'
            ? $enrollments->sortByDesc(fn (Enrollment $enrollment): int => self::completedTopicsCount($enrollment))
            : $enrollments->sortBy(fn (Enrollment $enrollment): int => self::completedTopicsCount($enrollment));

        if ($ordered->isEmpty()) {
            return $query;
        }

        $cases = $ordered->values()
            ->map(fn (Enrollment $enrollment, int $index): string => 'WHEN '.(int) $enrollment->id.' THEN '.$index)
            ->implode(' ');

        return $query->orderByRaw('CASE enrollments.id '.$cases.' ELSE '.$ordered->count().' END');
    }

    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('course.title')->label('Curso')->icon('heroicon-o-book-open')->searchable()->sortable()->description(fn ($record) => $record->course->estimated_duration_hours.' horas estimadas'),
            TextColumn::make('progress_percentage')->label('Progreso')->suffix('%')->badge()->color(fn ($state) => $state >= 100 ? 'success' : ($state < 40 ? 'danger' : 'primary'))->sortable(),
            TextColumn::make('topics_progress')->label('Temas completados')->state(function ($record) {
                $total = $record->course->topics()->count();
                $done = self::completedTopicsCount($record);

                return "{$done} de {$total}";
            })->icon('heroicon-o-check-circle')->sortable(query: fn (Builder $query, string $direction): Builder => self::sortByCompletedTopics($query, $direction)),
            TextColumn::make('status')->label('Estado')->badge()->color(fn ($state) => $state === 'completed' ? 'success' : 'info')->formatStateUsing(fn ($state) => $state === 'completed' ? 'Completado' : 'En curso')->sortable(),
        ])->recordActions([ViewAction::make()->label('Ver progreso')]);
    }
}
