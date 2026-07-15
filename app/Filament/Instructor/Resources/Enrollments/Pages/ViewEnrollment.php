<?php

namespace App\Filament\Instructor\Resources\Enrollments\Pages;

use App\Filament\Instructor\Resources\Enrollments\EnrollmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEnrollment extends ViewRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
