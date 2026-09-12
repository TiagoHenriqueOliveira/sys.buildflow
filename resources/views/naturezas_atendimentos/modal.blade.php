{{-- Modal de criação/edição de natureza de atendimento — mesmo padrão do
     clientes/modal.blade.php (ver comentários lá): visibilidade via Alpine
     (x-data no index.blade.php), <form> submetido normalmente (POST/PUT com
     redirect), sem AJAX. --}}
<div class="modal-backdrop show" x-show="aberto" x-cloak></div>
<div
    class="modal"
    :class="{ show: aberto }"
    :style="aberto ? 'display: block' : 'display: none'"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal_natureza_atendimento_label"
    @keydown.escape.window="aberto = false"
    @click.self="aberto = false"
>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_natureza_atendimento_label" x-text="editando ? 'Naturezas de Atendimento | Editar' : 'Naturezas de Atendimento | Novo'"></h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="aberto = false"></button>
            </div>

            <div class="modal-body">
                <form
                    id="form_natureza_atendimento"
                    method="POST"
                    action="{{ old('nat_aten_id') ? route('naturezas-dos-atendimentos.update', old('nat_aten_id')) : route('naturezas-dos-atendimentos.store') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" id="nat_aten_method" value="{{ old('nat_aten_id') ? 'PUT' : 'POST' }}">
                    <input type="hidden" id="nat_aten_id" name="nat_aten_id" value="{{ old('nat_aten_id') }}">

                    <x-sbadmin::form.input
                        id="nat_aten_descricao"
                        name="nat_aten_descricao"
                        label="Descrição"
                        :value="old('nat_aten_descricao')"
                        maxlength="50"
                        required
                        placeholder="Ex.: Visita Técnica"
                    />

                    <x-sbadmin::form.select
                        id="nat_aten_config_modelo_id"
                        name="nat_aten_config_modelo_id"
                        label="Modelo do Configurador"
                        :options="$configModelos->pluck('cfg_mod_nome', 'cfg_mod_id')->all()"
                        :value="old('nat_aten_config_modelo_id')"
                        placeholder="Selecione..."
                        required
                    />

                    <div x-show="editando" x-cloak>
                        <input type="hidden" name="nat_aten_ativo" value="0">
                        <x-sbadmin::form.checkbox
                            id="nat_aten_ativo"
                            name="nat_aten_ativo"
                            label="Ativo"
                            off-label="Inativo"
                            :checked="old('nat_aten_ativo', true)"
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
