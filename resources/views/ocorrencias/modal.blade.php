{{-- Modal de criação/edição de ocorrência — mesmo padrão do
     clientes/modal.blade.php (ver comentários lá). --}}
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
    aria-labelledby="modal_ocorrencia_label"
    @keydown.escape.window="aberto = false"
    @click.self="aberto = false"
>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_ocorrencia_label" x-text="editando ? 'Ocorrências | Editar' : 'Ocorrências | Novo'"></h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="aberto = false"></button>
            </div>

            <div class="modal-body">
                <form
                    id="form_ocorrencia"
                    method="POST"
                    action="{{ old('ocor_id') ? route('ocorrencias.update', old('ocor_id')) : route('ocorrencias.store') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" id="ocor_method" value="{{ old('ocor_id') ? 'PUT' : 'POST' }}">
                    <input type="hidden" id="ocor_id" name="ocor_id" value="{{ old('ocor_id') }}">

                    <x-sbadmin::form.input
                        id="ocor_descricao"
                        name="ocor_descricao"
                        label="Descrição"
                        :value="old('ocor_descricao')"
                        maxlength="50"
                        required
                        placeholder="Ex.: Falha no equipamento"
                    />

                    <div x-show="editando" x-cloak>
                        <input type="hidden" name="ocor_ativo" value="0">
                        <x-sbadmin::form.checkbox
                            id="ocor_ativo"
                            name="ocor_ativo"
                            label="Ativo"
                            :checked="old('ocor_ativo', true)"
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
