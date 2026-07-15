<?php

namespace App\Filament\Instructor\Resources\Quizzes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('topic_id')
                    ->relationship('topic', 'title')
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->default('Evaluación del tema'),
                Textarea::make('instructions')
                    ->columnSpanFull(),
                TextInput::make('passing_score')
                    ->required()
                    ->numeric()
                    ->default(70),
                TextInput::make('max_attempts')
                    ->required()
                    ->numeric()
                    ->default(2),
            ]);
    }
}
