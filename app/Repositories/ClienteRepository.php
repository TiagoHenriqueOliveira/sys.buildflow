<?php

namespace App\Repositories;

use App\Models\Cliente;
use App\Repositories\Contracts\CrudRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ClienteRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return Cliente::orderBy('cli_nome', 'asc')->get();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $cliente = Cliente::create([
                ...$this->camposPrincipais($data),
                'cli_ativo' => 1,
            ]);

            $this->sincronizarContatos($cliente, $data['contatos'] ?? []);

            return $cliente;
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $cliente = Cliente::findOrFail($id);

            $cliente->update([
                ...$this->camposPrincipais($data),
                'cli_ativo' => $data['cli_ativo'] ?? $cliente->cli_ativo,
            ]);

            $this->sincronizarContatos($cliente, $data['contatos'] ?? []);

            return $cliente;
        });
    }

    private function camposPrincipais(array $data): array
    {
        return [
            'cli_nome' => $data['cli_nome'],
            'cli_contato_principal' => $data['cli_contato_principal'] ?? null,
            'cli_vendedor_id' => $data['cli_vendedor_id'] ?? null,
            'cli_cnpj' => $data['cli_cnpj'],
            'cli_inscricao_estadual' => $data['cli_inscricao_estadual'] ?? null,
            'cli_cidade' => $data['cli_cidade'],
            'cli_uf' => strtoupper($data['cli_uf']),
            'cli_segmento' => $data['cli_segmento'] ?? null,
            'cli_classificacao_id' => $data['cli_classificacao_id'] ?? null,
            'cli_dias_alerta_recontato' => $data['cli_dias_alerta_recontato'] ?? null,
            'cli_telefone' => $data['cli_telefone'] ?? null,
            'cli_email' => $data['cli_email'] ?? null,
            'cli_latitude' => $data['cli_latitude'] ?? null,
            'cli_longitude' => $data['cli_longitude'] ?? null,
        ];
    }

    /**
     * Etapa 1 (telas): substitui a lista inteira de contatos a cada save —
     * simples o suficiente pro volume esperado (poucos contatos por
     * cliente) e evita ter que casar IDs entre o que veio do form e o que
     * já existe no banco. Se isso virar gargalo/perda de histórico, revisar
     * na sessão de persistência (03).
     */
    private function sincronizarContatos(Cliente $cliente, array $contatos): void
    {
        $cliente->contatos()->delete();

        $linhas = collect($contatos)
            ->filter(fn ($c) => filled($c['nome'] ?? null))
            ->map(fn ($c) => [
                'cli_cont_cliente_id' => $cliente->cli_id,
                'cli_cont_nome' => $c['nome'],
                'cli_cont_cargo' => $c['cargo'] ?? null,
                'cli_cont_telefone' => $c['telefone'] ?? null,
                'cli_cont_email' => $c['email'] ?? null,
                'cli_cont_tipo' => $c['tipo'] ?? 0,
            ])
            ->all();

        if ($linhas !== []) {
            $cliente->contatos()->insert($linhas);
        }
    }
}
