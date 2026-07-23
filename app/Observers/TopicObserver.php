<?php

namespace App\Observers;

use App\Jobs\ProcessRevealPresentation;
use App\Models\RevealPresentation;
use App\Models\Topic;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TopicObserver
{
    public function saved(Topic $topic): void
    {
        if (! $topic->wasChanged('pending_reveal_archive') || blank($topic->pending_reveal_archive)) {
            return;
        }

        $archivePath = $topic->pending_reveal_archive;
        $originalName = $topic->pending_reveal_original_name ?: basename($archivePath);

        $presentation = RevealPresentation::create([
            'topic_id' => $topic->id,
            'uploaded_by' => auth()->id() ?: $topic->created_by,
            'version' => (string) Str::uuid(),
            'status' => 'processing',
            'original_name' => $originalName,
            'archive_path' => $archivePath,
            'archive_size' => Storage::disk('local')->exists($archivePath)
                ? Storage::disk('local')->size($archivePath)
                : 0,
        ]);

        $topic->forceFill([
            'pending_reveal_archive' => null,
            'pending_reveal_original_name' => null,
        ])->saveQuietly();

        ProcessRevealPresentation::dispatch($presentation)->afterCommit();
    }

    public function deleting(Topic $topic): void
    {
        $topic->forceFill(['active_reveal_presentation_id' => null])->saveQuietly();
        $topic->revealUploads()->get()->each->delete();
    }
}
