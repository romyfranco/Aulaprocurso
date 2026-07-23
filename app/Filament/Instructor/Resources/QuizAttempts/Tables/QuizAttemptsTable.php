<?php

namespace App\Filament\Instructor\Resources\QuizAttempts\Tables;

use App\Services\AttemptService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuizAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('submitted_at', 'desc')->columns([
            TextColumn::make('student.name')->label('Estudiante')->icon('heroicon-o-user')->searchable(),
            TextColumn::make('quiz.title')->label('Evaluación')->searchable(),
            TextColumn::make('attempt_number')->label('Intento')->badge()->color('warning'),
            TextColumn::make('score')->label('Puntaje')->suffix('%')->placeholder('Pendiente')->badge()->color('primary'),
            TextColumn::make('status')->label('Estado')->badge()->sortable()->color(fn ($state) => $state === 'graded' ? 'success' : ($state === 'pending_grading' ? 'danger' : 'gray')),
            TextColumn::make('submitted_at')->label('Entregado')->since()->sortable(),
        ])->recordActions([
            ViewAction::make(),
            EditAction::make()->label('Calificar')->icon('heroicon-o-pencil-square')->visible(fn ($record) => $record->status === 'pending_grading')->mutateDataUsing(fn (array $data) => $data + ['graded_by' => auth()->id(), 'graded_at' => now()]),
            Action::make('grantAttempt')->label('Otorgar intento extra')->icon('heroicon-o-plus-circle')->color('success')->schema([
                TextInput::make('extra_attempts')->label('Cantidad')->numeric()->minValue(1)->default(1)->required(),
                Textarea::make('reason')->label('Motivo')->rows(3),
            ])->action(function ($record, array $data) {
                app(AttemptService::class)->grant($record->quiz, $record->student, auth()->user(), (int) $data['extra_attempts'], $data['reason'] ?? null);
                Notification::make()->title('Intento extra otorgado')->success()->send();
            }),
        ]);
    }
}
