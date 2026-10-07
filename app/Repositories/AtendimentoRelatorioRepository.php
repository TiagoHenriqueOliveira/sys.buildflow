<?php

namespace App\Repositories;

use App\Models\AtendimentoRelatorio;
use App\Repositories\Contracts\CrudRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class AtendimentoRelatorioRepository implements CrudRepositoryInterface
{
    /**
     * Retorna um Builder configurado para uso pelo DataTableService.
     */
    public function query(array $filters = []): Builder
    {
        $q = AtendimentoRelatorio::query()
            ->select('atendimentos_relatorios.*')
            ->with([
                'atendimento.cliente',
                'atendimento.natureza',
                'atendimento.usuario',
            ])
            ->join('atendimentos',          'atendimentos.aten_id',             '=', 'atendimentos_relatorios.aten_rel_atendimento_id')
            ->join('clientes',              'clientes.cli_id',                  '=', 'atendimentos.aten_cliente_id')
            ->join('naturezas_atendimentos','naturezas_atendimentos.nat_aten_id','=', 'atendimentos.aten_natureza_id')
            ->join('usuarios',              'usuarios.user_id',                 '=', 'atendimentos.aten_usuario_id')
            ->orderByDesc('atendimentos_relatorios.aten_rel_data')
            ->orderByDesc('atendimentos_relatorios.aten_rel_id');

        if (!empty($filters['aten_rel_atendimento_id'])) {
            $q->where('atendimentos_relatorios.aten_rel_atendimento_id', $filters['aten_rel_atendimento_id']);
        }

        if (!empty($filters['usuario_id'])) {
            $q->where('atendimentos.aten_usuario_id', $filters['usuario_id']);
        }

        return $q;
    }

    public function all(array $filters = []): \Illuminate\Support\Collection
    {
        return $this->query($filters)
            ->orderBy('aten_rel_data', 'desc')
            ->orderBy('aten_rel_id', 'desc')
            ->get();
    }

    public function create(array $data)
    {
        return AtendimentoRelatorio::create([
            'aten_rel_atendimento_id'      => $data['aten_rel_atendimento_id'],
            'aten_rel_modelo_relatorio_id' => $data['aten_rel_modelo_relatorio_id'],
            'aten_rel_data'                => $data['aten_rel_data'] ?? now()->toDateString(),
            'aten_rel_status'              => $data['aten_rel_status'] ?? 0,
        ]);
    }

    public function update(int $id, array $data)
    {
        $rel = AtendimentoRelatorio::findOrFail($id);

        $rel->update([
            'aten_rel_status' => $data['aten_rel_status'] ?? $rel->aten_rel_status,
        ]);

        return $rel;
    }
}
