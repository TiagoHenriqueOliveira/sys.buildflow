{{-- Modal de criação/edição de pergunta do Configurador — mesmo padrão de
     ocorrencias/modal.blade.php, com dois acréscimos: select de tipo
     (:x-model.number para controlar a visibilidade do repeater de opções)
     e a lista repetível de opções (Alpine, mesmo padrão de
     clientes/form.blade.php > Contatos), só visível para tipo Múltipla
     escolha/Escolha única. --}}
<div class="modal-backdrop show" x-show="aberto" x-cloak></div>
<div
    class="modal"
    :class="{ show: aberto }"
    :style="aberto ? 'display: block' : 'display: none'"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal_pergunta_label"
    @keydown.escape.window="aberto = false"
    @click.self="aberto = false"
>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_pergunta_label" x-text="editando ? 'Perguntas | Editar' : 'Perguntas | Nova'"></h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="aberto = false"></button>
            </div>

            <div class="modal-body">
                <form
                    id="form_pergunta"
                    method="POST"
                    action="{{ old('cfg_perg_id') ? route('configurador.perguntas.update', old('cfg_perg_id')) : route('configurador.perguntas.store') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" id="cfg_perg_method" value="{{ old('cfg_perg_id') ? 'PUT' : 'POST' }}">
                    <input type="hidden" id="cfg_perg_id" name="cfg_perg_id" value="{{ old('cfg_perg_id') }}">

                    <x-sbadmin::form.textarea
                        id="cfg_perg_texto"
                        name="cfg_perg_texto"
                        label="Texto da pergunta"
                        :value="old('cfg_perg_texto')"
                        rows="2"
                        required
                        placeholder="Ex.: O equipamento apresentou vazamento?"
                    />

                    <x-sbadmin::form.select
                        id="cfg_perg_tipo"
                        name="cfg_perg_tipo"
                        label="Tipo de resposta"
                        :options="collect(App\Enums\TipoPergunta::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()"
                        :value="old('cfg_perg_tipo', 2)"
                        x-model.number="tipo"
                        required
                    />

                    <div x-show="tipo !== 2" x-cloak>
                        <label class="sbadmin-form-label">Opções de resposta</label>
                        @error('opcoes')
                            <div class="sbadmin-alert sbadmin-alert-error mb-2" role="alert">{{ $message }}</div>
                        @enderror
                        <button type="button" class="btn btn-outline-primary btn-sm mb-2" @click="addOpcao()">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Incluir Opção
                        </button>
                        <template x-for="(opcao, i) in opcoes" :key="i">
                            <div class="d-flex gap-2 mb-2">
                                <input type="text" class="form-control sbadmin-form-control" maxlength="255" :name="'opcoes['+i+'][texto]'" x-model="opcao.texto" placeholder="Texto da opção">
                                <button type="button" class="btn btn-outline-danger btn-sm" @click="removerOpcao(i)">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <x-sbadmin::form.checkbox
                        id="cfg_perg_permite_anexo"
                        name="cfg_perg_permite_anexo"
                        label="Permite anexo de imagem"
                        :checked="old('cfg_perg_permite_anexo', false)"
                        :switch="true"
                    />

                    {{-- Pedido do cliente (2026-09-11): pergunta respondida
                         varias vezes no mesmo relatorio (ex.: "Descricao do
                         servico" com foto, repetida por item feito). --}}
                    <x-sbadmin::form.checkbox
                        id="cfg_perg_repetivel"
                        name="cfg_perg_repetivel"
                        label="Permite múltiplas respostas"
                        help="Na tela de relatório, o técnico poderá adicionar quantas respostas quiser para esta pergunta (com foto individual quando aplicável)."
                        :checked="old('cfg_perg_repetivel', false)"
                        :switch="true"
                    />

                    <div x-show="editando" x-cloak>
                        <input type="hidden" name="cfg_perg_ativo" value="0">
                        <x-sbadmin::form.checkbox
                            id="cfg_perg_ativo"
                            name="cfg_perg_ativo"
                            label="Ativo"
                            off-label="Inativo"
                            :checked="old('cfg_perg_ativo', true)"
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