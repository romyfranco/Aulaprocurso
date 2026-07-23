@extends('layouts.public')

@section('title', 'Iniciar sesión · VoranaPro')
@section('body-class', 'login-page')

@section('styles')
    .login-page { min-height: 100vh; background: #eef0f6; }
    .login-shell { min-height: 100vh; display: grid; grid-template-columns: minmax(420px, .92fr) minmax(520px, 1.08fr); }
    .login-story { position: relative; display: flex; min-height: 100%; flex-direction: column; justify-content: space-between; padding: clamp(34px, 5vw, 72px); color: #fff; background: linear-gradient(145deg, #0b1220 0%, #151d32 54%, #271d65 100%); overflow: hidden; }
    .login-story::before { content: ''; position: absolute; inset: 0; pointer-events: none; opacity: .25; background-image: radial-gradient(circle, rgba(255,255,255,.22) 1px, transparent 1px); background-size: 29px 29px; mask-image: linear-gradient(145deg, black, transparent 72%); }
    .login-story::after { content: ''; position: absolute; right: -24%; bottom: -20%; width: 68%; aspect-ratio: 1; border-radius: 50%; background: linear-gradient(135deg, rgba(103,87,245,.72), rgba(34,211,182,.42)); filter: blur(4px); opacity: .48; }
    .login-story .vp-brand { position: relative; z-index: 2; color: #fff; }
    .login-story .vp-brand strong { color: #a89cff; }
    .story-content { position: relative; z-index: 2; max-width: 570px; margin: clamp(70px, 12vh, 150px) 0; }
    .story-kicker { display: inline-flex; align-items: center; gap: 9px; padding: 8px 12px; border: 1px solid rgba(255,255,255,.12); border-radius: var(--vp-radius-pill); color: #d8d2ff; background: rgba(255,255,255,.06); font-size: .72rem; font-weight: 750; letter-spacing: .09em; text-transform: uppercase; backdrop-filter: blur(12px); }
    .story-kicker::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--vp-accent); }
    .story-content h1 { margin: 24px 0 18px; font-family: Manrope, sans-serif; font-size: clamp(2.75rem, 5vw, 5.25rem); line-height: .98; letter-spacing: -.06em; }
    .story-content > p { max-width: 510px; margin: 0; color: rgba(255,255,255,.62); font-size: 1rem; line-height: 1.72; }
    .journey-preview { max-width: 520px; display: grid; gap: 10px; margin-top: 38px; }
    .journey-line { display: grid; grid-template-columns: 38px 1fr auto; gap: 13px; align-items: center; padding: 12px 14px; border: 1px solid rgba(255,255,255,.1); border-radius: 16px; background: rgba(255,255,255,.055); backdrop-filter: blur(12px); }
    .journey-line-icon { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 12px; color: var(--vp-secondary); background: var(--vp-accent); font-size: .76rem; font-weight: 800; }
    .journey-line strong { display: block; margin-bottom: 3px; font-size: .76rem; }
    .journey-line small { color: rgba(255,255,255,.5); font-size: .66rem; }
    .journey-line > span:last-child { color: #a89cff; font-size: .68rem; font-weight: 750; }
    .story-footer { position: relative; z-index: 2; color: rgba(255,255,255,.4); font-size: .7rem; }

    .login-access { position: relative; display: grid; place-items: center; padding: 48px clamp(24px, 7vw, 110px); background: rgba(247,248,252,.88); }
    .login-access::before { content: ''; position: absolute; top: 10%; right: 6%; width: 240px; height: 240px; border-radius: 50%; background: rgba(103,87,245,.12); filter: blur(72px); pointer-events: none; }
    .login-card { position: relative; z-index: 1; width: min(100%, 480px); padding: clamp(28px, 4vw, 48px); border: 1px solid rgba(255,255,255,.8); border-radius: 30px; background: rgba(255,255,255,.84); box-shadow: var(--vp-shadow-medium); backdrop-filter: blur(22px); }
    .mobile-brand { display: none; margin-bottom: 36px; }
    .login-card .vp-eyebrow { box-shadow: none; }
    .login-card h2 { margin: 22px 0 10px; color: var(--vp-secondary); font-family: Manrope, sans-serif; font-size: clamp(2rem, 4vw, 2.8rem); line-height: 1.04; letter-spacing: -.05em; }
    .login-lead { margin: 0 0 30px; color: var(--vp-text-muted); font-size: .9rem; line-height: 1.65; }
    .field { margin-top: 19px; }
    .field label { display: block; margin-bottom: 8px; color: #364153; font-size: .78rem; font-weight: 700; }
    .input-shell { position: relative; }
    .input-shell > svg { position: absolute; top: 50%; left: 15px; width: 19px; color: #9aa3b5; transform: translateY(-50%); pointer-events: none; }
    .input-shell input { width: 100%; min-height: 52px; padding: 0 46px; border: 1px solid #d8dce8; border-radius: 14px; outline: none; color: var(--vp-text); background: rgba(255,255,255,.88); transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease; }
    .input-shell input::placeholder { color: #9aa3b5; }
    .input-shell input:focus { border-color: var(--vp-primary); background: #fff; box-shadow: 0 0 0 4px rgba(103,87,245,.11); }
    .password-toggle { position: absolute; top: 50%; right: 10px; width: 36px; height: 36px; display: grid; place-items: center; border: 0; border-radius: 10px; color: #697386; background: transparent; cursor: pointer; transform: translateY(-50%); }
    .password-toggle:hover { color: var(--vp-primary); background: #f0edff; }
    .password-toggle svg { width: 19px; }
    .form-options { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin: 20px 0 24px; }
    .remember { display: inline-flex; align-items: center; gap: 9px; color: #596477; font-size: .76rem; cursor: pointer; }
    .remember input { width: 17px; height: 17px; accent-color: var(--vp-primary); }
    .login-submit { width: 100%; min-height: 52px; border: 0; cursor: pointer; }
    .login-submit:hover { transform: translateY(-2px); }
    .login-submit:focus-visible { outline: 3px solid rgba(103,87,245,.24); outline-offset: 3px; }
    .form-error { display: flex; align-items: flex-start; gap: 8px; margin-top: 8px; color: var(--vp-error); font-size: .72rem; line-height: 1.45; }
    .form-error::before { content: '!'; flex: 0 0 18px; height: 18px; display: grid; place-items: center; border-radius: 50%; color: #fff; background: var(--vp-error); font-size: .62rem; font-weight: 800; }
    .login-role-note { display: flex; align-items: center; justify-content: center; gap: 9px; margin: 24px 0 0; padding-top: 22px; border-top: 1px solid #eef0f6; color: var(--vp-text-muted); font-size: .7rem; text-align: center; }
    .login-role-note svg { width: 16px; color: var(--vp-accent-dark); }

    @media (max-width: 960px) {
        .login-shell { grid-template-columns: 1fr; }
        .login-story { min-height: auto; padding: 30px 32px 70px; }
        .story-content { margin: 68px 0 0; }
        .story-content h1 { max-width: 700px; }
        .journey-preview, .story-footer { display: none; }
        .login-access { min-height: auto; padding: 0 24px 72px; background: linear-gradient(to bottom, var(--vp-secondary) 0 70px, var(--vp-bg) 70px); }
        .login-card { margin-top: -28px; }
    }

    @media (max-width: 560px) {
        .login-story { padding: 24px 20px 58px; }
        .login-story > .vp-brand { display: none; }
        .story-content { margin-top: 28px; }
        .story-content h1 { font-size: clamp(2.65rem, 13vw, 3.75rem); }
        .story-content > p { font-size: .9rem; }
        .login-access { padding-inline: 16px; padding-bottom: 40px; }
        .login-card { padding: 28px 20px; border-radius: 24px; }
        .mobile-brand { display: inline-flex; }
        .form-options { align-items: flex-start; flex-direction: column; }
    }
@endsection

@section('content')
    <main class="login-shell">
        <section class="login-story" aria-labelledby="story-title">
            <a class="vp-brand" href="{{ url('/') }}" aria-label="VoranaPro, volver al inicio">
                <span class="vp-brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4.5 6.2 12 18l7.5-11.8M8.1 6.2 12 12.5l3.9-6.3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>Vorana<strong>Pro</strong></span>
            </a>
            <div class="story-content">
                <div class="story-kicker">Tu recorrido continúa</div>
                <h1 id="story-title">Vuelve al punto exacto donde estabas creciendo.</h1>
                <p>Tu progreso, tus evaluaciones y tus próximos logros están listos para continuar.</p>
                <div class="journey-preview" aria-hidden="true">
                    <div class="journey-line"><span class="journey-line-icon">✓</span><div><strong>Fundamentos completados</strong><small>Ruta actualizada automáticamente</small></div><span>100%</span></div>
                    <div class="journey-line"><span class="journey-line-icon" style="background:#a89cff">02</span><div><strong>Comunicación efectiva</strong><small>Próximo tema disponible</small></div><span>56%</span></div>
                </div>
            </div>
            <div class="story-footer">Aprendizaje con dirección · VoranaPro</div>
        </section>

        <section class="login-access" aria-labelledby="login-title">
            <div class="login-card">
                <a class="vp-brand mobile-brand" href="{{ url('/') }}" aria-label="VoranaPro, volver al inicio">
                    <span class="vp-brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4.5 6.2 12 18l7.5-11.8M8.1 6.2 12 12.5l3.9-6.3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span>Vorana<strong>Pro</strong></span>
                </a>
                <div class="vp-eyebrow">Acceso seguro</div>
                <h2 id="login-title">Bienvenido de nuevo.</h2>
                <p class="login-lead">Ingresa con tu cuenta y te llevaremos al espacio que te corresponde.</p>

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="field">
                        <label for="email">Correo electrónico</label>
                        <div class="input-shell">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16v12H4V6Zm0 1 8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nombre@empresa.com" required autofocus autocomplete="username" aria-describedby="email-error">
                        </div>
                        @error('email')<div class="form-error" id="email-error" role="alert">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="password">Contraseña</label>
                        <div class="input-shell">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2m-11 0h12v10H6V10Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <input id="password" name="password" type="password" placeholder="Tu contraseña" required autocomplete="current-password" aria-describedby="password-error">
                            <button class="password-toggle" type="button" aria-label="Mostrar contraseña" aria-controls="password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12S6 6.5 12 6.5 21.5 12 21.5 12 18 17.5 12 17.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                            </button>
                        </div>
                        @error('password')<div class="form-error" id="password-error" role="alert">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-options">
                        <label class="remember" for="remember"><input id="remember" name="remember" type="checkbox" value="1">Mantener mi sesión iniciada</label>
                    </div>

                    <button class="vp-button vp-button--primary login-submit" type="submit">
                        Ingresar a VoranaPro
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>

                <p class="login-role-note"><svg viewBox="0 0 24 24" fill="none"><path d="m7 12 3 3 7-7M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>Un acceso para estudiantes, instructores y administradores.</p>
            </div>
        </section>
    </main>
@endsection

@section('scripts')
    <script>
        const passwordToggle = document.querySelector('.password-toggle');
        const passwordInput = document.getElementById('password');
        passwordToggle?.addEventListener('click', () => {
            const shouldShow = passwordInput.type === 'password';
            passwordInput.type = shouldShow ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed', String(shouldShow));
            passwordToggle.setAttribute('aria-label', shouldShow ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    </script>
@endsection
