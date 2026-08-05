@extends('layouts.public')

@section('title', $topic->title.' · Presentación · VoranaPro')
@section('body-class', 'pdf-presentation-page')

@section('styles')
    .pdf-presentation-page { min-height: 100vh; overflow-x: hidden; color: #eef2ff; background: #080c15; }
    .pdf-presentation-page::before { display: none; }
    .pdf-shell { width: 100%; min-height: 100vh; display: grid; grid-template-rows: auto minmax(0, 1fr) auto; gap: 14px; padding: 16px; background: radial-gradient(circle at 84% 10%, rgba(103, 87, 245, .18), transparent 30%), #080c15; }
    .pdf-topbar, .pdf-toolbar { width: min(1500px, 100%); margin-inline: auto; border: 1px solid rgba(255, 255, 255, .11); background: rgba(16, 23, 39, .9); box-shadow: 0 20px 60px rgba(0, 0, 0, .24); backdrop-filter: blur(18px); }
    .pdf-topbar { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 13px 15px; border-radius: 18px; }
    .pdf-heading { min-width: 0; display: flex; align-items: center; gap: 12px; }
    .pdf-heading-mark { width: 42px; height: 42px; display: grid; flex: 0 0 auto; place-items: center; border-radius: 14px; color: #fff; background: linear-gradient(135deg, #6757f5, #22d3b6); box-shadow: 0 12px 30px rgba(103, 87, 245, .32); }
    .pdf-heading-mark svg { width: 22px; height: 22px; }
    .pdf-heading strong, .pdf-heading span { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pdf-heading strong { font-family: Manrope, Inter, sans-serif; font-size: .95rem; }
    .pdf-heading span { margin-top: 3px; color: #9da9bd; font-size: .76rem; }
    .pdf-top-actions { display: flex; align-items: center; gap: 9px; }
    .pdf-action { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 15px; border: 1px solid rgba(255, 255, 255, .12); border-radius: 999px; color: #e8ecf7; background: rgba(255, 255, 255, .06); font-size: .82rem; font-weight: 750; cursor: pointer; transition: transform 160ms, border-color 160ms, background 160ms, opacity 160ms; }
    .pdf-action:hover:not(:disabled) { transform: translateY(-1px); border-color: rgba(124, 108, 255, .6); background: rgba(103, 87, 245, .18); }
    .pdf-action:focus-visible { outline: 3px solid rgba(124, 108, 255, .45); outline-offset: 2px; }
    .pdf-action:disabled { cursor: not-allowed; opacity: .38; }
    .pdf-action--primary { border-color: transparent; color: #fff; background: linear-gradient(135deg, #6757f5, #7c6cff); box-shadow: 0 12px 28px rgba(103, 87, 245, .26); }
    .pdf-stage { position: relative; width: min(1500px, 100%); height: min(74vh, 900px); min-height: 420px; margin-inline: auto; overflow: hidden; border: 1px solid rgba(255, 255, 255, .12); border-radius: 22px; background: #101725; box-shadow: 0 28px 90px rgba(0, 0, 0, .42); }
    .pdf-canvas-scroll { width: 100%; height: 100%; overflow: auto; overscroll-behavior: contain; scrollbar-color: #6757f5 #111827; }
    .pdf-canvas-wrap { min-width: 100%; min-height: 100%; display: grid; place-items: center; padding: 24px; }
    .pdf-canvas { display: block; max-width: none; border-radius: 3px; background: #fff; box-shadow: 0 20px 65px rgba(0, 0, 0, .42); opacity: 0; transition: opacity 220ms ease; }
    .pdf-canvas.is-ready { opacity: 1; }
    .pdf-loading, .pdf-error { position: absolute; inset: 0; z-index: 3; display: grid; place-items: center; padding: 24px; text-align: center; background: radial-gradient(circle at 50% 38%, #182238 0, rgba(11, 17, 21, .97) 64%); }
    .pdf-loading[hidden], .pdf-error[hidden] { display: none; }
    .pdf-loading-content, .pdf-error-content { width: min(390px, 92%); }
    .pdf-spinner { width: 48px; height: 48px; margin: 0 auto 18px; border: 4px solid rgba(255, 255, 255, .13); border-top-color: #22d3b6; border-right-color: #7c6cff; border-radius: 50%; animation: pdf-spin .8s linear infinite; }
    .pdf-loading h1, .pdf-error h2 { margin: 0; font-family: Manrope, Inter, sans-serif; font-size: clamp(1.25rem, 4vw, 1.7rem); }
    .pdf-loading p, .pdf-error p { min-height: 1.35rem; margin: 9px 0 17px; color: #aab6ca; font-size: .86rem; line-height: 1.5; }
    .pdf-progress { height: 9px; overflow: hidden; border-radius: 999px; background: rgba(255, 255, 255, .09); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .04); }
    .pdf-progress-bar { width: 4%; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #6757f5, #22d3b6); transition: width 220ms ease; }
    .pdf-error-icon { width: 52px; height: 52px; display: grid; place-items: center; margin: 0 auto 17px; border-radius: 17px; color: #ffadbd; background: rgba(220, 49, 87, .17); font-size: 1.5rem; font-weight: 900; }
    .pdf-toolbar { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 16px; padding: 12px 14px; border-radius: 18px; }
    .pdf-toolbar-group { display: flex; align-items: center; gap: 8px; }
    .pdf-toolbar-group:last-child { justify-content: flex-end; }
    .pdf-page-status { min-width: 150px; text-align: center; color: #cbd5e1; font-size: .86rem; font-weight: 750; font-variant-numeric: tabular-nums; }
    .pdf-zoom-value { min-width: 49px; color: #aab6ca; text-align: center; font-size: .78rem; font-variant-numeric: tabular-nums; }
    .pdf-icon-button { width: 42px; padding: 0; font-size: 1.1rem; }
    .pdf-shell:fullscreen { overflow: hidden; padding: 12px; background: #080c15; }
    .pdf-shell:fullscreen .pdf-stage { height: calc(100vh - 148px); max-height: none; }
    @keyframes pdf-spin { to { transform: rotate(360deg); } }

    @media (max-width: 760px) {
        .pdf-shell { gap: 8px; padding: 8px; }
        .pdf-topbar { padding: 10px; border-radius: 15px; }
        .pdf-heading-mark { width: 38px; height: 38px; border-radius: 12px; }
        .pdf-heading strong { font-size: .84rem; }
        .pdf-heading span { max-width: 48vw; }
        .pdf-top-actions .pdf-action span:last-child { display: none; }
        .pdf-top-actions .pdf-action { width: 42px; padding: 0; }
        .pdf-stage { height: calc(100dvh - 174px); min-height: 320px; border-radius: 16px; }
        .pdf-canvas-wrap { padding: 12px; }
        .pdf-toolbar { grid-template-columns: 1fr auto 1fr; gap: 6px; padding: 8px; border-radius: 15px; }
        .pdf-toolbar .pdf-action { min-height: 44px; padding-inline: 13px; }
        .pdf-toolbar-group[data-zoom-controls] { display: none; }
        .pdf-page-status { min-width: 94px; font-size: .76rem; }
        .pdf-shell:fullscreen { padding: 6px; }
        .pdf-shell:fullscreen .pdf-stage { height: calc(100dvh - 136px); }
    }

    @media (prefers-reduced-motion: reduce) {
        .pdf-spinner { animation-duration: 1.8s !important; }
    }
@endsection

@section('content')
    <main
        class="pdf-shell"
        data-pdf-viewer
        data-pdf-url="{{ route('topics.pdf-presentation.file', $topic) }}"
        data-pdf-name="{{ $presentation->file_name }}"
    >
        <header class="pdf-topbar">
            <div class="pdf-heading">
                <span class="pdf-heading-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="m9 10 2 2 4-4"/></svg>
                </span>
                <div>
                    <strong>{{ $topic->title }}</strong>
                    <span>{{ $presentation->name }}</span>
                </div>
            </div>
            <div class="pdf-top-actions">
                <button class="pdf-action" type="button" data-pdf-action="fullscreen" aria-label="Activar pantalla completa">
                    <span aria-hidden="true">⛶</span><span data-fullscreen-label>Pantalla completa</span>
                </button>
                <a class="pdf-action" href="{{ $returnUrl }}" aria-label="Volver al tema">
                    <span aria-hidden="true">×</span><span>Volver al tema</span>
                </a>
            </div>
        </header>

        <section class="pdf-stage" data-pdf-stage aria-label="Diapositiva de la presentación">
            <div class="pdf-canvas-scroll">
                <div class="pdf-canvas-wrap">
                    <canvas class="pdf-canvas" data-pdf-canvas aria-label="Página actual de la presentación"></canvas>
                </div>
            </div>

            <div class="pdf-loading" data-pdf-loading role="status" aria-live="polite">
                <div class="pdf-loading-content">
                    <div class="pdf-spinner" aria-hidden="true"></div>
                    <h1>Preparando la presentación</h1>
                    <p data-pdf-loading-status>Conectando con el documento…</p>
                    <div class="pdf-progress" aria-hidden="true"><div class="pdf-progress-bar" data-pdf-progress></div></div>
                </div>
            </div>

            <div class="pdf-error" data-pdf-error role="alert" hidden>
                <div class="pdf-error-content">
                    <div class="pdf-error-icon" aria-hidden="true">!</div>
                    <h2>No se pudo abrir la presentación</h2>
                    <p>Comprueba tu conexión e inténtalo nuevamente.</p>
                    <button class="pdf-action pdf-action--primary" type="button" data-pdf-action="retry">Reintentar</button>
                </div>
            </div>
        </section>

        <nav class="pdf-toolbar" aria-label="Controles de la presentación">
            <div class="pdf-toolbar-group">
                <button class="pdf-action" type="button" data-pdf-action="previous" disabled>← Atrás</button>
            </div>
            <div class="pdf-page-status" aria-live="polite">Página <span data-current-page>—</span> de <span data-total-pages>—</span></div>
            <div class="pdf-toolbar-group">
                <div class="pdf-toolbar-group" data-zoom-controls>
                    <button class="pdf-action pdf-icon-button" type="button" data-pdf-action="zoom-out" aria-label="Alejar">−</button>
                    <span class="pdf-zoom-value" data-zoom-value>100%</span>
                    <button class="pdf-action pdf-icon-button" type="button" data-pdf-action="zoom-in" aria-label="Acercar">+</button>
                </div>
                <button class="pdf-action pdf-action--primary" type="button" data-pdf-action="next" disabled>Siguiente →</button>
            </div>
        </nav>
    </main>
@endsection

@section('scripts')
    @vite('resources/js/pdf-presentation-viewer.js')
@endsection
