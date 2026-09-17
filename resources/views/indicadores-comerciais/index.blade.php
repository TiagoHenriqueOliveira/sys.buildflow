{{-- CRM08 — painel de indicadores comerciais. Pedido do cliente (2026-09-17):
     "fechadas"/"taxa de conversão" deixaram de ser mockadas - vêm do campo
     orc_resultado do orçamento (ver IndicadoresComerciaisController). --}}
<x-layout title="Indicadores Comerciais">
    <div class="sbadmin-page-header">
        <h2 class="sbadmin-page-heading">Indicadores Comerciais</h2>
        <p class="sbadmin-page-subheading">Visão geral de propostas, conversão por vendedor e clientes visitados.</p>
    </div>

    <div class="sbadmin-alert sbadmin-alert-info mb-3" role="alert">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        "Propostas fechadas" e "taxa de conversão" consideram só orçamentos com resultado definido como Convertido ou Não convertido — orçamentos Adiados, marcados como Projeto futuro, ou ainda Em aberto (sem resultado preenchido) não entram nesse cálculo até que o resultado seja definido no cadastro do orçamento.
    </div>

    <div class="sbadmin-card mb-3">
        <div class="sbadmin-card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-3">
                    <label class="sbadmin-form-label" for="f_periodo_inicio">Período — início</label>
                    <input type="date" class="form-control sbadmin-form-control" id="f_periodo_inicio" name="f_periodo_inicio" value="{{ $filtroInicio }}">
                </div>
                <div class="col-sm-3">
                    <label class="sbadmin-form-label" for="f_periodo_fim">Período — fim</label>
                    <input type="date" class="form-control sbadmin-form-control" id="f_periodo_fim" name="f_periodo_fim" value="{{ $filtroFim }}">
                </div>
                <div class="col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-info text-white">
                        <i class="bi bi-funnel" aria-hidden="true"></i> Aplicar
                    </button>
                    <a href="{{ route('indicadores-comerciais.index') }}" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="sbadmin-card h-100">
                <div class="sbadmin-card-body">
                    <p class="text-body-secondary mb-1">Propostas levantadas</p>
                    <h3 class="mb-0">{{ $totalLevantadas }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="sbadmin-card h-100">
                <div class="sbadmin-card-body">
                    <p class="text-body-secondary mb-1">Propostas fechadas</p>
                    <h3 class="mb-0">{{ $totalFechadas }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="sbadmin-card h-100">
                <div class="sbadmin-card-body">
                    <p class="text-body-secondary mb-1">Taxa de conversão</p>
                    <h3 class="mb-0">{{ $taxaConversaoGeral !== null ? $taxaConversaoGeral.'%' : '—' }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="sbadmin-card h-100">
                <div class="sbadmin-card-body">
                    <p class="text-body-secondary mb-1">Clientes visitados no período</p>
                    <h3 class="mb-0">{{ $clientesVisitados }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="sbadmin-card">
        <div class="sbadmin-card-body">
            <h6 class="fw-bold">Conversão por vendedor</h6>
            @if($indicadoresPorVendedor->isEmpty())
                <p class="text-body-secondary mb-0">Nenhum orçamento cadastrado para os filtros selecionados.</p>
            @else
                <div class="table-responsive">
                    <table class="table sbadmin-table">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Levantadas</th>
                                <th>Fechadas</th>
                                <th>Taxa de conversão</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($indicadoresPorVendedor as $linha)
                                <tr>
                                    <td>{{ $linha['vendedor'] }}</td>
                                    <td>{{ $linha['levantadas'] }}</td>
                                    <td>{{ $linha['fechadas'] }}</td>
                                    <td>{{ $linha['taxaConversao'] !== null ? $linha['taxaConversao'].'%' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layout>