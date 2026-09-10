{{-- Modal de criação/edição de usuário — mesmo padrão do
     clientes/modal.blade.php (ver comentários lá). Os campos de senha
     (grupo com botão "Sugerir" + toggle de visibilidade) não têm componente
     equivalente em <x-sbadmin::form.*>, então ficam com markup manual
     (input-group Bootstrap 5) só trocando fontawesome por bootstrap-icons e
     jQuery por listener vanilla (ver usuarios/index.blade.php). O campo
     "Nível" era um par de radio buttons Bootstrap4; virou
     <x-sbadmin::form.select> pelo mesmo motivo do Tipo de Data em
     modelos_relatorios (sem <x-sbadmin::form.radio> no pacote). --}}
<div class="modal-backdrop show" x-show="aberto" x-cloak></div>
<div
    class="modal"
    :class="{ show: aberto }"
    :style="aberto ? 'display: block' : 'display: none'"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal_usuario_label"
    @keydown.escape.window="aberto = false"
    @click.self="aberto = false"
>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_usuario_label" x-text="editando ? 'Usuários | Editar' : 'Usuários | Novo'"></h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="aberto = false"></button>
            </div>

            <div class="modal-body">
                <form
                    id="form_usuario"
                    method="POST"
                    action="{{ old('user_id') ? route('usuarios.update', old('user_id')) : route('usuarios.store') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" id="user_method" value="{{ old('user_id') ? 'PUT' : 'POST' }}">
                    <input type="hidden" id="user_id" name="user_id" value="{{ old('user_id') }}">

                    <div class="row g-2">
                        <div class="col-4 col-md-2">
                            <x-sbadmin::form.select
                                id="user_nivel_acesso"
                                name="user_nivel_acesso"
                                label="Nível"
                                :options="['0' => 'Administrador', '1' => 'Técnico', '2' => 'Comercial']"
                                :value="old('user_nivel_acesso')"
                                placeholder="Selecione..."
                                required
                            />
                        </div>
                        <div class="col-8 col-md-10">
                            <x-sbadmin::form.input
                                id="user_nome"
                                name="user_nome"
                                label="Nome"
                                :value="old('user_nome')"
                                maxlength="50"
                                required
                                placeholder="Ex.: João da Silva"
                            />
                        </div>
                    </div>

                    <x-sbadmin::form.input
                        id="user_email"
                        type="email"
                        name="user_email"
                        label="E-mail"
                        :value="old('user_email')"
                        maxlength="100"
                        required
                        placeholder="Ex.: joao@email.com"
                    />

                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="sbadmin-form-group">
                                <label for="user_senha" class="sbadmin-form-label">Senha</label>
                                <div class="input-group">
                                    <input
                                        type="password"
                                        class="form-control sbadmin-form-control @error('user_senha') is-invalid @enderror"
                                        id="user_senha"
                                        name="user_senha"
                                        maxlength="50"
                                        placeholder="Informe uma senha"
                                    >
                                    <button type="button" class="btn btn-outline-primary" id="btnSugerirSenha">Sugerir</button>
                                    <button type="button" class="btn btn-outline-secondary btn-toggle-password" data-target="#user_senha" aria-label="Mostrar/ocultar senha">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                    @error('user_senha')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="sbadmin-form-help" id="senha_help">No cadastro a senha é obrigatória. Na edição, preencha apenas se desejar alterá-la.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="sbadmin-form-group">
                                <label for="user_senha_confirmation" class="sbadmin-form-label">Confirmar</label>
                                <div class="input-group">
                                    <input
                                        type="password"
                                        class="form-control sbadmin-form-control @error('user_senha_confirmation') is-invalid @enderror"
                                        id="user_senha_confirmation"
                                        name="user_senha_confirmation"
                                        maxlength="50"
                                        placeholder="Confirme a senha"
                                    >
                                    <button type="button" class="btn btn-outline-secondary btn-toggle-password" data-target="#user_senha_confirmation" aria-label="Mostrar/ocultar senha">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                    @error('user_senha_confirmation')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="editando" x-cloak>
                        <input type="hidden" name="user_ativo" value="0">
                        <x-sbadmin::form.checkbox
                            id="user_ativo"
                            name="user_ativo"
                            label="Ativo"
                            off-label="Inativo"
                            :checked="old('user_ativo', true)"
                            :switch="true"
                        />
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg" aria-hidden="true"></i> Salvar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" @click="aberto = false">
                            <i class="bi bi-x-lg" aria-hidden="true"></i> Fechar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
