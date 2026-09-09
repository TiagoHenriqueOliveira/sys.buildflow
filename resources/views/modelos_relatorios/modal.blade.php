{{-- Modal de criação/edição de modelo de relatório — mesmo padrão do
     clientes/modal.blade.php (ver comentários lá). O campo "Tipo de Data"
     era um par de radio buttons Bootstrap4 (custom-control-input); o pacote
     sbadmin/dashboard não tem um <x-sbadmin::form.radio>, então virou um
     <x-sbadmin::form.select> com as mesmas duas opções/values (0/1) — sem
     mudança de comportamento, só de widget. --}}
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
    aria-labelledby="modal_modelo_relatorio_label"
    @keydown.escape.window="aberto = false"
    @click.self="aberto = false"
>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_modelo_relatorio_label" x-text="editando ? 'Modelos de Relatórios | Editar' : 'Modelos de Relatórios | Novo'"></h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="aberto = false"></button>
            </div>

            <div class="modal-body">
                <form
                    id="form_modelo_relatorio"
                    method="POST"
                    action="{{ old('mod_rel_id') ? route('modelos-de-relatorios.update', old('mod_rel_id')) : route('modelos-de-relatorios.store') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" id="mod_rel_method" value="{{ old('mod_rel_id') ? 'PUT' : 'POST' }}">
                    <input type="hidden" id="mod_rel_id" name="mod_rel_id" value="{{ old('mod_rel_id') }}">

                    <x-sbadmin::form.input
                        id="mod_rel_descricao"
                        name="mod_rel_descricao"
                        label="Descrição"
                        :value="old('mod_rel_descricao')"
                        maxlength="50"
                        required
                        placeholder="Ex.: Relatório Obra Padrão"
                    />

                    <x-sbadmin::form.select
                        id="mod_rel_tp_data"
                        name="mod_rel_tp_data"
                        label="Tipo de Data"
                        :options="['0' => 'Relatório Diário', '1' => 'Relatório Período']"
                        :value="old('mod_rel_tp_data', '0')"
                        required
                    />

                    <div x-show="editando" x-cloak>
                        <input type="hidden" name="mod_rel_ativo" value="0">
                        <x-sbadmin::form.checkbox
                            id="mod_rel_ativo"
                            name="mod_rel_ativo"
                            label="Ativo"
                            :checked="old('mod_rel_ativo', true)"
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
