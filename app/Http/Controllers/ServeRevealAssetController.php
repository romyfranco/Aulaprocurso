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
            $html = $this->inlineCriticalAssets($html, $presentation->storage_path, $relativePath);

            return response($this->prepareHtml($html, $token, $relativePath), 200, $headers);
        }

        return response()->file($filePath, $headers);
    }

    private function inlineCriticalAssets(string $html, string $storagePath, string $relativePath): string
    {
        $html = preg_replace_callback('/<link\b(?<attributes>[^>]*)>/i', function (array $match) use ($storagePath, $relativePath): string {
            $attributes = $match['attributes'];
            $relationship = strtolower($this->htmlAttribute($attributes, 'rel') ?? '');
            $source = $this->htmlAttribute($attributes, 'href');

            if (! str_contains($relationship, 'stylesheet') || ! $source) {
                return $match[0];
            }

            $assetPath = $this->localCriticalAssetPath($source, $storagePath, $relativePath, 'css');
            if (! $assetPath) {
                return $match[0];
            }

            $contents = file_get_contents($assetPath);
            if (! is_string($contents)) {
                return $match[0];
            }

            $label = htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $media = $this->htmlAttribute($attributes, 'media');
            $mediaAttribute = $media ? ' media="'.htmlspecialchars($media, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"' : '';

            return '<style'.$mediaAttribute.' data-voranapro-inlined-stylesheet="'.$label.'">'
                .str_ireplace('</style', '<\/style', $contents)
                .'</style>';
        }, $html) ?? $html;

        return preg_replace_callback('/<script\b(?<attributes>[^>]*)>\s*<\/script\s*>/is', function (array $match) use ($storagePath, $relativePath): string {
            $attributes = $match['attributes'];
            $source = $this->htmlAttribute($attributes, 'src');
            if (! $source) {
                return $match[0];
            }

            $assetPath = $this->localCriticalAssetPath($source, $storagePath, $relativePath, 'js');
            if (! $assetPath) {
                return $match[0];
            }

            $contents = file_get_contents($assetPath);
            if (! is_string($contents)) {
                return $match[0];
            }

            $type = $this->htmlAttribute($attributes, 'type');
            $typeAttribute = $type ? ' type="'.htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"' : '';
            $label = htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return '<script'.$typeAttribute.' data-voranapro-inlined-script="'.$label.'">'
                .str_ireplace('</script', '<\/script', $contents)
                .'</script>';
        }, $html) ?? $html;
    }

    private function htmlAttribute(string $attributes, string $name): ?string
    {
        $pattern = '/\b'.preg_quote($name, '/').'\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))/i';
        if (preg_match($pattern, $attributes, $match) !== 1) {
            return null;
        }

        $value = $match[1] !== '' ? $match[1] : ($match[2] !== '' ? $match[2] : ($match[3] ?? ''));

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function localCriticalAssetPath(string $source, string $storagePath, string $relativePath, string $extension): ?string
    {
        if (preg_match('#^(?:[a-z][a-z0-9+.-]*:|//|/|\\\\)#i', $source) === 1) {
            return null;
        }

        $sourcePath = parse_url($source, PHP_URL_PATH);
        if (! is_string($sourcePath) || $sourcePath === '') {
            return null;
        }

        $sourcePath = str_replace('\\', '/', rawurldecode($sourcePath));
        if (strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) !== $extension) {
            return null;
        }

        $directory = dirname($relativePath);
        $candidate = ($directory === '.' ? '' : trim(str_replace('\\', '/', $directory), '/').'/').$sourcePath;
        $disk = Storage::disk(config('reveal.disk'));
        $basePath = realpath($disk->path($storagePath));
        $assetPath = realpath($disk->path($storagePath.'/'.$candidate));

        if (! $basePath || ! $assetPath || ! is_file($assetPath) || ! str_starts_with($assetPath, $basePath.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return filesize($assetPath) <= 5 * 1024 * 1024 ? $assetPath : null;
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
    const nextFrame = () => new Promise(resolve => window.requestAnimationFrame(() => window.requestAnimationFrame(resolve)));
    const stylesheetContainsRules = link => {
        if (!link.sheet) return false;
        try {
            return new URL(link.href, document.baseURI).origin !== window.location.origin
                || link.sheet.cssRules.length > 0;
        } catch (error) {
            return true;
        }
    };
    const revealStylesAreApplied = () => {
        const root = document.querySelector('.reveal');
        const slides = root?.querySelector('.slides');
        if (!root || !slides || root.clientWidth < 1 || root.clientHeight < 1) return false;

        const rootStyle = window.getComputedStyle(root);
        const slidesStyle = window.getComputedStyle(slides);

        return rootStyle.position === 'relative'
            && rootStyle.overflowX === 'hidden'
            && slidesStyle.position === 'absolute';
    };
    const reloadStylesheet = (link, attempt) => withTimeout(new Promise(resolve => {
        const replacement = link.cloneNode(true);
        const url = new URL(link.href, document.baseURI);
        url.searchParams.set('voranapro_css_retry', String(attempt));
        replacement.href = url.href;
        replacement.addEventListener('load', () => {
            link.remove();
            resolve(true);
        }, { once: true });
        replacement.addEventListener('error', () => {
            replacement.remove();
            resolve(false);
        }, { once: true });
        link.after(replacement);
    }), 60000);
    const ensureRevealStyles = async () => {
        let stylesheets = [...document.querySelectorAll('link[rel="stylesheet"]')];
        const initialResults = await Promise.all(stylesheets.map(waitForStylesheet));
        await nextFrame();
        if (initialResults.every(Boolean) && stylesheets.every(stylesheetContainsRules) && revealStylesAreApplied()) return true;

        for (let attempt = 1; attempt <= 2; attempt += 1) {
            setProgress(48 + attempt * 3, 'Recuperando estilos de la presentación…');
            const reloadResults = await Promise.all(stylesheets.map(link => reloadStylesheet(link, attempt)));
            stylesheets = [...document.querySelectorAll('link[rel="stylesheet"]')];
            if (reloadResults.some(result => result !== true)) continue;
            await nextFrame();
            if (stylesheets.every(stylesheetContainsRules) && revealStylesAreApplied()) return true;
        }

        return false;
    };
    const reloadScript = (script, attempt) => withTimeout(new Promise(resolve => {
        const replacement = document.createElement('script');
        [...script.attributes].forEach(attribute => {
            if (attribute.name !== 'src') replacement.setAttribute(attribute.name, attribute.value);
        });
        const url = new URL(script.src, document.baseURI);
        url.searchParams.set('voranapro_script_retry', String(attempt));
        replacement.src = url.href;
        replacement.async = false;
        replacement.addEventListener('load', () => resolve(true), { once: true });
        replacement.addEventListener('error', () => {
            replacement.remove();
            resolve(false);
        }, { once: true });
        document.head.appendChild(replacement);
    }), 60000);
    const runRevealInitializer = async () => {
        if (!window.Reveal || (typeof window.Reveal.isReady === 'function' && window.Reveal.isReady())) return true;

        const initializer = [...document.querySelectorAll('script:not([src])')]
            .find(script => /(?:window\.)?Reveal\.initialize\s*\(/.test(script.textContent || ''));
        if (!initializer) return false;

        try {
            const result = Function(initializer.textContent || '').call(window);
            if (result && typeof result.then === 'function') await result;
            return true;
        } catch (error) {
            return false;
        }
    };
    const ensureRevealRuntime = async () => {
        let available = await waitUntil(() => window.Reveal && typeof window.Reveal.layout === 'function', 12000);
        if (available) {
            const initiallyReady = await waitUntil(() => typeof window.Reveal.isReady === 'function' && window.Reveal.isReady(), 5000);
            if (initiallyReady) return true;
        }

        const scripts = [...document.querySelectorAll('script[src]')];
        for (let attempt = 1; attempt <= 2; attempt += 1) {
            setProgress(30 + attempt * 4, 'Recuperando el motor y sus complementos…');
            for (const script of scripts) await reloadScript(script, attempt);

            available = Boolean(window.Reveal && typeof window.Reveal.layout === 'function');
            if (!available) continue;

            const initializerStarted = await runRevealInitializer();
            if (!initializerStarted) continue;

            const ready = await waitUntil(() => typeof window.Reveal.isReady === 'function' && window.Reveal.isReady(), 30000);
            if (ready) return true;
        }

        return false;
    };
    const revealGeometryIsValid = () => {
        if (!revealStylesAreApplied()) return false;
        const root = document.querySelector('.reveal');
        const currentSlide = root?.querySelector('.slides section.present')
            || root?.querySelector('.slides section');
        if (!root || !currentSlide) return false;

        const rootRect = root.getBoundingClientRect();
        const slideRect = currentSlide.getBoundingClientRect();
        if (rootRect.width < 1 || rootRect.height < 1 || slideRect.width < 1 || slideRect.height < 1) return false;

        const viewportCoverage = Math.max(
            slideRect.width / rootRect.width,
            slideRect.height / rootRect.height,
        );

        return viewportCoverage >= 0.72 && viewportCoverage <= 1.08;
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
    const stabilizeLayout = async () => {
        for (let attempt = 0; attempt < 5; attempt += 1) {
            if (typeof window.Reveal.sync === 'function') window.Reveal.sync();
            window.Reveal.layout();
            await nextFrame();
            await new Promise(resolve => window.setTimeout(resolve, 140 + attempt * 80));
            if (revealGeometryIsValid()) return true;
        }

        return false;
    };
    const waitUntilFrameIsVisible = () => {
        if (window.parent === window) return Promise.resolve(true);

        return withTimeout(new Promise(resolve => {
            const onMessage = event => {
                if (event.origin !== parentOrigin || event.data !== 'voranapro:reveal-visible') return;
                window.removeEventListener('message', onMessage);
                resolve(true);
            };
            window.addEventListener('message', onMessage);
            post('voranapro:reveal-prepared', { progress: 97 });
        }), 15000);
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
            const revealReady = await ensureRevealRuntime();
            if (!revealReady) throw new Error('La presentación tardó demasiado en inicializarse.');

            setProgress(46, 'Verificando estilos…');
            const stylesReady = await ensureRevealStyles();
            if (!stylesReady) throw new Error('Los estilos de Reveal.js no se aplicaron correctamente.');

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
            await waitUntilFrameIsVisible();
            const geometryReady = await stabilizeLayout();
            if (!geometryReady) throw new Error('No se pudo ajustar la presentación al visor.');
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

        if (preg_match_all('/<\/body\s*>/i', $html, $body, PREG_OFFSET_CAPTURE) >= 1) {
            $lastBodyTag = array_key_last($body[0]);
            $offset = $body[0][$lastBodyTag][1];

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
