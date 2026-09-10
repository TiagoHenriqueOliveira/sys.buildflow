<x-layout title="Atendimentos">
    <div>
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Atendimentos</h2>
                <p class="sbadmin-page-subheading">Gerencie os atendimentos técnicos cadastrados no sistema.</p>
            </div>
            @if(auth()->user()->user_nivel_acesso === 0)
                <a href="{{ route('atendimentos.create') }}" class="btn btn-primary sbadmin-btn-primary">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
                </a>
            @endif
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('atendimentos.index') }}" class="sbadmin-card mb-4">
            {{-- Filtros individuais por coluna — combinaveis entre si (AND:
                 cada filtro preenchido restringe ainda mais o resultado).
                 Substituem a busca unica que existia antes (removida a
                 pedido do cliente). --}}
            <div class="sbadmin-card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label for="f_natureza" class="sbadmin-form-label">Natureza</label>
                        <select id="f_natureza" name="f_natureza" class="form-select sbadmin-form-control">
                            <option value="">Todas</option>
                            @foreach($naturezasAtendimentos as $natureza)
                                <option value="{{ $natureza->nat_aten_id }}" @selected((string) $filtroNatureza === (string) $natureza->nat_aten_id)>{{ $natureza->nat_aten_descricao }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_tecnico" class="sbadmin-form-label">Técnico</label>
                        <input type="text" id="f_tecnico" name="f_tecnico" value="{{ $filtroTecnico }}" class="form-control sbadmin-form-control" placeholder="Técnico">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="f_cliente" class="sbadmin-form-label">Cliente</label>
                        <input type="text" id="f_cliente" name="f_cliente" value="{{ $filtroCliente }}" class="form-control sbadmin-form-control" placeholder="Cliente">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_nr_proposta" class="sbadmin-form-label">Nº Proposta</label>
                        <input type="text" id="f_nr_proposta" name="f_nr_proposta" value="{{ $filtroNrProposta }}" class="form-control sbadmin-form-control" placeholder="Nº Proposta">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_periodo_de" class="sbadmin-form-label">Período de</label>
                        <input type="date" id="f_periodo_de" name="f_periodo_de" value="{{ $filtroPeriodoDe }}" class="form-control sbadmin-form-control">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_periodo_ate" class="sbadmin-form-label">Período até</label>
                        <input type="date" id="f_periodo_ate" name="f_periodo_ate" value="{{ $filtroPeriodoAte }}" class="form-control sbadmin-form-control">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_status" class="sbadmin-form-label">Status</label>
                        <select id="f_status" name="f_status" class="form-select sbadmin-form-control">
                            <option value="">Todos</option>
                            @foreach(\App\Enums\AtendimentoStatus::cases() as $statusOpcao)
                                <option value="{{ $statusOpcao->value }}" @selected($filtroStatus === (string) $statusOpcao->value)>{{ $statusOpcao->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-funnel" aria-hidden="true"></i> Aplicar
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <x-sbadmin::table
            :headers="auth()->user()->user_nivel_acesso === 0
                ? ['Ações', 'Natureza', 'Técnico', 'Cliente', 'Nº Proposta', 'Período', 'Status']
                : ['Natureza', 'Técnico', 'Cliente', 'Nº Proposta', 'Período', 'Status']"
            :paginator="$atendimentos"
            :count="$atendimentos->count()"
            empty-message="Nenhum atendimento encontrado."
        >
            @php
                $hoje = \Illuminate\Support\Carbon::today();
            @endphp
            @foreach($atendimentos as $a)
                @php
                    $status = \App\Enums\AtendimentoStatus::tryFrom($a->aten_status);
                    $atrasado = (int) $a->aten_status !== 3 && $a->aten_dt_fim->lt($hoje);
                @endphp
                <tr class="{{ $atrasado ? 'table-danger' : '' }}">
                    @if(auth()->user()->user_nivel_acesso === 0)
                        <td class="text-center">
                            <a href="{{ route('atendimentos.edit', $a->aten_id) }}" class="btn btn-sm sbadmin-table-action-btn" aria-label="Editar atendimento">
                                <i class="bi bi-pencil" aria-hidden="true"></i>
                            </a>
                        </td>
                    @endif
                    <td>{{ optional($a->natureza)->nat_aten_descricao }}</td>
                    <td>{{ optional($a->usuario)->user_nome }}</td>
                    <td>{{ optional($a->cliente)->cli_nome }}</td>
                    <td>{{ $a->aten_nr_proposta }}</td>
                    <td>{{ $a->aten_dt_inicio->format('d/m/Y') }} - {{ $a->aten_dt_fim->format('d/m/Y') }}</td>
                    <td>
                        @if($status)
                            <x-sbadmin::badge :type="match($status) {
                                \App\Enums\AtendimentoStatus::Concluida => 'success',
                                \App\Enums\AtendimentoStatus::Paralisada => 'warning',
                                \App\Enums\AtendimentoStatus::EmAndamento => 'info',
                                default => 'neutral',
                            }">
                                {{ $status->label() }}
                            </x-sbadmin::badge>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>
    </div>
</x-layout>