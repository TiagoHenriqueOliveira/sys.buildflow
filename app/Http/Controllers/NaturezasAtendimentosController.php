<?php

namespace App\Http\Controllers;

use App\Http\Requests\NaturezaAtendimentoRequest;
use App\Repositories\NaturezaAtendimentoRepository;
use Illuminate\Http\Request;

class NaturezasAtendimentosController extends Controller
{
    public function __construct(
        private NaturezaAtendimentoRepository $repository
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->repository->all()->map(function ($n) {
                return [
                    'acoes' => view('naturezas_atendimentos.partials.acoes', compact('n'))->render(),
                    'nat_aten_descricao' => e($n->nat_aten_descricao),
                    'nat_aten_ativo' => (int) $n->nat_aten_ativo,
                    'status' => $n->nat_aten_ativo ? 'Ativo' : 'Desativado',
                ];
            });

            return response()->json(['data' => $data]);
        }

        return view('naturezas_atendimentos.index');
    }

    public function store(NaturezaAtendimentoRequest $request)
    {
        try {
            $this->repository->create($request->validated());
            return response()->json(['message' => 'Cadastrado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao cadastrar.'], 500);
        }
    }

    public function update(NaturezaAtendimentoRequest $request, int $id)
    {
        try {
            $this->repository->update($id, $request->validated());
            return response()->json(['message' => 'Atualizado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao atualizar.'], 500);
        }
    }
}
