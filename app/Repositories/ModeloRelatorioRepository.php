<?php

namespace App\Repositories;

use App\Models\ModeloRelatorio;
use App\Repositories\Contracts\CrudRepositoryInterface;

class ModeloRelatorioRepository implements CrudRepositoryInterface
{
    public function all()
    {
        return ModeloRelatorio::orderBy('mod_rel_descricao', 'asc')->get();
    }

    public function create(array $data)
    {
        return ModeloRelatorio::create(array_merge($this->defaults(), $data, [
            'mod_rel_ativo' => 1,
        ]));
    }

    public function update(int $id, array $data)
    {
        $modelo = ModeloRelatorio::findOrFail($id);

        $modelo->update(array_merge($this->defaults(), $data));

        return $modelo;
    }

    private function defaults(): array
    {
        return [
            'mod_rel_anexo' => 0,
            'mod_rel_atividade' => 0,
            'mod_rel_checklist' => 0,
            'mod_rel_comentario' => 0,
            'mod_rel_cond_clima' => 0,
            'mod_rel_controle_material' => 0,
            'mod_rel_entrega_tecnica' => 0,
            'mod_rel_equipamento' => 0,
            'mod_rel_foto' => 0,
            'mod_rel_horarios' => 0,
            'mod_rel_ocorrencia' => 0,
            'mod_rel_ocupacao' => 0,
            'mod_rel_video' => 0,
        ];
    }
}
