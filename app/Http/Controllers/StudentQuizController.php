<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\StudentQuizAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentQuizController extends Controller
{
    public function show(Quiz $quiz, Request $request, StudentQuizAccessService $access): View
    {
        $student = $this->student($request);
        $this->unlockedEnrollment($quiz, $student, $access);
        abort_if($quiz->availableAttemptsFor($student) < 1, 403, 'No tienes intentos disponibles para esta evaluación.');

        $quiz->load(['topic', 'questions.options']);

        return view('quizzes.take', compact('quiz', 'student'));
    }

    public function submit(
        Quiz $quiz,
        Request $request,
        StudentQuizAccessService $access,
        AttemptService $attempts,
    ): RedirectResponse {
        $student = $this->student($request);
        $enrollment = $this->unlockedEnrollment($quiz, $student, $access);
        abort_if($quiz->availableAttemptsFor($student) < 1, 403, 'No tienes intentos disponibles para esta evaluación.');

        $quiz->load('questions.options');
        $responses = $request->validate($this->rules($quiz), [
            'responses.*.required' => 'Debes responder esta pregunta.',
            'responses.*.string' => 'La respuesta debe ser texto.',
            'responses.*.max' => 'La respuesta supera la extensión permitida.',
            'responses.*.integer' => 'Selecciona una opción válida.',
            'responses.*.exists' => 'La opción seleccionada no pertenece a esta pregunta.',
        ])['responses'];

        $attempt = $attempts->start($quiz, $student, $enrollment);
        $attempt = $attempts->submit($attempt, $responses);

        return redirect()->route('student.quizzes.result', $attempt);
    }

    public function result(QuizAttempt $attempt, Request $request): View
    {
        $student = $this->student($request);
        abort_unless($attempt->student_id === $student->id, 403);

        $attempt->load(['quiz.topic', 'answers.question', 'answers.selectedOption']);
        $attemptsLeft = $attempt->quiz->availableAttemptsFor($student);

        return view('quizzes.result', compact('attempt', 'attemptsLeft'));
    }

    private function student(Request $request): User
    {
        $user = $request->user();
        abort_unless($user?->role === 'student', 403);

        return $user;
    }

    private function unlockedEnrollment(Quiz $quiz, User $student, StudentQuizAccessService $access): Enrollment
    {
        $enrollment = $access->unlockedEnrollment($quiz, $student);
        abort_unless($enrollment, 403, 'Esta evaluación todavía está bloqueada.');

        return $enrollment;
    }

    private function rules(Quiz $quiz): array
    {
        $rules = ['responses' => ['required', 'array']];

        foreach ($quiz->questions as $question) {
            $rules['responses.'.$question->id] = match ($question->question_type) {
                'multiple_choice', 'true_false' => [
                    'required',
                    'integer',
                    Rule::exists('quiz_question_options', 'id')->where('question_id', $question->id),
                ],
                'essay' => ['required', 'string', 'max:10000'],
                default => ['required', 'string', 'max:2000'],
            };
        }

        return $rules;
    }
}
