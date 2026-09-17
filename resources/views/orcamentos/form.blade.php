{{-- CRM02/03/04 — cadastro/edição de orçamento. Form simples (POST/PUT
     com redirect, sem AJAX) como clientes/form.blade.php — diferente de
     atendimentos/form.blade.php, aqui não há sub-recurso que precise de um
     ID salvo antes de funcionar (perguntas e vendedores adicionais vão
     juntos no mesmo POST); só Comentários (log imutável, timestampado)
     tem form próprio, e só aparece depois de salvo. --}}
@php
    $editando = $orcamento->exists;

    // Respostas já salvas (edição): pergunta_id => valor decodificado
    // (múltipla escolha vem serializada em JSON, ver OrcamentoRepository).
    $respostasExistentes = $editando
        ? $orcamento->respostas->mapWithKeys(function ($r) {
            $valor = $r->orc_resp_valor;
            $decodificado = json_decode((string) $valor, true);
            return [$r->orc_resp_pergunta_id => is_array($decodificado) ? $decodificado : $valor];
        })
        : collect();

    $vendedoresAdicionaisIds = $editando ? $orcamento->vendedoresAdicionais->pluck('user_id')->all() : [];
@endphp
<x-layout :title="$editando ? 'Orçamentos | Editar' : 'Orçamentos | Novo'">
    <div
        id="orcamento-form-root"
        x-data="{
            tab: '{{ session('tab', 'dados') }}',
            tipoOrcamentoId: null,
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">{{ $editando ? 'Editar Orçamento' : 'Novo Orçamento' }}</h2>
                <p class="sbadmin-page-subheading">{{ $editando ? 'Orçamento #'.$orcamento->orc_id.' — '.optional($orcamento->cliente)->cli_nome : 'Cadastre um novo orçamento comercial.' }}</p>
            </div>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form
            id="form_orcamento"
            method="POST"
            action="{{ $editando ? route('orcamentos.update', $orcamento->orc_id) : route('orcamentos.store') }}"
        >
            @csrf
            @if($editando)
                @method('PUT')
            @endif

            <div class="sbadmin-card">
                <div class="sbadmin-card-body">
                    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-x-auto overflow-y-hidden" role="tablist">
                        <li class="nav-item text-nowrap">
                            <button type="button" class="nav-link" :class="{ active: tab === 'dados' }" @click="tab = 'dados'">Dados</button>
                        </li>
                        <li class="nav-item text-nowrap">
                            <button type="button" class="nav-link" :class="{ active: tab === 'perguntas' }" @click="tab = 'perguntas'">Perguntas</button>
                        </li>
                        <li class="nav-item text-nowrap">
                            <button type="button" class="nav-link" :class="{ active: tab === 'vendedores' }" @click="tab = 'vendedores'">Vendedores Adicionais</button>
                        </li>
                        @if($editando)
                            <li class="nav-item text-nowrap">
                                <button type="button" class="nav-link" :class="{ active: tab === 'comentarios' }" @click="tab = 'comentarios'">Comentários</button>
                            </li>
                        @endif
                    </ul>

                    {{-- ── Dados Gerais ─────────────────────────────────────────── --}}
                    <div x-show="tab === 'dados'">
                        <div class="sbadmin-form-group">
                            <label for="orc_cliente_nome" class="sbadmin-form-label">Cliente<span class="sbadmin-required" aria-hidden="true">*</span></label>
                            <input type="hidden" id="orc_cliente_id" name="orc_cliente_id" value="{{ old('orc_cliente_id', $orcamento->orc_cliente_id) }}">
                            <input
                                type="text"
                                class="form-control sbadmin-form-control"
                                id="orc_cliente_nome"
                                placeholder="Digite o nome do cliente"
                                value="{{ old('orc_cliente_nome', optional($orcamento->cliente)->cli_nome) }}"
                                autocomplete="off"
                                required
                            >
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <x-sbadmin::form.select
                                    id="orc_vendedor_id"
                                    name="orc_vendedor_id"
                                    label="Vendedor responsável"
                                    :options="$vendedores->pluck('user_nome', 'user_id')->all()"
                                    :value="old('orc_vendedor_id', $orcamento->orc_vendedor_id)"
                                    placeholder="Selecione..."
                                    required
                                />
                            </div>
                            <div class="col-sm-6">
                                <x-sbadmin::form.select
                                    id="orc_tipo_orcamento_id"
                                    name="orc_tipo_orcamento_id"
                                    label="Tipo de sistema"
                                    :options="$tiposOrcamento->pluck('crm_tp_orc_nome', 'crm_tp_orc_id')->all()"
                                    :value="old('orc_tipo_orcamento_id', $orcamento->orc_tipo_orcamento_id)"
                                    placeholder="Selecione..."
                                    onchange="window.atualizarTipoOrcamentoSelecionado(this.value)"
                                />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <x-sbadmin::form.select
                                    id="orc_nivel"
                                    name="orc_nivel"
                                    label="Nível"
                                    :options="collect(App\Enums\NivelOrcamento::cases())->mapWithKeys(fn ($n) => [$n->value => $n->label()])->all()"
                                    :value="old('orc_nivel', $orcamento->orc_nivel?->value)"
                                    placeholder="Não classificado"
                                />
                            </div>
                            <div class="col-sm-6">
                                <x-sbadmin::form.input
                                    id="orc_prazo_envio"
                                    type="date"
                                    name="orc_prazo_envio"
                                    label="Prazo de envio"
                                    :value="old('orc_prazo_envio', optional($orcamento->orc_prazo_envio)->format('Y-m-d'))"
                                    help="Deixe em branco para usar a sugestão automática a partir do nível."
                                />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                {{-- Pedido do cliente (2026-09-17): resultado do
                                     orçamento, pra alimentar os indicadores
                                     comerciais (CRM08) com dado real. --}}
                                <x-sbadmin::form.select
                                    id="orc_resultado"
                                    name="orc_resultado"
                                    label="Resultado"
                                    :options="collect(App\Enums\ResultadoOrcamento::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()"
                                    :value="old('orc_resultado', $orcamento->orc_resultado?->value)"
                                    placeholder="Em aberto"
                                />
                            </div>
                        </div>

                    </div>
                    {{-- ── Perguntas (dinâmicas por tipo, NC02/CRM01) ──────────────── --}}
                    <div x-show="tab === 'perguntas'" x-cloak>
                        @forelse($tiposOrcamento as $tipo)
                            <div x-show="tipoOrcamentoId === {{ $tipo->crm_tp_orc_id }}" x-cloak>
                                @php $perguntas = optional($tipo->configModelo)->perguntas ?? collect(); @endphp
                                @if($perguntas->isEmpty())
                                    <p class="text-body-secondary small">
                                        @if($tipo->configModelo)
                                            O modelo "{{ $tipo->configModelo->cfg_mod_nome }}" ainda não tem perguntas cadastradas.
                                        @else
                                            Nenhum modelo do Configurador vinculado a este tipo de orçamento.
                                        @endif
                                    </p>
                                @else
                                    @foreach($perguntas as $pergunta)
                                        <div class="sbadmin-form-group">
                                            <label class="sbadmin-form-label">{{ $pergunta->cfg_perg_texto }}</label>
                                            @php
                                                $valorAntigo = old('respostas.'.$pergunta->cfg_perg_id, $respostasExistentes->get($pergunta->cfg_perg_id));
                                            @endphp
                                            @if($pergunta->cfg_perg_tipo->value === 2)
                                                <textarea name="respostas[{{ $pergunta->cfg_perg_id }}]" class="form-control sbadmin-form-control" rows="2" placeholder="Digite sua resposta...">{{ is_array($valorAntigo) ? '' : $valorAntigo }}</textarea>
                                            @elseif($pergunta->cfg_perg_tipo->value === 1)
                                                @foreach($pergunta->opcoes as $opcao)
                                                    <div class="form-check">
                                                        <input
                                                            class="form-check-input"
                                                            type="radio"
                                                            name="respostas[{{ $pergunta->cfg_perg_id }}]"
                                                            id="resposta_{{ $pergunta->cfg_perg_id }}_{{ $opcao->cfg_perg_op_id }}"
                                                            value="{{ $opcao->cfg_perg_op_id }}"
                                                            @checked((string) $valorAntigo === (string) $opcao->cfg_perg_op_id)
                                                        >
                                                        <label class="form-check-label" for="resposta_{{ $pergunta->cfg_perg_id }}_{{ $opcao->cfg_perg_op_id }}">{{ $opcao->cfg_perg_op_texto }}</label>
                                                    </div>
                                                @endforeach
                                            @else
                                                @foreach($pergunta->opcoes as $opcao)
                                                    <div class="form-check">
                                                        <input
                                                            class="form-check-input"
                                                            type="checkbox"
                                                            name="respostas[{{ $pergunta->cfg_perg_id }}][]"
                                                            id="resposta_{{ $pergunta->cfg_perg_id }}_{{ $opcao->cfg_perg_op_id }}"
                                                            value="{{ $opcao->cfg_perg_op_id }}"
                                                            @checked(is_array($valorAntigo) && in_array((string) $opcao->cfg_perg_op_id, array_map('strval', $valorAntigo), true))
                                                        >
                                                        <label class="form-check-label" for="resposta_{{ $pergunta->cfg_perg_id }}_{{ $opcao->cfg_perg_op_id }}">{{ $opcao->cfg_perg_op_texto }}</label>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @empty
                            <p class="text-body-secondary small">Nenhum tipo de orçamento cadastrado.</p>
                        @endforelse
                        <p class="text-body-secondary small" x-show="!tipoOrcamentoId">Selecione um tipo de sistema na aba "Dados Gerais" para ver as perguntas.</p>
                    </div>
                    {{-- ── Vendedores Adicionais (CRM04) ────────────────────────────── --}}
                    <div x-show="tab === 'vendedores'" x-cloak>
                        <p class="text-body-secondary small">Indicação conjunta — sem cálculo/divisão de comissão.</p>
                        @forelse($vendedores as $vendedor)
                            <div class="form-check mb-2">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="vendedores_adicionais[]"
                                    id="vendedor_adicional_{{ $vendedor->user_id }}"
                                    value="{{ $vendedor->user_id }}"
                                    @checked(in_array($vendedor->user_id, old('vendedores_adicionais', $vendedoresAdicionaisIds)))
                                >
                                <label class="form-check-label" for="vendedor_adicional_{{ $vendedor->user_id }}">{{ $vendedor->user_nome }}</label>
                            </div>
                        @empty
                            <p class="text-body-secondary small">Nenhum outro usuário com perfil Comercial cadastrado.</p>
                        @endforelse
                        @error('vendedores_adicionais')
                            <div class="sbadmin-alert sbadmin-alert-error mt-2" role="alert">{{ $message }}</div>
                        @enderror
                        <div class="mb-3"></div>
                    </div>

                </form>

                @if($editando)
                    {{-- ── Comentários (CRM03) — log próprio, fora do <form> principal
                         (form próprio abaixo), mas dentro do MESMO card das outras
                         abas — mesmo padrão de Atendimentos > Equipamentos/Anexos.
                         Antes ficava num <div class="sbadmin-card mt-3"> separado,
                         dando a impressão de tela "dividida" em dois componentes. --}}
                    <div x-show="tab === 'comentarios'" x-cloak>
                        <form method="POST" action="{{ route('orcamentos.store-comentario', $orcamento->orc_id) }}" class="mb-4">
                            @csrf
                            <x-sbadmin::form.textarea
                                id="orc_com_texto"
                                name="orc_com_texto"
                                label="Novo comentário"
                                rows="3"
                                required
                                placeholder="Escreva o comentário..."
                            />
                            <div class="row">
                                <div class="col-sm-6">
                                    <x-sbadmin::form.select
                                        id="orc_com_alerta_usuario_id"
                                        name="orc_com_alerta_usuario_id"
                                        label="Alertar usuário (opcional)"
                                        :options="$vendedores->pluck('user_nome', 'user_id')->all()"
                                        placeholder="Nenhum"
                                    />
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar comentário
                            </button>
                        </form>

                        <hr>

                        <h6 class="fw-bold">Histórico</h6>
                        @forelse($orcamento->comentarios as $comentario)
                            <div class="sbadmin-card mb-2">
                                <div class="sbadmin-card-body py-2">
                                    <div class="d-flex justify-content-between align-items-start small text-body-secondary">
                                        <span>{{ optional($comentario->autor)->user_nome }} — {{ $comentario->orc_com_criado_em->format('d/m/Y H:i') }}</span>
                                        <form method="POST" action="{{ route('orcamentos.destroy-comentario', [$orcamento->orc_id, $comentario->orc_com_id]) }}" class="m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1" title="Excluir comentário">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <p class="mb-0" style="white-space:pre-wrap;">{{ $comentario->orc_com_texto }}</p>
                                    @if($comentario->orc_com_alerta_usuario_id)
                                        <span class="sbadmin-badge sbadmin-badge-warning sbadmin-badge-pill">
                                            <i class="bi bi-bell" aria-hidden="true"></i> Alerta para {{ optional($comentario->usuarioAlertado)->user_nome }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-body-secondary small">Nenhum comentário registrado ainda.</p>
                        @endforelse
                    </div>
                @endif

                {{-- Rodapé único (Salvar + Voltar), sempre visível independente da
                     aba ativa, dentro do MESMO card — mesmo padrão de
                     atendimentos/form.blade.php. `form="form_orcamento"` associa o
                     botão ao form sem precisar ficar dentro dele. --}}
                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <button type="submit" form="form_orcamento" class="btn btn-success">
                        <i class="bi bi-check-lg" aria-hidden="true"></i> Salvar
                    </button>
                    <a href="{{ route('orcamentos.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg" aria-hidden="true"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Le o <select> ja renderizado corretamente pelo servidor
            // (old()/$orcamento) em vez de duplicar essa logica num
            // x-model.number, que o Chrome ignora quando a option
            // placeholder e disabled+selected (ver memoria do projeto).
            window.atualizarTipoOrcamentoSelecionado = function (valor) {
                const root = document.getElementById('orcamento-form-root');
                Alpine.$data(root).tipoOrcamentoId = valor ? parseInt(valor, 10) : null;
            };

            document.addEventListener('DOMContentLoaded', function () {
                window.setupAutocomplete('#orc_cliente_nome', '#orc_cliente_id', '{{ route('clientes.autocomplete') }}');
                window.atualizarTipoOrcamentoSelecionado(document.getElementById('orc_tipo_orcamento_id').value);
            });
        </script>
    @endpush
</x-layout>