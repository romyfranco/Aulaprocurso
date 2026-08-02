<?php

namespace App\Filament\Actions;

use App\Models\Quiz;
use Filament\Actions\Action;

class PreviewQuizAction
{
    public static function make(): Action
    {
        return Action::make('preview')
            ->label('Vista previa')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(fn (Quiz $record): string => route('quizzes.preview', $record))
            ->openUrlInNewTab();
    }
}
