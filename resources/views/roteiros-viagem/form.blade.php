{{-- CRM05/06 — cadastro de roteiro de viagem (saída) e registro do
     retorno por cliente. Form simples (POST/PUT com redirect), como
     clientes/form.blade.php — repeater de clientes via Alpine, igual ao
     padrão de Contatos do cliente, mas o nome dos campos de retorno
     (resultado/observação) é indexado pelo ID do cliente escolhido em
     cada linha (não pela posição), pra bater com o Repository. --}}
@php
    $editando = $roteiro->exists;

    $linhasIniciais = old('linhas', $editando
        ? $roteiro->clientes->map(fn ($rc) => [
            'clienteId' => $rc->crm_rot_cli_cliente_id,
            'clienteNome' => optional($rc->cliente)->cli_nome,
            'resultado' => $rc->crm_rot_cli_resultado?->value,
            'observacao' => $rc->crm_rot_cli_observacao,
        ])->values()->all()
        : []);
@endphp
<x-layout :title="$editando ? 'Roteiros de Viagem | Editar' : 'Roteiros de Viagem | Novo'">
    <div
        x-data="{
            linhas: {{ \Illuminate\Support\Js::from($linhasIniciais) }},
            linkMapa: {{ \Illuminate\Support\Js::from(old('crm_rot_link_mapa', $roteiro->crm_rot_link_mapa)) }},
            addLinha() { this.linhas.push({ clienteId: '', clienteNome: '', resultado: '', observacao: '' }); },
            removerLinha(i) { this.linhas.splice(i, 1); },
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">{{ $editando ? 'Editar Roteiro de Viagem' : 'Novo Roteiro de Viagem' }}</h2>
                <p class="sbadmin-page-subheading">Cadastre a saída (clientes a visitar) e, depois da viagem, o retorno de cada visita.</p>
            </div>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form
            method="POST"
            action="{{ $editando ? route('roteiros-viagem.update', $roteiro->crm_rot_id) : route('roteiros-viagem.store') }}"
        >
            @csrf
            @if($editando)
                @method('PUT')
            @endif

            <div class="sbadmin-card">
                <div class="sbadmin-card-body">
                    <div class="row">
                        <div class="col-6 col-md-2">
                            <x-sbadmin::form.select
                                id="crm_rot_vendedor_id"
                                name="crm_rot_vendedor_id"
                                label="Vendedor"
                                :options="$vendedores->pluck('user_nome', 'user_id')->all()"
                                :value="old('crm_rot_vendedor_id', $roteiro->crm_rot_vendedor_id)"
                                placeholder="Selecione..."
                                required
                            />
                        </div>
                        <div class="col-6 col-md-2">
                            <x-sbadmin::form.input
                                id="crm_rot_periodo_inicio"
                                type="date"
                                name="crm_rot_periodo_inicio"
                                label="Período — início"
                                :value="old('crm_rot_periodo_inicio', optional($roteiro->crm_rot_periodo_inicio)->format('Y-m-d'))"
                                required
                            />
                        </div>
                        <div class="col-6 col-md-2">
                            <x-sbadmin::form.input
                                id="crm_rot_periodo_fim"
                                type="date"
                                name="crm_rot_periodo_fim"
                                label="Período — fim"
                                :value="old('crm_rot_periodo_fim', optional($roteiro->crm_rot_periodo_fim)->format('Y-m-d'))"
                                required
                            />
                        </div>
                        <div class="col-md-5">
                            <x-sbadmin::form.input
                                id="crm_rot_link_mapa"
                                type="url"
                                name="crm_rot_link_mapa"
                                label="Link do Google Maps"
                                maxlength="500"
                                placeholder="https://maps.app.goo.gl/..."
                                help="Cole aqui o link da rota compartilhada pelo Google Maps."
                                x-model="linkMapa"
                            />
                        </div>
                        <div class="col-md-1">
                            <label class="sbadmin-form-label d-block">&nbsp;</label>
                            <a
                                class="btn btn-outline-primary btn-sm w-100"
                                :class="{ disabled: !linkMapa }"
                                :href="linkMapa || '#'"
                                target="_blank"
                                rel="noopener"
                                title="Abrir no Google Maps"
                            >
                                <i class="bi bi-map" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    @if($editando)
                        <div class="row">
                            <div class="col-md-3">
                                <x-sbadmin::form.select
                                    id="crm_rot_status"
                                    name="crm_rot_status"
                                    label="Situação da viagem"
                                    :options="collect(App\Enums\StatusRoteiroViagem::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                                    :value="old('crm_rot_status', $roteiro->crm_rot_status?->value)"
                                />
                            </div>
                        </div>
                    @endif

                    <hr>

                    <h6 class="fw-bold">Clientes a visitar e retorno da viagem</h6>
                    @error('clientes')
                        <div class="sbadmin-alert sbadmin-alert-error mb-2" role="alert">{{ $message }}</div>
                    @enderror

                    <template x-for="(linha, i) in linhas" :key="i">
                        <div class="sbadmin-card mb-3">
                            <div class="sbadmin-card-body">
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <label class="sbadmin-form-label">Cliente</label>
                                        <select class="form-select sbadmin-form-control" :name="'clientes['+i+']'" x-model.number="linha.clienteId">
                                            <option value="">Selecione...</option>
                                            @foreach($clientesDisponiveis as $c)
                                                <option value="{{ $c->cli_id }}">{{ $c->cli_nome }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="sbadmin-form-label">Resultado</label>
                                        <select class="form-select sbadmin-form-control" :name="'resultados['+linha.clienteId+']'" x-model="linha.resultado">
                                            <option value="">Aguardando</option>
                                            <option value="0">Visitado</option>
                                            <option value="1">Não realizado</option>
                                            <option value="2">Reagendado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="sbadmin-form-label">Observação</label>
                                        <input type="text" class="form-control sbadmin-form-control" :name="'observacoes['+linha.clienteId+']'" x-model="linha.observacao" placeholder="Observações sobre a visita...">
                                    </div>
                                </div>
                                <div class="text-end mt-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removerLinha(i)">
                                        <i class="bi bi-trash" aria-hidden="true"></i> Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <p class="text-body-secondary small" x-show="linhas.length === 0">Nenhum cliente adicionado ao roteiro.</p>

                    <button type="button" class="btn btn-outline-primary btn-sm" @click="addLinha()">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar cliente
                    </button>
                </div>

                <div class="sbadmin-card-body d-flex justify-content-end gap-2 border-top">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg" aria-hidden="true"></i> Salvar
                    </button>
                    <a href="{{ route('roteiros-viagem.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg" aria-hidden="true"></i> Voltar
                    </a>
                </div>
            </div>
        </form>
    </div>
</x-layout>