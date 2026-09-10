{{-- Modal de criação/edição de tipo de orçamento (CRM01) — mesmo padrão de
     naturezas_atendimentos/modal.blade.php. --}}
<div class="modal-backdrop show" x-show="aberto" x-cloak></div>
<div
    class="modal"
    :class="{ show: aberto }"
    :style="aberto ? 'display: block' : 'display: none'"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal_tipo_orcamento_label"
    @keydown.escape.window="aberto = false"
    @click.self="aberto = false"
>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_tipo_orcamento_label" x-text="editando ? 'Tipos de Orçamento | Editar' : 'Tipos de Orçamento | Novo'"></h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="aberto = false"></button>
            </div>

            <div class="modal-body">
                <form
                    id="form_tipo_orcamento"
                    method="POST"
                    action="{{ old('crm_tp_orc_id') ? route('crm.tipos-orcamento.update', old('crm_tp_orc_id')) : route('crm.tipos-orcamento.store') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" id="crm_tp_orc_method" value="{{ old('crm_tp_orc_id') ? 'PUT' : 'POST' }}">
                    <input type="hidden" id="crm_tp_orc_id" name="crm_tp_orc_id" value="{{ old('crm_tp_orc_id') }}">

                    <x-sbadmin::form.input
                        id="crm_tp_orc_nome"
                        name="crm_tp_orc_nome"
                        label="Nome"
                        :value="old('crm_tp_orc_nome')"
                        maxlength="100"
                        required
                        placeholder="Ex.: ETE Nova Industrial"
                    />

                    <x-sbadmin::form.select
                        id="crm_tp_orc_config_modelo_id"
                        name="crm_tp_orc_config_modelo_id"
                        label="Modelo do Configurador (Comercial)"
                        :options="$modelosComerciais->pluck('cfg_mod_nome', 'cfg_mod_id')->all()"
                        :value="old('crm_tp_orc_config_modelo_id')"
                        placeholder="Nenhum"
                        :help="$modelosComerciais->isEmpty() ? 'Nenhum modelo de setor Comercial cadastrado ainda.' : null"
                    />

                    <div x-show="editando" x-cloak>
                        <input type="hidden" name="crm_tp_orc_ativo" value="0">
                        <x-sbadmin::form.checkbox
                            id="crm_tp_orc_ativo"
                            name="crm_tp_orc_ativo"
                            label="Ativo"
                            off-label="Inativo"
                            :checked="old('crm_tp_orc_ativo', true)"
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