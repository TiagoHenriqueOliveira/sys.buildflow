{{-- Modal de criação/edição de modelo do Configurador. O checklist de
     perguntas usa @checked contra old('perguntas', []) pra sobreviver a um
     retorno de validação sem depender de Alpine — o JS (preencherFormularioModelo,
     ver configurador/modelos/index.blade.php) só sobrescreve os checkboxes
     no fluxo de edição via clique no lápis da tabela. --}}
<div class="modal-backdrop show" x-show="aberto" x-cloak></div>
<div
    class="modal"
    :class="{ show: aberto }"
    :style="aberto ? 'display: block' : 'display: none'"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal_modelo_label"
    @keydown.escape.window="aberto = false"
    @click.self="aberto = false"
>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_modelo_label" x-text="editando ? 'Modelos | Editar' : 'Modelos | Novo'"></h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="aberto = false"></button>
            </div>

            <div class="modal-body">
                <form
                    id="form_modelo"
                    method="POST"
                    action="{{ old('cfg_mod_id') ? route('configurador.modelos.update', old('cfg_mod_id')) : route('configurador.modelos.store') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" id="cfg_mod_method" value="{{ old('cfg_mod_id') ? 'PUT' : 'POST' }}">
                    <input type="hidden" id="cfg_mod_id" name="cfg_mod_id" value="{{ old('cfg_mod_id') }}">

                    <div class="row">
                        <div class="col-sm-8">
                            <x-sbadmin::form.input
                                id="cfg_mod_nome"
                                name="cfg_mod_nome"
                                label="Nome do modelo"
                                :value="old('cfg_mod_nome')"
                                maxlength="100"
                                required
                                placeholder="Ex.: Manutenção preventiva - ETE"
                            />
                        </div>
                        <div class="col-sm-4">
                            <x-sbadmin::form.select
                                id="cfg_mod_setor"
                                name="cfg_mod_setor"
                                label="Setor"
                                :options="collect(App\Enums\SetorModelo::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                                :value="old('cfg_mod_setor')"
                                placeholder="Selecione..."
                                required
                            />
                        </div>
                    </div>

                    {{-- Pedido do cliente (2026-09-11): banco de perguntas vai
                         crescer pra ~500 - checklist estatico nao escala, troca
                         por busca-e-adiciona (mesmo espirito do autocomplete de
                         cliente ja usado em outras telas). --}}
                    <label class="sbadmin-form-label" for="pergunta_busca">Perguntas do modelo</label>
                    @error('perguntas')
                        <div class="sbadmin-alert sbadmin-alert-error mb-2" role="alert">{{ $message }}</div>
                    @enderror
                    <input
                        type="text"
                        id="pergunta_busca"
                        class="form-control sbadmin-form-control mb-2"
                        placeholder="Digite para buscar uma pergunta cadastrada..."
                        autocomplete="off"
                    >
                    <div id="perguntasSelecionadasContainer" class="border rounded p-2 mb-3" style="max-height: 260px; overflow-y: auto;">
                        <p class="text-body-secondary small mb-0" id="perguntasVazioMsg">Nenhuma pergunta adicionada ainda.</p>
                    </div>


                    <div x-show="editando" x-cloak>
                        <input type="hidden" name="cfg_mod_ativo" value="0">
                        <x-sbadmin::form.checkbox
                            id="cfg_mod_ativo"
                            name="cfg_mod_ativo"
                            label="Ativo"
                            off-label="Inativo"
                            :checked="old('cfg_mod_ativo', true)"
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