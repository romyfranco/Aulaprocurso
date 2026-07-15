<?php

namespace App\Filament\Resources\Enrollments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('Estudiante')->icon('heroicon-o-user')
                    ->searchable(),
                TextColumn::make('course.title')
                    ->label('Curso')->icon('heroicon-o-academic-cap')
                    ->searchable(),
                TextColumn::make('enrolled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('progress_percentage')
                    ->label('Progreso')->suffix('%')->badge()->color(fn ($state) => $state >= 100 ? 'success' : ($state < 40 ? 'danger' : 'primary'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')->badge()->color(fn ($state) => match ($state) {
                        'completed' => 'success','dropped' => 'danger',default => 'info'
                    }),
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
