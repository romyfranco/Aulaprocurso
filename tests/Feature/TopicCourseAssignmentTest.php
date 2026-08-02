<?php

namespace Tests\Feature;

use App\Filament\Instructor\Resources\Topics\Pages\CreateTopic;
use App\Filament\Instructor\Resources\Topics\Pages\EditTopic;
use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TopicCourseAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_selects_an_assigned_course_when_creating_a_topic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = $this->createCourse($admin, 'Curso asignado', 'curso-asignado');
        $course->instructors()->attach($instructor, ['assigned_at' => now()]);
        $existingTopic = $this->createTopic($instructor, 'Tema existente');
        $course->topics()->attach($existingTopic, ['order' => 3]);

        Livewire::actingAs($instructor)
            ->test(CreateTopic::class)
            ->fillForm([
                'title' => 'Tema nuevo',
                'course_id' => $course->id,
                'description' => 'Resumen del tema',
                'created_by' => $instructor->id,
                'content' => 'Contenido del tema',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $topic = Topic::where('title', 'Tema nuevo')->firstOrFail();

        $this->assertDatabaseHas('course_topic', [
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'order' => 4,
        ]);
    }

    public function test_instructor_can_move_a_topic_to_another_assigned_course_when_editing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $firstCourse = $this->createCourse($admin, 'Curso uno', 'curso-uno');
        $secondCourse = $this->createCourse($admin, 'Curso dos', 'curso-dos');
        $firstCourse->instructors()->attach($instructor, ['assigned_at' => now()]);
        $secondCourse->instructors()->attach($instructor, ['assigned_at' => now()]);
        $topic = $this->createTopic($instructor, 'Tema que se moverá');
        $existingTopic = $this->createTopic($instructor, 'Tema del segundo curso');
        $firstCourse->topics()->attach($topic, ['order' => 1]);
        $secondCourse->topics()->attach($existingTopic, ['order' => 2]);

        $component = Livewire::actingAs($instructor)
            ->test(EditTopic::class, ['record' => $topic->id])
            ->fillForm(['course_id' => $secondCourse->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseMissing('course_topic', [
            'course_id' => $firstCourse->id,
            'topic_id' => $topic->id,
        ]);
        $this->assertDatabaseHas('course_topic', [
            'course_id' => $secondCourse->id,
            'topic_id' => $topic->id,
            'order' => 3,
        ]);

        $component->call('save')->assertHasNoFormErrors();

        $this->assertDatabaseHas('course_topic', [
            'course_id' => $secondCourse->id,
            'topic_id' => $topic->id,
            'order' => 3,
        ]);
    }

    public function test_instructor_cannot_assign_a_topic_to_an_unrelated_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $assignedCourse = $this->createCourse($admin, 'Curso asignado', 'asignado');
        $unrelatedCourse = $this->createCourse($admin, 'Curso ajeno', 'ajeno');
        $assignedCourse->instructors()->attach($instructor, ['assigned_at' => now()]);

        Livewire::actingAs($instructor)
            ->test(CreateTopic::class)
            ->assertFormFieldExists('course_id', function ($field) use ($assignedCourse, $unrelatedCourse): bool {
                $options = $field->getOptions();

                return array_key_exists($assignedCourse->id, $options)
                    && ! array_key_exists($unrelatedCourse->id, $options);
            });
    }

    private function createCourse(User $creator, string $title, string $slug): Course
    {
        return Course::create([
            'title' => $title,
            'slug' => $slug,
            'description' => 'Curso de prueba',
            'status' => 'published',
            'estimated_duration_hours' => 2,
            'created_by' => $creator->id,
        ]);
    }

    private function createTopic(User $creator, string $title): Topic
    {
        return Topic::create([
            'title' => $title,
            'description' => 'Tema de prueba',
            'content' => 'Contenido del tema',
            'created_by' => $creator->id,
        ]);
    }
}
