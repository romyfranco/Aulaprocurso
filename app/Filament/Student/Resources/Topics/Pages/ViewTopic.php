<?php

namespace App\Filament\Student\Resources\Topics\Pages;

use App\Filament\Student\Resources\Topics\TopicResource;
use App\Models\Enrollment;
use App\Services\TopicAccessService;
use Filament\Resources\Pages\ViewRecord;

class ViewTopic extends ViewRecord
{
    protected static string $resource = TopicResource::class;

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $topic = $this->getRecord();
        $accessible = Enrollment::query()
            ->where('student_id', auth()->id())
            ->whereIn('status', ['active', 'completed'])
            ->whereHas('course.topics', fn ($query) => $query->whereKey($topic->id))
            ->with('course')
            ->get()
            ->contains(fn (Enrollment $enrollment) => app(TopicAccessService::class)->isUnlocked($enrollment, $topic));

        abort_unless($accessible, 403, 'Este tema todavía está bloqueado.');
    }
}
