<?php

namespace App\Repositories;

use App\Models\Atendimento;
use App\Repositories\Contracts\CrudRepositoryInterface;

class AtendimentoRepository implements CrudRepositoryInterface
{
    public function all(?int $usuarioId = null)
    {
        return Atendimento::with([
            'natureza.tipoAtendimento',
            'cliente',
            'usuario'
        ])
            ->when($usuarioId, fn($q) => $q->where('atendimentos.aten_usuario_id', $usuarioId))

            ->join('usuarios', 'usuarios.user_id', '=', 'atendimentos.aten_usuario_id')

            ->orderBy('aten_status', 'asc')
            ->orderBy('aten_dt_inicio', 'asc')
            ->orderBy('usuarios.user_nome', 'asc')

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
