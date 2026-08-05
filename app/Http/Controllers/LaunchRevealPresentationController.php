<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Services\RevealAccessService;
use App\Services\TopicPresentationAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LaunchRevealPresentationController extends Controller
{
    public function __invoke(Request $request, Topic $topic, RevealAccessService $access, TopicPresentationAccessService $topicPresentationAccess): RedirectResponse
    {
        $user = $request->user();
        $topicPresentationAccess->authorize($user, $topic);

        $presentation = $topic->revealPresentation;
        abort_unless($presentation?->isReady(), 404, 'Este tema no tiene una presentación disponible.');

        $token = $access->issue($user, $presentation);
        $entryPath = implode('/', array_map('rawurlencode', explode('/', $presentation->entry_path)));
        $url = rtrim(config('reveal.url'), '/').'/p/'.$token.'/'.$entryPath;

        return redirect()->away($url);
    }
}
