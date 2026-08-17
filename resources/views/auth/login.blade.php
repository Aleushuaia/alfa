<!doctype html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión — {{ config('app.name', 'Alfa colaborador inteligente') }}</title>

    <script>
        (function(){var t=localStorage.getItem('alfa-theme')||'light';document.documentElement.setAttribute('data-theme',t);})();
    </script>

    <link rel="icon" href="{{ asset('alfa.png') }}" sizes="100x100" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        /* ── CSS Variables ─────────────────────────────────────────────── */
        :root {
            --accent: #3b82f6;
            --accent2: #60a5fa;
        }

        /* ── Light theme (default) ─────────────────────────────────────── */
        html[data-theme="light"], html:not([data-theme]) {
            --login-bg: #f1f5f9;
            --login-card-bg: #ffffff;
            --login-card-border: rgba(0,0,0,.08);
            --login-card-shadow: 0 8px 40px rgba(0,0,0,.08);
            --login-text: #1e293b;
            --login-muted: #64748b;
            --login-input-bg: #f8fafc;
            --login-input-border: #e2e8f0;
            --login-input-color: #1e293b;
            --login-input-focus: var(--accent);
            --login-footer: #94a3b8;
        }

        /* ── Dark theme ────────────────────────────────────────────────── */
        html[data-theme="dark"] {
            --login-bg: #0b0f19;
            --login-card-bg: #111827;
            --login-card-border: rgba(255,255,255,.06);
            --login-card-shadow: 0 8px 40px rgba(0,0,0,.35);
            --login-text: #e2e8f0;
            --login-muted: #94a3b8;
            --login-input-bg: #1e293b;
            --login-input-border: rgba(255,255,255,.1);
            --login-input-color: #e2e8f0;
            --login-input-focus: var(--accent);
            --login-footer: #475569;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--login-bg);
            color: var(--login-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: background .3s, color .3s;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: .4rem;
        }

        .brand-avatar {
            flex-shrink: 0;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid rgba(59,130,246,.25);
            box-shadow: 0 0 0 4px rgba(59,130,246,.08), 0 4px 14px rgba(0,0,0,.12);
            background: var(--login-input-bg);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .brand-text {
            text-align: left;
        }

        .brand-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .brand-text p {
            font-size: .82rem;
            color: var(--login-muted);
            margin: .2rem 0 0;
        }

        .mode-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .2rem .65rem;
            border-radius: 20px;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
        }

        .mode-badge.dev {
            background: rgba(245,158,11,.12);
            border: 1px solid rgba(245,158,11,.35);
            color: #d97706;
        }

        .mode-badge.prod {
            background: rgba(16,185,129,.1);
            border: 1px solid rgba(16,185,129,.3);
            color: #059669;
        }

        .login-brand > img {
            display: none;
        }

        .login-brand h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-brand p {
            font-size: .85rem;
            color: var(--login-muted);
            margin: .25rem 0 0;
        }

        .login-card {
            background: var(--login-card-bg);
            border: 1px solid var(--login-card-border);
            border-radius: 16px;
            box-shadow: var(--login-card-shadow);
            padding: 2rem;
            transition: background .3s, border-color .3s, box-shadow .3s;
        }

        .login-card h2 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 1.5rem;
            color: var(--login-text);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--login-muted);
            margin-bottom: .4rem;
        }

        .form-group .input-wrap {
            position: relative;
        }

        .form-group .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--login-muted);
            font-size: .9rem;
            pointer-events: none;
        }

        .form-group input {
            width: 100%;
            padding: .7rem .75rem .7rem 2.5rem;
            font-family: 'Inter', sans-serif;
            font-size: .9rem;
            color: var(--login-input-color);
            background: var(--login-input-bg);
            border: 1.5px solid var(--login-input-border);
            border-radius: 10px;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .3s;
        }

        .form-group input:focus {
            border-color: var(--login-input-focus);
            box-shadow: 0 0 0 3px rgba(59,130,246,.15);
        }

        .form-group input::placeholder {
            color: var(--login-muted);
            opacity: .6;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: .82rem;
        }

        .form-options label {
            display: flex;
            align-items: center;
            gap: .4rem;
            color: var(--login-muted);
            cursor: pointer;
            font-weight: 500;
            text-transform: none;
            letter-spacing: 0;
        }

        .form-options input[type="checkbox"] {
            accent-color: var(--accent);
            width: 15px;
            height: 15px;
        }

        .btn-login {
            display: block;
            width: 100%;
            padding: .75rem;
            font-family: 'Inter', sans-serif;
            font-size: .95rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
        }

        .btn-login:hover {
            opacity: .92;
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert-login {
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.25);
            color: #f87171;
            padding: .65rem 1rem;
            border-radius: 10px;
            font-size: .85rem;
            margin-bottom: 1.25rem;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: .75rem;
            color: var(--login-footer);
        }

        /* ── Theme switch on login ─────────────────────────────────────── */
        .login-theme-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
            z-index: 100;
        }

        .login-theme-toggle .theme-switch {
            position: relative;
            width: 40px;
            height: 22px;
        }

        .login-theme-toggle .theme-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .login-theme-toggle .theme-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #cbd5e1;
            border-radius: 22px;
            transition: background .3s;
        }

        .login-theme-toggle .theme-slider::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: transform .3s;
        }

        .login-theme-toggle input:checked + .theme-slider {
            background: var(--accent);
        }

        .login-theme-toggle input:checked + .theme-slider::before {
            transform: translateX(18px);
        }

        .login-theme-toggle .theme-label {
            font-size: .78rem;
            font-weight: 600;
            color: var(--login-muted);
        }
    </style>
