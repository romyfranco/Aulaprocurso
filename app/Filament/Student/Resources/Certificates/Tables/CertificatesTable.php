<?php

namespace App\Filament\Student\Resources\Certificates\Tables;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('course.title')->label('Curso')->icon('heroicon-o-trophy')->searchable(),
            TextColumn::make('certificate_code')->label('Código')->badge()->color('primary')->copyable(),
            TextColumn::make('issued_at')->label('Emitido')->date('d M Y')->sortable(),
        ])->recordActions([
            ViewAction::make()->label('Ver certificado'),
            Action::make('verify')->label('Verificar')->icon('heroicon-o-qr-code')->url(fn ($record) => route('certificates.verify', $record))->openUrlInNewTab(),
            Action::make('download')->label('Descargar PDF')->icon('heroicon-o-arrow-down-tray')->url(fn ($record) => $record->pdf_path ? asset('storage/'.$record->pdf_path) : null)->openUrlInNewTab()->visible(fn ($record) => filled($record->pdf_path)),
        ]);
    }
}
