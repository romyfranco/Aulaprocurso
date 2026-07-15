<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptGrant;
use App\Models\QuizQuestionOption;
use App\Models\User;
use App\Notifications\ExtraAttemptGranted;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttemptService
{
    public function start(Quiz $quiz, User $student, Enrollment $enrollment): QuizAttempt
    {
        if ($enrollment->student_id !== $student->id || ! $quiz->topic->courses()->whereKey($enrollment->course_id)->exists()) {
            throw ValidationException::withMessages(['quiz' => 'La evaluación no pertenece a esta matrícula.']);
        }
        if ($quiz->availableAttemptsFor($student) < 1) {
            throw ValidationException::withMessages(['attempts' => 'No tienes intentos disponibles. Solicita uno extra a tu instructor.']);
        }

        return DB::transaction(function () use ($quiz, $student, $enrollment) {
            $number = $quiz->attempts()->where('student_id', $student->id)->lockForUpdate()->count() + 1;

            return QuizAttempt::create([
                'quiz_id' => $quiz->id, 'student_id' => $student->id, 'enrollment_id' => $enrollment->id,
                'attempt_number' => $number, 'started_at' => now(), 'status' => 'in_progress',
            ]);
        });
    }

    public function submit(QuizAttempt $attempt, array $responses): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $responses) {
            $questions = $attempt->quiz->questions()->with('options')->get();
            $manual = false;
            $earned = 0;
            $possible = max(1, $questions->sum('points'));

            foreach ($questions as $question) {
                $value = $responses[$question->id] ?? null;
                $answer = ['question_id' => $question->id];
                if ($question->requiresManualGrading()) {
                    $manual = true;
                    $answer['answer_text'] = is_scalar($value) ? (string) $value : null;
                } else {
                    $selected = QuizQuestionOption::query()->whereKey($value)->where('question_id', $question->id)->first();
                    $correct = (bool) $selected?->is_correct;
                    $answer += ['selected_option_id' => $selected?->id, 'is_correct' => $correct, 'score_awarded' => $correct ? $question->points : 0];
                    $earned += $correct ? $question->points : 0;
                }
                $attempt->answers()->updateOrCreate(['question_id' => $question->id], $answer);
            }

            $attempt->update([
                'submitted_at' => now(),
                'status' => $manual ? 'pending_grading' : 'graded',
                'score' => $manual ? null : round(($earned / $possible) * 100, 2),
                'graded_at' => $manual ? null : now(),
            ]);

            return $attempt->refresh();
        });
    }

    public function grant(Quiz $quiz, User $student, User $instructor, int $extraAttempts = 1, ?string $reason = null): QuizAttemptGrant
    {
        if ($instructor->role !== 'instructor' || ! $instructor->coursesAsInstructor()->whereHas('topics', fn ($q) => $q->whereKey($quiz->topic_id))->exists()) {
            throw ValidationException::withMessages(['instructor' => 'No puedes otorgar intentos para este curso.']);
        }
        $grant = QuizAttemptGrant::query()->firstOrNew(['quiz_id' => $quiz->id, 'student_id' => $student->id]);
        $grant->fill(['extra_attempts' => ($grant->extra_attempts ?? 0) + max(1, $extraAttempts), 'granted_by' => $instructor->id, 'reason' => $reason, 'granted_at' => now()])->save();
        $student->notify(new ExtraAttemptGranted($quiz, $grant->extra_attempts, $reason));

        return $grant;
    }

    public function gradeManualAnswer(QuizAttempt $attempt, User $instructor, bool $isCorrect, ?string $feedback = null): QuizAttempt
    {
        $attempt->loadMissing('answers.question');

        foreach ($attempt->answers as $answer) {
            if ($answer->question->requiresManualGrading()) {
                $this->gradeAnswer($answer, $instructor, $isCorrect, $feedback);
            }
        }

        return $attempt->refresh();
    }

    public function gradeAnswer(QuizAnswer $answer, User $instructor, bool $isCorrect, ?string $feedback = null): QuizAnswer
    {
        $answer->loadMissing('attempt.quiz.questions', 'attempt.student', 'question');
        $attempt = $answer->attempt;

        if (! $answer->question->requiresManualGrading()) {
            throw ValidationException::withMessages(['answer' => 'Esta respuesta se califica automáticamente.']);
        }

        if ($instructor->role !== 'instructor' || ! $instructor->coursesAsInstructor()->whereHas('topics', fn ($query) => $query->whereKey($attempt->quiz->topic_id))->exists()) {
            throw ValidationException::withMessages(['instructor' => 'No puedes calificar esta evaluación.']);
        }

        $shouldGrantAttempt = ! $isCorrect && ! $attempt->extra_attempt_granted_at;

        DB::transaction(function () use ($answer, $attempt, $instructor, $isCorrect, $feedback, $shouldGrantAttempt): void {
            $answer->update([
                'is_correct' => $isCorrect,
                'score_awarded' => $isCorrect ? $answer->question->points : 0,
                'graded_by' => $instructor->id,
                'graded_at' => now(),
            ]);

            $hasPendingAnswers = $attempt->answers()
                ->whereHas('question', fn ($query) => $query->whereIn('question_type', ['short_answer', 'essay']))
                ->whereNull('is_correct')
                ->exists();
            $possible = max(1, $attempt->quiz->questions->sum('points'));
            $earned = (float) $attempt->answers()->sum('score_awarded');
            $attempt->update([
                'score' => $hasPendingAnswers ? null : round(($earned / $possible) * 100, 2),
                'status' => $hasPendingAnswers ? 'pending_grading' : 'graded',
                'graded_by' => $hasPendingAnswers ? null : $instructor->id,
                'graded_at' => $hasPendingAnswers ? null : now(),
                'instructor_feedback' => $feedback ?: ($isCorrect ? 'Respuesta correcta.' : 'Respuesta incorrecta. Se habilitó un nuevo intento.'),
                'extra_attempt_granted_at' => $shouldGrantAttempt ? now() : $attempt->extra_attempt_granted_at,
            ]);
        });

        if ($shouldGrantAttempt) {
            $this->grant($attempt->quiz, $attempt->student, $instructor, 1, 'Nuevo intento por respuesta incorrecta.');
        }

        return $answer->refresh();
    }
}
