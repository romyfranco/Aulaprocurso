<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RevealPresentation extends Model
{
    protected $fillable = [
        'topic_id',
        'uploaded_by',
        'version',
        'status',
        'original_name',
        'archive_path',
        'storage_path',
        'entry_path',
        'archive_size',
        'extracted_size',
        'file_count',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'archive_size' => 'integer',
            'extracted_size' => 'integer',
            'file_count' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (RevealPresentation $presentation): void {
            if ($presentation->storage_path) {
                Storage::disk('local')->deleteDirectory($presentation->storage_path);
            }

            if ($presentation->archive_path) {
                Storage::disk('local')->delete($presentation->archive_path);
            }
        });
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && filled($this->storage_path);
    }
}
