@extends('layouts.public')

@section('title', 'Vista previa · '.$quiz->title.' · VoranaPro')

@section('styles')
    .quiz-preview-shell { min-height: 100vh; padding: 34px 0 72px; }
    .quiz-preview-bar { position: sticky; top: 16px; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 28px; padding: 14px 18px; border: 1px solid rgba(103, 87, 245, .18); border-radius: 18px; background: rgba(255, 255, 255, .88); box-shadow: var(--vp-shadow-medium); backdrop-filter: blur(18px); }
    .quiz-preview-status { display: flex; align-items: center; gap: 11px; min-width: 0; }
    .quiz-preview-status-icon { width: 38px; height: 38px; display: grid; flex: 0 0 auto; place-items: center; border-radius: 12px; color: #fff; background: linear-gradient(135deg, var(--vp-primary), #8d7cff); }
    .quiz-preview-status-icon svg { width: 20px; }
    .quiz-preview-status strong { display: block; font-family: Manrope, Inter, sans-serif; font-size: .9rem; }
    .quiz-preview-status span { display: block; margin-top: 2px; color: var(--vp-text-muted); font-size: .78rem; }
    .quiz-preview-close { min-height: 40px; padding-inline: 16px; border: 0; cursor: pointer; }
    .quiz-preview-card { overflow: hidden; border: 1px solid var(--vp-border); border-radius: 30px; background: rgba(255, 255, 255, .92); box-shadow: var(--vp-shadow-high); }
    .quiz-preview-hero { padding: 48px; color: #fff; background: radial-gradient(circle at 88% 10%, rgba(34, 211, 182, .36), transparent 28%), linear-gradient(135deg, #111a2c, #3b2ca2); }
    .quiz-preview-hero h1 { max-width: 780px; margin: 18px 0 12px; font-family: Manrope, Inter, sans-serif; font-size: clamp(2rem, 5vw, 3.75rem); line-height: 1.04; letter-spacing: -.055em; }
    .quiz-preview-hero p { max-width: 760px; margin: 0; color: rgba(255, 255, 255, .76); font-size: 1rem; line-height: 1.7; }
    .quiz-preview-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 28px; }
    .quiz-preview-pill { padding: 8px 12px; border: 1px solid rgba(255, 255, 255, .18); border-radius: 999px; color: rgba(255, 255, 255, .9); background: rgba(255, 255, 255, .08); font-size: .78rem; font-weight: 700; }
    .quiz-preview-content { padding: 18px 48px 48px; }
    .quiz-question { display: grid; grid-template-columns: 48px minmax(0, 1fr); gap: 18px; padding: 30px 0; border-bottom: 1px solid #e8e9f1; }
    .quiz-question:last-child { border-bottom: 0; }
    .quiz-question-number { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 14px; color: var(--vp-primary-dark); background: #efedff; font-family: Manrope, Inter, sans-serif; font-weight: 800; }
    .quiz-question-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; }
    .quiz-question-heading h2 { margin: 5px 0 20px; font-family: Manrope, Inter, sans-serif; font-size: 1.12rem; line-height: 1.45; }
    .quiz-points { flex: 0 0 auto; padding: 6px 10px; border-radius: 999px; color: var(--vp-text-muted); background: var(--vp-surface-muted); font-size: .72rem; font-weight: 800; }
    .quiz-options { display: grid; gap: 10px; }
    .quiz-option { display: flex; align-items: center; gap: 12px; min-height: 50px; padding: 12px 16px; border: 1px solid #e0e2eb; border-radius: 14px; color: #3c465b; background: #fafbfe; }
    .quiz-option input { width: 18px; height: 18px; accent-color: var(--vp-primary); }
    .quiz-answer { width: 100%; min-height: 50px; resize: vertical; padding: 14px 16px; border: 1px solid #dfe1eb; border-radius: 14px; color: var(--vp-text-muted); background: #f8f9fc; font: inherit; }
    textarea.quiz-answer { min-height: 150px; }
    .quiz-empty { margin: 30px 0 12px; padding: 28px; border: 1px dashed rgba(103, 87, 245, .3); border-radius: 18px; color: var(--vp-text-muted); text-align: center; background: #faf9ff; }

    @media (max-width: 720px) {
        .quiz-preview-shell { padding-top: 16px; }
        .quiz-preview-bar { top: 8px; }
        .quiz-preview-status span { display: none; }
        .quiz-preview-hero, .quiz-preview-content { padding-left: 24px; padding-right: 24px; }
        .quiz-preview-hero { padding-top: 36px; padding-bottom: 36px; }
        .quiz-question { grid-template-columns: 1fr; }
        .quiz-question-number { width: 38px; height: 38px; }
    }
@endsection

@section('content')
    <main class="quiz-preview-shell">
        <div class="vp-container">
            <header class="quiz-preview-bar">
                <div class="quiz-preview-status">
                    <span class="quiz-preview-status-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </span>
                    <div>
                        <strong>Vista previa del instructor</strong>
                        <span>No crea intentos ni registra respuestas.</span>
                    </div>
                </div>
                <button class="vp-button quiz-preview-close" type="button" onclick="window.close()">Cerrar</button>
            </header>

            <article class="quiz-preview-card">
                <header class="quiz-preview-hero">
                    <span class="vp-eyebrow">Evaluación · Vista del estudiante</span>
                    <h1>{{ $quiz->title }}</h1>
                    <p>{{ $quiz->instructions ?: 'Lee cada pregunta con atención y completa todas las respuestas antes de enviar la evaluación.' }}</p>
                    <div class="quiz-preview-meta">
                        <span class="quiz-preview-pill">Tema: {{ $quiz->topic->title }}</span>
                        <span class="quiz-preview-pill">{{ $quiz->questions->count() }} {{ $quiz->questions->count() === 1 ? 'pregunta' : 'preguntas' }}</span>
                        <span class="quiz-preview-pill">Aprobación: {{ $quiz->passing_score }}%</span>
                        <span class="quiz-preview-pill">Intentos: {{ $quiz->max_attempts }}</span>
                    </div>
                </header>

                <section class="quiz-preview-content" aria-label="Preguntas de la evaluación">
                    @forelse ($quiz->questions as $question)
                        <article class="quiz-question">
                            <div class="quiz-question-number">{{ $loop->iteration }}</div>
                            <div>
                                <div class="quiz-question-heading">
                                    <h2>{{ $question->question_text }}</h2>
                                    <span class="quiz-points">{{ $question->points }} {{ $question->points === 1 ? 'punto' : 'puntos' }}</span>
                                </div>

                                @if (in_array($question->question_type, ['multiple_choice', 'true_false'], true))
                                    <div class="quiz-options">
                                        @foreach ($question->options as $option)
                                            <label class="quiz-option">
                                                <input type="radio" disabled>
                                                <span>{{ $option->option_text }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif ($question->question_type === 'essay')
                                    <textarea class="quiz-answer" disabled placeholder="El estudiante escribirá aquí su respuesta extensa."></textarea>
                                @else
                                    <input class="quiz-answer" type="text" disabled placeholder="El estudiante escribirá aquí su respuesta.">
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="quiz-empty">Esta evaluación todavía no contiene preguntas.</div>
                    @endforelse
                </section>
            </article>
        </div>
    </main>
@endsection
