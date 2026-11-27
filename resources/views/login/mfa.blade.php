{{-- Login — passo 2: código de 6 dígitos do app autenticador (2026-11-27). --}}
<x-auth-card subtitulo="Verificação em duas etapas">
    <p class="text-center mb-3">
        Olá, <strong>{{ $usuario->user_nome }}</strong>.<br>
        <span class="text-body-secondary small">Digite o código de 6 dígitos do seu aplicativo autenticador.</span>
    </p>

    <form method="POST" action="{{ route('login.mfa.store') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label for="codigo" class="form-label">Código</label>
            <input type="text"
                id="codigo"
                name="codigo"
                class="form-control text-center fs-4"
                required
                autofocus
                inputmode="numeric"
                autocomplete="one-time-code"
                pattern="[0-9]*"
                maxlength="6"
                placeholder="000000">
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Confirmar</button>
    </form>

    <form method="POST" action="{{ route('login.esquecer') }}" class="text-center mt-3">
        @csrf
        <button type="submit" class="btn btn-link btn-sm">
            {{ $lembrado ? 'Não é você? Entrar com outra conta' : 'Voltar' }}
        </button>
    </form>
</x-auth-card>
