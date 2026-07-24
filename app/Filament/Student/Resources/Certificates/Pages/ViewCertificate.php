<?php

namespace App\Filament\Student\Resources\Certificates\Pages;

use App\Filament\Student\Resources\Certificates\CertificateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewCertificate extends ViewRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (): string => route('certificates.download', $this->getRecord())),
        ];
    }
}
