<?php

namespace App\Jobs;

use App\Exceptions\InvalidRevealArchive;
use App\Models\RevealPresentation;
use App\Models\Topic;
use App\Services\RevealArchiveExtractor;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessRevealPresentation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public RevealPresentation $presentation) {}

    public function handle(RevealArchiveExtractor $extractor): void
    {
        $presentation = RevealPresentation::query()->findOrFail($this->presentation->id);
        $presentation->update(['status' => 'processing', 'error_message' => null]);

        $temporaryPath = 'reveal/tmp/'.$presentation->version;
        $finalPath = 'reveal/decks/'.$presentation->version;
        $disk = Storage::disk(config('reveal.disk'));
        $temporaryAbsolutePath = $disk->path($temporaryPath);
        $finalAbsolutePath = $disk->path($finalPath);

        try {
            $metadata = $extractor->extract(
                $disk->path($presentation->archive_path),
                $temporaryAbsolutePath,
            );

            if (is_dir($finalAbsolutePath)) {
                app('files')->deleteDirectory($finalAbsolutePath);
            }

            app('files')->ensureDirectoryExists(dirname($finalAbsolutePath));

            if (! app('files')->moveDirectory($temporaryAbsolutePath, $finalAbsolutePath, true)) {
                throw new \RuntimeException('No se pudo publicar la carpeta extraída.');
            }

            $activated = $this->activate($presentation, $finalPath, $metadata);

            if ($activated) {
                $this->notify($presentation, 'Presentación disponible', 'La presentación Reveal.js de "'.$presentation->topic->title.'" ya está lista.', true);
            }
        } catch (InvalidRevealArchive $exception) {
            app('files')->deleteDirectory($temporaryAbsolutePath);
            app('files')->deleteDirectory($finalAbsolutePath);
            $this->markFailed($presentation, $exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        if (! $exception) {
            return;
        }

        $presentation = $this->presentation->fresh();

        if ($presentation) {
            $disk = Storage::disk(config('reveal.disk'));
            $disk->deleteDirectory('reveal/tmp/'.$presentation->version);
            $disk->deleteDirectory('reveal/decks/'.$presentation->version);
            $this->markFailed($presentation, $exception);
        }
    }

    /**
     * @param  array{entry_path: string, extracted_size: int, file_count: int}  $metadata
     */
    private function activate(RevealPresentation $presentation, string $finalPath, array $metadata): bool
    {
        $oldPresentationId = null;
        $activated = false;

        DB::transaction(function () use ($presentation, $finalPath, $metadata, &$oldPresentationId, &$activated): void {
            $lockedPresentation = RevealPresentation::query()->lockForUpdate()->findOrFail($presentation->id);
            $topic = Topic::query()->lockForUpdate()->findOrFail($lockedPresentation->topic_id);

            $newerReadyUploadExists = RevealPresentation::query()
                ->where('topic_id', $topic->id)
                ->where('id', '>', $lockedPresentation->id)
                ->where('status', 'ready')
                ->exists();

            if ($newerReadyUploadExists) {
                $lockedPresentation->update([
                    'status' => 'superseded',
                    'storage_path' => $finalPath,
                    'entry_path' => $metadata['entry_path'],
                    'extracted_size' => $metadata['extracted_size'],
                    'file_count' => $metadata['file_count'],
                    'processed_at' => now(),
                ]);

                return;
            }

            $oldPresentationId = $topic->active_reveal_presentation_id;
            $lockedPresentation->update([
                'status' => 'ready',
                'storage_path' => $finalPath,
                'entry_path' => $metadata['entry_path'],
                'extracted_size' => $metadata['extracted_size'],
                'file_count' => $metadata['file_count'],
                'error_message' => null,
                'processed_at' => now(),
            ]);
            $topic->forceFill(['active_reveal_presentation_id' => $lockedPresentation->id])->saveQuietly();
            $activated = true;
        });

        $presentation->refresh();

        if (! $activated) {
            $presentation->delete();

            return false;
        }

        if ($oldPresentationId && $oldPresentationId !== $presentation->id) {
            RevealPresentation::find($oldPresentationId)?->delete();
        }

        return true;
    }

    private function markFailed(RevealPresentation $presentation, Throwable $exception): void
    {
        $presentation->update([
            'status' => 'failed',
            'error_message' => (string) str($exception->getMessage())->limit(1000),
            'processed_at' => now(),
        ]);

        Storage::disk(config('reveal.disk'))->delete($presentation->archive_path);
        $this->notify($presentation, 'No se pudo procesar la presentación', $presentation->error_message, false);
    }

    private function notify(RevealPresentation $presentation, string $title, string $body, bool $success): void
    {
        $uploader = $presentation->uploader;

        if (! $uploader) {
            return;
        }

        $notification = Notification::make()->title($title)->body($body);
        $success ? $notification->success() : $notification->danger();
        $notification->sendToDatabase($uploader);
    }
}
