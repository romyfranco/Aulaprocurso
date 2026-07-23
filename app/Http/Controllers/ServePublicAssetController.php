<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ServePublicAssetController extends Controller
{
    public function __invoke(string $path): Response
    {
        $relativePath = $this->normalizeRequestedPath($path);
        $disk = Storage::disk('public');
        $basePath = realpath($disk->path(''));
        $filePath = realpath($disk->path($relativePath));

        abort_unless(
            $basePath &&
            $filePath &&
            is_file($filePath) &&
            str_starts_with($filePath, $basePath.DIRECTORY_SEPARATOR),
            404,
        );

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeType = $this->mimeTypes()[$extension] ?? null;
        abort_unless(is_string($mimeType), 404);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addslashes(basename($filePath)).'"',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
        ]);
    }

    private function normalizeRequestedPath(string $path): string
    {
        $path = str_replace('\\', '/', rawurldecode($path));

        abort_if(str_contains($path, "\0") || str_starts_with($path, '/'), 404);

        $parts = explode('/', $path);
        abort_if(collect($parts)->contains(fn (string $part) => $part === '' || $part === '.' || $part === '..' || str_starts_with($part, '.')), 404);

        return implode('/', $parts);
    }

    /** @return array<string, string> */
    private function mimeTypes(): array
    {
        return [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'avif' => 'image/avif',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];
    }
}
