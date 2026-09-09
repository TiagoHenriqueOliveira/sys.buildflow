{{-- Modal de criação/edição de cliente. Visibilidade controlada pelo estado
     Alpine (`aberto`/`editando`) declarado no <div x-data> que envolve esta
     partial em clientes/index.blade.php — não depende do JS de modal do
     Bootstrap 4 (data-toggle/data-target) nem do Bootstrap 5 (data-bs-*),
     só de x-show, igual ao <x-sbadmin::confirm-modal /> do próprio pacote.
     O <form> é submetido de forma normal (POST/PUT com redirect), não via
     AJAX — em caso de erro de validação o Laravel redireciona de volta com
     $errors + old() preenchidos, e o estado inicial de `aberto`/`editando`
     (ver clientes/index.blade.php) já reabre o modal certo automaticamente. --}}
<div class="modal-backdrop show" x-show="aberto" x-cloak></div>
<div
    class="modal"
    :class="{ show: aberto }"
    x-show="aberto"
    style="display: block"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal_cliente_label"
    @keydown.escape.window="aberto = false"
    @click.self="aberto = false"
>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_cliente_label" x-text="editando ? 'Clientes | Editar' : 'Clientes | Novo'"></h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="aberto = false"></button>
            </div>

            <div class="modal-body">
                <form
                    id="form_cliente"
                    method="POST"
                    action="{{ old('cli_id') ? route('clientes.update', old('cli_id')) : route('clientes.store') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" id="cli_method" value="{{ old('cli_id') ? 'PUT' : 'POST' }}">
                    <input type="hidden" id="cli_id" name="cli_id" value="{{ old('cli_id') }}">

                    <x-sbadmin::form.input
                        id="cli_nome"
                        name="cli_nome"
                        label="Nome"
                        :value="old('cli_nome')"
                        maxlength="100"
                        required
                        placeholder="Ex.: Empresa ABC Ltda"
                    />

                    <div class="row">
                        <div class="col-sm-6">
                            <x-sbadmin::form.input
                                id="cli_cnpj"
                                name="cli_cnpj"
                                label="CNPJ"
                                :value="old('cli_cnpj')"
                                maxlength="18"
                                required
                                placeholder="00.000.000/0000-00"
                                oninput="this.value = window.formatarCnpj(this.value)"
                            />
                        </div>
                        <div class="col-sm-6">
                            <x-sbadmin::form.input
                                id="cli_telefone"
                                name="cli_telefone"
                                label="Telefone"
                                :value="old('cli_telefone')"
                                maxlength="15"
                                placeholder="(00) 00000-0000"
                                oninput="this.value = window.formatarTelefone(this.value)"
                            />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-8">
                            <x-sbadmin::form.input
                                id="cli_cidade"
                                name="cli_cidade"
                                label="Cidade"
                                :value="old('cli_cidade')"
                                maxlength="100"
                                required
                            />
                        </div>
                        <div class="col-sm-4">
                            <x-sbadmin::form.input
                                id="cli_uf"
                                name="cli_uf"
                                label="UF"
                                :value="old('cli_uf')"
                                maxlength="2"
                                required
                                placeholder="Ex.: SC"
                                style="text-transform:uppercase"
                            />
                        </div>
                    </div>

                    <x-sbadmin::form.input
                        id="cli_email"
                        type="email"
                        name="cli_email"
                        label="E-mail"
                        :value="old('cli_email')"
                        maxlength="100"
                    />

                    {{-- Só existe (visualmente) em modo edição — igual ao antigo
                         #div_cli_ativo com classe d-none no cadastro. O input
                         hidden antes do checkbox garante que "0" seja enviado
                         quando o usuário desmarca (checkbox desmarcado não é
                         enviado por HTML puro; o último valor com o mesmo
                         name="cli_ativo" é o que prevalece). --}}
                    <div x-show="editando" x-cloak>
                        <input type="hidden" name="cli_ativo" value="0">
                        <x-sbadmin::form.checkbox
                            id="cli_ativo"
                            name="cli_ativo"
                            label="Ativo"
                            :checked="old('cli_ativo', true)"
                        />
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="submit" class="btn btn-primary sbadmin-btn-primary">
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
