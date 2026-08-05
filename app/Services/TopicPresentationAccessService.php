<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TopicPresentationAccessService
{
    public function __construct(private readonly TopicAccessService $topicAccess) {}

    public function authorize(User $user, Topic $topic): void
    {
        Gate::forUser($user)->authorize('view', $topic);

        if ($user->role !== 'student') {
            return;
        }

        $canOpen = Enrollment::query()
            ->where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->whereHas('course.topics', fn ($query) => $query->whereKey($topic->id))
            ->with('course')
            ->get()
            ->contains(fn (Enrollment $enrollment) => $this->topicAccess->isUnlocked($enrollment, $topic));

        abort_unless($canOpen, 403, 'Este tema todavía está bloqueado.');
    }
}
