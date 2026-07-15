<?php

namespace App\Filament\Resources\Courses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Curso')->icon('heroicon-o-academic-cap')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')->badge()->color(fn ($state) => match ($state) {
                        'published' => 'success','archived' => 'gray',default => 'warning'
                    }),
                TextColumn::make('estimated_duration_hours')
                    ->label('Duración')->suffix(' h')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('creator.name')->label('Creado por')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
