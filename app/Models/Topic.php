<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;

class Topic extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['title', 'slug', 'description', 'content', 'pending_reveal_archive', 'pending_reveal_original_name', 'created_by'];

    protected static function booted(): void
    {
        static::saving(function (Topic $topic): void {
            if (! $topic->slug || $topic->isDirty('title')) {
                $baseSlug = Str::slug($topic->title) ?: 'tema';
                $slug = $baseSlug;
                $suffix = 2;

                while (static::query()->where('slug', $slug)->when($topic->exists, fn ($query) => $query->whereKeyNot($topic->getKey()))->exists()) {
                    $slug = $baseSlug.'-'.$suffix++;
                }

                $topic->slug = $slug;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class)->withPivot('order')->withTimestamps();
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function revealPresentation(): BelongsTo
    {
        return $this->belongsTo(RevealPresentation::class, 'active_reveal_presentation_id');
    }

    public function revealUploads(): HasMany
    {
        return $this->hasMany(RevealPresentation::class)->latest();
    }

    public function latestRevealUpload(): HasOne
    {
        return $this->hasOne(RevealPresentation::class)->latestOfMany();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('videos')->acceptsMimeTypes(['video/mp4', 'video/webm']);
        $this->addMediaCollection('documents')->acceptsFile(fn (File $file) => in_array($file->mimeType, [
            'application/pdf', 'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ], true));
    }
}
