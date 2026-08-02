<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class TopicCourseAssignmentService
{
    public function authorize(User $user, int $courseId): Course
    {
        $course = Course::query()->findOrFail($courseId);

        if ($user->role === 'admin') {
            return $course;
        }

        $canManageCourse = $user->role === 'instructor'
            && $course->status !== 'archived'
            && $course->instructors()->whereKey($user->id)->exists();

        if (! $canManageCourse) {
            throw new AuthorizationException('No puedes asignar temas a este curso.');
        }

        return $course;
    }

    public function assign(Topic $topic, int $courseId, User $user): void
    {
        $this->authorize($user, $courseId);

        DB::transaction(function () use ($topic, $courseId): void {
            $course = Course::query()->lockForUpdate()->findOrFail($courseId);
            $currentCourseIds = $topic->courses()->pluck('courses.id');

            if ($currentCourseIds->count() === 1 && (int) $currentCourseIds->first() === $course->id) {
                return;
            }

            $nextOrder = ((int) $course->topics()->max('course_topic.order')) + 1;

            $topic->courses()->sync([
                $course->id => ['order' => $nextOrder],
            ]);
        });
    }
}
