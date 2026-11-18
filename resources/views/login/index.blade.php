<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ config('sbadmin.brand.name') }}</title>

        {{-- Mesmo script do <x-sbadmin::layout>: tema sempre escuro, sem
             alternancia (ver decisao do produto - dark mode unico). --}}
        <script>
            (function () {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            })();
        </script>

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    </head>

    {{-- Visual aprovado pelo cliente em 2026-11-18 (opção B): padrão da tela
         de login do promaq-sys + logo da FAÉ com texto branco. Estilos em
         resources/sass/app.scss (.fae-auth-*). O nome "FAÉ Bioenergia" saiu
         do texto porque já está na logo. --}}
    <body class="fae-auth-body">
            <main class="fae-auth-card">
                <div class="fae-auth-header">
                    <img src="{{ asset('img/logo-fae-branca.png') }}" alt="FAÉ Bioenergia" class="fae-auth-logo" width="170" height="204">
                    <h1 class="fae-auth-title">Buildflow</h1>
                    <p class="fae-auth-subtitle">Gestão Comercial, Start-up e Assistência Técnica</p>
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
                            autocomplete="email"
                            placeholder="nome@exemplo.com">
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
            </main>

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
