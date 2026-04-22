<?php

namespace App\Repositories;

use App\Models\AtendimentoRelatorio;
use App\Repositories\Contracts\CrudRepositoryInterface;

class AtendimentoRelatorioRepository implements CrudRepositoryInterface
{
    public function all(array $filters = [])
    {
        $q = AtendimentoRelatorio::query()
            ->select('atendimentos_relatorios.*')
            ->with([
                'atendimento.cliente',
                'atendimento.natureza.tipoAtendimento',
            ])
            ->orderBy('aten_rel_data', 'desc')
            ->orderBy('aten_rel_id', 'desc');

        if (!empty($filters['aten_rel_atendimento_id'])) {
            $q->where('aten_rel_atendimento_id', $filters['aten_rel_atendimento_id']);
        }

        return $q->get();
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
