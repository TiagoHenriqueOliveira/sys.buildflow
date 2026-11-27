{{-- Página de acesso negado (pedido do cliente 2026-11-16). Antes, abrir
     uma URL sem permissão mostrava o 403 padrão do Laravel, sem menu nem
     saída. Standalone (sem <x-layout>) de propósito: não depende do menu nem
     de nada que possa falhar de novo; mesmo visual da tela de login.
     Requisições JSON/API continuam recebendo {"message": ...} — o Laravel só
     usa esta view para HTML. --}}
@php
    $destino = auth()->check() ? route('dashboard') : route('login');
    $rotuloDestino = auth()->check() ? 'Dashboard' : 'login';
    // Mensagem específica do abort(403, '...') — a genérica do Gate
    // ("This action is unauthorized.") não é exibida.
    $mensagem = $exception?->getMessage();
    if (! $mensagem || in_array($mensagem, ['This action is unauthorized.', 'Forbidden'], true)) {
        $mensagem = null;
    }
@endphp
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Acesso negado | {{ config('sbadmin.brand.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">

        <script>
            (function () {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            })();
        </script>

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])

        <style>
            body.sbadmin-erro-body {
                min-height: 100vh;
                display: flex;
                align-items: center;
                background-color: var(--sbadmin-bg);
            }

            .sbadmin-erro-card {
                max-width: 460px;
                margin: 0 auto;
                padding: 2rem;
                border-radius: var(--sbadmin-radius-lg, 0.75rem);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                background: var(--sbadmin-surface);
                border: 1px solid var(--sbadmin-border);
            }

            .sbadmin-erro-icone {
                font-size: 3rem;
                line-height: 1;
            }
        </style>
    </head>
    <body class="sbadmin-erro-body">
        <div class="container px-3">
            <div class="sbadmin-erro-card text-center">
                <div class="sbadmin-erro-icone text-warning mb-3">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                </div>
                <h1 class="h4 fw-semibold mb-2">Você não tem acesso a esta página</h1>
                <p class="text-body-secondary mb-3">
                    {{ $mensagem ?? 'Seu perfil não tem permissão para esta função. Se precisar dela, fale com um administrador do sistema.' }}
                </p>
                <p class="small text-body-secondary mb-4" aria-live="polite">
                    Redirecionando para o {{ $rotuloDestino }} em <strong id="contador-redirecionamento">15</strong>s.
                </p>
                <a href="{{ $destino }}" class="btn btn-primary sbadmin-btn-primary w-100">
                    <i class="bi bi-house-door me-1" aria-hidden="true"></i> Ir para o {{ $rotuloDestino }} agora
                </a>
            </div>
        </div>

        <script>
            (function () {
                const destino = @json($destino);
                const contador = document.getElementById('contador-redirecionamento');
                let restantes = 15;
                const intervalo = setInterval(function () {
                    restantes--;
                    if (contador) contador.textContent = String(Math.max(restantes, 0));
                    if (restantes <= 0) {
                        clearInterval(intervalo);
                        window.location.href = destino;
                    }
                }, 1000);
            })();
        </script>
    </body>
</html>
