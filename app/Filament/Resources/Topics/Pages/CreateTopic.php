<?php

namespace App\Filament\Resources\Topics\Pages;

use App\Filament\Resources\Topics\TopicResource;
use App\Services\TopicCourseAssignmentService;
use Filament\Resources\Pages\CreateRecord;

class CreateTopic extends CreateRecord
{
    protected static string $resource = TopicResource::class;

    protected function beforeCreate(): void
    {
        app(TopicCourseAssignmentService::class)->authorize(auth()->user(), (int) $this->data['course_id']);
    }

    protected function afterCreate(): void
    {
        app(TopicCourseAssignmentService::class)->assign($this->record, (int) $this->data['course_id'], auth()->user());
    }
}
