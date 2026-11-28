{{-- Login — passo 1 (e-mail e senha). Depois vem o código do app
     autenticador (login/mfa.blade.php), obrigatório para todos desde
     2026-11-27. "Lembrar dados" deixa este navegador lembrado até completar
     6h do login completo: nesse prazo, a volta vai direto ao código (a senha
     nunca é guardada). Com 6h a sessão cai (SessaoComPrazo) e o próximo
     acesso é este formulário de novo, com a caixa desmarcada. --}}
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
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2" id="loginButton">
            <span id="buttonText">Entrar</span>
            <span id="loadingSpinner"
                class="spinner-border spinner-border-sm d-none ms-2"
                role="status"
                aria-hidden="true"></span>
        </button>
    </form>

    {{-- S-08 (28/11/2026): redefinição com o código gerado pelo administrador. --}}
    <div class="text-center mt-3">
        <a href="{{ route('login.redefinir') }}" class="btn btn-link btn-sm">Esqueci minha senha</a>
    </div>

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
