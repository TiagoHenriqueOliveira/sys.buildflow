<?php

namespace App\Repositories;

use App\Models\Ocupacao;
use App\Repositories\Contracts\CrudRepositoryInterface;

class OcupacaoRepository implements CrudRepositoryInterface
{
    public function all()
    {
        return Ocupacao::query()
            ->select('ocupacoes.*')
            ->join(
                'tipos_ocupacoes',
                'tipos_ocupacoes.tp_ocup_id',
                '=',
                'ocupacoes.ocup_tp_ocupacao_id'
            )
            ->with('tipoOcupacao')
            ->orderBy('ocupacoes.ocup_descricao', 'asc')
            ->orderBy('tipos_ocupacoes.tp_ocup_descricao', 'asc')
            ->get();
    }

    public function create(array $data)
    {
        return Ocupacao::create([
            'ocup_tp_ocupacao_id' => $data['ocup_tp_ocupacao_id'],
            'ocup_descricao' => $data['ocup_descricao'],
            'ocup_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $ocup = Ocupacao::findOrFail($id);

        $ocup->update([
            'ocup_tp_ocupacao_id' => $data['ocup_tp_ocupacao_id'],
            'ocup_descricao' => $data['ocup_descricao'],
            'ocup_ativo' => $data['ocup_ativo'] ?? $ocup->ocup_ativo,
        ]);

        return $ocup;
    }
}
