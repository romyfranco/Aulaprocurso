<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

class PreviewQuizController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        $quiz->load(['topic.courses', 'questions.options']);

        return view('quizzes.preview', compact('quiz'));
    }
}
