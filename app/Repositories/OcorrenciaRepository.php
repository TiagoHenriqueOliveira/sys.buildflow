<?php

namespace App\Repositories;

use App\Models\Ocorrencia;
use App\Repositories\Contracts\CrudRepositoryInterface;

class OcorrenciaRepository implements CrudRepositoryInterface
{
    public function all()
    {
        return Ocorrencia::orderBy('ocor_descricao')->get();
    }

    public function create(array $data)
    {
        return Ocorrencia::create([
            'ocor_descricao' => $data['ocor_descricao'],
            'ocor_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $ocorrencia = Ocorrencia::findOrFail($id);

        $ocorrencia->update([
            'ocor_descricao' => $data['ocor_descricao'],
            'ocor_ativo' => $data['ocor_ativo'] ?? $ocorrencia->ocor_ativo,
        ]);

        return $ocorrencia;
    }
}
