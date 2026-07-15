<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuizInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')->icon('heroicon-o-academic-cap')->schema([
                TextEntry::make('title')->label('Evaluación')->size('lg')->weight('bold'),
                TextEntry::make('topic.title')->label('Tema')->badge()->color('primary'),
                TextEntry::make('passing_score')->label('Puntaje mínimo')->suffix('%')->badge()->color('success'),
                TextEntry::make('max_attempts')->label('Intentos base')->badge()->color('warning'),
                TextEntry::make('instructions')->label('Instrucciones')->placeholder('Sin instrucciones')->columnSpanFull(),
            ])->columns(2),
            Section::make('Contenido de la evaluación')->icon('heroicon-o-question-mark-circle')->schema([
                RepeatableEntry::make('questions')->label('Preguntas')->schema([
                    TextEntry::make('question_text')->label('Pregunta')->weight('semibold')->columnSpanFull(),
                    TextEntry::make('question_type')->label('Tipo')->badge()->color('info')->formatStateUsing(fn ($state) => match ($state) {
                        'multiple_choice' => 'Opción múltiple','true_false' => 'Verdadero / falso','short_answer' => 'Respuesta corta',default => 'Ensayo'
                    }),
                    TextEntry::make('points')->label('Puntos')->badge()->color('warning'),
                    RepeatableEntry::make('options')->label('Opciones')->schema([
                        TextEntry::make('option_text')->label(''),
                        TextEntry::make('is_correct')->label('Correcta')->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')->color(fn ($state) => $state ? 'success' : 'gray')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                    ])->columns(2)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            ]),
            Section::make('Metadatos')->icon('heroicon-o-information-circle')->schema([
                TextEntry::make('created_at')->label('Creado')->dateTime('d M Y, H:i'),
                TextEntry::make('updated_at')->label('Actualizado')->dateTime('d M Y, H:i'),
                TextEntry::make('questions_count')->label('Total de preguntas')->state(fn ($record) => $record->questions()->count())->badge(),
            ])->columns(3),
        ]);
    }
}
