<?php

namespace App\Filament\Instructor\Resources\QuizAttempts\Schemas;

use App\Filament\Instructor\Resources\QuizAttempts\Pages\ViewQuizAttempt;
use App\Models\QuizAnswer;
use App\Services\AttemptService;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuizAttemptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Intento del estudiante')->icon('heroicon-o-clipboard-document-check')->schema([
                TextEntry::make('student.name')->label('Estudiante')->icon('heroicon-o-user'),
                TextEntry::make('quiz.title')->label('Evaluación'),
                TextEntry::make('attempt_number')->label('Intento')->badge()->color('warning'),
                TextEntry::make('status')->label('Estado')->badge()->color(fn ($state) => $state === 'graded' ? 'success' : 'danger'),
                TextEntry::make('score')->label('Puntaje')->suffix('%')->placeholder('Pendiente')->badge()->color('primary'),
                TextEntry::make('submitted_at')->label('Entregado')->dateTime('d M Y, H:i'),
            ])->columns(3),
            Section::make('Respuestas')->icon('heroicon-o-chat-bubble-left-right')->schema([
                RepeatableEntry::make('answers')->label('Respuestas del intento')->hiddenLabel()->schema([
                    TextEntry::make('question.question_text')->label('Pregunta')->weight('semibold')->columnSpanFull(),
                    TextEntry::make('answer_text')->label('Respuesta abierta')->placeholder('No aplica')->columnSpanFull(),
                    TextEntry::make('selectedOption.option_text')->label('Opción seleccionada')->placeholder('No aplica'),
                    TextEntry::make('score_awarded')->label('Puntos')->placeholder('Por asignar')->badge(),
                    TextEntry::make('is_correct')
                        ->label('Resultado y calificación')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state === null ? 'Por calificar' : ($state ? 'Correcta' : 'Incorrecta'))
                        ->color(fn ($state) => $state === null ? 'warning' : ($state ? 'success' : 'danger'))
                        ->belowContent(fn (QuizAnswer $record) => ! $record->question->requiresManualGrading() ? null : [
                            Action::make('markCorrect')
                                ->label('Correcta')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->button()
                                ->action(function (QuizAnswer $record, ViewQuizAttempt $livewire): void {
                                    app(AttemptService::class)->gradeAnswer($record, auth()->user(), true);
                                    $livewire->getRecord()->refresh()->load('answers.question', 'answers.selectedOption');
                                    $livewire->refreshFormData(['status', 'score', 'instructor_feedback']);
                                    Notification::make()->title('Respuesta marcada como correcta')->success()->send();
                                }),
                            Action::make('markIncorrect')
                                ->label('Incorrecta + nuevo intento')
                                ->icon('heroicon-o-x-circle')
                                ->color('danger')
                                ->button()
                                ->requiresConfirmation()
                                ->modalHeading('Marcar esta respuesta como incorrecta')
                                ->modalDescription('La respuesta recibirá cero puntos. Si aún no se había concedido, el estudiante obtendrá un intento adicional.')
                                ->action(function (QuizAnswer $record, ViewQuizAttempt $livewire): void {
                                    $alreadyGranted = filled($record->attempt->extra_attempt_granted_at);
                                    app(AttemptService::class)->gradeAnswer($record, auth()->user(), false);
                                    $livewire->getRecord()->refresh()->load('answers.question', 'answers.selectedOption');
                                    $livewire->refreshFormData(['status', 'score', 'instructor_feedback']);
                                    Notification::make()
                                        ->title('Respuesta marcada como incorrecta')
                                        ->body($alreadyGranted ? 'El intento adicional ya había sido concedido.' : 'Se concedió un nuevo intento al estudiante.')
                                        ->warning()
                                        ->send();
                                }),
                        ])
                        ->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            ]),
            Section::make('Retroalimentación')->icon('heroicon-o-light-bulb')->schema([
                TextEntry::make('instructor_feedback')->label('Comentario del instructor')->placeholder('Sin comentarios')->columnSpanFull(),
            ]),
        ]);
    }
}
