<?php

namespace App\Http\Controllers;

use App\Enums\ResultadoOrcamento;
use App\Enums\ResultadoVisitaRoteiro;
use App\Models\RoteiroViagemCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IndicadoresComerciaisController extends Controller
{
    /**
     * CRM08 — painel de indicadores comerciais. Pedido do cliente
     * (2026-09-17): "propostas fechadas"/"taxa de conversão" deixam de ser
     * mockadas (70% fixo) - passam a vir do campo orc_resultado
     * (Convertido/Não Convertido/Adiado/Projeto Futuro), preenchido no
     * cadastro do orçamento. "Fechadas" = Convertido; a taxa de conversão
     * considera só orçamentos já decididos (Convertido + Não Convertido) -
     * Adiado/Projeto Futuro/Em aberto ainda não têm resultado definitivo,
     * não entram no cálculo. Query de agregação em DB::table() direto (não
     * via o model Orcamento) pra evitar comparar enum castado com inteiro
     * bruto nas somas condicionais abaixo.
     */
    public function index(Request $request): View
    {
        $inicio = trim((string) $request->get('f_periodo_inicio', ''));
        $fim = trim((string) $request->get('f_periodo_fim', ''));
        $convertido = ResultadoOrcamento::Convertido->value;
        $naoConvertido = ResultadoOrcamento::NaoConvertido->value;

        $porVendedor = DB::table('orcamentos')
            ->join('usuarios', 'usuarios.user_id', '=', 'orcamentos.orc_vendedor_id')
            ->selectRaw(
                'usuarios.user_nome as vendedor,
                 count(*) as levantadas,
                 sum(case when orc_resultado = ? then 1 else 0 end) as fechadas,
                 sum(case when orc_resultado = ? then 1 else 0 end) as nao_convertidas',
                [$convertido, $naoConvertido]
            )
            ->when($inicio !== '', fn ($q) => $q->whereDate('orc_criado_em', '>=', $inicio))
            ->when($fim !== '', fn ($q) => $q->whereDate('orc_criado_em', '<=', $fim))
            ->groupBy('usuarios.user_id', 'usuarios.user_nome')
            ->orderByDesc('levantadas')
            ->get();

        $indicadoresPorVendedor = $porVendedor->map(function ($linha) {
            $decididas = $linha->fechadas + $linha->nao_convertidas;

            return [
                'vendedor' => $linha->vendedor,
                'levantadas' => (int) $linha->levantadas,
                'fechadas' => (int) $linha->fechadas,
                'taxaConversao' => $decididas > 0 ? round(($linha->fechadas / $decididas) * 100) : null,
            ];
        });

        $totalLevantadas = (int) $porVendedor->sum('levantadas');
        $totalFechadas = (int) $porVendedor->sum('fechadas');
        $totalDecididas = $totalFechadas + (int) $porVendedor->sum('nao_convertidas');

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
            'totalFechadas' => $totalFechadas,
            'taxaConversaoGeral' => $totalDecididas > 0 ? round(($totalFechadas / $totalDecididas) * 100) : null,
            'clientesVisitados' => $clientesVisitados,
            'indicadoresPorVendedor' => $indicadoresPorVendedor,
            'filtroInicio' => $inicio,
            'filtroFim' => $fim,
        ]);
    }
}