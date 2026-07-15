<?php

namespace App\Filament\Instructor\Resources\Quizzes\Pages;

use App\Filament\Instructor\Resources\Quizzes\QuizResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuiz extends ViewRecord
{
    protected static string $resource = QuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
