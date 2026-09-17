{{-- Modal de criação/edição de segmento — mesmo padrão de
     classificacoes-cliente/modal.blade.php. --}}
<div class="modal-backdrop show" x-show="aberto" x-cloak></div>
<div
    class="modal"
    :class="{ show: aberto }"
    :style="aberto ? 'display: block' : 'display: none'"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal_segmento_label"
    @keydown.escape.window="aberto = false"
    @click.self="aberto = false"
>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_segmento_label" x-text="editando ? 'Segmentos | Editar' : 'Segmentos | Novo'"></h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="aberto = false"></button>
            </div>

            <div class="modal-body">
                <form
                    id="form_segmento"
                    method="POST"
                    action="{{ old('seg_id') ? route('segmentos.update', old('seg_id')) : route('segmentos.store') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" id="seg_method" value="{{ old('seg_id') ? 'PUT' : 'POST' }}">
                    <input type="hidden" id="seg_id" name="seg_id" value="{{ old('seg_id') }}">

                    <x-sbadmin::form.input
                        id="seg_descricao"
                        name="seg_descricao"
                        label="Descrição"
                        :value="old('seg_descricao')"
                        maxlength="100"
                        required
                        placeholder="Ex.: Sucroenergético"
                    />

                    <div x-show="editando" x-cloak>
                        <input type="hidden" name="seg_ativo" value="0">
                        <x-sbadmin::form.checkbox
                            id="seg_ativo"
                            name="seg_ativo"
                            label="Ativo"
                            off-label="Inativo"
                            :checked="old('seg_ativo', true)"
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