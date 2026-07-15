<?php

namespace App\Filament\Resources\Topics\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')->icon('heroicon-o-document-text')->schema([
                TextInput::make('title')->label('Título')->required(),
                Textarea::make('description')->label('Resumen')->rows(3)->required()->columnSpanFull(),
                Select::make('created_by')->label('Autor')->relationship('creator', 'name')->default(fn () => auth()->id())->required()->searchable(),
            ])->columns(2),
            Section::make('Contenido del tema')->icon('heroicon-o-pencil-square')->schema([
                RichEditor::make('content')->label('Lección')->required()->columnSpanFull(),
            ]),
            Section::make('Recursos multimedia')->icon('heroicon-o-paper-clip')->description('Imágenes, videos y documentos de apoyo para la lección.')->schema([
                SpatieMediaLibraryFileUpload::make('images')->label('Imágenes')->collection('images')->image()->multiple()->reorderable(),
                SpatieMediaLibraryFileUpload::make('videos')->label('Videos')->collection('videos')->acceptedFileTypes(['video/mp4', 'video/webm'])->multiple(),
                SpatieMediaLibraryFileUpload::make('documents')->label('Documentos y presentaciones')->collection('documents')->acceptedFileTypes(['application/pdf', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])->multiple(),
            ])->columns(3),
        ]);
    }
}
