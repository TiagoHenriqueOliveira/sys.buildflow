{{-- Login — passo 1 (e-mail e senha). Depois vem o código do app
     autenticador (login/mfa.blade.php), obrigatório para todos desde
     2026-11-27. "Lembrar dados" deixa este navegador lembrado por 30 dias:
     o próximo acesso vai direto ao código (a senha nunca é guardada). --}}
<x-auth-card>
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

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="lembrar" name="lembrar" @checked(old('lembrar'))>
            <label class="form-check-label" for="lembrar">Lembrar dados</label>
            <div class="form-text">Neste navegador, os próximos acessos pedem só o código do aplicativo autenticador. Não marque em computador compartilhado.</div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2" id="loginButton">
            <span id="buttonText">Entrar</span>
            <span id="loadingSpinner"
                class="spinner-border spinner-border-sm d-none ms-2"
                role="status"
                aria-hidden="true"></span>
        </button>
    </form>

    @push('scripts')
        <script>
            document.getElementById('loginForm').addEventListener('submit', function () {
                const button = document.getElementById('loginButton');
                button.style.opacity = '0.7';
                button.style.pointerEvents = 'none';
                document.getElementById('buttonText').textContent = 'Autenticando...';
                document.getElementById('loadingSpinner').classList.remove('d-none');
                button.disabled = true;
            });
        </script>
    @endpush
</x-auth-card>
