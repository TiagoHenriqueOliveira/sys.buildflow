<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Login | {{ config('sbadmin.brand.name', config('app.name')) }}</title>

        {{-- Mesmo script do <x-sbadmin::layout> para evitar flash de tema errado
             (le a preferencia salva ou o prefers-color-scheme do sistema). --}}
        <script>
            (function () {
                var stored = localStorage.getItem('sbadmin-theme');
                var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.setAttribute('data-bs-theme', theme);
            })();
        </script>

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])

        {{-- Teste de tipografia especifico desta tela de login (nao afeta o
             restante do app, que continua em Poppins via design system). --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Ubuntu+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

        <style>
            body.sbadmin-login-body {
                min-height: 100vh;
                display: flex;
                align-items: center;
                background-color: var(--sbadmin-bg);
                font-family: 'Ubuntu Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }

            .sbadmin-login-card {
                max-width: 400px;
                margin: 0 auto;
                padding: 2rem;
                border-radius: var(--sbadmin-radius-lg, 0.75rem);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                background: var(--sbadmin-surface);
                border: 1px solid var(--sbadmin-border);
            }

            .sbadmin-login-logo {
                text-align: center;
                margin-bottom: 2rem;
            }

            .sbadmin-login-brand-h1 {
                font-size: 1.75rem;
                line-height: 1.2;
                margin: 0 0 0.25rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                color: var(--sbadmin-text);
                text-align: center;
            }

            .sbadmin-login-brand-h2 {
                font-size: 1.15rem;
                line-height: 1.25;
                margin: 0 0 0.5rem;
                font-weight: 600;
                color: var(--sbadmin-text);
                text-align: center;
            }

            .sbadmin-login-brand-h3 {
                font-size: 0.9rem;
                line-height: 1.35;
                margin: 0 0 1rem;
                font-weight: 400;
                color: var(--sbadmin-text-muted);
                text-align: center;
            }
        </style>
    </head>

    <body class="sbadmin-login-body">
        <div class="container">
            <div class="sbadmin-login-card">
                <div class="sbadmin-login-logo">
                    <h1 class="sbadmin-login-brand-h1">BUILDFLOW</h1>
                    <h2 class="sbadmin-login-brand-h2">FAÉ Bioenergia</h2>
                    <h3 class="sbadmin-login-brand-h3">Gestão Comercial, Start-up e Assistência Técnica</h3>
                </div>

                @if($errors->any())
                    <x-sbadmin::alert type="error">
                        @foreach($errors->all() as $error)
                            {{ $error }}@if(!$loop->last)<br>@endif
                        @endforeach
                    </x-sbadmin::alert>
                @endif

                <form method="POST" action="{{ route('login.post') }}" id="loginForm">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email"
                            id="email"
                            class="form-control"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email">
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password"
                            id="senha"
                            class="form-control"
                            name="senha"
                            required
                            autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2" id="loginButton">
                        <span id="buttonText">Entrar</span>
                        <span id="loadingSpinner"
                            class="spinner-border spinner-border-sm d-none ms-2"
                            role="status"
                            aria-hidden="true"></span>
                    </button>
                </form>
            </div>
        </div>

        <script>
            document.getElementById('loginForm').addEventListener('submit', function () {
                const button = document.getElementById('loginButton');
                const buttonText = document.getElementById('buttonText');
                const spinner = document.getElementById('loadingSpinner');

                button.classList.add('loading');
                button.style.opacity = '0.7';
                button.style.pointerEvents = 'none';
                buttonText.textContent = 'Autenticando...';
                spinner.classList.remove('d-none');
                button.disabled = true;
            });
        </script>
    </body>
</html>
