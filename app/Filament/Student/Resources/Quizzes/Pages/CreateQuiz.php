<?php

namespace App\Filament\Student\Resources\Quizzes\Pages;

use App\Filament\Student\Resources\Quizzes\QuizResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;
}
