<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentQuizService
{
    public function pendingQuery(User $student, ?Enrollment $enrollment = null): Builder
    {
        return Quiz::query()
            ->whereHas('topic.courses.enrollments', fn (Builder $query) => $query
                ->where('student_id', $student->id)
                ->whereIn('status', ['active', 'completed']))
            ->when($enrollment, fn (Builder $query) => $query
                ->whereHas('topic.courses', fn (Builder $courses) => $courses->whereKey($enrollment->course_id)))
            ->whereDoesntHave('attempts', fn (Builder $query) => $query
                ->where('student_id', $student->id)
                ->where('status', 'graded')
                ->whereColumn('quiz_attempts.score', '>=', 'quizzes.passing_score'));
    }

    public function pendingCount(User $student, ?Enrollment $enrollment = null): int
    {
        return $this->pendingQuery($student, $enrollment)->count();
    }
}
