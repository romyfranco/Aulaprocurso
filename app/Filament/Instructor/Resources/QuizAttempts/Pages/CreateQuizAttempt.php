<?php

namespace App\Filament\Instructor\Resources\QuizAttempts\Pages;

use App\Filament\Instructor\Resources\QuizAttempts\QuizAttemptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuizAttempt extends CreateRecord
{
    protected static string $resource = QuizAttemptResource::class;
}
