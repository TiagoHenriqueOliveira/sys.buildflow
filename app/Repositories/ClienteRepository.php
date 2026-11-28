<?php

namespace App\Repositories;

use App\Models\Cliente;
use App\Models\ClienteContato;
use App\Repositories\Contracts\CrudRepositoryInterface;
use App\Support\CoordenadasMapa;
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
            ...array_combine(
                ['cli_link_mapa', 'cli_latitude', 'cli_longitude'],
                $this->localizacao($data['cli_link_mapa'] ?? null, $data['cli_latitude'] ?? null, $data['cli_longitude'] ?? null),
            ),
        ];
    }

    /**
     * Desde 2026-11-17 o link do Google Maps é a fonte da localização (a tela
     * não tem campo de latitude/longitude desde 2026-09-14): as coordenadas
     * que os mapas usam são derivadas do link a cada save — antes ficavam
     * vazias para quem só colava o link, e o cliente sumia do Mapa de
     * Demandas/Relações. Sem link mas com coordenadas (ex.: "usar minha
     * localização" no app), gera o link a partir delas. Link sem coordenadas
     * reconhecíveis mantém as coordenadas enviadas, se houver.
     *
     * @return array{0: ?string, 1: ?float, 2: ?float} [link, lat, lng]
     */
    private function localizacao(?string $link, mixed $lat, mixed $lng): array
    {
        $link = filled($link) ? trim($link) : null;
        $lat = is_numeric($lat) ? round((float) $lat, 7) : null;
        $lng = is_numeric($lng) ? round((float) $lng, 7) : null;

        if ($link !== null) {
            $c = CoordenadasMapa::doLink($link);

            return $c !== null ? [$link, $c['lat'], $c['lng']] : [$link, $lat, $lng];
        }

        if ($lat !== null && $lng !== null) {
            return [CoordenadasMapa::linkDasCoordenadas($lat, $lng), $lat, $lng];
        }

        return [null, null, null];
    }

    /**
     * Auditoria 27/11/2026 (S-03): sincronização por id em vez de "apaga e
     * recria". Entre os contatos que quem salva pode ver: com id → atualiza
     * (mantém o autor), sem id → cria com o autor = quem salvou, ausente da
     * lista → remove. Os contatos que ele não vê (Comercial de outro, D-01)
     * ficam intocados — antes, salvar o cliente apagava esses contatos.
     * Quem envia a lista sem ids (app antigo) continua com o efeito de
     * antes sobre os contatos visíveis.
     */
    private function sincronizarContatos(Cliente $cliente, array $contatos): void
    {
        $usuario = auth()->user();
        $existentes = $cliente->contatos()->get()->keyBy('cli_cont_id');
        $visiveis = $usuario
            ? $existentes->filter(fn (ClienteContato $c) => $c->visivelPara($usuario, $cliente))
            : $existentes;
        $mantidos = [];

        foreach (collect($contatos)->filter(fn ($c) => filled($c['nome'] ?? null)) as $c) {
            $dados = [
                'cli_cont_nome' => $c['nome'],
                'cli_cont_cargo' => $c['cargo'] ?? null,
                'cli_cont_telefone' => $c['telefone'] ?? null,
                'cli_cont_email' => $c['email'] ?? null,
                'cli_cont_tipo' => $c['tipo'] ?? 0,
            ];
            $id = isset($c['id']) ? (int) $c['id'] : 0;

            if ($id && $visiveis->has($id) && ! in_array($id, $mantidos, true)) {
                $visiveis[$id]->update($dados);
                $mantidos[] = $id;
            } else {
                $cliente->contatos()->create($dados + ['cli_cont_usuario_id' => $usuario?->user_id]);
            }
        }

        $remover = $visiveis->keys()->diff($mantidos);
        if ($remover->isNotEmpty()) {
            ClienteContato::whereIn('cli_cont_id', $remover->all())->delete();
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
            ->map(function ($l) use ($cliente) {
                [$link, $lat, $lng] = $this->localizacao($l['link_mapa'] ?? null, $l['latitude'] ?? null, $l['longitude'] ?? null);

                return [
                    'cli_loc_cliente_id' => $cliente->cli_id,
                    'cli_loc_descricao' => $l['descricao'],
                    'cli_loc_latitude' => $lat,
                    'cli_loc_longitude' => $lng,
                    'cli_loc_link_mapa' => $link,
                ];
            })
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
