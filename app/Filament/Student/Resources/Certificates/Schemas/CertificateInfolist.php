<?php

namespace App\Filament\Student\Resources\Certificates\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CertificateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('student.name')
                    ->label('Student'),
                TextEntry::make('course.title')
                    ->label('Course'),
                TextEntry::make('enrollment.id')
                    ->label('Enrollment'),
                TextEntry::make('certificate_code'),
                TextEntry::make('issued_at')
                    ->dateTime(),
                TextEntry::make('qr_code_path')
                    ->placeholder('-'),
                TextEntry::make('pdf_path')
                    ->placeholder('-'),
            ]);
    }
}
