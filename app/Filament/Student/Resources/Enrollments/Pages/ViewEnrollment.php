<?php

namespace App\Filament\Student\Resources\Enrollments\Pages;

use App\Filament\Student\Resources\Enrollments\EnrollmentResource;
use App\Filament\Student\Resources\Quizzes\QuizResource;
use App\Services\StudentQuizService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewEnrollment extends ViewRecord
{
    protected static string $resource = EnrollmentResource::class;

    private ?int $pendingQuizCount = null;

    private function pendingQuizCount(): int
    {
        return $this->pendingQuizCount ??= app(StudentQuizService::class)
            ->pendingCount(auth()->user(), $this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pendingQuizzes')
                ->label(fn (): string => $this->pendingQuizCount() > 0
                    ? 'Ver mis evaluaciones pendientes ('.$this->pendingQuizCount().')'
                    : 'No tienes evaluaciones pendientes')
                ->icon(fn (): string => $this->pendingQuizCount() > 0
                    ? 'heroicon-o-clipboard-document-list'
                    : 'heroicon-o-check-circle')
                ->color(fn (): string => $this->pendingQuizCount() > 0 ? 'warning' : 'gray')
                ->url(fn (): string => QuizResource::getUrl('index'))
                ->disabled(fn (): bool => $this->pendingQuizCount() === 0),
        ];
    }
}
