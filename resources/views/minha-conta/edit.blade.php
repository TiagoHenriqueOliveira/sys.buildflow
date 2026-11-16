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
