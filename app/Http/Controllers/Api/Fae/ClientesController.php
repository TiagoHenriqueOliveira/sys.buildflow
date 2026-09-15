<?php

namespace App\Http\Controllers\Api\Fae;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Repositories\ClienteRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NC01 (mobile) - CRUD de clientes consumido pelo app. Reaproveita
 * ClienteRequest/ClienteRepository (mesma validacao/regra do web,
 * ver app/Http/Controllers/ClientesController.php).
 */
class ClientesController extends Controller
{
    public function __construct(private ClienteRepository $repository)
    {
    }

    /**
     * GET /api/fae/v1/clientes
     * Query params: search (string)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Cliente::query()->with('classificacao')->orderBy('cli_nome');

        if ($request->filled('search')) {
            $query->where('cli_nome', 'like', '%' . $request->search . '%');
        }

        $clientes = $query->paginate(20);

        return response()->json([
            'data'         => collect($clientes->items())->map(fn ($c) => $this->formatResumo($c))->values(),
            'current_page' => $clientes->currentPage(),
            'last_page'    => $clientes->lastPage(),
            'total'        => $clientes->total(),
        ]);
    }

    /**
     * GET /api/fae/v1/clientes/{id}
     */
    public function show(int $cliente): JsonResponse
    {
        $registro = Cliente::with(['classificacao', 'contatos', 'equipamentos', 'localizacoes'])->findOrFail($cliente);

        return response()->json(['data' => $this->formatDetalhe($registro)]);
    }

    /**
     * POST /api/fae/v1/clientes
     * Middleware `comercial`: so Comercial/Administrador podem criar.
     */
    public function store(ClienteRequest $request): JsonResponse
    {
        $cliente = $this->repository->create($request->validated());
        $cliente->load(['classificacao', 'contatos', 'equipamentos', 'localizacoes']);

        return response()->json(['data' => $this->formatDetalhe($cliente)], 201);
    }

    /**
     * PUT /api/fae/v1/clientes/{id}
     * Middleware `comercial`: so Comercial/Administrador podem editar.
     */
    public function update(ClienteRequest $request, int $cliente): JsonResponse
    {
        $registro = $this->repository->update($cliente, $request->validated());
        $registro->load(['classificacao', 'contatos', 'equipamentos', 'localizacoes']);

        return response()->json(['data' => $this->formatDetalhe($registro)]);
    }

    /**
     * GET /api/fae/v1/clientes/autocomplete?term=
     * Usado ao vincular cliente a um atendimento novo (qualquer perfil).
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $term = $request->get('term', '');

        $clientes = Cliente::where('cli_ativo', 1)
            ->where('cli_nome', 'like', '%' . $term . '%')
            ->orderBy('cli_nome')
            ->limit(20)
            ->get(['cli_id', 'cli_nome']);

        return response()->json([
            'data' => $clientes->map(fn ($c) => [
                'id'   => $c->cli_id,
                'nome' => $c->cli_nome,
            ])->values(),
        ]);
    }

    private function formatResumo(Cliente $c): array
    {
        return [
            'id'            => $c->cli_id,
            'nome'          => $c->cli_nome,
            'cidade'        => $c->cli_cidade,
            'uf'            => $c->cli_uf,
            'telefone'      => $c->cli_telefone,
            'classificacao' => $c->classificacao?->cla_cli_nome,
            'ativo'         => (bool) $c->cli_ativo,
        ];
    }

    private function formatDetalhe(Cliente $c): array
    {
        return [
            ...$this->formatResumo($c),
            'cnpj'                   => $c->cli_cnpj,
            'inscricao_estadual'     => $c->cli_inscricao_estadual,
            'contato_principal'      => $c->cli_contato_principal,
            'segmento'               => $c->cli_segmento,
            'email'                  => $c->cli_email,
            'classificacao_id'       => $c->cli_classificacao_id,
            'caso_sucesso'           => (bool) $c->cli_caso_sucesso,
            'caso_sucesso_descricao' => $c->cli_caso_sucesso_descricao,
            'latitude'               => $c->cli_latitude,
            'longitude'              => $c->cli_longitude,
            'link_mapa'              => $c->cli_link_mapa,
            'contatos'               => $c->contatos->map(fn ($ct) => [
                'id'        => $ct->cli_cont_id,
                'nome'      => $ct->cli_cont_nome,
                'cargo'     => $ct->cli_cont_cargo,
                'telefone'  => $ct->cli_cont_telefone,
                'email'     => $ct->cli_cont_email,
                'tipo'      => $ct->cli_cont_tipo?->value,
            ])->values(),
            'equipamentos' => $c->equipamentos->map(fn ($e) => [
                'id'       => $e->cli_equip_id,
                'descricao' => $e->cli_equip_descricao,
            ])->values(),
            'localizacoes' => $c->localizacoes->map(fn ($l) => [
                'id'        => $l->cli_loc_id,
                'descricao' => $l->cli_loc_descricao,
                'latitude'  => $l->cli_loc_latitude,
                'longitude' => $l->cli_loc_longitude,
                'link_mapa' => $l->cli_loc_link_mapa,
            ])->values(),
        ];
    }
}