<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · AulaPro</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif; color: #10213f; background: radial-gradient(circle at top left, #dbeafe, transparent 42%), radial-gradient(circle at bottom right, #ede9fe, transparent 38%), #f7f9fc; }
        .card { width: min(440px, 100%); padding: 38px; border: 1px solid #e2e8f0; border-radius: 24px; background: rgba(255, 255, 255, .96); box-shadow: 0 28px 80px rgba(30, 58, 138, .16); }
        .brand { display: inline-block; color: #10213f; font-size: 22px; font-weight: 850; letter-spacing: -.6px; text-decoration: none; }
        .brand span { color: #2563eb; }
        h1 { margin: 30px 0 8px; font-size: 31px; letter-spacing: -1.2px; }
        .lead { margin: 0 0 28px; color: #64748b; line-height: 1.55; }
        label { display: block; margin: 18px 0 7px; font-size: 14px; font-weight: 750; }
        input[type="email"], input[type="password"] { width: 100%; padding: 13px 14px; border: 1px solid #cbd5e1; border-radius: 11px; outline: none; font: inherit; color: #10213f; background: #fff; transition: border-color .15s, box-shadow .15s; }
        input:focus { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37, 99, 235, .12); }
        .remember { display: flex; align-items: center; gap: 9px; margin: 19px 0 23px; color: #475569; font-size: 14px; }
        .remember input { width: 16px; height: 16px; accent-color: #2563eb; }
        button { width: 100%; padding: 14px 18px; border: 0; border-radius: 11px; color: #fff; background: linear-gradient(135deg, #2563eb, #7c3aed); box-shadow: 0 12px 28px rgba(37, 99, 235, .25); cursor: pointer; font: inherit; font-weight: 800; }
        button:hover { filter: brightness(.97); }
        .error { margin-top: 8px; color: #b91c1c; font-size: 13px; }
        .roles { margin-top: 24px; padding-top: 21px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 13px; line-height: 1.55; text-align: center; }
        @media (max-width: 520px) { .card { padding: 28px 22px; } }
    </style>
</head>
<body>
    <main class="card">
        <a class="brand" href="{{ url('/') }}">Aula<span>Pro</span></a>
        <h1>Bienvenido de nuevo</h1>
        <p class="lead">Ingresa con tu cuenta. Te llevaremos automáticamente al panel que te corresponde.</p>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <div class="error" role="alert">{{ $message }}</div>
            @enderror

            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            @error('password')
                <div class="error" role="alert">{{ $message }}</div>
            @enderror

            <label class="remember" for="remember">
                <input id="remember" name="remember" type="checkbox" value="1">
                Mantener mi sesión iniciada
            </label>

            <button type="submit">Ingresar</button>
        </form>

        <div class="roles">Un solo acceso para estudiantes, instructores y administradores.</div>
    </main>
</body>
</html>
