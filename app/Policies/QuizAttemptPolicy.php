<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, QuizAttempt $attempt): bool
    {
        return $user->role === 'admin' || $attempt->student_id === $user->id || $attempt->quiz->topic->courses()->whereHas('instructors', fn ($q) => $q->whereKey($user->id))->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === 'student';
    }

    public function update(User $user, QuizAttempt $attempt): bool
    {
        return $user->role === 'admin' || ($user->role === 'instructor' && $attempt->quiz->topic->courses()->whereHas('instructors', fn ($q) => $q->whereKey($user->id))->exists());
    }

    public function delete(User $user, QuizAttempt $attempt): bool
    {
        return $user->role === 'admin';
    }
}
