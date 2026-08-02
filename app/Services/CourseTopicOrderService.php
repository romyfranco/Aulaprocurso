<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseTopicOrderService
{
    public function reorder(Course $course, array $requestedOrder): void
    {
        $requestedOrder = collect($requestedOrder)
            ->map(fn ($topicId): int => (int) $topicId)
            ->values();

        if ($requestedOrder->isEmpty() || $requestedOrder->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['order' => 'El orden recibido no es válido.']);
        }

        DB::transaction(function () use ($course, $requestedOrder): void {
            Course::query()->lockForUpdate()->findOrFail($course->id);

            $currentOrder = DB::table('course_topic')
                ->where('course_id', $course->id)
                ->orderBy('order')
                ->lockForUpdate()
                ->pluck('topic_id')
                ->map(fn ($topicId): int => (int) $topicId)
                ->values();

            if ($requestedOrder->diff($currentOrder)->isNotEmpty()) {
                throw ValidationException::withMessages(['order' => 'Uno de los temas no pertenece a este curso.']);
            }

            $replacementIndex = 0;
            $finalOrder = $currentOrder->map(function (int $topicId) use ($requestedOrder, &$replacementIndex): int {
                if (! $requestedOrder->containsStrict($topicId)) {
                    return $topicId;
                }

                return $requestedOrder[$replacementIndex++];
            });

            $maxOrder = (int) DB::table('course_topic')
                ->where('course_id', $course->id)
                ->max('order');
            $temporaryOffset = $maxOrder + $currentOrder->count() + 100;
            $wrappedOrder = DB::connection()->getQueryGrammar()->wrap('order');

            DB::table('course_topic')
                ->where('course_id', $course->id)
                ->update(['order' => DB::raw($wrappedOrder.' + '.$temporaryOffset)]);

            foreach ($finalOrder as $index => $topicId) {
                DB::table('course_topic')
                    ->where('course_id', $course->id)
                    ->where('topic_id', $topicId)
                    ->update([
                        'order' => $index + 1,
                        'updated_at' => now(),
                    ]);
            }
        });
    }
}
