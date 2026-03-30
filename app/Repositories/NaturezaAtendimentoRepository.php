<?php

namespace App\Repositories;

use App\Models\NaturezaAtendimento;
use App\Repositories\Contracts\CrudRepositoryInterface;

class NaturezaAtendimentoRepository implements CrudRepositoryInterface
{
    public function all()
    {
        return NaturezaAtendimento::with(['modeloRelatorio', 'tipoAtendimento'])
            ->orderBy('nat_aten_descricao', 'asc')
            ->get();
    }

    public function create(array $data)
    {
        return NaturezaAtendimento::create([
            'nat_aten_descricao' => $data['nat_aten_descricao'],
            'nat_aten_mod_relatorio_id' => $data['nat_aten_mod_relatorio_id'],
            'nat_aten_tp_atendimento_id' => $data['nat_aten_tp_atendimento_id'],
            'nat_aten_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $nat = NaturezaAtendimento::findOrFail($id);

        $nat->update([
            'nat_aten_descricao' => $data['nat_aten_descricao'],
            'nat_aten_mod_relatorio_id' => $data['nat_aten_mod_relatorio_id'],
            'nat_aten_tp_atendimento_id' => $data['nat_aten_tp_atendimento_id'],
            'nat_aten_ativo' => $data['nat_aten_ativo'] ?? $nat->nat_aten_ativo,
        ]);

        return $nat;
    }
}
