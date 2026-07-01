<?php

namespace App\Repositories;

use App\Models\Atendimento;
use App\Repositories\Contracts\CrudRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class AtendimentoRepository implements CrudRepositoryInterface
{
    /**
     * Retorna um Builder já configurado com joins e eager loading,
     * pronto para ser usado pelo DataTableService.
     */
    public function query(?int $usuarioId = null): Builder
    {
        return Atendimento::with(['natureza', 'cliente', 'usuario'])
            ->join('usuarios', 'usuarios.user_id', '=', 'atendimentos.aten_usuario_id')
            ->leftJoin('clientes', 'clientes.cli_id', '=', 'atendimentos.aten_cliente_id')
            ->leftJoin('naturezas_atendimentos', 'naturezas_atendimentos.nat_aten_id', '=', 'atendimentos.aten_natureza_id')
            ->when($usuarioId, fn($q) => $q->where('atendimentos.aten_usuario_id', $usuarioId))
            ->select('atendimentos.*');
    }

    public function all(?int $usuarioId = null): \Illuminate\Support\Collection
    {
        return $this->query($usuarioId)
            ->orderBy('aten_status', 'asc')
            ->orderBy('aten_dt_inicio', 'asc')
            ->orderBy('usuarios.user_nome', 'asc')
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
