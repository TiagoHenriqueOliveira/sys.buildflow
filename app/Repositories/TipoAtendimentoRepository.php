<?php

namespace App\Repositories;

use App\Models\TipoAtendimento;
use App\Repositories\Contracts\CrudRepositoryInterface;

class TipoAtendimentoRepository implements CrudRepositoryInterface
{
    public function all()
    {
        return TipoAtendimento::with('naturezaAtendimento')
            ->get()
            ->sortBy(function ($a) {
                $nat = optional($a->naturezaAtendimento)->nat_aten_descricao ?? '';
                return mb_strtolower($a->tp_aten_descricao) . '|' . mb_strtolower($nat);
            })
            ->values();
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
