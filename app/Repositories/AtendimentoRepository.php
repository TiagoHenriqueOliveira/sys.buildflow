<?php

namespace App\Repositories;

use App\Models\Atendimento;
use App\Repositories\Contracts\CrudRepositoryInterface;

class AtendimentoRepository implements CrudRepositoryInterface
{
    public function all()
    {
        return Atendimento::with(['tipoAtendimento', 'cliente', 'usuario'])
            ->orderBy('aten_dt_inicio', 'desc')
            ->get();
    }

    public function create(array $data)
    {
        return Atendimento::create($data);
    }

    public function update(int $id, array $data)
    {
        $aten = Atendimento::findOrFail($id);
        $aten->update($data);
        return $aten;
    }
}
