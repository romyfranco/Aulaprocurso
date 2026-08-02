@extends('layouts.public')

@section('title', $quiz->title.' · VoranaPro')

@section('styles')
    .take-shell { min-height: 100vh; padding: 28px 0 72px; }
    .take-topbar { position: sticky; top: 12px; z-index: 20; display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 24px; padding: 14px 18px; border: 1px solid rgba(103, 87, 245, .18); border-radius: 18px; background: rgba(255, 255, 255, .92); box-shadow: var(--vp-shadow-medium); backdrop-filter: blur(18px); }
    .take-brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .take-brand-icon { width: 40px; height: 40px; display: grid; flex: 0 0 auto; place-items: center; border-radius: 13px; color: #fff; background: linear-gradient(135deg, var(--vp-primary), #8d7cff); }
    .take-brand-icon svg { width: 21px; }
    .take-brand strong, .take-brand span { display: block; }
    .take-brand strong { font-family: Manrope, Inter, sans-serif; font-size: .92rem; }
    .take-brand span { margin-top: 2px; color: var(--vp-text-muted); font-size: .76rem; }
    .take-card { overflow: hidden; border: 1px solid var(--vp-border); border-radius: 30px; background: rgba(255, 255, 255, .94); box-shadow: var(--vp-shadow-high); }
    .take-hero { padding: 46px; color: #fff; background: radial-gradient(circle at 88% 12%, rgba(34, 211, 182, .34), transparent 30%), linear-gradient(135deg, #10192b, #3b2ca2); }
    .take-hero h1 { max-width: 820px; margin: 18px 0 12px; font-family: Manrope, Inter, sans-serif; font-size: clamp(2rem, 5vw, 3.6rem); line-height: 1.05; letter-spacing: -.05em; }
    .take-hero p { max-width: 760px; margin: 0; color: rgba(255, 255, 255, .78); line-height: 1.7; }
    .take-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 26px; }
    .take-pill { padding: 8px 12px; border: 1px solid rgba(255, 255, 255, .18); border-radius: 999px; color: rgba(255, 255, 255, .92); background: rgba(255, 255, 255, .08); font-size: .76rem; font-weight: 750; }
    .take-errors { margin: 24px 46px 0; padding: 18px 20px; border: 1px solid rgba(220, 49, 87, .2); border-radius: 16px; color: #a51f40; background: #fff1f4; }
    .take-errors strong { display: block; margin-bottom: 7px; }
    .take-errors ul { margin: 0; padding-left: 20px; }
    .take-form { padding: 12px 46px 46px; }
    .take-question { display: grid; grid-template-columns: 48px minmax(0, 1fr); gap: 18px; padding: 32px 0; border-bottom: 1px solid #e7e9f1; }
    .take-question:last-of-type { border-bottom: 0; }
    .take-number { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 14px; color: var(--vp-primary-dark); background: #efedff; font-family: Manrope, Inter, sans-serif; font-weight: 800; }
    .take-question-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; }
    .take-question h2 { margin: 5px 0 20px; font-family: Manrope, Inter, sans-serif; font-size: 1.12rem; line-height: 1.48; }
    .take-points { flex: 0 0 auto; padding: 6px 10px; border-radius: 999px; color: var(--vp-text-muted); background: var(--vp-surface-muted); font-size: .72rem; font-weight: 800; }
    .take-options { display: grid; gap: 10px; }
    .take-option { display: flex; align-items: center; gap: 12px; min-height: 54px; padding: 13px 16px; border: 1px solid #dfe2eb; border-radius: 15px; cursor: pointer; background: #fafbfe; transition: border-color 160ms var(--vp-ease), background 160ms var(--vp-ease), transform 160ms var(--vp-ease); }
    .take-option:hover { transform: translateY(-1px); border-color: rgba(103, 87, 245, .42); background: #f7f5ff; }
    .take-option:has(input:checked) { border-color: var(--vp-primary); background: #f0edff; box-shadow: 0 0 0 3px rgba(103, 87, 245, .1); }
    .take-option input { width: 19px; height: 19px; flex: 0 0 auto; accent-color: var(--vp-primary); }
    .take-answer { width: 100%; min-height: 52px; padding: 14px 16px; border: 1px solid #d9dce7; border-radius: 15px; color: var(--vp-text); background: #fff; font: inherit; transition: border-color 160ms, box-shadow 160ms; }
    .take-answer:focus { outline: none; border-color: var(--vp-primary); box-shadow: 0 0 0 4px rgba(103, 87, 245, .12); }
    textarea.take-answer { min-height: 170px; resize: vertical; line-height: 1.6; }
    .take-field-error { margin: 9px 0 0; color: var(--vp-error); font-size: .8rem; font-weight: 650; }
    .take-submit-row { position: sticky; bottom: 14px; display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-top: 24px; padding: 16px 18px; border: 1px solid rgba(103, 87, 245, .16); border-radius: 18px; background: rgba(255, 255, 255, .94); box-shadow: var(--vp-shadow-medium); backdrop-filter: blur(18px); }
    .take-submit-copy strong, .take-submit-copy span { display: block; }
    .take-submit-copy span { margin-top: 3px; color: var(--vp-text-muted); font-size: .78rem; }
    .take-submit { border: 0; cursor: pointer; }
    .take-submit[disabled] { cursor: wait; opacity: .72; transform: none; }

    @media (max-width: 720px) {
        .take-shell { padding: 10px 0 40px; }
        .take-topbar { top: 6px; margin-bottom: 12px; padding: 11px 13px; border-radius: 15px; }
        .take-brand span { display: none; }
        .take-topbar .vp-button { min-height: 40px; padding-inline: 13px; }
        .take-card { border-radius: 22px; }
        .take-hero { padding: 34px 22px; }
        .take-hero h1 { font-size: clamp(1.85rem, 10vw, 2.65rem); }
        .take-errors { margin: 18px 20px 0; }
        .take-form { padding: 4px 20px 24px; }
        .take-question { grid-template-columns: 1fr; gap: 12px; padding: 27px 0; }
        .take-number { width: 38px; height: 38px; }
        .take-question-head { display: block; }
        .take-question h2 { margin-bottom: 12px; }
        .take-points { display: inline-flex; margin-bottom: 15px; }
        .take-option { align-items: flex-start; min-height: 52px; }
        .take-submit-row { bottom: 7px; display: block; padding: 12px; }
        .take-submit-copy { display: none; }
        .take-submit { width: 100%; }
    }
@endsection

@section('content')
    <main class="take-shell">
        <div class="vp-container">
            <header class="take-topbar">
                <div class="take-brand">
                    <span class="take-brand-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </span>
                    <div><strong>Evaluación en curso</strong><span>Completa todas las preguntas antes de enviar.</span></div>
                </div>
                <button class="vp-button" type="button" onclick="window.close()">Cerrar</button>
            </header>

            <article class="take-card">
                <header class="take-hero">
                    <span class="vp-eyebrow">{{ $quiz->topic->title }}</span>
                    <h1>{{ $quiz->title }}</h1>
                    <p>{{ $quiz->instructions ?: 'Lee cada pregunta con atención y completa todas las respuestas.' }}</p>
                    <div class="take-meta">
                        <span class="take-pill">{{ $quiz->questions->count() }} {{ $quiz->questions->count() === 1 ? 'pregunta' : 'preguntas' }}</span>
                        <span class="take-pill">Aprobación: {{ $quiz->passing_score }}%</span>
                        <span class="take-pill">Intentos disponibles: {{ $quiz->availableAttemptsFor($student) }}</span>
                    </div>
                </header>

                @if ($errors->any())
                    <div class="take-errors" role="alert">
                        <strong>Revisa las respuestas marcadas.</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <form id="quiz-form" class="take-form" method="POST" action="{{ route('student.quizzes.submit', $quiz) }}">
                    @csrf
                    @foreach ($quiz->questions as $question)
                        <section class="take-question">
                            <div class="take-number">{{ $loop->iteration }}</div>
                            <div>
                                <div class="take-question-head">
                                    <h2>{{ $question->question_text }}</h2>
                                    <span class="take-points">{{ $question->points }} {{ $question->points === 1 ? 'punto' : 'puntos' }}</span>
                                </div>

                                @if (in_array($question->question_type, ['multiple_choice', 'true_false'], true))
                                    <div class="take-options">
                                        @foreach ($question->options as $option)
                                            <label class="take-option">
                                                <input type="radio" name="responses[{{ $question->id }}]" value="{{ $option->id }}" @checked((string) old('responses.'.$question->id) === (string) $option->id) required>
                                                <span>{{ $option->option_text }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif ($question->question_type === 'essay')
                                    <textarea class="take-answer" name="responses[{{ $question->id }}]" maxlength="10000" placeholder="Escribe una respuesta completa…" required>{{ old('responses.'.$question->id) }}</textarea>
                                @else
                                    <input class="take-answer" type="text" name="responses[{{ $question->id }}]" value="{{ old('responses.'.$question->id) }}" maxlength="2000" placeholder="Escribe tu respuesta…" required>
                                @endif

                                @error('responses.'.$question->id)<p class="take-field-error">{{ $message }}</p>@enderror
                            </div>
                        </section>
                    @endforeach

                    <div class="take-submit-row">
                        <div class="take-submit-copy"><strong>¿Terminaste la evaluación?</strong><span>Después de enviarla no podrás modificar este intento.</span></div>
                        <button id="submit-quiz" class="vp-button vp-button--primary take-submit" type="submit">Enviar evaluación</button>
                    </div>
                </form>
            </article>
        </div>
    </main>
@endsection

@section('scripts')
    <script>
        document.getElementById('quiz-form')?.addEventListener('submit', (event) => {
            const button = document.getElementById('submit-quiz');
            if (button?.disabled) {
                event.preventDefault();
                return;
            }
            if (button) {
                button.disabled = true;
                button.textContent = 'Enviando…';
            }
        });
    </script>
@endsection
