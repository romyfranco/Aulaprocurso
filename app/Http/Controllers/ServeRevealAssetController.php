<?php

namespace App\Http\Controllers;

use App\Models\RevealPresentation;
use App\Services\RevealAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ServeRevealAssetController extends Controller
{
    public function __invoke(Request $request, RevealAccessService $access, string $token, ?string $path = null): Response
    {
        $payload = $access->resolve($token);
        abort_unless($payload, 404);

        $presentation = RevealPresentation::query()->find($payload['presentation_id']);
        abort_unless($presentation?->isReady() && hash_equals($presentation->version, $payload['version']), 404);

        if ($path === null) {
            $entryPath = implode('/', array_map('rawurlencode', explode('/', $presentation->entry_path)));

            return redirect()->away(rtrim(config('reveal.url'), '/').'/p/'.$token.'/'.$entryPath);
        }

        $relativePath = $this->normalizeRequestedPath($path);
        $disk = Storage::disk(config('reveal.disk'));
        $basePath = realpath($disk->path($presentation->storage_path));
        $filePath = realpath($disk->path($presentation->storage_path.'/'.$relativePath));

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
            'Cache-Control' => in_array($extension, ['html', 'htm'], true)
                ? 'private, no-store'
                : 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Content-Security-Policy' => $this->contentSecurityPolicy(),
        ];

        if (in_array($extension, ['html', 'htm'], true)) {
            $html = file_get_contents($filePath);
            abort_unless(is_string($html), 404);

            return response($this->prepareHtml($html, $token, $relativePath), 200, $headers);
        }

        return response()->file($filePath, $headers);
    }

    private function prepareHtml(string $html, string $token, string $relativePath): string
    {
        $directory = dirname($relativePath);
        $directory = $directory === '.' ? '' : trim(str_replace('\\', '/', $directory), '/').'/';
        $baseUrl = rtrim(config('reveal.url'), '/').'/p/'.$token.'/'.$directory;
        $baseTag = '<base href="'.htmlspecialchars($baseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">';

        if (preg_match('/<head\b[^>]*>/i', $html, $head, PREG_OFFSET_CAPTURE) === 1) {
            $offset = $head[0][1] + strlen($head[0][0]);
            $html = substr($html, 0, $offset)."\n    {$baseTag}".substr($html, $offset);
        } else {
            $html = $baseTag."\n".$html;
        }

        $parentOrigin = json_encode(
            rtrim(config('reveal.parent_origin'), '/'),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
        );
        $bridge = <<<HTML
<script data-voranapro-reveal-bridge>
(() => {
    const layout = () => {
        if (window.Reveal && typeof window.Reveal.layout === 'function') {
            window.Reveal.layout();
        }
    };
    const refresh = () => {
        window.requestAnimationFrame(layout);
        window.setTimeout(layout, 150);
        window.setTimeout(layout, 600);
    };
    window.addEventListener('load', refresh, { once: true });
    window.addEventListener('resize', refresh);
    window.addEventListener('pageshow', refresh);
    window.addEventListener('message', event => {
        if (event.origin === {$parentOrigin} && event.data === 'voranapro:reveal-layout') {
            refresh();
        }
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refresh();
    });
    refresh();
})();
</script>
HTML;

        if (preg_match('/<\/body\s*>/i', $html, $body, PREG_OFFSET_CAPTURE) === 1) {
            $offset = $body[0][1];

            return substr($html, 0, $offset).$bridge."\n".substr($html, $offset);
        }

        return $html."\n".$bridge;
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
