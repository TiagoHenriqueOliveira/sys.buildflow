{{-- "Esqueci minha senha" (auditoria S-08, 28/11/2026): o administrador
     gera o código de redefinição (uso único, 24 h) na tela de Usuários; o
     usuário informa esse código, o do autenticador e a senha nova. --}}
<x-auth-card subtitulo="Redefinir a senha">
    <p class="small mb-3">
        Peça ao administrador um <strong>código de redefinição de senha</strong> e tenha à mão o aplicativo autenticador.
    </p>

    <form method="POST" action="{{ route('login.redefinir.store') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}"
                required autofocus autocomplete="email" maxlength="100" placeholder="nome@exemplo.com">
        </div>

        <div class="mb-3">
            <label for="codigo_redefinicao" class="form-label">Código de redefinição</label>
            <input type="text" id="codigo_redefinicao" name="codigo_redefinicao" class="form-control text-uppercase"
                required autocomplete="off" maxlength="20" placeholder="XXXXX-XXXXX">
        </div>

        <div class="mb-3">
            <label for="codigo" class="form-label">Código do aplicativo autenticador</label>
            <input type="text" id="codigo" name="codigo" class="form-control"
                required inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="6" placeholder="000000">
        </div>

        <div class="mb-3">
            <label for="user_senha" class="form-label">Senha nova</label>
            <input type="password" id="user_senha" name="user_senha" class="form-control"
                required autocomplete="new-password" maxlength="50" aria-describedby="ajudaSenha">
            <div id="ajudaSenha" class="form-text">No mínimo 10 caracteres, com letras e números.</div>
        </div>

        <div class="mb-3">
            <label for="user_senha_confirmation" class="form-label">Confirmar senha nova</label>
            <input type="password" id="user_senha_confirmation" name="user_senha_confirmation" class="form-control"
                required autocomplete="new-password" maxlength="50">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">Alterar a senha</button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="btn btn-link btn-sm">Voltar ao login</a>
    </div>
</x-auth-card>
