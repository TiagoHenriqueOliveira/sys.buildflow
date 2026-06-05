<?php

namespace App\Repositories;

use App\Models\AtendimentoEquipamento;

class AtendimentoEquipamentoRepository
{
    public function findByAtendimento(int $atendimentoId)
    {
        return AtendimentoEquipamento::where('aten_equip_atendimento_id', $atendimentoId)
            ->orderBy('aten_equip_id', 'asc')
            ->get();
    }

    public function create(array $data)
    {
        return AtendimentoEquipamento::create($data);
    }

    public function delete(int $id)
    {
        return AtendimentoEquipamento::findOrFail($id)->delete();
    }
}
