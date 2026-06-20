<?php

namespace App\Http\Controllers;

use App\Enums\AtendimentoStatus;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $tecnicos = Usuario::where('user_nivel_acesso', 1)
            ->where('user_ativo', 1)
            ->orderBy('user_nome')
            ->get(['user_id', 'user_nome']);

        return view('dashboard.index', compact('tecnicos'));
    }

    // ── 1. KPI Cards ─────────────────────────────────────────────────────────

    public function kpis(): JsonResponse
    {
        $total       = (clone $this->baseQuery())->count();
        $emAndamento = (clone $this->baseQuery())->where('aten_status', AtendimentoStatus::EmAndamento->value)->count();
        $concluidos  = (clone $this->baseQuery())->where('aten_status', AtendimentoStatus::Concluida->value)->count();
        $relatorios  = AtendimentoRelatorio::when($this->isTecnico(), fn($q) =>
            $q->whereHas('atendimento', fn($a) => $a->where('aten_usuario_id', Auth::id()))
        )->count();

        return response()->json([
            'total'        => $total,
            'em_andamento' => $emAndamento,
            'concluidos'   => $concluidos,
            'relatorios'   => $relatorios,
        ]);
    }

    // ── 2. Atendimentos por Status ────────────────────────────────────────────

    public function porStatus(Request $request): JsonResponse
    {
        $dtInicio = $request->get('dt_inicio');
        $dtFim    = $request->get('dt_fim');

        $rows = (clone $this->baseQuery())
            ->select('aten_status', DB::raw('COUNT(*) as total'))
            ->when($dtInicio, fn($q) => $q->where('aten_dt_inicio', '>=', $dtInicio))
            ->when($dtFim,    fn($q) => $q->where('aten_dt_inicio', '<=', $dtFim))
            ->groupBy('aten_status')
            ->get();

        $labels = [];
        $data   = [];
        $colors = ['#858796', '#e74a3b', '#f6c23e', '#1cc88a'];

        foreach (AtendimentoStatus::cases() as $case) {
            $labels[] = $case->label();
            $data[]   = $rows->firstWhere('aten_status', $case->value)?->total ?? 0;
        }

        return response()->json(compact('labels', 'data', 'colors'));
    }

    // ── 3. Evolução: relatórios criados ao longo do tempo ────────────────────

    public function evolucao(Request $request): JsonResponse
    {
        $meses = (int) $request->get('meses', 12);
        $meses = max(3, min(24, $meses));

        $rows = AtendimentoRelatorio::select(
                DB::raw("DATE_FORMAT(aten_rel_data, '%Y-%m') as mes"),
                DB::raw('COUNT(*) as total')
            )
            ->when($this->isTecnico(), fn($q) =>
                $q->whereHas('atendimento', fn($a) => $a->where('aten_usuario_id', Auth::id()))
            )
            ->where('aten_rel_data', '>=', now()->subMonths($meses)->startOfMonth())
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        $labels = [];
        $data   = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $key      = now()->subMonths($i)->format('Y-m');
            $labels[] = now()->subMonths($i)->locale('pt_BR')->isoFormat('MMM/YY');
            $data[]   = $rows->get($key)?->total ?? 0;
        }

        return response()->json(compact('labels', 'data'));
    }

    // ── 4. Relatórios por cliente — em aberto, empilhado por status ──────────

    public function maisRelatorios(Request $request): JsonResponse
    {
        $limite = (int) $request->get('limite', 10);
        $limite = max(5, min(20, $limite));

        // Excluir Concluída; contar atendimentos por cliente + status
        $rows = Atendimento::select(
                'clientes.cli_nome as cliente',
                'atendimentos.aten_status',
                DB::raw('COUNT(*) as total')
            )
            ->join('clientes', 'clientes.cli_id', '=', 'atendimentos.aten_cliente_id')
            ->when($this->isTecnico(), fn($q) => $q->where('atendimentos.aten_usuario_id', Auth::id()))
            ->whereIn('atendimentos.aten_status', [
                AtendimentoStatus::NaoIniciada->value,
                AtendimentoStatus::Paralisada->value,
                AtendimentoStatus::EmAndamento->value,
            ])
            ->groupBy('clientes.cli_nome', 'atendimentos.aten_status')
            ->get();

        // Top N clientes por total de atendimentos em aberto
        $byCliente     = $rows->groupBy('cliente');
        $clienteTotals = $byCliente
            ->map(fn($g) => $g->sum('total'))
            ->filter(fn($total) => $total > 0)
            ->sortDesc()
            ->take($limite);

        $clienteNames = $clienteTotals->keys()->toArray();
        $labels       = array_map(fn($n) => mb_strimwidth($n, 0, 40, '…'), $clienteNames);

        $statusConfigs = [
            AtendimentoStatus::NaoIniciada->value => ['label' => 'Não iniciada', 'color' => '#858796'],
            AtendimentoStatus::Paralisada->value  => ['label' => 'Paralisada',   'color' => '#e74a3b'],
            AtendimentoStatus::EmAndamento->value => ['label' => 'Em andamento', 'color' => '#f6c23e'],
        ];

        $datasets = [];
        foreach ($statusConfigs as $statusVal => $config) {
            $data = [];
            foreach ($clienteNames as $cliente) {
                $row    = $byCliente->get($cliente)?->firstWhere('aten_status', $statusVal);
                $data[] = (int) ($row?->total ?? 0);
            }
            $datasets[] = ['label' => $config['label'], 'data' => $data, 'color' => $config['color']];
        }

        return response()->json(compact('labels', 'datasets'));
    }

    // ── 5. Atendimentos por Estado (UF do cliente) ───────────────────────────

    public function porEstado(Request $request): JsonResponse
    {
        $tecnicoId = $request->get('tecnico_id');

        $rows = Atendimento::select(
                'clientes.cli_uf as uf',
                DB::raw('COUNT(*) as total')
            )
            ->join('clientes', 'clientes.cli_id', '=', 'atendimentos.aten_cliente_id')
            ->when($this->isTecnico(), fn($q) => $q->where('atendimentos.aten_usuario_id', Auth::id()))
            ->when($tecnicoId && !$this->isTecnico(), fn($q) => $q->where('atendimentos.aten_usuario_id', $tecnicoId))
            ->whereNotNull('clientes.cli_uf')
            ->where('clientes.cli_uf', '!=', '')
            ->groupBy('clientes.cli_uf')
            ->orderByDesc('total')
            ->get();

        return response()->json(['data' => $rows]);
    }

    // ── 6. Atendimentos por Técnico ───────────────────────────────────────────

    public function porTecnico(Request $request): JsonResponse
    {
        if ($this->isTecnico()) {
            return response()->json(['labels' => [], 'data' => []]);
        }

        $uf = $request->get('uf');

        $query = Atendimento::select(
                'usuarios.user_nome as tecnico',
                DB::raw('COUNT(*) as total')
            )
            ->join('usuarios', 'usuarios.user_id', '=', 'atendimentos.aten_usuario_id');

        if ($uf) {
            $query->join('clientes', 'clientes.cli_id', '=', 'atendimentos.aten_cliente_id')
                  ->where('clientes.cli_uf', strtoupper($uf));
        }

        $rows = $query->groupBy('usuarios.user_nome')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'labels' => $rows->pluck('tecnico')->toArray(),
            'data'   => $rows->pluck('total')->toArray(),
        ]);
    }

    // ── 7. Por Setor/Natureza ─────────────────────────────────────────────────

    public function porSetor(): JsonResponse
    {
        $rows = (clone $this->baseQuery())
            ->select(
                'naturezas_atendimentos.nat_aten_descricao as setor',
                DB::raw('COUNT(*) as total')
            )
            ->join('naturezas_atendimentos', 'naturezas_atendimentos.nat_aten_id', '=', 'atendimentos.aten_natureza_id')
            ->groupBy('naturezas_atendimentos.nat_aten_descricao')
            ->orderByDesc('total')
            ->get();

        $palette = ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#5a5c69','#2e59d9'];

        return response()->json([
            'labels' => $rows->pluck('setor')->toArray(),
            'data'   => $rows->pluck('total')->toArray(),
            'colors' => $rows->keys()->map(fn($i) => $palette[$i % count($palette)])->toArray(),
        ]);
    }

    // ── 8. Tempo médio de atendimento (dias) ──────────────────────────────────

    public function tempoMedio(): JsonResponse
    {
        $rows = (clone $this->baseQuery())
            ->select(
                'aten_status',
                DB::raw('ROUND(AVG(DATEDIFF(IFNULL(aten_dt_fim, CURDATE()), aten_dt_inicio)), 0) as media_dias')
            )
            ->groupBy('aten_status')
            ->get();

        $labels = [];
        $data   = [];
        $colors = ['#858796', '#e74a3b', '#f6c23e', '#1cc88a'];

        foreach (AtendimentoStatus::cases() as $case) {
            $labels[] = $case->label();
            $data[]   = (int) ($rows->firstWhere('aten_status', $case->value)?->media_dias ?? 0);
        }

        return response()->json(compact('labels', 'data', 'colors'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isTecnico(): bool
    {
        return Auth::user()->user_nivel_acesso === 1;
    }

    private function baseQuery()
    {
        return Atendimento::query()
            ->when($this->isTecnico(), fn($q) => $q->where('aten_usuario_id', Auth::id()));
    }
}
