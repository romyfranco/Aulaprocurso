<?php

namespace App\Filament\Resources\Topics\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TopicInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')->icon('heroicon-o-academic-cap')->schema([
                TextEntry::make('title')->label('Tema')->size('lg')->weight('bold'),
                TextEntry::make('courses.title')->label('Curso')->placeholder('Sin curso')->badge()->color('info'),
                TextEntry::make('quiz.title')->label('Evaluación')->placeholder('Sin evaluación')->badge()->color('warning'),
                TextEntry::make('description')->label('Resumen')->columnSpanFull(),
            ])->columns(2),
            Section::make('Contenido')->icon('heroicon-o-document-text')->schema([
                TextEntry::make('content')->label('')->html()->prose()->columnSpanFull(),
            ]),
            Section::make('Recursos del tema')->icon('heroicon-o-paper-clip')->schema([
                ViewEntry::make('media_resources')
                    ->label('')
                    ->view('filament.infolists.topic-media')
                    ->viewData(fn ($record): array => ['topic' => $record])
                    ->columnSpanFull(),
            ])->visible(fn ($record): bool => $record->media()->whereIn('collection_name', ['images', 'videos', 'documents'])->exists()),
            Section::make('Presentación PDF')->icon('heroicon-o-document-chart-bar')->schema([
                ViewEntry::make('pdf_presentation')
                    ->label('')
                    ->view('filament.infolists.pdf-presentation')
                    ->viewData(fn ($record): array => ['topic' => $record])
                    ->columnSpanFull(),
            ])->visible(fn ($record): bool => $record->hasMedia('presentation_pdf')),
            Section::make('Presentación interactiva')->icon('heroicon-o-presentation-chart-bar')->schema([
                ViewEntry::make('reveal_presentation')
                    ->label('')
                    ->view('filament.infolists.reveal-presentation')
                    ->viewData(fn ($record): array => ['topic' => $record])
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
