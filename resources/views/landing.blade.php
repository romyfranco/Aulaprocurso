@extends('layouts.public')

@section('title', 'VoranaPro · Tu progreso tiene una ruta')

@section('styles')
    .site-header { position: relative; z-index: 20; padding-top: 18px; }
    .nav-shell { min-height: 66px; display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 0 10px 0 14px; border: 1px solid rgba(255, 255, 255, .78); border-radius: var(--vp-radius-pill); background: rgba(255, 255, 255, .72); box-shadow: var(--vp-shadow-low); backdrop-filter: blur(20px); }
    .nav-links { display: flex; align-items: center; gap: 28px; margin-left: auto; color: #4b5568; font-size: .86rem; font-weight: 650; }
    .nav-links a { transition: color 160ms ease; }
    .nav-links a:hover { color: var(--vp-primary); }
    .nav-shell .vp-button { min-height: 44px; box-shadow: none; }

    .hero { position: relative; padding: clamp(76px, 10vw, 132px) 0 98px; overflow: clip; }
    .hero-grid { display: grid; grid-template-columns: minmax(0, 1.02fr) minmax(430px, .98fr); align-items: center; gap: clamp(50px, 7vw, 96px); }
    .hero-copy h1 { max-width: 760px; margin: 24px 0 26px; color: var(--vp-secondary); font-family: Manrope, Inter, sans-serif; font-size: clamp(3.3rem, 6.2vw, 6.35rem); font-weight: 800; line-height: .94; letter-spacing: -.065em; }
    .hero-copy h1 span { display: block; }
    .hero-lead { max-width: 650px; margin: 0; color: var(--vp-text-muted); font-size: clamp(1.05rem, 1.4vw, 1.2rem); line-height: 1.72; }
    .hero-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 34px; }
    .hero-note { display: flex; align-items: center; gap: 12px; margin-top: 26px; color: var(--vp-text-muted); font-size: .8rem; font-weight: 600; }
    .hero-note-dots { display: flex; }
    .hero-note-dots span { width: 28px; height: 28px; display: grid; place-items: center; margin-left: -7px; border: 2px solid #fff; border-radius: 50%; color: #fff; background: var(--vp-secondary); font-size: .62rem; font-weight: 800; }
    .hero-note-dots span:first-child { margin-left: 0; background: var(--vp-primary); }
    .hero-note-dots span:nth-child(2) { background: var(--vp-accent-dark); }

    .product-stage { position: relative; min-height: 570px; display: grid; place-items: center; }
    .product-stage::before { content: ''; position: absolute; width: 88%; aspect-ratio: 1; border-radius: 50%; background: radial-gradient(circle, rgba(103, 87, 245, .22), rgba(34, 211, 182, .09) 48%, transparent 70%); filter: blur(4px); }
    .product-card { position: relative; width: min(100%, 520px); padding: 22px; border: 1px solid rgba(255, 255, 255, .78); border-radius: 30px; background: rgba(255, 255, 255, .78); box-shadow: var(--vp-shadow-high); backdrop-filter: blur(22px); transform: rotate(1.25deg); transition: transform 320ms var(--vp-ease); }
    .product-card:hover { transform: rotate(.35deg) translateY(-4px); }
    .product-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 2px 2px 18px; }
    .product-label { font-size: .8rem; font-weight: 750; }
    .product-period { padding: 7px 10px; border-radius: var(--vp-radius-pill); color: var(--vp-text-muted); background: var(--vp-surface-muted); font-size: .68rem; font-weight: 700; }
    .progress-panel { display: grid; grid-template-columns: 144px 1fr; gap: 26px; align-items: center; padding: 28px; border-radius: 24px; color: #fff; background: linear-gradient(145deg, #151d32, #0b1220 64%, #241d5d); overflow: hidden; }
    .progress-panel::after { content: ''; position: absolute; width: 180px; height: 180px; margin: -150px 0 0 330px; border-radius: 50%; background: rgba(103, 87, 245, .18); filter: blur(2px); }
    .progress-ring { width: 134px; height: 134px; display: grid; place-items: center; border-radius: 50%; background: conic-gradient(var(--vp-accent) 0 74%, rgba(255,255,255,.11) 74%); box-shadow: 0 0 0 9px rgba(255,255,255,.04); }
    .progress-ring::before { content: '74%'; width: 102px; height: 102px; display: grid; place-items: center; border-radius: 50%; background: var(--vp-secondary); font-family: Manrope, sans-serif; font-size: 1.75rem; font-weight: 800; }
    .progress-copy { position: relative; z-index: 1; }
    .progress-copy small { color: rgba(255,255,255,.58); font-size: .7rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; }
    .progress-copy h2 { margin: 8px 0; font-family: Manrope, sans-serif; font-size: 1.35rem; line-height: 1.2; letter-spacing: -.03em; }
    .progress-copy p { margin: 0; color: rgba(255,255,255,.64); font-size: .76rem; line-height: 1.5; }
    .learning-list { display: grid; gap: 10px; margin-top: 14px; }
    .learning-row { display: grid; grid-template-columns: 42px 1fr auto; align-items: center; gap: 13px; padding: 13px; border: 1px solid rgba(103, 87, 245, .09); border-radius: 17px; background: #fff; }
    .learning-icon { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 13px; color: var(--vp-primary); background: #f0edff; }
    .learning-icon svg { width: 20px; }
    .learning-row strong { display: block; margin-bottom: 4px; font-size: .77rem; }
    .learning-bar { width: 100%; max-width: 178px; height: 5px; overflow: hidden; border-radius: var(--vp-radius-pill); background: var(--vp-surface-muted); }
    .learning-bar span { display: block; width: var(--progress); height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--vp-primary), var(--vp-accent)); }
    .learning-row > span { color: var(--vp-text-muted); font-size: .72rem; font-weight: 700; }
    .floating-achievement { position: absolute; top: 28px; right: -24px; z-index: 3; display: flex; align-items: center; gap: 10px; padding: 12px 15px; border: 1px solid rgba(255,255,255,.8); border-radius: 16px; background: rgba(255,255,255,.86); box-shadow: var(--vp-shadow-medium); backdrop-filter: blur(16px); animation: achievement-float 4s var(--vp-ease) infinite alternate; }
    .floating-achievement .check { width: 32px; height: 32px; display: grid; place-items: center; border-radius: 11px; color: #fff; background: var(--vp-accent-dark); }
    .floating-achievement strong { display: block; font-size: .7rem; }
    .floating-achievement span { color: var(--vp-text-muted); font-size: .62rem; }
    @keyframes achievement-float { to { transform: translateY(8px); } }

    .proof-strip { position: relative; z-index: 3; margin-top: -26px; }
    .proof-grid { display: grid; grid-template-columns: repeat(3, 1fr); border: 1px solid rgba(103,87,245,.12); border-radius: 24px; background: rgba(255,255,255,.82); box-shadow: var(--vp-shadow-medium); backdrop-filter: blur(18px); }
    .proof-item { padding: 27px 32px; }
    .proof-item + .proof-item { border-left: 1px solid rgba(103,87,245,.1); }
    .proof-item strong { display: block; color: var(--vp-secondary); font-family: Manrope, sans-serif; font-size: 1.45rem; letter-spacing: -.04em; }
    .proof-item span { color: var(--vp-text-muted); font-size: .78rem; }

    .section { padding: clamp(88px, 10vw, 132px) 0; }
    .section-heading { max-width: 720px; }
    .section-heading h2 { margin: 20px 0 16px; color: var(--vp-secondary); font-family: Manrope, sans-serif; font-size: clamp(2.25rem, 4vw, 3.7rem); line-height: 1.04; letter-spacing: -.05em; }
    .section-heading p { margin: 0; color: var(--vp-text-muted); font-size: 1rem; line-height: 1.7; }
    .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-top: 48px; }
    .feature-card { position: relative; min-height: 300px; padding: 28px; overflow: hidden; border: 1px solid var(--vp-border); border-radius: var(--vp-radius-lg); background: rgba(255,255,255,.82); box-shadow: var(--vp-shadow-low); transition: transform 220ms var(--vp-ease), box-shadow 220ms var(--vp-ease); }
    .feature-card:hover { transform: translateY(-4px); box-shadow: var(--vp-shadow-medium); }
    .feature-card::after { content: ''; position: absolute; right: -60px; bottom: -70px; width: 170px; height: 170px; border-radius: 50%; background: var(--card-glow, rgba(103,87,245,.12)); filter: blur(4px); }
    .feature-icon { width: 50px; height: 50px; display: grid; place-items: center; border-radius: 16px; color: var(--icon-color, var(--vp-primary)); background: var(--icon-bg, #f0edff); }
    .feature-icon svg { width: 23px; }
    .feature-card h3 { margin: 54px 0 10px; font-family: Manrope, sans-serif; font-size: 1.22rem; letter-spacing: -.03em; }
    .feature-card p { margin: 0; color: var(--vp-text-muted); font-size: .88rem; line-height: 1.7; }

    .journey { padding-top: 30px; }
    .journey-shell { display: grid; grid-template-columns: .9fr 1.1fr; gap: 80px; align-items: center; padding: clamp(34px, 6vw, 68px); border-radius: 34px; color: #fff; background: linear-gradient(145deg, #0b1220, #151d32 58%, #261d61); box-shadow: var(--vp-shadow-high); overflow: hidden; }
    .journey-copy h2 { margin: 20px 0 16px; font-family: Manrope, sans-serif; font-size: clamp(2.2rem, 4vw, 3.55rem); line-height: 1.04; letter-spacing: -.05em; }
    .journey-copy p { margin: 0; color: rgba(255,255,255,.63); line-height: 1.7; }
    .journey .vp-eyebrow { color: #d8d2ff; border-color: rgba(255,255,255,.12); background: rgba(255,255,255,.06); box-shadow: none; }
    .journey-steps { display: grid; gap: 14px; }
    .journey-step { display: grid; grid-template-columns: 46px 1fr; gap: 16px; align-items: start; padding: 18px; border: 1px solid rgba(255,255,255,.1); border-radius: 18px; background: rgba(255,255,255,.055); }
    .journey-step span { width: 46px; height: 46px; display: grid; place-items: center; border-radius: 15px; color: var(--vp-secondary); background: var(--vp-accent); font-family: Manrope, sans-serif; font-weight: 800; }
    .journey-step h3 { margin: 2px 0 5px; font-family: Manrope, sans-serif; font-size: .98rem; }
    .journey-step p { margin: 0; color: rgba(255,255,255,.55); font-size: .76rem; line-height: 1.5; }

    .final-cta { padding: 110px 0 44px; text-align: center; }
    .final-cta h2 { max-width: 800px; margin: 20px auto 18px; color: var(--vp-secondary); font-family: Manrope, sans-serif; font-size: clamp(2.5rem, 5vw, 4.5rem); line-height: 1; letter-spacing: -.055em; }
    .final-cta p { max-width: 590px; margin: 0 auto 30px; color: var(--vp-text-muted); line-height: 1.7; }
    .site-footer { padding: 32px 0 42px; }
    .footer-shell { display: flex; align-items: center; justify-content: space-between; gap: 24px; padding-top: 28px; border-top: 1px solid rgba(103,87,245,.12); }
    .footer-shell p { margin: 0; color: var(--vp-text-muted); font-size: .75rem; }

    @media (max-width: 980px) {
        .nav-links { display: none; }
        .hero-grid { grid-template-columns: 1fr; }
        .hero-copy { text-align: center; }
        .hero-copy h1, .hero-lead { margin-inline: auto; }
        .hero-actions, .hero-note { justify-content: center; }
        .product-stage { min-height: 520px; }
        .feature-grid { grid-template-columns: 1fr 1fr; }
        .feature-card:last-child { grid-column: 1 / -1; min-height: 250px; }
        .journey-shell { grid-template-columns: 1fr; gap: 42px; }
    }

    @media (max-width: 650px) {
        .site-header { padding-top: 10px; }
        .nav-shell { min-height: 60px; padding-left: 10px; }
        .nav-shell .vp-brand { font-size: 1rem; }
        .nav-shell .vp-brand-mark { width: 34px; height: 34px; }
        .nav-shell .vp-button { min-height: 40px; padding-inline: 14px; font-size: .78rem; }
        .hero { padding-top: 66px; padding-bottom: 74px; }
        .hero-copy h1 { font-size: clamp(3rem, 15vw, 4.3rem); }
        .hero-actions .vp-button { width: 100%; }
        .product-stage { min-height: 450px; margin-top: 8px; }
        .product-card { padding: 14px; border-radius: 24px; transform: none; }
        .floating-achievement { top: -38px; right: 8px; }
        .progress-panel { grid-template-columns: 100px 1fr; gap: 18px; padding: 20px; }
        .progress-ring { width: 96px; height: 96px; }
        .progress-ring::before { width: 72px; height: 72px; font-size: 1.25rem; }
        .progress-copy h2 { font-size: 1.05rem; }
        .proof-grid { grid-template-columns: 1fr; }
        .proof-item { padding: 20px 24px; }
        .proof-item + .proof-item { border-left: 0; border-top: 1px solid rgba(103,87,245,.1); }
        .feature-grid { grid-template-columns: 1fr; }
        .feature-card:last-child { grid-column: auto; }
        .journey-shell { padding: 28px 20px; border-radius: 26px; }
        .footer-shell { align-items: flex-start; flex-direction: column; }
    }
@endsection

@section('content')
    <header class="site-header">
        <div class="vp-container nav-shell">
            <a class="vp-brand" href="{{ url('/') }}" aria-label="VoranaPro, inicio">
                <span class="vp-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4.5 6.2 12 18l7.5-11.8M8.1 6.2 12 12.5l3.9-6.3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span>Vorana<strong>Pro</strong></span>
            </a>
            <nav class="nav-links" aria-label="Navegación principal">
                <a href="#plataforma">Plataforma</a>
                <a href="#recorrido">Cómo funciona</a>
                <a href="#resultados">Resultados</a>
            </nav>
            <a class="vp-button vp-button--primary" href="{{ route('login') }}">Entrar a mi cuenta</a>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="vp-container hero-grid">
                <div class="hero-copy">
                    <div class="vp-eyebrow">Aprendizaje que sí avanza</div>
                    <h1>Tu próxima meta tiene una <span class="vp-gradient-text">ruta clara.</span></h1>
                    <p class="hero-lead">VoranaPro reúne cursos, evaluaciones, acompañamiento y certificados en una experiencia diseñada para que siempre sepas qué sigue.</p>
                    <div class="hero-actions">
                        <a class="vp-button vp-button--primary" href="{{ route('login') }}">
                            Comenzar ahora
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <a class="vp-button" href="#plataforma">Explorar la plataforma</a>
                    </div>
                    <div class="hero-note">
                        <span class="hero-note-dots" aria-hidden="true"><span>V</span><span>+</span><span>✓</span></span>
                        <span>Una experiencia para estudiantes, instructores y equipos.</span>
                    </div>
                </div>

                <div class="product-stage" aria-label="Vista previa del panel de aprendizaje">
                    <div class="floating-achievement">
                        <span class="check" aria-hidden="true">✓</span>
                        <span><strong>Tema completado</strong><span>Tu ruta se actualizó</span></span>
                    </div>
                    <div class="product-card">
                        <div class="product-header">
                            <div class="product-label">Mi recorrido</div>
                            <div class="product-period">Esta semana</div>
                        </div>
                        <div class="progress-panel">
                            <div class="progress-ring" aria-label="74 por ciento completado"></div>
                            <div class="progress-copy">
                                <small>Progreso general</small>
                                <h2>Estás construyendo impulso.</h2>
                                <p>3 temas para completar tu ruta actual.</p>
                            </div>
                        </div>
                        <div class="learning-list">
                            <div class="learning-row">
                                <div class="learning-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M5 4h11a3 3 0 0 1 3 3v13H7a2 2 0 0 1-2-2V4Zm2 12h12M8 8h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
                                <div><strong>Liderazgo de equipos</strong><div class="learning-bar"><span style="--progress:82%"></span></div></div>
                                <span>82%</span>
                            </div>
                            <div class="learning-row">
                                <div class="learning-icon" style="--icon-color:var(--vp-accent-dark);background:#e7fbf6"><svg viewBox="0 0 24 24" fill="none"><path d="m7 12 3 3 7-7M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                                <div><strong>Comunicación efectiva</strong><div class="learning-bar"><span style="--progress:56%"></span></div></div>
                                <span>56%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="proof-strip reveal-on-scroll" id="resultados" aria-label="Beneficios principales">
            <div class="vp-container proof-grid">
                <div class="proof-item"><strong>Una sola ruta</strong><span>Contenido, evaluación y avance conectados.</span></div>
                <div class="proof-item"><strong>Progreso visible</strong><span>Cada logro desbloquea el siguiente paso.</span></div>
                <div class="proof-item"><strong>Logros verificables</strong><span>Certificados digitales con validación segura.</span></div>
            </div>
        </section>

        <section class="section" id="plataforma">
            <div class="vp-container">
                <div class="section-heading reveal-on-scroll">
                    <div class="vp-eyebrow">Diseñada para avanzar</div>
                    <h2>Menos incertidumbre. Más aprendizaje con propósito.</h2>
                    <p>Cada parte de VoranaPro te orienta, mide lo que aprendiste y mantiene a tu equipo conectado con el progreso real.</p>
                </div>
                <div class="feature-grid">
                    <article class="feature-card reveal-on-scroll">
                        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 18V8m6 10V4m6 14v-7m4 7H2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
                        <h3>Avance que se entiende</h3>
                        <p>Visualiza tus temas completados, evaluaciones y próximos pasos sin perderte entre menús.</p>
                    </article>
                    <article class="feature-card reveal-on-scroll" style="--icon-color:var(--vp-accent-dark);--icon-bg:#e7fbf6;--card-glow:rgba(34,211,182,.13)">
                        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M8 10h8M8 14h5m8-2a9 9 0 1 1-4-7.5L21 4v5h-5l2.1-2.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        <h3>Acompañamiento oportuno</h3>
                        <p>Los instructores revisan respuestas, entregan retroalimentación y habilitan nuevos intentos cuando hace falta.</p>
                    </article>
                    <article class="feature-card reveal-on-scroll" style="--icon-color:#8b5cf6;--icon-bg:#f2edff;--card-glow:rgba(139,92,246,.14)">
                        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3 14.6 8l5.4.8-3.9 3.8.9 5.4-5-2.6L7 18l1-5.4-4-3.8L9.4 8 12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></div>
                        <h3>Logros que tienen valor</h3>
                        <p>Completa tu ruta y recibe certificados profesionales con código único y verificación digital.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="journey" id="recorrido">
            <div class="vp-container journey-shell reveal-on-scroll">
                <div class="journey-copy">
                    <div class="vp-eyebrow">Tu recorrido</div>
                    <h2>Un siguiente paso claro, desde el primer día.</h2>
                    <p>VoranaPro mantiene el aprendizaje en movimiento con reglas simples, orientación permanente y evidencia de cada logro.</p>
                </div>
                <div class="journey-steps">
                    <div class="journey-step"><span>01</span><div><h3>Aprende a tu ritmo</h3><p>Explora contenidos, recursos y presentaciones interactivas desde cualquier dispositivo.</p></div></div>
                    <div class="journey-step"><span>02</span><div><h3>Demuestra lo aprendido</h3><p>Completa evaluaciones claras y recibe retroalimentación cuando la necesitas.</p></div></div>
                    <div class="journey-step"><span>03</span><div><h3>Desbloquea tu siguiente meta</h3><p>Tu avance actualiza la ruta y te acerca a una certificación verificable.</p></div></div>
                </div>
            </div>
        </section>

        <section class="final-cta">
            <div class="vp-container reveal-on-scroll">
                <div class="vp-eyebrow">Tu progreso empieza aquí</div>
                <h2>Haz visible todo lo que eres capaz de aprender.</h2>
                <p>Ingresa a tu cuenta y continúa exactamente donde quedaste.</p>
                <a class="vp-button vp-button--primary" href="{{ route('login') }}">Entrar a VoranaPro</a>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="vp-container footer-shell">
            <a class="vp-brand" href="{{ url('/') }}" aria-label="VoranaPro, inicio">
                <span class="vp-brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4.5 6.2 12 18l7.5-11.8M8.1 6.2 12 12.5l3.9-6.3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>Vorana<strong>Pro</strong></span>
            </a>
            <p>Aprendizaje con dirección, acompañamiento y resultados.</p>
        </div>
    </footer>
@endsection
