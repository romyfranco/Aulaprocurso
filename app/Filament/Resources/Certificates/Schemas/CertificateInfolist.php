<?php

namespace App\Filament\Resources\Certificates\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CertificateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Certificado verificable')->icon('heroicon-o-trophy')->schema([
                TextEntry::make('student.name')->label('Estudiante')->size('lg')->weight('bold'),
                TextEntry::make('course.title')->label('Curso')->icon('heroicon-o-academic-cap'),
                TextEntry::make('certificate_code')->label('Código')->badge()->color('primary')->copyable(),
                TextEntry::make('issued_at')->label('Emitido')->dateTime('d M Y'),
                ImageEntry::make('qr_code_path')->label('Código QR')->disk('public')->height(180)->columnSpanFull(),
                TextEntry::make('verification_url')->label('Verificación pública')->state(fn ($record) => route('certificates.verify', $record))->url(fn ($state) => $state)->openUrlInNewTab()->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
