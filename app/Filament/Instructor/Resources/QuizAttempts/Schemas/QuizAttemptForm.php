<?php

namespace App\Filament\Instructor\Resources\QuizAttempts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuizAttemptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Calificación')->icon('heroicon-o-pencil-square')->schema([
                TextInput::make('score')->label('Puntaje final')->numeric()->minValue(0)->maxValue(100)->suffix('%')->required(),
                Select::make('status')->label('Estado')->options(['pending_grading' => 'Pendiente', 'graded' => 'Calificado'])->native(false)->required(),
                Textarea::make('instructor_feedback')->label('Retroalimentación')->rows(5)->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
