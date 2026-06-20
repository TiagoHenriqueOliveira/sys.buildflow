<?php

namespace App\Repositories;

use App\Models\Cliente;
use App\Repositories\Contracts\CrudRepositoryInterface;

class ClienteRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return Cliente::orderBy('cli_nome', 'asc')->get();
    }

    public function create(array $data)
    {
        return Cliente::create([
            'cli_nome' => $data['cli_nome'],
            'cli_cnpj' => $data['cli_cnpj'],
            'cli_cidade' => $data['cli_cidade'],
            'cli_uf' => strtoupper($data['cli_uf']),
            'cli_telefone' => $data['cli_telefone'] ?? null,
            'cli_email' => $data['cli_email'] ?? null,
            'cli_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $cliente = Cliente::findOrFail($id);

        $cliente->update([
            'cli_nome' => $data['cli_nome'],
            'cli_cnpj' => $data['cli_cnpj'],
            'cli_cidade' => $data['cli_cidade'],
            'cli_uf' => strtoupper($data['cli_uf']),
            'cli_telefone' => $data['cli_telefone'] ?? null,
            'cli_email' => $data['cli_email'] ?? null,
            'cli_ativo' => $data['cli_ativo'] ?? $cliente->cli_ativo,
        ]);

        return $cliente;
    }
}
