<?php

namespace Tests\Feature;

use App\Filament\Instructor\Resources\Courses\Pages\ViewCourse;
use App\Filament\Resources\Courses\RelationManagers\TopicsRelationManager;
use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use App\Services\CourseTopicOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseTopicReorderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_topics_can_be_reordered_without_violating_the_unique_position(): void
    {
        $scenario = $this->scenario();
        $topics = $scenario['topics'];

        app(CourseTopicOrderService::class)->reorder($scenario['course'], [
            $topics[4]->id,
            $topics[1]->id,
            $topics[3]->id,
            $topics[0]->id,
            $topics[2]->id,
        ]);

        $this->assertSame(
            [$topics[4]->id, $topics[1]->id, $topics[3]->id, $topics[0]->id, $topics[2]->id],
            $this->orderedTopicIds($scenario['course']),
        );
        $this->assertSame([1, 2, 3, 4, 5], $this->positions($scenario['course']));
    }

    public function test_reordering_search_results_preserves_topics_outside_the_filtered_list(): void
    {
        $scenario = $this->scenario();
        $topics = $scenario['topics'];

        app(CourseTopicOrderService::class)->reorder($scenario['course'], [
            $topics[3]->id,
            $topics[1]->id,
        ]);

        $this->assertSame(
            [$topics[0]->id, $topics[3]->id, $topics[2]->id, $topics[1]->id, $topics[4]->id],
            $this->orderedTopicIds($scenario['course']),
        );
        $this->assertSame([1, 2, 3, 4, 5], $this->positions($scenario['course']));
    }

    public function test_instructor_uses_the_drag_and_drop_control_from_the_course_view(): void
    {
        $scenario = $this->scenario();
        $topics = $scenario['topics'];

        Livewire::actingAs($scenario['instructor'])
            ->test(TopicsRelationManager::class, [
                'ownerRecord' => $scenario['course'],
                'pageClass' => ViewCourse::class,
            ])
            ->assertSeeText('Cambiar orden')
            ->assertSeeText('arrastra los temas')
            ->call('reorderTable', [
                $topics[2]->id,
                $topics[1]->id,
                $topics[0]->id,
                $topics[3]->id,
                $topics[4]->id,
            ])
            ->assertHasNoErrors();

        $this->assertSame(
            [$topics[2]->id, $topics[1]->id, $topics[0]->id, $topics[3]->id, $topics[4]->id],
            $this->orderedTopicIds($scenario['course']),
        );
    }

    public function test_unrelated_instructor_cannot_reorder_course_topics(): void
    {
        $scenario = $this->scenario();
        $otherInstructor = User::factory()->create(['role' => 'instructor']);
        $topics = $scenario['topics'];

        Livewire::actingAs($otherInstructor)
            ->test(TopicsRelationManager::class, [
                'ownerRecord' => $scenario['course'],
                'pageClass' => ViewCourse::class,
            ])
            ->call('reorderTable', $topics->reverse()->pluck('id')->all());

        $this->assertSame($topics->pluck('id')->all(), $this->orderedTopicIds($scenario['course']));
    }

    private function scenario(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::create([
            'title' => 'Curso ordenable',
            'slug' => 'curso-ordenable',
            'description' => 'Curso de prueba',
            'status' => 'published',
            'estimated_duration_hours' => 5,
            'created_by' => $admin->id,
        ]);
        $course->instructors()->attach($instructor, ['assigned_at' => now()]);
        $topics = collect(range(1, 5))->map(function (int $number) use ($course, $instructor): Topic {
            $topic = Topic::create([
                'title' => 'Tema '.$number,
                'description' => 'Descripción',
                'content' => 'Contenido',
                'created_by' => $instructor->id,
            ]);
            $course->topics()->attach($topic, ['order' => $number]);

            return $topic;
        });

        return compact('admin', 'instructor', 'course', 'topics');
    }

    private function orderedTopicIds(Course $course): array
    {
        return $course->topics()->pluck('topics.id')->map(fn ($id): int => (int) $id)->all();
    }

    private function positions(Course $course): array
    {
        return $course->topics()->get()->pluck('pivot.order')->map(fn ($order): int => (int) $order)->all();
    }
}
