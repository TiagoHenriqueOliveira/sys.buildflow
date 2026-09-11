{{-- Modal de criação/edição de classificação de cliente — mesmo padrão de
     ocorrencias/modal.blade.php (ver comentários lá). --}}
<div class="modal-backdrop show" x-show="aberto" x-cloak></div>
<div
    class="modal"
    :class="{ show: aberto }"
    :style="aberto ? 'display: block' : 'display: none'"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal_classificacao_label"
    @keydown.escape.window="aberto = false"
    @click.self="aberto = false"
>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_classificacao_label" x-text="editando ? 'Classificações | Editar' : 'Classificações | Nova'"></h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="aberto = false"></button>
            </div>

            <div class="modal-body">
                <form
                    id="form_classificacao"
                    method="POST"
                    action="{{ old('cla_cli_id') ? route('classificacoes-cliente.update', old('cla_cli_id')) : route('classificacoes-cliente.store') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" id="cla_cli_method" value="{{ old('cla_cli_id') ? 'PUT' : 'POST' }}">
                    <input type="hidden" id="cla_cli_id" name="cla_cli_id" value="{{ old('cla_cli_id') }}">

                    <x-sbadmin::form.input
                        id="cla_cli_nome"
                        name="cla_cli_nome"
                        label="Nome"
                        :value="old('cla_cli_nome')"
                        maxlength="50"
                        required
                        placeholder="Ex.: A, B, C ou Ativo, Prospect, Inativo"
                    />

                    <div x-show="editando" x-cloak>
                        <input type="hidden" name="cla_cli_ativo" value="0">
                        <x-sbadmin::form.checkbox
                            id="cla_cli_ativo"
                            name="cla_cli_ativo"
                            label="Ativo"
                            off-label="Inativo"
                            :checked="old('cla_cli_ativo', true)"
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