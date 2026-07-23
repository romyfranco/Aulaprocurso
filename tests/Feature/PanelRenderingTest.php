<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PanelRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_login_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('VoranaPro');
        $this->get('/login')->assertOk()->assertSee('Un acceso para');
        $this->get('/admin/login')->assertRedirect('/login');
        $this->get('/instructor/login')->assertRedirect('/login');
        $this->get('/student/login')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/instructor')->assertRedirect('/login');
        $this->get('/student')->assertRedirect('/login');
    }

    public function test_login_redirects_each_role_to_its_panel(): void
    {
        foreach (['admin', 'instructor', 'student'] as $role) {
            $user = User::factory()->create([
                'email' => $role.'@example.test',
                'password' => 'password',
                'role' => $role,
            ]);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect('/'.$role);

            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'incorrecta',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_panel_and_resources_render(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->get('/admin')->assertOk();
        foreach (['users', 'courses', 'topics', 'enrollments', 'quizzes', 'certificates'] as $resource) {
            $this->actingAs($user)->get('/admin/'.$resource)->assertOk();
        }
    }

    public function test_role_specific_panels_render(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($instructor)->get('/instructor')->assertOk();
        foreach (['courses', 'topics', 'quizzes', 'quiz-attempts', 'enrollments'] as $resource) {
            $this->actingAs($instructor)->get('/instructor/'.$resource)->assertOk();
        }
        $this->actingAs($student)->get('/student')->assertOk();
        foreach (['enrollments', 'quizzes', 'certificates'] as $resource) {
            $this->actingAs($student)->get('/student/'.$resource)->assertOk();
        }
        $this->actingAs($student)->get('/admin')->assertForbidden();
        $this->actingAs($instructor)->get('/student')->assertForbidden();
    }

    public function test_detail_cards_render_with_real_demo_data(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::where('role', 'admin')->firstOrFail();
        $instructor = User::where('role', 'instructor')->firstOrFail();
        $student = User::where('role', 'student')->firstOrFail();
        $certificate = Certificate::firstOrFail();
        foreach (['/admin/courses/1', '/admin/topics/1', '/admin/quizzes/1', '/admin/enrollments/1', '/admin/certificates/'.$certificate->certificate_code] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->actingAs($instructor)->get('/instructor/courses/1')->assertOk();
        $this->actingAs($instructor)->get('/instructor/quizzes/1')->assertOk();
        $this->actingAs($instructor)->get('/instructor/quiz-attempts/2')
            ->assertOk()
            ->assertSee('Correcta')
            ->assertSee('Incorrecta + nuevo intento')
            ->assertDontSee('Ajustar puntaje');
        $this->actingAs($student)->get('/student/enrollments/1')->assertOk();
        $this->actingAs($student)->get('/student/quizzes/1')
            ->assertOk()
            ->assertSee('Presentar evaluación')
            ->assertSee('¿Qué comportamiento fortalece más la confianza del equipo?')
            ->assertSee('Mantener acuerdos y explicar decisiones')
            ->assertDontSee('Evitar conversaciones difíciles')
            ->assertDontSee('Cambiar prioridades sin contexto');
        $this->actingAs($student)->get('/student/certificates/'.$certificate->certificate_code)->assertOk();
    }

    public function test_management_forms_render(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::where('role', 'admin')->firstOrFail();
        $instructor = User::where('role', 'instructor')->firstOrFail();
        foreach (['/admin/users/create', '/admin/courses/1/edit', '/admin/topics/1/edit', '/admin/quizzes/1/edit', '/admin/enrollments/1/edit'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        foreach (['/instructor/topics/1/edit', '/instructor/quizzes/1/edit', '/instructor/quiz-attempts/2/edit'] as $url) {
            $this->actingAs($instructor)->get($url)->assertOk();
        }
        $this->actingAs($instructor)->get('/instructor/topics/create')
            ->assertOk()
            ->assertDontSee('Identificador URL');
    }

    public function test_student_sees_incorrect_answers_with_a_red_result(): void
    {
        Storage::fake('public');
        $this->seed();
        $student = User::where('role', 'student')->firstOrFail();
        $quiz = Quiz::findOrFail(1);
        $enrollment = Enrollment::where('student_id', $student->id)->where('course_id', 1)->firstOrFail();
        $question = $quiz->questions()->with('options')->firstOrFail();
        $incorrectOption = $question->options->firstWhere('is_correct', false);
        $attempt = app(AttemptService::class)->start($quiz, $student, $enrollment);
        app(AttemptService::class)->submit($attempt, [$question->id => $incorrectOption->id]);

        $this->actingAs($student)->get('/student/quizzes/1')
            ->assertOk()
            ->assertSee($question->question_text)
            ->assertSee($incorrectOption->option_text)
            ->assertSee('Incorrecta')
            ->assertSee('fi-color-danger', false);
    }
}
