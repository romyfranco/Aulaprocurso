<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = ['topic_id', 'title', 'instructions', 'passing_score', 'max_attempts'];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function grants(): HasMany
    {
        return $this->hasMany(QuizAttemptGrant::class);
    }

    public function availableAttemptsFor(User $student): int
    {
        $granted = (int) $this->grants()->where('student_id', $student->id)->sum('extra_attempts');
        $used = $this->attempts()->where('student_id', $student->id)->count();

        return max(0, $this->max_attempts + $granted - $used);
    }
}
