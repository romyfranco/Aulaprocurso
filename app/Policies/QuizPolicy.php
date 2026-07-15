<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->role === 'admin' || $quiz->topic->courses()->whereHas('instructors', fn ($q) => $q->whereKey($user->id))->exists() || $quiz->topic->courses()->whereHas('enrollments', fn ($q) => $q->where('student_id', $user->id))->exists();
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'instructor'], true);
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->role === 'admin' || ($user->role === 'instructor' && $quiz->topic->courses()->whereHas('instructors', fn ($q) => $q->whereKey($user->id))->exists());
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }
}
