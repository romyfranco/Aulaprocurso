<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Services\TopicPresentationAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ViewTopicPdfPresentationController extends Controller
{
    public function __invoke(Request $request, Topic $topic, TopicPresentationAccessService $access): View
    {
        $access->authorize($request->user(), $topic);

        $presentation = $topic->getFirstMedia('presentation_pdf');
        abort_unless($presentation, 404, 'Este tema no tiene una presentación PDF disponible.');

        $returnUrl = match ($request->user()->role) {
            'admin' => url('/admin/topics/'.$topic->getKey()),
            'instructor' => url('/instructor/topics/'.$topic->getKey()),
            default => url('/student/topics/'.$topic->getKey()),
        };

        return view('presentations.pdf', compact('topic', 'presentation', 'returnUrl'));
    }
}
