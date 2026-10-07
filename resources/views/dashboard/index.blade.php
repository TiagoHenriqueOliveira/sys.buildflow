<x-layout title="Dashboard">


{{-- ── KPI Cards ──────────────────────────────────────────────────────────── --}}
<div class="row" id="kpiRow">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total de Atendimentos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpiTotal">—</div>
                    </div>
                    <div class="col-auto"><i class="fas fa-headset fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Em Andamento</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpiEmAndamento">—</div>
                    </div>
                    <div class="col-auto"><i class="fas fa-spinner fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Concluídos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpiConcluidos">—</div>
                    </div>
                    <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total de Relatórios</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpiRelatorios">—</div>
                    </div>
                    <div class="col-auto"><i class="fas fa-file-alt fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Linha 1: Status (donut) + Evolução (line) + Tempo Médio (bar) ─────── --}}
<div class="row">
    {{-- Donut: Por Status --}}
    <div class="col-xl-3 col-lg-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary mb-2">Atendimentos por Status</h6>
                <div class="d-flex align-items-center flex-wrap" style="gap:0.4rem;">
                    <input type="date" id="statusDtInicio" class="form-control form-control-sm" style="width:auto;" title="Data inicial">
                    <span class="text-xs text-gray-500">até</span>
                    <input type="date" id="statusDtFim" class="form-control form-control-sm" style="width:auto;" title="Data final">
                </div>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartStatus" style="max-height:240px;"></canvas>
            </div>
        </div>
    </div>

    {{-- Line: Evolução de Relatórios --}}
    <div class="col-xl-5 col-lg-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Evolução de Relatórios Criados</h6>
                <select id="evolucaoMeses" class="form-control form-control-sm d-inline-block" style="width:auto;">
                    <option value="6">6 meses</option>
                    <option value="12" selected>12 meses</option>
                    <option value="18">18 meses</option>
                    <option value="24">24 meses</option>
                </select>
            </div>
            <div class="card-body">
                <canvas id="chartEvolucao" style="max-height:240px;"></canvas>
            </div>
        </div>
    </div>

    {{-- Bar: Tempo Médio de Atendimento --}}
    <div class="col-xl-4 col-lg-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tempo Médio de Atendimento (dias)</h6>
            </div>
            <div class="card-body">
                <canvas id="chartTempoMedio" style="max-height:240px;"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- ── Linha 2: Mais Relatórios (bar + multiselect — altura dinâmica) ──────── --}}
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Atendimentos por Cliente — Em Aberto</h6>
                <select id="maisRelatoriosLimite" class="form-control form-control-sm d-inline-block" style="width:auto;">
                    <option value="5">Top 5</option>
                    <option value="10" selected>Top 10</option>
                    <option value="15">Top 15</option>
                    <option value="20">Top 20</option>
                </select>
            </div>
            <div class="card-body">
                {{-- altura controlada via JS: 48px por barra + 60px de margem --}}
                <div id="chartMaisRelatoriosWrapper" style="position:relative;">
                    <canvas id="chartMaisRelatorios"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Linha 3: Mapa (direita) + Técnico e Setor empilhados (esquerda) ──────── --}}
<div class="row align-items-stretch">

    {{-- Coluna esquerda: Técnico em cima, Setor embaixo --}}
    <div class="col-xl-5 col-lg-5 mb-4 d-flex flex-column" style="gap:1.5rem;">

        {{-- Bar: Por Técnico --}}
        <div class="card shadow flex-fill">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Atendimentos por Técnico
                    <span id="tecnicoEstadoLabel" class="text-gray-500 font-weight-normal"></span>
                </h6>
            </div>
            <div class="card-body d-flex align-items-center" style="min-height:220px;">
                <canvas id="chartTecnico" style="width:100%;"></canvas>
            </div>
        </div>

        {{-- Pie: Distribuição por Setor --}}
        <div class="card shadow flex-fill">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Distribuição por Setor</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height:220px;">
                <canvas id="chartSetor" style="max-height:260px;"></canvas>
            </div>
        </div>

    </div>

    {{-- Coluna direita: Mapa do Brasil --}}
    <div class="col-xl-7 col-lg-7 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Atendimentos por Estado</h6>
                @if(Auth::user()->user_nivel_acesso === 0)
                <select id="mapaTecnicoFiltro" class="form-control form-control-sm d-inline-block" style="width:auto;">
                    <option value="">Todos os técnicos</option>
                    @foreach($tecnicos as $t)
                    <option value="{{ $t->user_id }}">{{ $t->user_nome }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            <div class="card-body p-2" id="mapaWrapper" style="position:relative;">
                <div id="mapTooltip" style="position:fixed;background:rgba(15,23,42,.85);color:#e2e8f0;padding:5px 11px;border-radius:6px;font-size:12px;pointer-events:none;display:none;z-index:9999;white-space:nowrap;"></div>
                <div id="brazilMapContainer" style="width:100%;"></div>
                <div class="text-center mt-1">
                    <small class="text-gray-500">Clique em um estado para filtrar "Atendimentos por Técnico"</small>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="{{ asset('js/chart.js/Chart.bundle.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/topojson/3.0.2/topojson.min.js"></script>
<script>
    var dashboardTecnicos = @json($tecnicos);
    var isAdmin = {{ Auth::user()->user_nivel_acesso === 0 ? 'true' : 'false' }};
</script>
<script src="{{ asset('js/app/dashboard.js') }}"></script>
@endpush

</x-layout>
