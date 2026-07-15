<?php

namespace App\Services;

use App\Models\Enrollment;

class ProgressService
{
    public function recalculate(Enrollment $enrollment): Enrollment
    {
        $topicIds = $enrollment->course->topics()->pluck('topics.id');
        $total = $topicIds->count();
        $completed = $enrollment->attempts()
            ->where('status', 'graded')
            ->whereHas('quiz', fn ($query) => $query->whereIn('topic_id', $topicIds))
            ->with('quiz:id,topic_id,passing_score')
            ->get()
            ->filter(fn ($attempt) => $attempt->score !== null && (float) $attempt->score >= $attempt->quiz->passing_score)
            ->pluck('quiz.topic_id')
            ->unique()
            ->count();
        $percentage = $total === 0 ? 0 : round(($completed / $total) * 100, 2);
        $enrollment->update(['progress_percentage' => $percentage, 'status' => $percentage >= 100 ? 'completed' : 'active', 'completed_at' => $percentage >= 100 ? ($enrollment->completed_at ?? now()) : null]);
        if ($percentage >= 100) {
            app(CertificateService::class)->issue($enrollment);
        }

        return $enrollment->refresh();
    }
}
