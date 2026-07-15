<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información del curso')->icon('heroicon-o-academic-cap')->schema([
                TextInput::make('title')->label('Título')->required()->maxLength(255),
                TextInput::make('slug')->label('Identificador URL')->required()->unique(ignoreRecord: true),
                Textarea::make('description')->label('Descripción')->rows(5)->required()->columnSpanFull(),
                FileUpload::make('thumbnail')->label('Portada')->image()->directory('courses')->imageEditor(),
                Select::make('status')->label('Estado')->options(['draft' => 'Borrador', 'published' => 'Publicado', 'archived' => 'Archivado'])->required()->native(false),
                TextInput::make('estimated_duration_hours')->label('Duración estimada (horas)')->numeric()->minValue(0)->required(),
                Select::make('created_by')->label('Creado por')->relationship('creator', 'name')->searchable()->preload()->required(),
            ])->columns(2),
            Section::make('Equipo y contenido')->icon('heroicon-o-user-group')->schema([
                Select::make('instructors')->label('Instructores')->relationship('instructors', 'name')->multiple()->searchable()->preload(),
            ])->columns(2),
        ]);
    }
}
