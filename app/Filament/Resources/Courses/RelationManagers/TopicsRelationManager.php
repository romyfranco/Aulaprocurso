<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TopicsRelationManager extends RelationManager
{
    protected static string $relationship = 'topics';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Tema')
                    ->required()
                    ->maxLength(255),
                TextInput::make('order')->label('Orden secuencial')->numeric()->minValue(1)->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Tema')->icon('heroicon-o-document-text')
                    ->searchable(),
                TextColumn::make('order')->label('Orden')->badge()->color('primary')->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()->label('Agregar tema')->icon('heroicon-o-plus-circle')->schema(fn (AttachAction $action): array => [
                    $action->getRecordSelect()->label('Tema'),
                    TextInput::make('order')->label('Orden secuencial')->numeric()->minValue(1)->required(),
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
