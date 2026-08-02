<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentQuizAccessService
{
    public function enrollments(Quiz $quiz, User $student): Collection
    {
        return Enrollment::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['active', 'completed'])
            ->whereHas('course.topics', fn ($query) => $query->whereKey($quiz->topic_id))
            ->with('course.topics')
            ->get();
    }

    public function unlockedEnrollment(Quiz $quiz, User $student): ?Enrollment
    {
        return $this->enrollments($quiz, $student)
            ->first(fn (Enrollment $enrollment): bool => app(TopicAccessService::class)->isUnlocked($enrollment, $quiz->topic));
    }
}
