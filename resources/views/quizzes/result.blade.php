@extends('layouts.public')

@section('title', 'Resultado · '.$attempt->quiz->title.' · VoranaPro')

@section('styles')
    .result-shell { min-height: 100vh; display: grid; place-items: center; padding: 32px 0; }
    .result-card { width: min(760px, 100%); overflow: hidden; border: 1px solid var(--vp-border); border-radius: 30px; background: rgba(255, 255, 255, .94); box-shadow: var(--vp-shadow-high); }
    .result-hero { padding: 46px; color: #fff; background: radial-gradient(circle at 86% 12%, rgba(34, 211, 182, .36), transparent 32%), linear-gradient(135deg, #10192b, #3b2ca2); }
    .result-icon { width: 62px; height: 62px; display: grid; place-items: center; border-radius: 20px; color: #fff; background: rgba(255, 255, 255, .14); }
    .result-icon svg { width: 32px; }
    .result-hero h1 { margin: 22px 0 10px; font-family: Manrope, Inter, sans-serif; font-size: clamp(2rem, 6vw, 3.3rem); line-height: 1.05; letter-spacing: -.05em; }
    .result-hero p { margin: 0; color: rgba(255, 255, 255, .76); line-height: 1.65; }
    .result-content { padding: 38px 46px 46px; }
    .result-score { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 22px; border: 1px solid #e3e4ed; border-radius: 18px; background: #fafbfe; }
    .result-score span, .result-score strong { display: block; }
    .result-score span { color: var(--vp-text-muted); font-size: .8rem; font-weight: 750; text-transform: uppercase; letter-spacing: .08em; }
    .result-score strong { margin-top: 5px; font-family: Manrope, Inter, sans-serif; font-size: 1.3rem; }
    .result-number { color: var(--vp-primary); font-family: Manrope, Inter, sans-serif; font-size: 2.6rem; font-weight: 850; }
    .result-message { margin: 20px 0 26px; color: var(--vp-text-muted); line-height: 1.65; }
    .result-actions { display: flex; flex-wrap: wrap; gap: 12px; }
    .result-actions .vp-button { border: 0; cursor: pointer; }
    @media (max-width: 620px) {
        .result-shell { align-items: start; padding: 16px 0; }
        .result-card { border-radius: 22px; }
        .result-hero, .result-content { padding: 30px 22px; }
        .result-score { align-items: flex-start; }
        .result-number { font-size: 2rem; }
        .result-actions { display: grid; }
        .result-actions .vp-button { width: 100%; }
    }
@endsection

@section('content')
    <main class="result-shell">
        <div class="vp-container">
            <article class="result-card">
                <header class="result-hero">
                    <div class="result-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <h1>Evaluación enviada</h1>
                    <p>{{ $attempt->quiz->title }} · {{ $attempt->quiz->topic->title }}</p>
                </header>
                <section class="result-content">
                    <div class="result-score">
                        <div>
                            <span>Estado</span>
                            <strong>{{ $attempt->status === 'graded' ? ((float) $attempt->score >= $attempt->quiz->passing_score ? 'Aprobada' : 'No aprobada') : 'Pendiente de revisión' }}</strong>
                        </div>
                        <div class="result-number">{{ $attempt->status === 'graded' ? rtrim(rtrim(number_format((float) $attempt->score, 2), '0'), '.').'%' : '—' }}</div>
                    </div>
                    <p class="result-message">
                        @if ($attempt->status === 'graded')
                            {{ (float) $attempt->score >= $attempt->quiz->passing_score ? '¡Buen trabajo! Si existe un tema siguiente, ya quedó desbloqueado.' : 'Puedes revisar el contenido y volver a intentarlo si todavía tienes intentos disponibles.' }}
                        @else
                            Tu instructor revisará las respuestas abiertas. El resultado aparecerá cuando termine la calificación.
                        @endif
                    </p>
                    <div class="result-actions">
                        <a class="vp-button vp-button--primary" href="{{ url('/student/quizzes') }}">Volver a evaluaciones</a>
                        <button class="vp-button" type="button" onclick="window.close()">Cerrar pestaña</button>
                    </div>
                </section>
            </article>
        </div>
    </main>
@endsection
