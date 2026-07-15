<?php

namespace App\Filament\Student\Resources\Quizzes\Pages;

use App\Filament\Student\Resources\Quizzes\QuizResource;
use App\Filament\Student\Resources\Quizzes\Tables\QuizzesTable;
use Filament\Resources\Pages\ViewRecord;

class ViewQuiz extends ViewRecord
{
    protected static string $resource = QuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            QuizzesTable::takeAction()->label('Presentar evaluación'),
        ];
    }
}
