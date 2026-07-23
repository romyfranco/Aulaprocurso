@props(['topic'])

@php
    $topic->loadMissing(['revealPresentation', 'latestRevealUpload']);
    $presentation = $topic->revealPresentation;
    $latestUpload = $topic->latestRevealUpload;
    $isStaff = in_array(auth()->user()?->role, ['admin', 'instructor'], true);
    $launchUrl = route('topics.presentation.launch', $topic);
@endphp

@once
    <style>
        @keyframes voranapro-viewer-spin { to { transform: rotate(360deg); } }
        @keyframes voranapro-viewer-pulse { 50% { opacity: .55; transform: scale(.96); } }
        @media (prefers-reduced-motion: reduce) {
            .voranapro-viewer-spinner, .voranapro-viewer-mark { animation-duration: 1.8s !important; }
        }
    </style>
@endonce

<div style="display:grid;gap:1rem;width:100%">
    @if ($presentation?->isReady())
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
            <div>
                <p style="font-weight:700;margin:0">{{ $presentation->original_name }}</p>
                <p style="color:#64748b;font-size:.875rem;margin:.25rem 0 0">
                    {{ number_format($presentation->file_count) }} archivos · {{ number_format($presentation->extracted_size / 1048576, 1) }} MB
                </p>
            </div>
            <a href="{{ $launchUrl }}" target="_blank" rel="noopener noreferrer"
               style="display:inline-flex;align-items:center;gap:.5rem;border-radius:.65rem;background:#2563eb;color:white;padding:.7rem 1rem;font-weight:700;text-decoration:none">
                Abrir en otra pestaña ↗
            </a>
        </div>

        <div
            id="reveal-presentation-{{ $topic->getKey() }}"
            data-reveal-origin="{{ rtrim(config('reveal.url'), '/') }}"
            x-data="{ revealPrepared: false, revealReady: false, revealFailed: false, revealProgress: 4, revealStatus: 'Conectando con la presentación…' }"
            x-init="
                const frame = $refs.presentation;
                const origin = $el.dataset.revealOrigin;
                const notify = () => frame.contentWindow?.postMessage('voranapro:reveal-layout', origin);
                const onMessage = event => {
                    if (event.source !== frame.contentWindow || event.origin !== origin || !event.data) return;
                    if (event.data.type === 'voranapro:reveal-progress') {
                        revealProgress = Math.max(revealProgress, event.data.progress || 4);
                        revealStatus = event.data.status || revealStatus;
                    }
                    if (event.data.type === 'voranapro:reveal-prepared') {
                        revealPrepared = true;
                        revealProgress = Math.max(revealProgress, 97);
                        revealStatus = 'Ajustando al tamaño del visor…';
                        requestAnimationFrame(() => requestAnimationFrame(() => {
                            frame.contentWindow?.postMessage('voranapro:reveal-visible', origin);
                        }));
                    }
                    if (event.data.type === 'voranapro:reveal-ready') {
                        revealProgress = 100;
                        revealStatus = 'Presentación lista';
                        setTimeout(() => revealReady = true, 220);
                    }
                    if (event.data.type === 'voranapro:reveal-error') {
                        revealFailed = true;
                        revealStatus = event.data.status || 'No se pudo completar la carga.';
                    }
                };
                window.addEventListener('message', onMessage);
                frame.addEventListener('load', notify);
                const observer = new IntersectionObserver(entries => {
                    if (entries.some(entry => entry.isIntersecting)) notify();
                }, { threshold: 0.01 });
                observer.observe(frame);
                setTimeout(notify, 750);
                $el._voranaproRevealCleanup?.();
                $el._voranaproRevealCleanup = () => {
                    window.removeEventListener('message', onMessage);
                    observer.disconnect();
                };
            "
            style="position:relative;width:100%;aspect-ratio:16/9;min-height:420px;max-height:75vh;overflow:hidden;border:1px solid #dbe3ef;border-radius:1rem;background:#0f172a"
        >
            <div
                x-show="!revealReady"
                role="status"
                aria-live="polite"
                style="position:absolute;inset:0;z-index:2;display:grid;place-items:center;padding:1.5rem;background:radial-gradient(circle at 50% 38%,#182238 0,#0b1115 62%);color:#f8fafc"
            >
                <div style="width:min(390px,90%);text-align:center">
                    <div class="voranapro-viewer-mark" aria-hidden="true"
                         style="width:54px;height:54px;margin:0 auto 1.15rem;border-radius:17px;display:grid;place-items:center;background:linear-gradient(135deg,#6757f5,#22d3b6);box-shadow:0 18px 50px #6757f54d;font-size:1.25rem;font-weight:900;animation:voranapro-viewer-pulse 1.8s ease-in-out infinite">
                        V
                    </div>
                    <div class="voranapro-viewer-spinner" aria-hidden="true"
                         style="width:40px;height:40px;margin:0 auto 1rem;border:4px solid #ffffff24;border-top-color:#22d3b6;border-right-color:#7c6cff;border-radius:50%;animation:voranapro-viewer-spin .8s linear infinite"></div>
                    <p style="margin:0;font-size:clamp(1.1rem,3vw,1.5rem);font-weight:800">Preparando la presentación</p>
                    <p x-text="revealStatus" style="min-height:1.4rem;margin:.55rem 0 1.1rem;color:#aab6ca;font-size:.875rem"></p>
                    <div style="height:9px;overflow:hidden;border-radius:999px;background:#ffffff17;box-shadow:inset 0 0 0 1px #ffffff0d">
                        <div :style="`width:${revealProgress}%`" style="height:100%;border-radius:inherit;background:linear-gradient(90deg,#6757f5,#22d3b6);transition:width .25s ease"></div>
                    </div>
                    <p style="margin:.55rem 0 0;color:#dbe4f3;font-size:.8rem;font-variant-numeric:tabular-nums"><span x-text="Math.round(revealProgress)"></span>%</p>
                    <button
                        x-show="revealFailed"
                        type="button"
                        @click="revealFailed = false; revealPrepared = false; revealReady = false; revealProgress = 4; revealStatus = 'Reintentando…'; $refs.presentation.src = $refs.presentation.src"
                        style="margin:1rem auto 0;border:0;border-radius:.7rem;padding:.65rem 1rem;background:#6757f5;color:white;font-weight:750;cursor:pointer"
                    >
                        Reintentar
                    </button>
                </div>
            </div>

            <iframe
                x-ref="presentation"
                src="{{ $launchUrl }}"
                title="Presentación: {{ $topic->title }}"
                loading="lazy"
                sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-presentation"
                allow="fullscreen"
                allowfullscreen
                referrerpolicy="no-referrer"
                :aria-hidden="(! revealReady).toString()"
                :style="revealReady ? 'opacity:1;visibility:visible' : (revealPrepared ? 'opacity:0;visibility:visible' : 'opacity:0;visibility:hidden')"
                style="position:absolute;inset:0;display:block;width:100%;height:100%;border:0;opacity:0;visibility:hidden;transition:opacity .35s ease"
            ></iframe>
        </div>

        @if ($isStaff && $latestUpload?->status === 'processing' && $latestUpload->id !== $presentation->id)
            <p style="margin:0;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:.75rem;padding:.75rem 1rem">
                Hay una versión nueva procesándose. Esta presentación seguirá activa hasta que la nueva esté lista.
            </p>
        @elseif ($isStaff && $latestUpload?->status === 'failed')
            <p style="margin:0;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;border-radius:.75rem;padding:.75rem 1rem">
                La última carga no pudo activarse: {{ $latestUpload->error_message }}
            </p>
        @endif
    @elseif ($latestUpload?->status === 'processing')
        <p style="margin:0;color:#1e40af;background:#eff6ff;border:1px solid #bfdbfe;border-radius:.75rem;padding:1rem">
            La presentación se está validando y estará disponible en unos minutos.
        </p>
    @elseif ($isStaff && $latestUpload?->status === 'failed')
        <p style="margin:0;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;border-radius:.75rem;padding:1rem">
            No se pudo activar la presentación: {{ $latestUpload->error_message }}
        </p>
    @else
        <p style="margin:0;color:#64748b;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:.75rem;padding:1rem">
            Este tema todavía no tiene una presentación interactiva.
        </p>
    @endif
</div>
