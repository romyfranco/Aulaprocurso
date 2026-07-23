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
        $loaderStyle = <<<'HTML'
<style data-voranapro-reveal-loader-style>
html,body{background:#0b1115!important}.reveal{opacity:0!important;visibility:hidden!important}.voranapro-reveal-ready .reveal{opacity:1!important;visibility:visible!important;transition:opacity .35s ease}
#voranapro-reveal-loader{position:fixed;inset:0;z-index:2147483647;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 50% 38%,#182238 0,#0b1115 58%);color:#f8fafc;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;transition:opacity .3s ease}
#voranapro-reveal-loader.is-finished{opacity:0;pointer-events:none}.voranapro-loader-card{width:min(440px,92vw);text-align:center}.voranapro-loader-mark{width:58px;height:58px;margin:0 auto 22px;border-radius:18px;display:grid;place-items:center;background:linear-gradient(135deg,#6d5dfc,#22d3b6);box-shadow:0 18px 50px #6757f54d;font-size:24px;font-weight:900}.voranapro-loader-spinner{width:42px;height:42px;margin:0 auto 20px;border:4px solid #ffffff24;border-top-color:#22d3b6;border-right-color:#7c6cff;border-radius:50%;animation:voranapro-reveal-spin .8s linear infinite}.voranapro-loader-title{margin:0;font-size:clamp(20px,3vw,28px);font-weight:800}.voranapro-loader-status{min-height:24px;margin:9px 0 20px;color:#aab6ca;font-size:14px}.voranapro-loader-track{height:9px;overflow:hidden;border-radius:999px;background:#ffffff17;box-shadow:inset 0 0 0 1px #ffffff0d}.voranapro-loader-bar{width:4%;height:100%;border-radius:inherit;background:linear-gradient(90deg,#6757f5,#22d3b6);transition:width .25s ease}.voranapro-loader-percent{margin-top:10px;color:#dbe4f3;font-size:13px;font-variant-numeric:tabular-nums}.voranapro-loader-retry{display:none;margin:18px auto 0;border:0;border-radius:12px;padding:11px 18px;background:#6757f5;color:#fff;font:inherit;font-weight:750;cursor:pointer}.voranapro-reveal-error .voranapro-loader-spinner{animation:none;border-color:#ef4444}.voranapro-reveal-error .voranapro-loader-retry{display:inline-flex}@keyframes voranapro-reveal-spin{to{transform:rotate(360deg)}}@media(prefers-reduced-motion:reduce){.voranapro-loader-spinner{animation-duration:1.8s}.voranapro-loader-bar,.reveal,#voranapro-reveal-loader{transition:none!important}}
</style>
HTML;
        $loaderMarkup = <<<'HTML'
<div id="voranapro-reveal-loader" role="status" aria-live="polite">
    <div class="voranapro-loader-card">
        <div class="voranapro-loader-mark" aria-hidden="true">V</div>
        <div class="voranapro-loader-spinner" aria-hidden="true"></div>
        <p class="voranapro-loader-title">Preparando la presentación</p>
        <p class="voranapro-loader-status">Conectando con los recursos del tema…</p>
        <div class="voranapro-loader-track" aria-hidden="true"><div class="voranapro-loader-bar"></div></div>
        <div class="voranapro-loader-percent">4%</div>
        <button class="voranapro-loader-retry" type="button">Reintentar</button>
    </div>
</div>
HTML;

        if (preg_match('/<head\b[^>]*>/i', $html, $head, PREG_OFFSET_CAPTURE) === 1) {
            $offset = $head[0][1] + strlen($head[0][0]);
            $html = substr($html, 0, $offset)."\n    {$baseTag}\n    {$loaderStyle}".substr($html, $offset);
        } else {
            $html = $baseTag."\n".$loaderStyle."\n".$html;
        }

        if (preg_match('/<body\b[^>]*>/i', $html, $bodyStart, PREG_OFFSET_CAPTURE) === 1) {
            $offset = $bodyStart[0][1] + strlen($bodyStart[0][0]);
            $html = substr($html, 0, $offset)."\n".$loaderMarkup.substr($html, $offset);
        } else {
            $html = $loaderMarkup."\n".$html;
        }

        $parentOrigin = json_encode(
            rtrim(config('reveal.parent_origin'), '/'),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
        );
        $bridge = <<<HTML
<script data-voranapro-reveal-bridge>
(() => {
    const parentOrigin = {$parentOrigin};
    const loader = document.getElementById('voranapro-reveal-loader');
    const progressBar = loader?.querySelector('.voranapro-loader-bar');
    const progressText = loader?.querySelector('.voranapro-loader-percent');
    const statusText = loader?.querySelector('.voranapro-loader-status');
    const retryButton = loader?.querySelector('.voranapro-loader-retry');
    const post = (type, detail = {}) => {
        if (window.parent !== window) {
            window.parent.postMessage({ type, ...detail }, parentOrigin);
        }
    };
    const setProgress = (progress, status) => {
        const value = Math.max(4, Math.min(100, Math.round(progress)));
        if (progressBar) progressBar.style.width = value + '%';
        if (progressText) progressText.textContent = value + '%';
        if (status && statusText) statusText.textContent = status;
        post('voranapro:reveal-progress', { progress: value, status });
    };
    const withTimeout = (promise, timeout) => Promise.race([
        promise,
        new Promise(resolve => window.setTimeout(() => resolve(false), timeout)),
    ]);
    const waitUntil = (predicate, timeout = 90000) => new Promise(resolve => {
        const startedAt = Date.now();
        const check = () => {
            if (predicate()) return resolve(true);
            if (Date.now() - startedAt >= timeout) return resolve(false);
            window.setTimeout(check, 100);
        };
        check();
    });
    const waitForImageEvent = image => withTimeout(new Promise(resolve => {
            if (image.complete) return resolve(image.naturalWidth > 0);
            image.addEventListener('load', () => resolve(true), { once: true });
            image.addEventListener('error', () => resolve(false), { once: true });
        }), 45000);
    const waitForImage = async image => {
        if (await waitForImageEvent(image)) return true;

        const originalSource = image.currentSrc || image.getAttribute('src');
        if (!originalSource) return false;

        for (let attempt = 1; attempt <= 2; attempt += 1) {
            const retryUrl = new URL(originalSource, document.baseURI);
            retryUrl.searchParams.set('voranapro_retry', String(attempt));
            image.removeAttribute('srcset');
            image.src = retryUrl.href;
            if (await waitForImageEvent(image)) return true;
        }

        return false;
    };
    const preloadImage = url => withTimeout(new Promise(resolve => {
        const image = new Image();
        image.onload = () => resolve(true);
        image.onerror = () => resolve(false);
        image.src = url;
    }), 60000);
    const waitForStylesheet = link => {
        if (link.sheet) return Promise.resolve(true);
        return withTimeout(new Promise(resolve => {
            link.addEventListener('load', () => resolve(true), { once: true });
            link.addEventListener('error', () => resolve(false), { once: true });
        }), 60000);
    };
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
        if (event.origin === parentOrigin && event.data === 'voranapro:reveal-layout') {
            refresh();
        }
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refresh();
    });
    retryButton?.addEventListener('click', () => window.location.reload());

    const prepare = async () => {
        try {
            setProgress(12, 'Cargando estructura y estilos…');
            if (document.readyState !== 'complete') {
                await withTimeout(new Promise(resolve => window.addEventListener('load', () => resolve(true), { once: true })), 90000);
            }

            setProgress(28, 'Iniciando Reveal.js…');
            const revealAvailable = await waitUntil(() => window.Reveal && typeof window.Reveal.layout === 'function');
            if (!revealAvailable) throw new Error('Reveal.js no pudo iniciarse.');

            const revealReady = await waitUntil(() => typeof window.Reveal.isReady === 'function' && window.Reveal.isReady());
            if (!revealReady) throw new Error('La presentación tardó demasiado en inicializarse.');

            setProgress(46, 'Verificando estilos…');
            const stylesheetResults = await Promise.all([...document.querySelectorAll('link[rel="stylesheet"]')].map(waitForStylesheet));
            if (stylesheetResults.some(result => result !== true)) throw new Error('No se pudieron cargar todos los estilos.');

            setProgress(58, 'Preparando tipografías…');
            if (document.fonts?.ready) {
                const fontsReady = await withTimeout(document.fonts.ready.then(() => true), 30000);
                if (!fontsReady) throw new Error('Las tipografías tardaron demasiado en cargar.');
            }

            const mediaElements = [...document.querySelectorAll('video, audio')]
                .filter(element => element.currentSrc || element.getAttribute('src') || element.querySelector('source[src]'));
            if (mediaElements.length) {
                setProgress(62, 'Preparando audio y video…');
                const mediaResults = await Promise.all(mediaElements.map(element => {
                    if (element.readyState >= 1) return Promise.resolve(true);
                    return withTimeout(new Promise(resolve => {
                        element.addEventListener('loadedmetadata', () => resolve(true), { once: true });
                        element.addEventListener('error', () => resolve(false), { once: true });
                        element.load();
                    }), 30000);
                }));
                if (mediaResults.some(result => result !== true)) throw new Error('No se pudieron preparar todos los recursos multimedia.');
            }

            const images = [...document.images]
                .filter(image => image.currentSrc || image.getAttribute('src'));
            images.forEach(image => image.loading = 'eager');
            const backgroundUrls = [...document.querySelectorAll('[data-background-image], img[data-src]')]
                .map(element => element.getAttribute('data-background-image') || element.getAttribute('data-src'))
                .filter(Boolean);
            const tasks = [
                ...images.map(waitForImage),
                ...[...new Set(backgroundUrls)].map(preloadImage),
            ];
            let completed = 0;
            let failed = 0;
            setProgress(tasks.length ? 65 : 92, tasks.length ? 'Cargando imágenes del curso…' : 'Ajustando la presentación…');
            await Promise.all(tasks.map(task => Promise.resolve(task).then(result => {
                completed += 1;
                if (result !== true) failed += 1;
                setProgress(65 + (completed / tasks.length) * 28, 'Cargando imágenes ' + completed + ' de ' + tasks.length + '…');
            })));
            if (failed > 0) {
                setProgress(94, 'Finalizando; ' + failed + ' recursos visuales no respondieron…');
            }

            setProgress(96, 'Ajustando la presentación…');
            refresh();
            await new Promise(resolve => window.setTimeout(resolve, 350));
            document.documentElement.classList.add('voranapro-reveal-ready');
            setProgress(100, 'Presentación lista');
            post('voranapro:reveal-ready', { progress: 100 });
            window.setTimeout(() => {
                loader?.classList.add('is-finished');
                window.setTimeout(() => loader?.remove(), 350);
            }, 250);
        } catch (error) {
            const message = error instanceof Error ? error.message : 'No se pudo completar la carga.';
            document.documentElement.classList.add('voranapro-reveal-error');
            if (statusText) statusText.textContent = message;
            post('voranapro:reveal-error', { status: message });
        }
    };

    refresh();
    prepare();
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