</head>
<body>

    {{-- Theme toggle --}}
    <div class="login-theme-toggle">
        <label class="theme-switch">
            <input type="checkbox" id="loginDarkSwitch">
            <span class="theme-slider"></span>
        </label>
        <span class="theme-label">Dark</span>
    </div>

    <div class="login-container">
        <div class="login-brand">
            <div class="brand-row">
                <div class="brand-avatar">
                    <img src="{{ asset('alfa.png') }}" alt="Alfa">
                </div>
                <div class="brand-text">
                    <h1>Alfa</h1>
                    <p>colaborador inteligente</p>
                </div>
            </div>
            @if(config('app.mode') === 'DEV')
                <span class="mode-badge dev"><i class="fas fa-code"></i> Modo desarrollo</span>
            @else
                <span class="mode-badge prod"><i class="fas fa-shield-halved"></i> Modo producción</span>
            @endif
        </div>

        <div class="login-card">
            <h2><i class="fas fa-lock me-2" style="color:var(--accent)"></i>Iniciar sesión</h2>

            @if($errors->any())
                <div class="alert-login">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            @if(config('app.mode') === 'DEV')
            <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.35);border-radius:10px;padding:.65rem 1rem;margin-bottom:1.25rem;font-size:.82rem;color:#d97706;display:flex;align-items:flex-start;gap:.5rem">
                <i class="fas fa-triangle-exclamation mt-1 flex-shrink-0"></i>
                <span><strong>Modo desarrollo activo.</strong> Ingresá con cualquier email registrado. La contraseña no es obligatoria.</span>
            </div>
            <div style="background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.18);border-radius:10px;padding:.6rem .9rem;margin-bottom:1rem;font-size:.82rem;color:#0353a4;display:flex;align-items:center;gap:.6rem">
                <i class="fas fa-user-shield" style="flex-shrink:0;font-size:1.05rem;color:rgba(59,130,246,.9)"></i>
                <div>
                    <strong>Credenciales de prueba:</strong>
                    <div style="font-size:.85rem;color:var(--login-muted);margin-top:.15rem">Usuario: <strong>admin</strong> &nbsp;·&nbsp; Email: <strong>admin@alfa.local</strong></div>
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email"
                               value="{{ old('email') }}"
                               placeholder="nombre@ejemplo.com"
                               required autofocus autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">
                        Contraseña
                        @if(config('app.mode') === 'DEV')
                        <span style="text-transform:none;letter-spacing:0;font-weight:400;color:var(--login-muted);font-size:.75rem">(opcional en modo dev)</span>
                        @endif
                    </label>
                    <div class="input-wrap">
                        <i class="fas fa-key"></i>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               @if(config('app.mode') !== 'DEV') required @endif
                               autocomplete="current-password">
                    </div>
                </div>

                <div class="form-options">
                    <label>
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        Recordarme
                    </label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Ingresar
                </button>
            </form>
        </div>

        <div class="login-footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'Alfa colaborador inteligente') }}
        </div>
    </div>

    <script>
        (function(){
            var html = document.documentElement;
            var sw = document.getElementById('loginDarkSwitch');
            var stored = localStorage.getItem('alfa-theme') || 'light';
            sw.checked = (stored === 'dark');
            sw.addEventListener('change', function(){
                var next = sw.checked ? 'dark' : 'light';
                html.setAttribute('data-theme', next);
                localStorage.setItem('alfa-theme', next);
            });
        })();
    </script>
</body>
</html>
