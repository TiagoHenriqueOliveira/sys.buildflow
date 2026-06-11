<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipamento;
use App\Models\Ocorrencia;
use App\Models\Ocupacao;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints de catálogo — somente leitura.
 * Usados pelo app para popular listas de seleção (dropdowns).
 * Nenhum desses campos é editável pelo app — apenas exibição.
 */
class CatalogoController extends Controller
{
    /**
     * Lista mão de obra disponível (ocupações ativas), agrupada por tipo.
     *
     * GET /api/v1/catalogos/mao-obra
     *
     * Campos retornados (somente leitura — apenas exibição):
     *   ocup_id    → use como ID ao adicionar mão de obra no relatório
     *   descricao  → exiba para o usuário
     *   tipo_id    → ID do tipo (agrupamento)
     *   tipo       → nome do tipo para exibir como cabeçalho de grupo
     */
    public function maoObra(): JsonResponse
    {
        $ocupacoes = Ocupacao::with('tipoOcupacao')
            ->where('ocup_ativo', true)
            ->orderBy('ocup_descricao')
            ->get()
            ->map(fn($o) => [
                'ocup_id'  => $o->ocup_id,
                'descricao'=> $o->ocup_descricao,   // exibição apenas
                'tipo_id'  => $o->ocup_tp_ocupacao_id,
                'tipo'     => optional($o->tipoOcupacao)->tp_ocup_descricao, // exibição apenas
            ]);

        return response()->json(['data' => $ocupacoes]);
    }

    /**
     * Lista ferramentas/equipamentos disponíveis (ativos).
     *
     * GET /api/v1/catalogos/ferramentas
     *
     * Campos retornados (somente leitura — apenas exibição):
     *   equip_id   → use como ID ao adicionar equipamento no relatório
     *   descricao  → exiba para o usuário
     */
    public function ferramentas(): JsonResponse
    {
        $equipamentos = Equipamento::where('equip_ativo', true)
            ->orderBy('equip_descricao')
            ->get()
            ->map(fn($e) => [
                'equip_id' => $e->equip_id,
                'descricao'=> $e->equip_descricao,  // exibição apenas
            ]);

        return response()->json(['data' => $equipamentos]);
    }

    /**
     * Lista ocorrências disponíveis (ativas).
     *
     * GET /api/v1/catalogos/ocorrencias
     *
     * Campos retornados (somente leitura — apenas exibição):
     *   ocorrencia_id → use como ID ao adicionar ocorrência no relatório
     *   descricao     → exiba para o usuário
     */
    public function ocorrencias(): JsonResponse
    {
        $ocorrencias = Ocorrencia::where('ocor_ativo', true)
            ->orderBy('ocor_descricao')
            ->get()
            ->map(fn($o) => [
                'ocorrencia_id' => $o->ocor_id,
                'descricao'     => $o->ocor_descricao,  // exibição apenas
            ]);

        return response()->json(['data' => $ocorrencias]);
    }
}
