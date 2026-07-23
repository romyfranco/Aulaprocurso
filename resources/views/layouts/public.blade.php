<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B1220">
    <meta name="description" content="VoranaPro convierte el aprendizaje en progreso claro, acompañado y verificable.">
    <title>@yield('title', 'VoranaPro')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --vp-primary: #6757f5;
            --vp-primary-dark: #4c3dd3;
            --vp-secondary: #0b1220;
            --vp-accent: #22d3b6;
            --vp-accent-dark: #159a7e;
            --vp-bg: #f7f8fc;
            --vp-surface: #fff;
            --vp-surface-muted: #eef0f6;
            --vp-text: #172033;
            --vp-text-muted: #697386;
            --vp-border: rgba(103, 87, 245, .14);
            --vp-error: #dc3157;
            --vp-radius-sm: 10px;
            --vp-radius-md: 16px;
            --vp-radius-lg: 28px;
            --vp-radius-pill: 999px;
            --vp-shadow-low: 0 8px 24px rgba(21, 24, 44, .06);
            --vp-shadow-medium: 0 24px 64px rgba(38, 30, 96, .12);
            --vp-shadow-high: 0 36px 110px rgba(27, 22, 70, .2);
            --vp-ease: cubic-bezier(.2, .8, .2, 1);
            --vp-content: 1200px;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; min-width: 320px; color: var(--vp-text); background: var(--vp-bg); font-family: Inter, ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        body::before { content: ''; position: fixed; inset: 0; z-index: -3; pointer-events: none; opacity: .32; background-image: radial-gradient(circle, rgba(103, 87, 245, .16) 1px, transparent 1px); background-size: 28px 28px; mask-image: linear-gradient(to bottom, black, transparent 68%); }
        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }
        .vp-container { width: min(var(--vp-content), calc(100% - 48px)); margin-inline: auto; }
        .vp-brand { display: inline-flex; align-items: center; gap: 11px; color: var(--vp-secondary); font-family: Manrope, Inter, sans-serif; font-size: 1.18rem; font-weight: 800; letter-spacing: -.045em; }
        .vp-brand strong { color: var(--vp-primary); font-weight: 800; }
        .vp-brand-mark { width: 36px; height: 36px; display: grid; place-items: center; border-radius: 12px; color: #fff; background: linear-gradient(145deg, var(--vp-primary), #8d7cff 55%, var(--vp-accent)); box-shadow: 0 10px 24px rgba(103, 87, 245, .28); }
        .vp-brand-mark svg { width: 21px; height: 21px; }
        .vp-button { min-height: 48px; display: inline-flex; align-items: center; justify-content: center; gap: 9px; padding: 0 20px; border: 1px solid rgba(23, 32, 51, .1); border-radius: var(--vp-radius-pill); color: var(--vp-text); background: rgba(255, 255, 255, .74); box-shadow: var(--vp-shadow-low); font-size: .9rem; font-weight: 700; transition: transform 180ms var(--vp-ease), box-shadow 180ms var(--vp-ease), border-color 180ms var(--vp-ease), background 180ms var(--vp-ease); }
        .vp-button:hover { transform: translateY(-2px); border-color: rgba(103, 87, 245, .28); box-shadow: 0 14px 34px rgba(38, 30, 96, .11); }
        .vp-button:focus-visible, input:focus-visible, .password-toggle:focus-visible { outline: 3px solid rgba(103, 87, 245, .24); outline-offset: 3px; }
        .vp-button--primary { color: #fff; border-color: transparent; background: linear-gradient(135deg, var(--vp-primary), #7968fa); box-shadow: 0 16px 34px rgba(103, 87, 245, .28); }
        .vp-button--primary:hover { border-color: transparent; background: linear-gradient(135deg, var(--vp-primary-dark), var(--vp-primary)); box-shadow: 0 20px 42px rgba(103, 87, 245, .34); }
        .vp-button svg { width: 18px; height: 18px; }
        .vp-eyebrow { display: inline-flex; align-items: center; gap: 9px; padding: 8px 12px; border: 1px solid rgba(103, 87, 245, .15); border-radius: var(--vp-radius-pill); color: var(--vp-primary-dark); background: rgba(255, 255, 255, .68); font-size: .75rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; box-shadow: var(--vp-shadow-low); backdrop-filter: blur(14px); }
        .vp-eyebrow::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--vp-accent); box-shadow: 0 0 0 5px rgba(34, 211, 182, .12); }
        .vp-gradient-text { color: transparent; background: linear-gradient(110deg, var(--vp-primary) 5%, #8b62e9 45%, var(--vp-accent-dark)); background-clip: text; -webkit-background-clip: text; }
        .vp-muted { color: var(--vp-text-muted); }
        .reveal-on-scroll { opacity: 0; transform: translateY(18px); transition: opacity 700ms var(--vp-ease), transform 700ms var(--vp-ease); }
        .reveal-on-scroll.is-visible { opacity: 1; transform: none; }
        .ambient-orb { position: fixed; z-index: -2; width: 34vw; aspect-ratio: 1; border-radius: 50%; filter: blur(90px); opacity: .16; pointer-events: none; animation: vp-float 18s var(--vp-ease) infinite alternate; }
        .ambient-orb--one { top: -14vw; right: -10vw; background: var(--vp-primary); }
        .ambient-orb--two { bottom: -18vw; left: -14vw; background: var(--vp-accent); animation-delay: -8s; }
        @keyframes vp-float { to { transform: translate3d(-4vw, 5vh, 0) scale(1.08); } }

        @media (max-width: 720px) {
            .vp-container { width: min(100% - 32px, var(--vp-content)); }
            .vp-button { min-height: 46px; padding-inline: 17px; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
            .reveal-on-scroll { opacity: 1; transform: none; }
        }

        @yield('styles')
    </style>
</head>
<body class="@yield('body-class')">
    <div class="ambient-orb ambient-orb--one" aria-hidden="true"></div>
    <div class="ambient-orb ambient-orb--two" aria-hidden="true"></div>
    @yield('content')
    <script>
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });
            document.querySelectorAll('.reveal-on-scroll').forEach((element) => observer.observe(element));
        } else {
            document.querySelectorAll('.reveal-on-scroll').forEach((element) => element.classList.add('is-visible'));
        }
    </script>
    @yield('scripts')
</body>
</html>
