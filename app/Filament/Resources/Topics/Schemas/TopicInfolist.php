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
            ])->visible(fn ($record): bool => $record->media()->exists()),
            Section::make('Presentación interactiva')->icon('heroicon-o-presentation-chart-bar')->schema([
                ViewEntry::make('reveal_presentation')
                    ->label('')
                    ->view('filament.infolists.reveal-presentation')
                    ->viewData(fn ($record): array => ['topic' => $record])
                    ->columnSpanFull(),
            ]),
            Section::make('Metadatos')->icon('heroicon-o-information-circle')->schema([
                TextEntry::make('creator.name')->label('Autor'),
                TextEntry::make('created_at')->label('Creado')->dateTime('d M Y, H:i'),
                TextEntry::make('updated_at')->label('Actualizado')->dateTime('d M Y, H:i'),
            ])->columns(3)->visible(fn (): bool => auth()->user()?->role === 'admin'),
        ]);
    }
}
