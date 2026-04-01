<?php

namespace App\Repositories;

use App\Models\Atendimento;
use App\Repositories\Contracts\CrudRepositoryInterface;

class AtendimentoRepository implements CrudRepositoryInterface
{
    public function all()
    {
        return Atendimento::with([
            'natureza.tipoAtendimento',
            'cliente',
            'usuario'
        ])
            ->orderBy('aten_dt_inicio', 'asc')

            ->join('usuarios', 'usuarios.user_id', '=', 'atendimentos.aten_usuario_id')
            ->orderBy('usuarios.user_nome', 'asc')

            ->orderBy('aten_status', 'asc')

            ->select('atendimentos.*')

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
