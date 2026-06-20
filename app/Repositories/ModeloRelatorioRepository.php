<?php

namespace App\Repositories;

use App\Models\ModeloRelatorio;
use App\Repositories\Contracts\CrudRepositoryInterface;

class ModeloRelatorioRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return ModeloRelatorio::orderBy('mod_rel_descricao', 'asc')->get();
    }

    public function create(array $data)
    {
        return ModeloRelatorio::create([
            'mod_rel_descricao' => $data['mod_rel_descricao'],
            'mod_rel_tp_data'   => $data['mod_rel_tp_data'],
            'mod_rel_ativo'     => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $modelo = ModeloRelatorio::findOrFail($id);

        $modelo->update([
            'mod_rel_descricao' => $data['mod_rel_descricao'],
            'mod_rel_tp_data'   => $data['mod_rel_tp_data'],
            'mod_rel_ativo'     => $data['mod_rel_ativo'] ?? $modelo->mod_rel_ativo,
        ]);

        return $modelo;
    }
}
