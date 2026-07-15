<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Certificate $certificate): bool
    {
        return $user->role === 'admin' || $certificate->student_id === $user->id || $certificate->course->instructors()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->role === 'admin';
    }
}
