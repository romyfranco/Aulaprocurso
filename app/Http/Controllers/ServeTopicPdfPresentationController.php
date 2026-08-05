<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Services\TopicPresentationAccessService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ServeTopicPdfPresentationController extends Controller
{
    public function __invoke(Request $request, Topic $topic, TopicPresentationAccessService $access): BinaryFileResponse
    {
        $access->authorize($request->user(), $topic);

        $presentation = $topic->getFirstMedia('presentation_pdf');
        abort_unless($presentation && $presentation->mime_type === 'application/pdf', 404);

        $path = $presentation->getPath();
        abort_unless(is_file($path), 404);

        $fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', $presentation->file_name) ?: 'presentacion.pdf';

        $response = response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition('inline', $presentation->file_name, $fallbackName),
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
        ]);

        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
