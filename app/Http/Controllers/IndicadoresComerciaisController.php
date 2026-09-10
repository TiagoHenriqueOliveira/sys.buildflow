<?php

namespace App\Http\Controllers;

use App\Enums\ResultadoVisitaRoteiro;
use App\Models\Orcamento;
use App\Models\RoteiroViagemCliente;
use App\Models\Usuario;
use App\Enums\NivelAcesso;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndicadoresComerciaisController extends Controller
{
    /**
     * CRM08 — painel de indicadores comerciais. "Propostas levantadas" e
     * "clientes visitados" vêm de dados reais (orcamentos/roteiros já
     * existentes); "propostas fechadas"/"taxa de conversão" são MOCKADOS
     * nesta etapa porque o orçamento ainda não tem um status de
     * fechamento (isso é workflow da Etapa 2/Persistência, sessão 07 —
     * ver docs/cronograma/06-web-crm-telas.md, CRM08).
     */
    public function index(Request $request): View
    {
        $inicio = trim((string) $request->get('f_periodo_inicio', ''));
        $fim = trim((string) $request->get('f_periodo_fim', ''));

        $orcamentosPorVendedor = Orcamento::query()
            ->selectRaw('orc_vendedor_id, count(*) as total')
            ->when($inicio !== '', fn ($q) => $q->whereDate('orc_criado_em', '>=', $inicio))
            ->when($fim !== '', fn ($q) => $q->whereDate('orc_criado_em', '<=', $fim))
            ->groupBy('orc_vendedor_id')
            ->with('vendedor')
            ->get();

        $totalLevantadas = (int) $orcamentosPorVendedor->sum('total');

        // Taxa de conversão mockada (70%) — não há status de fechamento
        // ainda; ver aviso acima e no cabeçalho da view.
        $taxaConversaoMockada = 0.70;

        $indicadoresPorVendedor = $orcamentosPorVendedor->map(function ($linha) use ($taxaConversaoMockada) {
            $fechadas = (int) round($linha->total * $taxaConversaoMockada);

            return [
                'vendedor' => optional($linha->vendedor)->user_nome ?? 'Sem vendedor',
                'levantadas' => $linha->total,
                'fechadas' => $fechadas,
                'taxaConversao' => $linha->total > 0 ? round(($fechadas / $linha->total) * 100) : 0,
            ];
        })->sortByDesc('levantadas')->values();

        $totalFechadasMockado = (int) round($totalLevantadas * $taxaConversaoMockada);

        $clientesVisitados = RoteiroViagemCliente::query()
            ->where('crm_rot_cli_resultado', ResultadoVisitaRoteiro::Visitado->value)
            ->whereHas('roteiro', function ($q) use ($inicio, $fim) {
                $q->when($inicio !== '', fn ($qq) => $qq->where('crm_rot_periodo_fim', '>=', $inicio))
                    ->when($fim !== '', fn ($qq) => $qq->where('crm_rot_periodo_inicio', '<=', $fim));
            })
            ->distinct('crm_rot_cli_cliente_id')
            ->count('crm_rot_cli_cliente_id');

        return view('indicadores-comerciais.index', [
            'totalLevantadas' => $totalLevantadas,
            'totalFechadasMockado' => $totalFechadasMockado,
            'taxaConversaoGeralMockada' => $totalLevantadas > 0 ? round(($totalFechadasMockado / $totalLevantadas) * 100) : 0,
            'clientesVisitados' => $clientesVisitados,
            'indicadoresPorVendedor' => $indicadoresPorVendedor,
            'filtroInicio' => $inicio,
            'filtroFim' => $fim,
        ]);
    }
}