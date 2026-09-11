<?php

namespace App\Repositories;

use App\Models\RoteiroViagem;
use App\Repositories\Contracts\CrudRepositoryInterface;
use Illuminate\Support\Facades\DB;

class RoteiroViagemRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return RoteiroViagem::orderByDesc('crm_rot_id')->get();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $roteiro = RoteiroViagem::create([
                'crm_rot_vendedor_id' => $data['crm_rot_vendedor_id'],
                'crm_rot_periodo_inicio' => $data['crm_rot_periodo_inicio'],
                'crm_rot_periodo_fim' => $data['crm_rot_periodo_fim'],
                'crm_rot_link_mapa' => $data['crm_rot_link_mapa'] ?? null,
                'crm_rot_status' => \App\Enums\StatusRoteiroViagem::NaoIniciada->value,
                'crm_rot_criado_em' => now(),
            ]);

            $this->sincronizarClientes($roteiro, $data);

            return $roteiro;
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $roteiro = RoteiroViagem::findOrFail($id);

            $roteiro->update([
                'crm_rot_vendedor_id' => $data['crm_rot_vendedor_id'],
                'crm_rot_periodo_inicio' => $data['crm_rot_periodo_inicio'],
                'crm_rot_periodo_fim' => $data['crm_rot_periodo_fim'],
                'crm_rot_link_mapa' => $data['crm_rot_link_mapa'] ?? null,
                'crm_rot_status' => $data['crm_rot_status'] ?? $roteiro->crm_rot_status?->value,
            ]);

            $this->sincronizarClientes($roteiro, $data);

            return $roteiro;
        });
    }

    /**
     * Etapa 1 (telas): substitui a lista inteira a cada save, igual ao
     * padrao ja usado em ClienteRepository::sincronizarContatos — o
     * formulario sempre reenvia clientes/resultados/observacoes juntos,
     * entao recriar do zero nao perde dado nenhum.
     */
    private function sincronizarClientes(RoteiroViagem $roteiro, array $data): void
    {
        $roteiro->clientes()->delete();

        $resultados = $data['resultados'] ?? [];
        $observacoes = $data['observacoes'] ?? [];

        $linhas = collect($data['clientes'] ?? [])
            ->values()
            ->map(fn ($clienteId, $ordem) => [
                'crm_rot_cli_roteiro_id' => $roteiro->crm_rot_id,
                'crm_rot_cli_cliente_id' => $clienteId,
                'crm_rot_cli_ordem' => $ordem,
                'crm_rot_cli_resultado' => $resultados[$clienteId] ?? null,
                'crm_rot_cli_observacao' => $observacoes[$clienteId] ?? null,
            ])
            ->all();

        if ($linhas !== []) {
            $roteiro->clientes()->insert($linhas);
        }
    }
}