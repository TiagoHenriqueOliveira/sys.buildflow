@props(['subtitulo' => 'Gestão Comercial, Start-up e Assistência Técnica'])
{{-- Card das telas de acesso (login, código do autenticador e cadastro do
     autenticador) — visual aprovado em 2026-11-18 (padrão do promaq-sys +
     logo da FAÉ com texto branco). Estilos .fae-auth-* em
     resources/sass/app.scss. Tema sempre escuro, como o resto do sistema. --}}
<!DOCTYPE html>
<html lang="pt-br" data-theme="dark" data-bs-theme="dark">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ config('sbadmin.brand.name') }}</title>
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body class="fae-auth-body">
        <main class="fae-auth-card">
            <div class="fae-auth-header">
                <img src="{{ asset('img/logo-fae-branca.png') }}" alt="FAÉ Bioenergia" class="fae-auth-logo" width="170" height="204">
                <h1 class="fae-auth-title">Buildflow</h1>
                <p class="fae-auth-subtitle">{{ $subtitulo }}</p>
            </div>

            @if($errors->any())
                <x-sbadmin::alert type="error">
                    @foreach($errors->all() as $error)
                        {{ $error }}@if(!$loop->last)<br>@endif
                    @endforeach
                </x-sbadmin::alert>
            @endif

            {{ $slot }}
        </main>
        @stack('scripts')
    </body>
</html>
