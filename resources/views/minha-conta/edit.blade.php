<x-layout title="Minha conta">
    <div class="sbadmin-page-header">
        <h2 class="sbadmin-page-heading">Minha conta</h2>
        <p class="sbadmin-page-subheading">Altere seu nome, e-mail de acesso e senha.</p>
    </div>

    @if(session('success'))
        <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
    @endif

    <form method="POST" action="{{ route('minha-conta.update') }}" class="sbadmin-card">
        @csrf
        @method('PUT')
        <div class="sbadmin-card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <x-sbadmin::form.input id="user_nome" name="user_nome" label="Nome" :value="old('user_nome', $usuario->user_nome)" maxlength="50" required />
                </div>
                <div class="col-md-6">
                    <x-sbadmin::form.input id="user_email" type="email" name="user_email" label="E-mail" :value="old('user_email', $usuario->user_email)" maxlength="100" required />
                </div>
                <div class="col-md-4">
                    <x-sbadmin::form.input id="perfil_atual" name="perfil_atual" label="Perfil" :value="\App\Enums\NivelAcesso::from((int) $usuario->user_nivel_acesso)->label()" readonly tabindex="-1" />
                </div>
                <div class="col-md-4">
                    <x-sbadmin::form.input id="user_senha" type="password" name="user_senha" label="Nova senha" maxlength="50" autocomplete="new-password" help="Deixe em branco para manter a senha atual." />
                </div>
                <div class="col-md-4">
                    <x-sbadmin::form.input id="user_senha_confirmation" type="password" name="user_senha_confirmation" label="Confirmar nova senha" maxlength="50" autocomplete="new-password" />
                </div>
                {{-- S-08 (28/11/2026): exigidos só para trocar o e-mail ou a senha. --}}
                <div class="col-md-6">
                    <x-sbadmin::form.input id="senha_atual" type="password" name="senha_atual" label="Senha atual" maxlength="50" autocomplete="current-password" help="Obrigatória para trocar o e-mail ou a senha." />
                </div>
                <div class="col-md-6">
                    <x-sbadmin::form.input id="codigo_mfa" name="codigo_mfa" label="Código do aplicativo autenticador" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" help="Obrigatório para trocar o e-mail ou a senha." />
                </div>
            </div>

            <div class="sbadmin-alert sbadmin-alert-info mt-3 mb-0" role="note">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                Verificação em duas etapas ativa
                @if($usuario->user_mfa_confirmado_em) desde {{ $usuario->user_mfa_confirmado_em->format('d/m/Y') }}@endif.
                Trocou de celular? Peça a um administrador para redefinir o autenticador.
                Trocar a senha encerra seus acessos em outros navegadores e no app.
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Voltar</a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg" aria-hidden="true"></i> Salvar
                </button>
            </div>
        </div>
    </form>
</x-layout>
