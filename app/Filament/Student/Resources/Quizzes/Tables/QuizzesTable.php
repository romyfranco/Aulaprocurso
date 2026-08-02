<?php

namespace App\Filament\Student\Resources\Quizzes\Tables;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Services\StudentQuizAccessService;
use App\Services\TopicAccessService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizzesTable
{
    private static function enrollment($quiz): ?Enrollment
    {
        $access = app(StudentQuizAccessService::class);

        return $access->unlockedEnrollment($quiz, auth()->user())
            ?? $access->enrollments($quiz, auth()->user())->first();
    }

    private static function unlocked($quiz): bool
    {
        $enrollment = self::enrollment($quiz);

        return $enrollment && app(TopicAccessService::class)->isUnlocked($enrollment, $quiz->topic);
    }

    private static function availability(Quiz $quiz): string
    {
        if (! self::unlocked($quiz)) {
            return 'Bloqueada';
        }

        return self::attemptsLeft($quiz) > 0 ? 'Disponible' : 'Sin intentos';
    }

    private static function attemptsLeft(Quiz $quiz): int
    {
        return $quiz->availableAttemptsFor(auth()->user());
    }

    private static function sortByComputedValue(Builder $query, string $direction, string $column): Builder
    {
        $quizzes = Quiz::query()
            ->whereHas('topic.courses.enrollments', fn (Builder $enrollments) => $enrollments->where('student_id', auth()->id()))
            ->with('topic')
            ->get();

        $value = fn (Quiz $quiz): int => $column === 'availability'
            ? match (self::availability($quiz)) {
                'Disponible' => 0,
                'Sin intentos' => 1,
                default => 2,
            }
        : self::attemptsLeft($quiz);

        $ordered = $direction === 'desc'
            ? $quizzes->sortByDesc($value)
            : $quizzes->sortBy($value);

        if ($ordered->isEmpty()) {
            return $query;
        }

        $cases = $ordered->values()
            ->map(fn (Quiz $quiz, int $index): string => 'WHEN '.(int) $quiz->id.' THEN '.$index)
            ->implode(' ');

        return $query->orderByRaw('CASE quizzes.id '.$cases.' ELSE '.$ordered->count().' END');
    }

    public static function takeAction(bool $onlyWhenAvailable = false): Action
    {
        $action = Action::make('take')
            ->label('Presentar')
            ->icon('heroicon-o-play')
            ->color('primary')
            ->disabled(fn ($record) => ! self::unlocked($record) || $record->availableAttemptsFor(auth()->user()) < 1)
            ->url(fn (Quiz $record): string => route('student.quizzes.take', $record))
            ->openUrlInNewTab();

        if ($onlyWhenAvailable) {
            $action->visible(fn ($record) => self::unlocked($record) && $record->availableAttemptsFor(auth()->user()) > 0);
        }

        return $action;
    }

    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('topic.title')->label('Tema')->icon('heroicon-o-document-text')->searchable(),
            TextColumn::make('title')->label('Evaluación')->searchable(),
            TextColumn::make('availability')
                ->label('Estado')
                ->state(fn (Quiz $record): string => self::availability($record))
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'Disponible' => 'success',
                    'Sin intentos' => 'danger',
                    default => 'gray',
                })
                ->sortable(query: fn (Builder $query, string $direction): Builder => self::sortByComputedValue($query, $direction, 'availability')),
            TextColumn::make('attempts_left')->label('Intentos disponibles')->state(fn (Quiz $record): int => self::attemptsLeft($record))->badge()->color('warning')->sortable(query: fn (Builder $query, string $direction): Builder => self::sortByComputedValue($query, $direction, 'attempts')),
            TextColumn::make('passing_score')->label('Aprobación')->suffix('%'),
        ])->recordActions([
            ViewAction::make()->label('Ver detalles'),
            self::takeAction(),
        ]);
    }
}
