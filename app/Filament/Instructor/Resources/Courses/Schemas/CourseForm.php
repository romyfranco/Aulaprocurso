<?php

namespace App\Filament\Instructor\Resources\Courses\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('thumbnail'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('estimated_duration_hours')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
            ]);
    }
}
