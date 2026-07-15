<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\ExtraAttemptGranted;
use App\Services\AttemptService;
use App\Services\TopicAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\File;
use Tests\TestCase;

class EducationBusinessFlowTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(int $topics = 1): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create(['title' => 'Curso de prueba', 'slug' => 'curso-prueba-'.uniqid(), 'description' => 'Contenido', 'status' => 'published', 'created_by' => $admin->id]);
        $course->instructors()->attach($instructor->id, ['assigned_at' => now()]);
        $quizzes = collect();
        for ($i = 1; $i <= $topics; $i++) {
            $topic = Topic::create(['title' => "Tema {$i}", 'slug' => 'tema-'.$i.'-'.uniqid(), 'description' => 'Resumen', 'content' => 'Contenido', 'created_by' => $instructor->id]);
            $course->topics()->attach($topic->id, ['order' => $i]);
            $quizzes->push(Quiz::create(['topic_id' => $topic->id, 'title' => "Quiz {$i}", 'passing_score' => 70, 'max_attempts' => 2]));
        }
        $enrollment = Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'enrolled_at' => now(), 'status' => 'active']);

        return compact('admin', 'instructor', 'student', 'course', 'quizzes', 'enrollment');
    }

    public function test_instructor_can_grant_unlimited_extra_attempts(): void
    {
        Notification::fake();
        $s = $this->scenario();
        $quiz = $s['quizzes'][0];
        QuizAttempt::create(['quiz_id' => $quiz->id, 'student_id' => $s['student']->id, 'enrollment_id' => $s['enrollment']->id, 'attempt_number' => 1, 'status' => 'in_progress']);
        QuizAttempt::create(['quiz_id' => $quiz->id, 'student_id' => $s['student']->id, 'enrollment_id' => $s['enrollment']->id, 'attempt_number' => 2, 'status' => 'graded']);
        $this->assertSame(0, $quiz->availableAttemptsFor($s['student']));
        app(AttemptService::class)->grant($quiz, $s['student'], $s['instructor'], 3, 'Necesita refuerzo');
        $this->assertSame(3, $quiz->refresh()->availableAttemptsFor($s['student']));
        Notification::assertSentTo($s['student'], ExtraAttemptGranted::class);
    }

    public function test_graded_attempt_unlocks_next_topic_and_completes_course(): void
    {
        Storage::fake('public');
        $s = $this->scenario(2);
        [$first,$second] = $s['quizzes'];
        $access = app(TopicAccessService::class);
        $this->assertTrue($access->isUnlocked($s['enrollment'], $first->topic));
        $this->assertFalse($access->isUnlocked($s['enrollment'], $second->topic));
        $attempt = QuizAttempt::create(['quiz_id' => $first->id, 'student_id' => $s['student']->id, 'enrollment_id' => $s['enrollment']->id, 'attempt_number' => 1, 'status' => 'in_progress']);
        $attempt->update(['status' => 'graded', 'score' => 90, 'graded_at' => now(), 'graded_by' => $s['instructor']->id]);
        $this->assertEquals('50.00', $s['enrollment']->refresh()->progress_percentage);
        $this->assertTrue($access->isUnlocked($s['enrollment'], $second->topic));
        $attemptTwo = QuizAttempt::create(['quiz_id' => $second->id, 'student_id' => $s['student']->id, 'enrollment_id' => $s['enrollment']->id, 'attempt_number' => 1, 'status' => 'in_progress']);
        $attemptTwo->update(['status' => 'graded', 'score' => 100, 'graded_at' => now(), 'graded_by' => $s['instructor']->id]);
        $this->assertEquals('100.00', $s['enrollment']->refresh()->progress_percentage);
        $this->assertDatabaseHas('certificates', ['enrollment_id' => $s['enrollment']->id]);
        Storage::disk('public')->assertExists($s['enrollment']->fresh()->certificate->qr_code_path);
    }

    public function test_open_answers_are_sent_to_manual_grading(): void
    {
        $s = $this->scenario();
        $quiz = $s['quizzes'][0];
        $question = QuizQuestion::create(['quiz_id' => $quiz->id, 'question_text' => 'Explica tu respuesta', 'question_type' => 'essay', 'points' => 20, 'order' => 1]);
        $attempt = app(AttemptService::class)->start($quiz, $s['student'], $s['enrollment']);
        $submitted = app(AttemptService::class)->submit($attempt, [$question->id => 'Una respuesta argumentada']);
        $this->assertSame('pending_grading', $submitted->status);
        $this->assertNull($submitted->score);
        $this->assertSame('Una respuesta argumentada', $submitted->answers->first()->answer_text);
    }

    public function test_instructor_can_mark_open_answer_incorrect_and_return_an_attempt(): void
    {
        Notification::fake();
        $s = $this->scenario();
        $quiz = $s['quizzes'][0];
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Explica tu respuesta',
            'question_type' => 'essay',
            'points' => 20,
            'order' => 1,
        ]);
        $attempt = app(AttemptService::class)->start($quiz, $s['student'], $s['enrollment']);
        $attempt = app(AttemptService::class)->submit($attempt, [$question->id => 'Respuesta por revisar']);

        $graded = app(AttemptService::class)->gradeManualAnswer($attempt, $s['instructor'], false);

        $this->assertSame('graded', $graded->status);
        $this->assertEquals('0.00', $graded->score);
        $this->assertFalse($graded->answers->first()->is_correct);
        $this->assertSame(2, $quiz->refresh()->availableAttemptsFor($s['student']));
        $this->assertEquals('0.00', $s['enrollment']->refresh()->progress_percentage);
        Notification::assertSentTo($s['student'], ExtraAttemptGranted::class);

        app(AttemptService::class)->gradeAnswer($graded->answers->first(), $s['instructor'], false);
        $this->assertSame(2, $quiz->refresh()->availableAttemptsFor($s['student']));
    }

    public function test_instructor_can_mark_open_answer_correct_from_the_attempt(): void
    {
        Storage::fake('public');
        $s = $this->scenario();
        $quiz = $s['quizzes'][0];
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Explica tu respuesta',
            'question_type' => 'essay',
            'points' => 20,
            'order' => 1,
        ]);
        $attempt = app(AttemptService::class)->start($quiz, $s['student'], $s['enrollment']);
        $attempt = app(AttemptService::class)->submit($attempt, [$question->id => 'Respuesta correcta']);

        $graded = app(AttemptService::class)->gradeManualAnswer($attempt, $s['instructor'], true);

        $this->assertSame('graded', $graded->status);
        $this->assertEquals('100.00', $graded->score);
        $this->assertTrue($graded->answers->first()->is_correct);
        $this->assertEquals('100.00', $s['enrollment']->refresh()->progress_percentage);
    }

    public function test_topic_generates_its_url_identifier_automatically(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $first = Topic::create(['title' => 'Comunicación efectiva', 'description' => 'Uno', 'content' => 'Contenido', 'created_by' => $instructor->id]);
        $second = Topic::create(['title' => 'Comunicación efectiva', 'description' => 'Dos', 'content' => 'Contenido', 'created_by' => $instructor->id]);

        $this->assertSame('comunicacion-efectiva', $first->slug);
        $this->assertSame('comunicacion-efectiva-2', $second->slug);
    }

    public function test_topic_accepts_powerpoint_documents(): void
    {
        $collection = (new Topic)->getMediaCollection('documents');
        $acceptsFile = $collection->acceptsFile;

        $this->assertTrue($acceptsFile(new File('presentacion.ppt', 1024, 'application/vnd.ms-powerpoint')));
        $this->assertTrue($acceptsFile(new File('presentacion.pptx', 1024, 'application/vnd.openxmlformats-officedocument.presentationml.presentation')));
    }

    public function test_each_open_answer_is_graded_individually(): void
    {
        Storage::fake('public');
        $s = $this->scenario();
        $quiz = $s['quizzes'][0];
        $firstQuestion = QuizQuestion::create(['quiz_id' => $quiz->id, 'question_text' => 'Primera respuesta', 'question_type' => 'essay', 'points' => 10, 'order' => 1]);
        $secondQuestion = QuizQuestion::create(['quiz_id' => $quiz->id, 'question_text' => 'Segunda respuesta', 'question_type' => 'essay', 'points' => 10, 'order' => 2]);
        $attempt = app(AttemptService::class)->start($quiz, $s['student'], $s['enrollment']);
        $attempt = app(AttemptService::class)->submit($attempt, [
            $firstQuestion->id => 'Primera',
            $secondQuestion->id => 'Segunda',
        ]);

        app(AttemptService::class)->gradeAnswer($attempt->answers->firstWhere('question_id', $firstQuestion->id), $s['instructor'], true);
        $this->assertSame('pending_grading', $attempt->refresh()->status);

        app(AttemptService::class)->gradeAnswer($attempt->answers->firstWhere('question_id', $secondQuestion->id), $s['instructor'], true);
        $this->assertSame('graded', $attempt->refresh()->status);
        $this->assertEquals('100.00', $attempt->score);
    }
}
