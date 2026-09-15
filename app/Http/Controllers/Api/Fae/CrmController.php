<?php

namespace App\Http\Controllers\Api\Fae;

use App\Enums\ResultadoVisitaRoteiro;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\RoteiroViagemCliente;
use Illuminate\Http\JsonResponse;

/**
 * CRM07 (mapa de relacoes, so-leitura) e CRM08 (indicadores comerciais).
 * Mesmos numeros/dados dos controllers web (MapaRelacoesController,
 * IndicadoresComerciaisController) - sem os filtros da tela Web nesta
 * primeira etapa (mobile so precisa da lista/números, ver sessao 15).
 *
 * Decisao do usuario (14/09/2026, sessao 15): Mapa de Relacoes no Android e
 * lista + link externo do Google Maps, sem mapa embutido - mesmo padrao ja
 * usado em Cliente e Roteiro de Viagem, sem depender de
 * google_maps_flutter/chave de API.
 */
class CrmController extends Controller
{
    public function mapaRelacoes(): JsonResponse
    {
        $clientes = Cliente::query()
            ->with('classificacao')
            ->whereNotNull('cli_latitude')
            ->whereNotNull('cli_longitude')
            ->orderBy('cli_nome')
            ->get();

        return response()->json([
            'data' => $clientes->map(fn ($c) => [
                'id' => $c->cli_id,
                'nome' => $c->cli_nome,
                'cidade' => $c->cli_cidade,
                'uf' => $c->cli_uf,
                'latitude' => $c->cli_latitude,
                'longitude' => $c->cli_longitude,
                'link_mapa' => $c->cli_link_mapa,
                'caso_sucesso' => (bool) $c->cli_caso_sucesso,
                'classificacao' => $c->classificacao?->cla_cli_nome,
            ])->values(),
        ]);
    }

    /**
     * CRM08 - "propostas fechadas"/"taxa de conversao" sao MOCKADOS nesta
     * etapa (mesma ressalva do web): orcamento ainda nao tem status de
     * fechamento, isso e workflow da Etapa 2/Persistencia.
     */
    public function indicadores(): JsonResponse
    {
        $orcamentosPorVendedor = Orcamento::query()
            ->selectRaw('orc_vendedor_id, count(*) as total')
            ->groupBy('orc_vendedor_id')
            ->with('vendedor')
            ->get();

        $totalLevantadas = (int) $orcamentosPorVendedor->sum('total');
        $taxaConversaoMockada = 0.70;

        $indicadoresPorVendedor = $orcamentosPorVendedor->map(function ($linha) use ($taxaConversaoMockada) {
            $fechadas = (int) round($linha->total * $taxaConversaoMockada);

            return [
                'vendedor' => optional($linha->vendedor)->user_nome ?? 'Sem vendedor',
                'levantadas' => $linha->total,
                'fechadas' => $fechadas,
                'taxa_conversao' => $linha->total > 0 ? round(($fechadas / $linha->total) * 100) : 0,
            ];
        })->sortByDesc('levantadas')->values();

        $totalFechadasMockado = (int) round($totalLevantadas * $taxaConversaoMockada);

        $clientesVisitados = RoteiroViagemCliente::query()
            ->where('crm_rot_cli_resultado', ResultadoVisitaRoteiro::Visitado->value)
            ->distinct('crm_rot_cli_cliente_id')
            ->count('crm_rot_cli_cliente_id');

        return response()->json([
            'total_levantadas' => $totalLevantadas,
            'total_fechadas_mockado' => $totalFechadasMockado,
            'taxa_conversao_geral_mockado' => $totalLevantadas > 0 ? round(($totalFechadasMockado / $totalLevantadas) * 100) : 0,
            'clientes_visitados' => $clientesVisitados,
            'por_vendedor' => $indicadoresPorVendedor,
        ]);
    }
}