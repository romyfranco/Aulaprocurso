<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Curso')->icon('heroicon-o-academic-cap')->schema([
                ImageEntry::make('thumbnail')->label('Portada')->height(160)->columnSpanFull(),
                TextEntry::make('title')->label('Título')->size('lg')->weight('bold'),
                TextEntry::make('status')->label('Estado')->badge()->color(fn ($state) => match ($state) {
                    'published' => 'success','archived' => 'gray',default => 'warning'
                }),
                TextEntry::make('description')->label('Descripción')->prose()->columnSpanFull(),
            ])->columns(2),
            Section::make('Resumen académico')->icon('heroicon-o-chart-bar')->schema([
                TextEntry::make('estimated_duration_hours')->label('Duración')->suffix(' horas')->icon('heroicon-o-clock'),
                TextEntry::make('topics_count')->label('Temas')->state(fn ($record) => $record->topics()->count())->badge(),
                TextEntry::make('enrollments_count')->label('Matrículas')->state(fn ($record) => $record->enrollments()->count())->badge(),
                TextEntry::make('creator.name')->label('Creado por')->icon('heroicon-o-user'),
            ])->columns(4),
        ]);
    }
}
