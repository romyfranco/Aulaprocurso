<?php

namespace App\Observers;

use App\Models\QuizAttempt;
use App\Services\ProgressService;

class QuizAttemptObserver
{
    /**
     * Handle the QuizAttempt "created" event.
     */
    public function created(QuizAttempt $quizAttempt): void
    {
        //
    }

    /**
     * Handle the QuizAttempt "updated" event.
     */
    public function updated(QuizAttempt $quizAttempt): void
    {
        if ($quizAttempt->status === 'graded' && $quizAttempt->wasChanged(['status', 'score']) && $quizAttempt->enrollment) {
            app(ProgressService::class)->recalculate($quizAttempt->enrollment);
        }
    }

    /**
     * Handle the QuizAttempt "deleted" event.
     */
    public function deleted(QuizAttempt $quizAttempt): void
    {
        //
    }

    /**
     * Handle the QuizAttempt "restored" event.
     */
    public function restored(QuizAttempt $quizAttempt): void
    {
        //
    }

    /**
     * Handle the QuizAttempt "force deleted" event.
     */
    public function forceDeleted(QuizAttempt $quizAttempt): void
    {
        //
    }
}
