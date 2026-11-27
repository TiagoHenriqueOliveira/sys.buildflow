{{-- Cadastro do app autenticador (MFA obrigatório para todos, 2026-11-27).
     Enquanto não cadastrar, o usuário só acessa esta tela e o "Sair". --}}
<x-auth-card subtitulo="Ative a verificação em duas etapas">
    <ol class="small ps-3 mb-3">
        <li class="mb-1">Instale no celular um aplicativo autenticador, como <strong>Google Authenticator</strong> ou <strong>Microsoft Authenticator</strong>.</li>
        <li class="mb-1">No aplicativo, adicione uma conta lendo o QR code abaixo.</li>
        <li>Digite o código de 6 dígitos que aparecer no aplicativo.</li>
    </ol>

    <div class="d-flex justify-content-center mb-2">
        <div class="bg-white rounded p-2">{!! $qrCode !!}</div>
    </div>
    <p class="text-center small text-body-secondary mb-3">
        Sem câmera? Digite a chave no aplicativo:<br>
        <code class="user-select-all">{{ trim(chunk_split($segredo, 4, ' ')) }}</code>
    </p>

    <form method="POST" action="{{ route('mfa.cadastro.store') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label for="codigo" class="form-label">Código do aplicativo</label>
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
        <button type="submit" class="btn btn-primary w-100 py-2">Ativar</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">
        @csrf
        <button type="submit" class="btn btn-link btn-sm">Sair</button>
    </form>
</x-auth-card>
