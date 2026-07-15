<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnswer extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['attempt_id', 'question_id', 'answer_text', 'selected_option_id', 'is_correct', 'score_awarded', 'graded_by', 'graded_at'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean', 'score_awarded' => 'decimal:2', 'graded_at' => 'datetime'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class);
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuizQuestionOption::class, 'selected_option_id');
    }
}
