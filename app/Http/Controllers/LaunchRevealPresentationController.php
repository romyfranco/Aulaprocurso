<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Topic;
use App\Services\RevealAccessService;
use App\Services\TopicAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LaunchRevealPresentationController extends Controller
{
    public function __invoke(Request $request, Topic $topic, RevealAccessService $access, TopicAccessService $topicAccess): RedirectResponse
    {
        Gate::authorize('view', $topic);

        $user = $request->user();

        if ($user->role === 'student') {
            $canOpen = Enrollment::query()
                ->where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->whereHas('course.topics', fn ($query) => $query->whereKey($topic->id))
                ->with('course')
                ->get()
                ->contains(fn (Enrollment $enrollment) => $topicAccess->isUnlocked($enrollment, $topic));

            abort_unless($canOpen, 403, 'Este tema todavía está bloqueado.');
        }

        $presentation = $topic->revealPresentation;
        abort_unless($presentation?->isReady(), 404, 'Este tema no tiene una presentación disponible.');

        $token = $access->issue($user, $presentation);
        $entryPath = implode('/', array_map('rawurlencode', explode('/', $presentation->entry_path)));
        $url = rtrim(config('reveal.url'), '/').'/p/'.$token.'/'.$entryPath;

        return redirect()->away($url);
    }
}
