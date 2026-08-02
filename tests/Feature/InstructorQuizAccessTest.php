<?php

namespace Tests\Feature;

use App\Filament\Actions\PreviewQuizAction;
use App\Filament\Instructor\Resources\Quizzes\Pages\CreateQuiz;
use App\Filament\Instructor\Resources\Quizzes\QuizResource;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InstructorQuizAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_reopen_and_edit_an_evaluation_from_a_topic_they_created(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $topic = $this->createTopic($instructor);
        $quiz = $this->createQuiz($topic, ['title' => 'Diagnóstico inicial']);

        $this->actingAs($instructor)
            ->get('/instructor/quizzes')
            ->assertOk()
            ->assertSeeText('Diagnóstico inicial')
            ->assertSeeText('Vista previa');

        $this->actingAs($instructor)
            ->get('/instructor/quizzes/'.$quiz->id.'/edit')
            ->assertOk();
    }

    public function test_instructor_is_redirected_to_edit_the_evaluation_after_creating_it(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $topic = $this->createTopic($instructor);

        $component = Livewire::actingAs($instructor)
            ->test(CreateQuiz::class)
            ->fillForm([
                'topic_id' => $topic->id,
                'title' => 'Evaluación recién creada',
                'passing_score' => 70,
                'max_attempts' => 2,
                'questions' => [[
                    'question_text' => 'Explica el diagnóstico.',
                    'question_type' => 'short_answer',
                    'points' => 10,
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $quiz = Quiz::where('topic_id', $topic->id)->firstOrFail();

        $component->assertRedirect(QuizResource::getUrl('edit', ['record' => $quiz]));
    }

    public function test_assigned_instructor_can_edit_and_preview_a_course_evaluation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::create([
            'title' => 'Curso activo',
            'slug' => 'curso-activo',
            'description' => 'Curso de prueba',
            'status' => 'published',
            'estimated_duration_hours' => 2,
            'created_by' => $admin->id,
        ]);
        $course->instructors()->attach($instructor, ['assigned_at' => now()]);
        $topic = $this->createTopic($admin);
        $course->topics()->attach($topic, ['order' => 1]);
        $quiz = $this->createQuiz($topic, [
            'title' => 'Evaluación de frenos',
            'instructions' => 'Selecciona la respuesta correcta.',
        ]);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => '¿Cuál es el primer paso?',
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order' => 1,
        ]);
        QuizQuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Realizar la inspección visual',
            'is_correct' => true,
            'order' => 1,
        ]);

        $this->actingAs($instructor)
            ->get('/instructor/quizzes/'.$quiz->id.'/edit')
            ->assertOk()
            ->assertSeeText('Vista previa');

        $this->actingAs($instructor)
            ->get(route('quizzes.preview', $quiz))
            ->assertOk()
            ->assertSeeText('Vista previa del instructor')
            ->assertSeeText('Evaluación de frenos')
            ->assertSeeText('¿Cuál es el primer paso?')
            ->assertSeeText('Realizar la inspección visual')
            ->assertSeeText('No crea intentos ni registra respuestas.');

        $previewAction = PreviewQuizAction::make()->record($quiz);

        $this->assertTrue($previewAction->shouldOpenUrlInNewTab());
        $this->assertSame(route('quizzes.preview', $quiz), $previewAction->getUrl());
    }

    public function test_unrelated_instructor_cannot_edit_or_preview_an_evaluation(): void
    {
        $owner = User::factory()->create(['role' => 'instructor']);
        $otherInstructor = User::factory()->create(['role' => 'instructor']);
        $topic = $this->createTopic($owner);
        $quiz = $this->createQuiz($topic);

        $this->actingAs($otherInstructor)
            ->get('/instructor/quizzes/'.$quiz->id.'/edit')
            ->assertNotFound();

        $this->actingAs($otherInstructor)
            ->get(route('quizzes.preview', $quiz))
            ->assertForbidden();
    }

    private function createTopic(User $creator): Topic
    {
        return Topic::create([
            'title' => 'Tema '.uniqid(),
            'description' => 'Tema de prueba',
            'content' => 'Contenido del tema',
            'created_by' => $creator->id,
        ]);
    }

    private function createQuiz(Topic $topic, array $attributes = []): Quiz
    {
        return Quiz::create(array_merge([
            'topic_id' => $topic->id,
            'title' => 'Evaluación de prueba',
            'passing_score' => 70,
            'max_attempts' => 2,
        ], $attributes));
    }
}
