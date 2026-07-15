<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Topic;

class TopicAccessService
{
    public function isUnlocked(Enrollment $enrollment, Topic $topic): bool
    {
        $ordered = $enrollment->course->topics()->get();
        $index = $ordered->search(fn (Topic $item) => $item->id === $topic->id);
        if ($index === false) {
            return false;
        }
        if ($index === 0) {
            return true;
        }
        $previous = $ordered[$index - 1];

        return $enrollment->attempts()
            ->whereHas('quiz', fn ($query) => $query->where('topic_id', $previous->id))
            ->where('status', 'graded')
            ->with('quiz:id,passing_score')
            ->get()
            ->contains(fn ($attempt) => $attempt->score !== null && (float) $attempt->score >= $attempt->quiz->passing_score);
    }
}
