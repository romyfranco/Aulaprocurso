<?php

namespace App\Http\Controllers;

use App\Models\RevealPresentation;
use App\Services\RevealAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServeRevealAssetController extends Controller
{
    public function __invoke(Request $request, RevealAccessService $access, string $token, ?string $path = null): BinaryFileResponse
    {
        $payload = $access->resolve($token);
        abort_unless($payload, 404);

        $presentation = RevealPresentation::query()->find($payload['presentation_id']);
        abort_unless($presentation?->isReady() && hash_equals($presentation->version, $payload['version']), 404);

        $relativePath = $this->normalizeRequestedPath($path ?: $presentation->entry_path);
        $basePath = realpath(Storage::disk('local')->path($presentation->storage_path));
        $filePath = realpath(Storage::disk('local')->path($presentation->storage_path.'/'.$relativePath));

        abort_unless(
            $basePath &&
            $filePath &&
            is_file($filePath) &&
            str_starts_with($filePath, $basePath.DIRECTORY_SEPARATOR),
            404,
        );

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeType = config("reveal.mime_types.{$extension}");
        abort_unless(is_string($mimeType), 404);

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addslashes(basename($filePath)).'"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Content-Security-Policy' => $this->contentSecurityPolicy(),
        ];

        return response()->file($filePath, $headers);
    }

    private function normalizeRequestedPath(string $path): string
    {
        $path = str_replace('\\', '/', rawurldecode($path));

        abort_if(str_contains($path, "\0") || str_starts_with($path, '/'), 404);

        $parts = explode('/', $path);
        abort_if(collect($parts)->contains(fn (string $part) => $part === '' || $part === '.' || $part === '..'), 404);

        return implode('/', $parts);
    }

    private function contentSecurityPolicy(): string
    {
        $parentOrigin = rtrim(config('reveal.parent_origin'), '/');

        return implode('; ', [
            "default-src 'self' https: data: blob:",
            "script-src 'self' https: 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' https: 'unsafe-inline'",
            "img-src 'self' https: data: blob:",
            "font-src 'self' https: data:",
            "media-src 'self' https: data: blob:",
            "connect-src 'self' https:",
            "frame-src 'self' https:",
            "worker-src 'self' blob:",
            "frame-ancestors {$parentOrigin}",
            "form-action 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ]).';';
    }
}
