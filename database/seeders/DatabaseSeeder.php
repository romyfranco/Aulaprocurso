<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\ProgressService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('demo12345');
        $admin = User::create(['name' => 'Laura Administradora', 'email' => 'admin@aulapro.test', 'password' => $password, 'role' => 'admin', 'bio' => 'Responsable de la operación académica.']);
        $instructor = User::create(['name' => 'Carlos Mendoza', 'email' => 'instructor@aulapro.test', 'password' => $password, 'role' => 'instructor', 'bio' => 'Facilitador en liderazgo y comunicación.']);
        $student = User::create(['name' => 'Sofía Ramírez', 'email' => 'estudiante@aulapro.test', 'password' => $password, 'role' => 'student']);

        $course = Course::create(['title' => 'Liderazgo de equipos', 'slug' => 'liderazgo-de-equipos', 'description' => 'Desarrolla habilidades prácticas para guiar equipos, dar retroalimentación y tomar decisiones con claridad.', 'status' => 'published', 'estimated_duration_hours' => 12, 'created_by' => $admin->id]);
        $course->instructors()->attach($instructor->id, ['assigned_at' => now()]);

        $topics = collect([
            ['title' => 'Fundamentos del liderazgo', 'slug' => 'fundamentos-liderazgo', 'description' => 'Principios para liderar con propósito.', 'content' => '<h2>Liderar es crear contexto</h2><p>Un buen liderazgo combina dirección, escucha y coherencia. En este tema reconocerás los comportamientos que generan confianza.</p>'],
            ['title' => 'Conversaciones efectivas', 'slug' => 'conversaciones-efectivas', 'description' => 'Herramientas para comunicar con claridad.', 'content' => '<h2>Conversaciones que mueven al equipo</h2><p>Prepara el objetivo, escucha activamente y convierte los acuerdos en acciones observables.</p>'],
            ['title' => 'Decisiones y seguimiento', 'slug' => 'decisiones-seguimiento', 'description' => 'Cierra ciclos y mide resultados.', 'content' => '<h2>Decidir y aprender</h2><p>Una decisión necesita responsable, fecha y criterio de éxito. El seguimiento convierte intenciones en resultados.</p>'],
        ])->map(fn ($data) => Topic::create($data + ['created_by' => $instructor->id]));
        foreach ($topics as $index => $topic) {
            $course->topics()->attach($topic->id, ['order' => $index + 1]);
        }

        $quizOne = Quiz::create(['topic_id' => $topics[0]->id, 'title' => 'Evaluación: fundamentos', 'instructions' => 'Selecciona la mejor respuesta.', 'passing_score' => 70, 'max_attempts' => 2]);
        $q1 = QuizQuestion::create(['quiz_id' => $quizOne->id, 'question_text' => '¿Qué comportamiento fortalece más la confianza del equipo?', 'question_type' => 'multiple_choice', 'points' => 10, 'order' => 1]);
        $q1->options()->createMany([
            ['option_text' => 'Mantener acuerdos y explicar decisiones', 'is_correct' => true, 'order' => 1],
            ['option_text' => 'Evitar conversaciones difíciles', 'is_correct' => false, 'order' => 2],
            ['option_text' => 'Cambiar prioridades sin contexto', 'is_correct' => false, 'order' => 3],
        ]);
        $quizTwo = Quiz::create(['topic_id' => $topics[1]->id, 'title' => 'Práctica: conversación efectiva', 'instructions' => 'Responde con un ejemplo concreto.', 'passing_score' => 70, 'max_attempts' => 2]);
        QuizQuestion::create(['quiz_id' => $quizTwo->id, 'question_text' => 'Describe cómo darías retroalimentación ante un acuerdo incumplido.', 'question_type' => 'essay', 'points' => 20, 'order' => 1]);
        $quizThree = Quiz::create(['topic_id' => $topics[2]->id, 'title' => 'Evaluación final', 'passing_score' => 70, 'max_attempts' => 2]);
        $q3 = QuizQuestion::create(['quiz_id' => $quizThree->id, 'question_text' => 'Toda decisión debe tener responsable y criterio de éxito.', 'question_type' => 'true_false', 'points' => 10, 'order' => 1]);
        $q3->options()->createMany([['option_text' => 'Verdadero', 'is_correct' => true, 'order' => 1], ['option_text' => 'Falso', 'is_correct' => false, 'order' => 2]]);

        $enrollment = Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'enrolled_at' => now()->subDays(14), 'progress_percentage' => 0, 'status' => 'active']);
        $attemptOne = QuizAttempt::create(['quiz_id' => $quizOne->id, 'student_id' => $student->id, 'enrollment_id' => $enrollment->id, 'attempt_number' => 1, 'started_at' => now()->subDays(8), 'submitted_at' => now()->subDays(8), 'score' => 100, 'status' => 'graded', 'graded_by' => $instructor->id, 'graded_at' => now()->subDays(8), 'instructor_feedback' => 'Excelente comprensión.']);
        $attemptOne->answers()->create(['question_id' => $q1->id, 'selected_option_id' => $q1->options()->where('is_correct', true)->value('id'), 'is_correct' => true, 'score_awarded' => 10, 'graded_by' => $instructor->id, 'graded_at' => now()->subDays(8)]);
        $attemptTwo = QuizAttempt::create(['quiz_id' => $quizTwo->id, 'student_id' => $student->id, 'enrollment_id' => $enrollment->id, 'attempt_number' => 1, 'started_at' => now()->subHour(), 'submitted_at' => now()->subMinutes(45), 'status' => 'pending_grading']);
        $attemptTwo->answers()->create(['question_id' => $quizTwo->questions()->value('id'), 'answer_text' => 'Primero describiría el acuerdo y el impacto, escucharía su perspectiva y cerraría con una nueva fecha verificable.']);
        app(AttemptService::class)->grant($quizTwo, $student, $instructor, 1, 'Práctica adicional para afianzar el aprendizaje.');
        app(ProgressService::class)->recalculate($enrollment);

        $shortCourse = Course::create(['title' => 'Comunicación esencial', 'slug' => 'comunicacion-esencial', 'description' => 'Microcurso para expresar ideas con claridad y escuchar activamente.', 'status' => 'published', 'estimated_duration_hours' => 2, 'created_by' => $admin->id]);
        $shortCourse->instructors()->attach($instructor->id, ['assigned_at' => now()]);
        $shortTopic = Topic::create(['title' => 'Escucha activa', 'slug' => 'escucha-activa', 'description' => 'Claves de una escucha presente.', 'content' => '<p>Escuchar es verificar lo comprendido antes de responder.</p>', 'created_by' => $instructor->id]);
        $shortCourse->topics()->attach($shortTopic->id, ['order' => 1]);
        $shortQuiz = Quiz::create(['topic_id' => $shortTopic->id, 'title' => 'Comprobación de aprendizaje', 'passing_score' => 70, 'max_attempts' => 2]);
        $shortQuestion = QuizQuestion::create(['quiz_id' => $shortQuiz->id, 'question_text' => 'Parafrasear ayuda a verificar lo comprendido.', 'question_type' => 'true_false', 'points' => 10, 'order' => 1]);
        $shortQuestion->options()->createMany([['option_text' => 'Verdadero', 'is_correct' => true, 'order' => 1], ['option_text' => 'Falso', 'is_correct' => false, 'order' => 2]]);
        $completed = Enrollment::create(['student_id' => $student->id, 'course_id' => $shortCourse->id, 'enrolled_at' => now()->subMonth(), 'progress_percentage' => 0, 'status' => 'active']);
        QuizAttempt::create(['quiz_id' => $shortQuiz->id, 'student_id' => $student->id, 'enrollment_id' => $completed->id, 'attempt_number' => 1, 'started_at' => now()->subWeeks(3), 'submitted_at' => now()->subWeeks(3), 'score' => 100, 'status' => 'graded', 'graded_by' => $instructor->id, 'graded_at' => now()->subWeeks(3)]);
        app(ProgressService::class)->recalculate($completed);
    }
}
