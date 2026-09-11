<x-layout title="Roteiros de Viagem">
    <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="sbadmin-page-heading">Roteiros de Viagem</h2>
            <p class="sbadmin-page-subheading">Cadastre saídas de vendedores e registre o retorno de cada visita.</p>
        </div>
        <a href="{{ route('roteiros-viagem.create') }}" class="btn btn-primary sbadmin-btn-primary">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
        </a>
    </div>

    @if(session('success'))
        <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
    @endif

    <form method="GET" action="{{ route('roteiros-viagem.index') }}" class="sbadmin-card mb-4">
        <div class="sbadmin-card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-4">
                    <label for="f_vendedor" class="sbadmin-form-label">Vendedor</label>
                    <select id="f_vendedor" name="f_vendedor" class="form-select sbadmin-form-control">
                        <option value="">Todos</option>
                        @foreach($vendedores as $v)
                            <option value="{{ $v->user_id }}" @selected((string) $filtroVendedor === (string) $v->user_id)>{{ $v->user_nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="f_periodo" class="sbadmin-form-label">Data dentro do período</label>
                    <input type="date" id="f_periodo" name="f_periodo" value="{{ $filtroPeriodo }}" class="form-control sbadmin-form-control">
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
        :headers="['Ações', 'Vendedor', 'Período', 'Situação', 'Clientes', 'Status']"
        :paginator="$roteiros"
        :count="$roteiros->count()"
        empty-message="Nenhum roteiro de viagem encontrado."
    >
        @foreach($roteiros as $r)
            @php
                $total = $r->clientes->count();
                $comRetorno = $r->clientes->whereNotNull('crm_rot_cli_resultado')->count();
            @endphp
            <tr class="{{ $r->crm_rot_status === \App\Enums\StatusRoteiroViagem::Cancelada ? 'table-danger' : '' }}">
                <td class="text-center">
                    <a href="{{ route('roteiros-viagem.edit', $r->crm_rot_id) }}" class="btn btn-sm sbadmin-table-action-btn" aria-label="Editar roteiro">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                </td>
                <td>{{ optional($r->vendedor)->user_nome }}</td>
                <td>{{ $r->crm_rot_periodo_inicio->format('d/m/Y') }} - {{ $r->crm_rot_periodo_fim->format('d/m/Y') }}</td>
                <td>
                    <x-sbadmin::badge :type="$r->crm_rot_status->badgeType()">{{ $r->crm_rot_status->label() }}</x-sbadmin::badge>
                </td>
                <td>{{ $total }}</td>
                <td>
                    @if($total === 0)
                        <x-sbadmin::badge type="neutral">Sem clientes</x-sbadmin::badge>
                    @elseif($comRetorno === 0)
                        <x-sbadmin::badge type="warning">Aguardando retorno</x-sbadmin::badge>
                    @elseif($comRetorno < $total)
                        <x-sbadmin::badge type="info">Retorno parcial ({{ $comRetorno }}/{{ $total }})</x-sbadmin::badge>
                    @else
                        <x-sbadmin::badge type="success">Retorno completo</x-sbadmin::badge>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-sbadmin::table>
</x-layout>