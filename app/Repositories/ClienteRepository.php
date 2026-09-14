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
            $this->sincronizarEquipamentos($cliente, $data['equipamentos'] ?? []);
            $this->sincronizarLocalizacoes($cliente, $data['localizacoes'] ?? []);

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
            $this->sincronizarEquipamentos($cliente, $data['equipamentos'] ?? []);
            $this->sincronizarLocalizacoes($cliente, $data['localizacoes'] ?? []);

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
            // CRM07 (mapa de relacoes) le este campo como resumo em texto —
            // mantido sincronizado a partir da lista de equipamentos da aba
            // Historico em vez de um input de texto proprio (ver
            // sincronizarEquipamentos), pra nao precisar tocar no popup do mapa.
            'cli_equipamento_vendido' => $this->resumoEquipamentos($data['equipamentos'] ?? []),
            'cli_caso_sucesso' => $data['cli_caso_sucesso'] ?? false,
            'cli_caso_sucesso_descricao' => $data['cli_caso_sucesso_descricao'] ?? null,
            'cli_classificacao_id' => $data['cli_classificacao_id'] ?? null,
            'cli_dias_alerta_recontato' => $data['cli_dias_alerta_recontato'] ?? null,
            'cli_telefone' => $data['cli_telefone'] ?? null,
            'cli_email' => $data['cli_email'] ?? null,
            'cli_latitude' => $data['cli_latitude'] ?? null,
            'cli_longitude' => $data['cli_longitude'] ?? null,
            'cli_link_mapa' => $data['cli_link_mapa'] ?? null,
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

    /** Mesmo padrão de sincronizarContatos — substitui a lista inteira a cada save. */
    private function sincronizarEquipamentos(Cliente $cliente, array $equipamentos): void
    {
        $cliente->equipamentos()->delete();

        $linhas = collect($equipamentos)
            ->filter(fn ($e) => filled($e['descricao'] ?? null))
            ->map(fn ($e) => [
                'cli_equip_cliente_id' => $cliente->cli_id,
                'cli_equip_descricao' => $e['descricao'],
            ])
            ->all();

        if ($linhas !== []) {
            $cliente->equipamentos()->insert($linhas);
        }
    }

    /**
     * Mesmo padrão de sincronizarContatos — substitui a lista inteira a
     * cada save. Pedido do cliente (2026-09-14): localização definida só
     * por descrição + link do Google Maps colado — coordenadas não são
     * mais coletadas no formulário (removido o "Escolher no mapa"), por
     * isso só a descrição é exigida aqui.
     */
    private function sincronizarLocalizacoes(Cliente $cliente, array $localizacoes): void
    {
        $cliente->localizacoes()->delete();

        $linhas = collect($localizacoes)
            ->filter(fn ($l) => filled($l['descricao'] ?? null))
            ->map(fn ($l) => [
                'cli_loc_cliente_id' => $cliente->cli_id,
                'cli_loc_descricao' => $l['descricao'],
                'cli_loc_latitude' => $l['latitude'] ?? null,
                'cli_loc_longitude' => $l['longitude'] ?? null,
                'cli_loc_link_mapa' => $l['link_mapa'] ?? null,
            ])
            ->all();

        if ($linhas !== []) {
            $cliente->localizacoes()->insert($linhas);
        }
    }

    private function resumoEquipamentos(array $equipamentos): ?string
    {
        $descricoes = collect($equipamentos)
            ->pluck('descricao')
            ->filter(fn ($d) => filled($d))
            ->implode(', ');

        return $descricoes !== '' ? $descricoes : null;
    }
}
