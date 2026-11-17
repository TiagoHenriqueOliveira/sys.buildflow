<?php

namespace App\Support;

use App\Enums\AtendimentoStatus;
use App\Models\Atendimento;
use Illuminate\Support\Collection;

/**
 * BF08 — agrupa os atendimentos do Mapa de Demandas por PONTO (mesma
 * latitude/longitude). Pedido do cliente (2026-11-17): o atendimento não tem
 * posição própria (usa a do cliente), então dois atendimentos do mesmo
 * cliente — ex.: um em aberto de um técnico e um concluído de outro —
 * caíam exatamente no mesmo lugar e a bolinha de cima escondia a de baixo.
 * Agora cada ponto vira um marcador só, com a quantidade e a lista de
 * todos os atendimentos dali no popup.
 *
 * A cor do ponto é a do atendimento mais pendente (ver
 * AtendimentoStatus::prioridadeNoMapa), para um atendimento em aberto
 * nunca ficar "escondido" atrás de um concluído.
 *
 * Usado pela web (MapaDemandasController) e pela API
 * (Api\Fae\AtendimentosController::mapaDemandas, chave `pontos`).
 */
final class PontosMapaDemandas
{
    /**
     * @param  Collection<int, Atendimento>  $atendimentos  com cliente/natureza/usuario carregados
     * @return list<array<string, mixed>>
     */
    public static function agrupar(Collection $atendimentos): array
    {
        return $atendimentos
            ->filter(fn (Atendimento $a) => $a->cliente?->temGeolocalizacao())
            ->groupBy(fn (Atendimento $a) => round((float) $a->cliente->cli_latitude, 6) . ',' . round((float) $a->cliente->cli_longitude, 6))
            ->map(function (Collection $grupo) {
                $ordenados = $grupo->sortBy([
                    fn ($a, $b) => self::prioridade($a) <=> self::prioridade($b),
                    fn ($a, $b) => $b->aten_id <=> $a->aten_id,
                ])->values();
                $principal = AtendimentoStatus::from($ordenados->first()->aten_status);
                $primeiroCliente = $ordenados->first()->cliente;

                return [
                    'latitude' => (float) $primeiroCliente->cli_latitude,
                    'longitude' => (float) $primeiroCliente->cli_longitude,
                    'clientes' => $ordenados->map(fn ($a) => $a->cliente->cli_nome)->unique()->values()->all(),
                    'total' => $ordenados->count(),
                    'status' => $principal->value,
                    'status_label' => $principal->label(),
                    'atendimentos' => $ordenados->map(fn (Atendimento $a) => [
                        'id' => $a->aten_id,
                        'cliente' => $a->cliente->cli_nome,
                        'status' => $a->aten_status,
                        'status_label' => AtendimentoStatus::from($a->aten_status)->label(),
                        'natureza' => $a->natureza?->nat_aten_descricao,
                        'tecnico' => $a->usuario?->user_nome,
                        'proposta' => $a->aten_nr_proposta,
                    ])->all(),
                ];
            })
            ->values()
            ->all();
    }

    private static function prioridade(Atendimento $a): int
    {
        return AtendimentoStatus::tryFrom((int) $a->aten_status)?->prioridadeNoMapa() ?? 99;
    }
}
