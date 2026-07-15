<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->role === 'admin' || $enrollment->student_id === $user->id || $enrollment->course->instructors()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->role === 'admin';
    }
}
