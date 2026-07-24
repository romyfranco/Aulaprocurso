<?php

namespace App\Filament\Resources\Certificates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CertificatesTable
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
                TextColumn::make('enrollment.id')
                    ->searchable(),
                TextColumn::make('certificate_code')
                    ->label('Código')->badge()->color('primary')->copyable()
                    ->searchable(),
                TextColumn::make('issued_at')
                    ->label('Emitido')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('qr_code_path')
                    ->searchable(),
                TextColumn::make('pdf_path')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('download')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => route('certificates.download', $record)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
