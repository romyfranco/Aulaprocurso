<?php

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Topic $topic): bool
    {
        return $user->role === 'admin' || $topic->created_by === $user->id || $topic->courses()->whereHas('instructors', fn ($q) => $q->whereKey($user->id))->exists() || $topic->courses()->whereHas('enrollments', fn ($q) => $q->where('student_id', $user->id))->exists();
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'instructor'], true);
    }

    public function update(User $user, Topic $topic): bool
    {
        return $user->role === 'admin' || ($user->role === 'instructor' && ($topic->created_by === $user->id || $topic->courses()->whereHas('instructors', fn ($q) => $q->whereKey($user->id))->exists()));
    }

    public function delete(User $user, Topic $topic): bool
    {
        return $user->role === 'admin' || ($user->role === 'instructor' && $topic->created_by === $user->id);
    }
}
