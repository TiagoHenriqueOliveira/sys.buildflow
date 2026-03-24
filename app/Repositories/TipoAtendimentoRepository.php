<?php

namespace App\Repositories;

use App\Models\TipoAtendimento;
use App\Repositories\Contracts\CrudRepositoryInterface;

class TipoAtendimentoRepository implements CrudRepositoryInterface
{
    public function all()
    {
        return TipoAtendimento::query()
            ->select('tipos_atendimentos.*')
            ->join(
                'naturezas_atendimentos',
                'naturezas_atendimentos.nat_aten_id',
                '=',
                'tipos_atendimentos.tp_aten_nat_atendimento_id'
            )
            ->with('naturezaAtendimento')
            ->orderBy('tipos_atendimentos.tp_aten_descricao', 'asc')
            ->orderBy('naturezas_atendimentos.nat_aten_descricao', 'asc')
            ->get();
    }

    public function create(array $data)
    {
        return TipoAtendimento::create([
            'tp_aten_nat_atendimento_id' => $data['tp_aten_nat_atendimento_id'],
            'tp_aten_descricao' => $data['tp_aten_descricao'],
            'tp_aten_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $tipo = TipoAtendimento::findOrFail($id);

        $tipo->update([
            'tp_aten_nat_atendimento_id' => $data['tp_aten_nat_atendimento_id'],
            'tp_aten_descricao' => $data['tp_aten_descricao'],
            'tp_aten_ativo' => $data['tp_aten_ativo'] ?? $tipo->tp_aten_ativo,
        ]);

        return $tipo;
    }
}
