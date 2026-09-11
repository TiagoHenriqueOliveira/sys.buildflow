{{-- CRM08 — painel de indicadores comerciais (dados mockados nesta etapa,
     ver comentário no IndicadoresComerciaisController). --}}
<x-layout title="Indicadores Comerciais">
    <div class="sbadmin-page-header">
        <h2 class="sbadmin-page-heading">Indicadores Comerciais</h2>
        <p class="sbadmin-page-subheading">Visão geral de propostas, conversão por vendedor e clientes visitados.</p>
    </div>

    <div class="sbadmin-alert sbadmin-alert-info mb-3" role="alert">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        "Propostas fechadas" e "taxa de conversão" são <strong>mockados</strong> nesta etapa (70% fixo) — o orçamento ainda não tem um status de fechamento real, isso é parte da sessão de Persistência do CRM. "Propostas levantadas" e "clientes visitados" já usam dados reais cadastrados.
    </div>

    <div class="sbadmin-card mb-3">
        <div class="sbadmin-card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <label class="sbadmin-form-label" for="f_periodo_inicio">Período — início</label>
                    <input type="date" class="form-control sbadmin-form-control" id="f_periodo_inicio" name="f_periodo_inicio" value="{{ $filtroInicio }}">
                </div>
                <div class="col-sm-4">
                    <label class="sbadmin-form-label" for="f_periodo_fim">Período — fim</label>
                    <input type="date" class="form-control sbadmin-form-control" id="f_periodo_fim" name="f_periodo_fim" value="{{ $filtroFim }}">
                </div>
                <div class="col-sm-4 d-flex gap-2">
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
                    <p class="text-body-secondary mb-1">Propostas fechadas <span class="badge bg-secondary">mockado</span></p>
                    <h3 class="mb-0">{{ $totalFechadasMockado }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="sbadmin-card h-100">
                <div class="sbadmin-card-body">
                    <p class="text-body-secondary mb-1">Taxa de conversão <span class="badge bg-secondary">mockado</span></p>
                    <h3 class="mb-0">{{ $taxaConversaoGeralMockada }}%</h3>
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
                                <th>Fechadas <span class="badge bg-secondary">mockado</span></th>
                                <th>Taxa de conversão <span class="badge bg-secondary">mockado</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($indicadoresPorVendedor as $linha)
                                <tr>
                                    <td>{{ $linha['vendedor'] }}</td>
                                    <td>{{ $linha['levantadas'] }}</td>
                                    <td>{{ $linha['fechadas'] }}</td>
                                    <td>{{ $linha['taxaConversao'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layout>