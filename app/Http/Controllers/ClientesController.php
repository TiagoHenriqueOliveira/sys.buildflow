<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Repositories\ClienteRepository;
use App\Services\DataTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientesController extends Controller
{
    public function __construct(
        private ClienteRepository $repository,
        private DataTableService $dataTable,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return response()->json(
                $this->dataTable->process(
                    $request,
                    Cliente::query(),
                    searchable: ['cli_nome', 'cli_cnpj', 'cli_cidade', 'cli_email'],
                    orderable:  [
                        'acoes'       => null,
                        'cli_nome'    => 'cli_nome',
                        'cli_cnpj'    => 'cli_cnpj',
                        'cli_cidade'  => 'cli_cidade',
                        'cli_uf'      => 'cli_uf',
                        'cli_telefone'=> 'cli_telefone',
                        'cli_email'   => 'cli_email',
                        'status'      => 'cli_ativo',
                    ],
                    mapper: fn($c) => [
                        'acoes'       => view('clientes.partials.acoes', compact('c'))->render(),
                        'cli_nome'    => e($c->cli_nome),
                        'cli_cnpj'   => e($c->cli_cnpj),
                        'cli_cidade'  => e($c->cli_cidade),
                        'cli_uf'      => e($c->cli_uf),
                        'cli_telefone'=> e($c->cli_telefone),
                        'cli_email'   => e($c->cli_email),
                        'cli_ativo'   => (int) $c->cli_ativo,
                        'status'      => $c->cli_ativo ? 'Ativo' : 'Desativado',
                    ],
                )
            );
        }

        return view('clientes.index');
    }

    public function store(ClienteRequest $request)
    {
        try {
            $this->repository->create($request->validated());
            return response()->json(['message' => 'Cadastrado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao cadastrar.'], 500);
        }
    }

    public function update(ClienteRequest $request, int $id)
    {
        try {
            $this->repository->update($id, $request->validated());
            return response()->json(['message' => 'Atualizado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao atualizar.'], 500);
        }
    }

    public function autoComplete(Request $request): JsonResponse
    {
        $term = $request->get('term', '');

        $clientes = Cliente::where('cli_ativo', 1)
            ->where('cli_nome', 'like', '%' . $term . '%')
            ->orderBy('cli_nome')
            ->limit(20)
            ->get();

        $result = $clientes->map(function ($c) {
            return [
                'id'    => $c->cli_id,
                'label' => $c->cli_nome,
                'value' => $c->cli_nome,
            ];
        })->values()->all();

        return response()->json($result);
    }
}
