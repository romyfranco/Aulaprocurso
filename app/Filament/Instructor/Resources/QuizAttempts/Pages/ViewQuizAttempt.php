<?php

namespace App\Filament\Instructor\Resources\QuizAttempts\Pages;

use App\Filament\Instructor\Resources\QuizAttempts\QuizAttemptResource;
use Filament\Resources\Pages\ViewRecord;

class ViewQuizAttempt extends ViewRecord
{
    protected static string $resource = QuizAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
