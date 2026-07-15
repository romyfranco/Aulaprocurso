<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Perfil')->icon('heroicon-o-user-circle')->description('Datos principales y rol dentro de la plataforma')->schema([
                TextInput::make('name')->label('Nombre completo')->required()->maxLength(255),
                TextInput::make('email')->label('Correo electrónico')->email()->required()->unique(ignoreRecord: true),
                Select::make('role')->label('Rol')->options(['admin' => 'Administrador', 'instructor' => 'Instructor', 'student' => 'Estudiante'])->required()->native(false),
                TextInput::make('avatar_url')->label('URL del avatar')->url(),
                Textarea::make('bio')->label('Biografía')->rows(4)->columnSpanFull(),
            ])->columns(2),
            Section::make('Acceso')->icon('heroicon-o-lock-closed')->schema([
                TextInput::make('password')->label('Contraseña')->password()->revealable()->required(fn (string $operation) => $operation === 'create')->dehydrated(fn ($state) => filled($state))->minLength(8),
            ]),
        ]);
    }
}
