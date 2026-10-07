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

    private function flags(array $data, array $defaults = []): array
    {
        $fields = [
            'mod_rel_descricao_secao',
            'mod_rel_servicos_prestados', 'mod_rel_pecas_substituidas',
            'mod_rel_informacoes_adicionais', 'mod_rel_horarios',
            'mod_rel_cond_clima', 'mod_rel_ocorrencia',
        ];

        $result = [];
        foreach ($fields as $f) {
            $result[$f] = isset($data[$f]) ? (int) $data[$f] : ($defaults[$f] ?? 0);
        }
        return $result;
    }

    public function create(array $data)
    {
        return ModeloRelatorio::create(array_merge([
            'mod_rel_descricao' => $data['mod_rel_descricao'],
            'mod_rel_tp_data'   => $data['mod_rel_tp_data'],
            'mod_rel_ativo'     => 1,
        ], $this->flags($data, ['mod_rel_horarios' => 1, 'mod_rel_cond_clima' => 1, 'mod_rel_ocorrencia' => 1])));
    }

    public function update(int $id, array $data)
    {
        $modelo = ModeloRelatorio::findOrFail($id);

        $modelo->update(array_merge([
            'mod_rel_descricao' => $data['mod_rel_descricao'],
            'mod_rel_tp_data'   => $data['mod_rel_tp_data'],
            'mod_rel_ativo'     => $data['mod_rel_ativo'] ?? $modelo->mod_rel_ativo,
        ], $this->flags($data)));

        return $modelo;
    }
}
