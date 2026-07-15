<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Course $course): bool
    {
        return $user->role === 'admin' || $course->instructors()->whereKey($user->id)->exists() || $course->enrollments()->where('student_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Course $course): bool
    {
        return $user->role === 'admin' || ($user->role === 'instructor' && $course->instructors()->whereKey($user->id)->exists());
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }
}
