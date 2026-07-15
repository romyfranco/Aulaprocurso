<?php

namespace App\Filament\Student\Resources\Quizzes\Schemas;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuizInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Evaluación')->icon('heroicon-o-academic-cap')->schema([
                TextEntry::make('title')->label('Evaluación')->size('lg')->weight('bold'),
                TextEntry::make('topic.title')->label('Tema')->badge()->color('primary'),
            ])->columns(2),
            Section::make('Mi última entrega')->icon('heroicon-o-chat-bubble-left-right')->schema([
                RepeatableEntry::make('student_answers')
                    ->label('Respuestas del estudiante')
                    ->hiddenLabel()
                    ->state(function (Quiz $record) {
                        return $record->attempts()
                            ->where('student_id', auth()->id())
                            ->latest('attempt_number')
                            ->with(['answers.question', 'answers.selectedOption'])
                            ->first()
                            ?->answers;
                    })
                    ->placeholder('Aún no has presentado esta evaluación.')
                    ->schema([
                        TextEntry::make('question.question_text')
                            ->label('Pregunta')
                            ->weight('semibold')
                            ->columnSpanFull(),
                        TextEntry::make('student_answer')
                            ->label('Tu respuesta')
                            ->state(fn (QuizAnswer $record) => $record->answer_text ?: $record->selectedOption?->option_text ?: 'Sin respuesta')
                            ->columnSpanFull(),
                        TextEntry::make('is_correct')
                            ->label('Resultado')
                            ->badge()
                            ->icon(fn ($state) => $state === null ? 'heroicon-o-clock' : ($state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'))
                            ->formatStateUsing(fn ($state) => $state === null ? 'Pendiente de calificación' : ($state ? 'Correcta' : 'Incorrecta'))
                            ->color(fn ($state) => $state === null ? 'warning' : ($state ? 'success' : 'danger')),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
