<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')->icon('heroicon-o-identification')->schema([
                TextEntry::make('name')->label('Nombre')->icon('heroicon-o-user'),
                TextEntry::make('email')->label('Correo')->icon('heroicon-o-envelope')->copyable(),
                TextEntry::make('role')->label('Rol')->badge()->color(fn ($state) => match ($state) {
                    'admin' => 'primary','instructor' => 'success',default => 'info'
                })->formatStateUsing(fn ($state) => match ($state) {
                    'admin' => 'Administrador','instructor' => 'Instructor',default => 'Estudiante'
                }),
                TextEntry::make('bio')->label('Biografía')->placeholder('Sin biografía')->columnSpanFull(),
            ])->columns(2),
            Section::make('Metadatos')->icon('heroicon-o-information-circle')->schema([
                TextEntry::make('created_at')->label('Creado')->dateTime('d M Y, H:i'),
                TextEntry::make('updated_at')->label('Actualizado')->dateTime('d M Y, H:i'),
            ])->columns(2),
        ]);
    }
}
