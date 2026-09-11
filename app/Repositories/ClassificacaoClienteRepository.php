<?php

namespace App\Repositories;

use App\Models\ClassificacaoCliente;
use App\Repositories\Contracts\CrudRepositoryInterface;

class ClassificacaoClienteRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return ClassificacaoCliente::orderBy('cla_cli_nome')->get();
    }

    public function create(array $data)
    {
        return ClassificacaoCliente::create([
            'cla_cli_nome' => $data['cla_cli_nome'],
            'cla_cli_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $classificacao = ClassificacaoCliente::findOrFail($id);

        $classificacao->update([
            'cla_cli_nome' => $data['cla_cli_nome'],
            'cla_cli_ativo' => $data['cla_cli_ativo'] ?? $classificacao->cla_cli_ativo,
        ]);

        return $classificacao;
    }
}