<?php

namespace App\Repositories;

use App\Models\TipoOcupacao;
use App\Repositories\Contracts\CrudRepositoryInterface;

class TipoOcupacaoRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return TipoOcupacao::orderBy('tp_ocup_descricao')->get();
    }

    public function create(array $data)
    {
        return TipoOcupacao::create([
            'tp_ocup_descricao' => $data['tp_ocup_descricao'],
            'tp_ocup_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $tipo = TipoOcupacao::findOrFail($id);

        $tipo->update([
            'tp_ocup_descricao' => $data['tp_ocup_descricao'],
            'tp_ocup_ativo' => $data['tp_ocup_ativo'] ?? $tipo->tp_ocup_ativo,
        ]);

        return $tipo;
    }
}
