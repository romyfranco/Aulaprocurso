<?php

namespace App\Filament\Instructor\Resources\Topics\Pages;

use App\Filament\Instructor\Resources\Topics\TopicResource;
use App\Services\TopicCourseAssignmentService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTopic extends EditRecord
{
    protected static string $resource = TopicResource::class;

    protected function beforeSave(): void
    {
        app(TopicCourseAssignmentService::class)->authorize(auth()->user(), (int) $this->data['course_id']);
    }

    protected function afterSave(): void
    {
        app(TopicCourseAssignmentService::class)->assign($this->record, (int) $this->data['course_id'], auth()->user());
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
