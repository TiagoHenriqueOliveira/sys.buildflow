<?php

namespace App\Http\Controllers\Api\Fae;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;

/**
 * CRM07 (mapa de relacoes, so-leitura).
 * Mesmos numeros/dados do controller web (MapaRelacoesController) - sem os
 * filtros da tela Web nesta primeira etapa (mobile so precisa da lista, ver
 * sessao 15).
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
}
