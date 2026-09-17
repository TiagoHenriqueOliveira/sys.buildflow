<?php

namespace App\Repositories;

use App\Models\Segmento;
use App\Repositories\Contracts\CrudRepositoryInterface;

class SegmentoRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return Segmento::orderBy('seg_descricao')->get();
    }

    public function create(array $data)
    {
        return Segmento::create([
            'seg_descricao' => $data['seg_descricao'],
            'seg_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $segmento = Segmento::findOrFail($id);

        $segmento->update([
            'seg_descricao' => $data['seg_descricao'],
            'seg_ativo' => $data['seg_ativo'] ?? $segmento->seg_ativo,
        ]);

        return $segmento;
    }
}