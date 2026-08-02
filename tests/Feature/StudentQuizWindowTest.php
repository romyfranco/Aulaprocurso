<?php

namespace Tests\Feature;

use App\Filament\Student\Resources\Quizzes\Tables\QuizzesTable;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentQuizWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_opens_and_submits_an_objective_evaluation_in_a_new_tab_page(): void
    {
        $scenario = $this->scenario();
        $quiz = $this->createQuiz($scenario['topic'], maxAttempts: 1);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => '¿Cuál es la respuesta correcta?',
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order' => 1,
        ]);
        $correctOption = QuizQuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'La opción correcta',
            'is_correct' => true,
            'order' => 1,
        ]);

        $this->actingAs($scenario['student'])
            ->get(route('student.quizzes.take', $quiz))
            ->assertOk()
            ->assertSeeText('Evaluación en curso')
            ->assertSeeText('¿Cuál es la respuesta correcta?')
            ->assertSeeText('La opción correcta')
            ->assertSee('@media (max-width: 720px)', false);

        $action = QuizzesTable::takeAction()->record($quiz);
        $this->assertTrue($action->shouldOpenUrlInNewTab());
        $this->assertSame(route('student.quizzes.take', $quiz), $action->getUrl());

        $response = $this->actingAs($scenario['student'])->post(route('student.quizzes.submit', $quiz), [
            'responses' => [$question->id => $correctOption->id],
        ]);

        $attempt = $quiz->attempts()->firstOrFail();
        $response->assertRedirect(route('student.quizzes.result', $attempt));
        $this->assertSame('graded', $attempt->status);
        $this->assertEquals('100.00', $attempt->score);

        $this->actingAs($scenario['student'])
            ->get(route('student.quizzes.result', $attempt))
            ->assertOk()
            ->assertSeeText('Evaluación enviada')
            ->assertSeeText('Aprobada')
            ->assertSeeText('100%');

        $this->actingAs($scenario['student'])
            ->get(route('student.quizzes.take', $quiz))
            ->assertForbidden();
    }

    public function test_open_answer_is_sent_to_the_instructor_from_the_separate_page(): void
    {
        $scenario = $this->scenario();
        $quiz = $this->createQuiz($scenario['topic']);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Explica el procedimiento.',
            'question_type' => 'essay',
            'points' => 20,
            'order' => 1,
        ]);

        $response = $this->actingAs($scenario['student'])->post(route('student.quizzes.submit', $quiz), [
            'responses' => [$question->id => 'Respuesta desarrollada por el estudiante.'],
        ]);

        $attempt = $quiz->attempts()->firstOrFail();
        $response->assertRedirect(route('student.quizzes.result', $attempt));
        $this->assertSame('pending_grading', $attempt->status);
        $this->assertSame('Respuesta desarrollada por el estudiante.', $attempt->answers()->firstOrFail()->answer_text);

        $this->actingAs($scenario['student'])
            ->get(route('student.quizzes.result', $attempt))
            ->assertOk()
            ->assertSeeText('Pendiente de revisión');
    }

    public function test_student_cannot_open_a_locked_evaluation_or_submit_an_option_from_another_question(): void
    {
        $scenario = $this->scenario(withSecondTopic: true);
        $firstQuiz = $this->createQuiz($scenario['topic']);
        $secondQuiz = $this->createQuiz($scenario['secondTopic']);
        $question = QuizQuestion::create([
            'quiz_id' => $firstQuiz->id,
            'question_text' => 'Pregunta uno',
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order' => 1,
        ]);
        $foreignQuestion = QuizQuestion::create([
            'quiz_id' => $secondQuiz->id,
            'question_text' => 'Pregunta dos',
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order' => 1,
        ]);
        $foreignOption = QuizQuestionOption::create([
            'question_id' => $foreignQuestion->id,
            'option_text' => 'Opción ajena',
            'is_correct' => true,
            'order' => 1,
        ]);

        $this->actingAs($scenario['student'])
            ->get(route('student.quizzes.take', $secondQuiz))
            ->assertForbidden();

        $this->actingAs($scenario['student'])
            ->from(route('student.quizzes.take', $firstQuiz))
            ->post(route('student.quizzes.submit', $firstQuiz), [
                'responses' => [$question->id => $foreignOption->id],
            ])
            ->assertRedirect(route('student.quizzes.take', $firstQuiz))
            ->assertSessionHasErrors('responses.'.$question->id);

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    private function scenario(bool $withSecondTopic = false): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Curso de prueba',
            'slug' => 'curso-'.uniqid(),
            'description' => 'Curso para evaluaciones',
            'status' => 'published',
            'estimated_duration_hours' => 2,
            'created_by' => $admin->id,
        ]);
        $course->instructors()->attach($instructor, ['assigned_at' => now()]);
        $topic = $this->createTopic($instructor, 'Primer tema');
        $course->topics()->attach($topic, ['order' => 1]);
        $secondTopic = null;

        if ($withSecondTopic) {
            $secondTopic = $this->createTopic($instructor, 'Segundo tema');
            $course->topics()->attach($secondTopic, ['order' => 2]);
        }

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        return compact('admin', 'instructor', 'student', 'course', 'topic', 'secondTopic', 'enrollment');
    }

    private function createTopic(User $creator, string $title): Topic
    {
        return Topic::create([
            'title' => $title,
            'description' => 'Tema de prueba',
            'content' => 'Contenido',
            'created_by' => $creator->id,
        ]);
    }

    private function createQuiz(Topic $topic, int $maxAttempts = 2): Quiz
    {
        return Quiz::create([
            'topic_id' => $topic->id,
            'title' => 'Evaluación de '.$topic->title,
            'instructions' => 'Responde todas las preguntas.',
            'passing_score' => 70,
            'max_attempts' => $maxAttempts,
        ]);
    }
}
