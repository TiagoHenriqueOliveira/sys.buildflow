<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipoAtendimentoRequest;
use App\Repositories\TipoAtendimentoRepository;
use Illuminate\Http\Request;

class TiposAtendimentosController extends Controller
{
    public function __construct(
        private TipoAtendimentoRepository $repository
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->repository->all()->map(function ($t) {
                return [
                    'acoes'            => view('tipos_atendimentos.partials.acoes', compact('t'))->render(),
                    'tp_aten_descricao' => e($t->tp_aten_descricao),
                    'tp_aten_ativo'     => (int) $t->tp_aten_ativo,
                    'status'            => $t->tp_aten_ativo ? 'Ativo' : 'Desativado',
                ];
            });

            return response()->json(['data' => $data]);
        }

        return view('tipos_atendimentos.index');
    }

    public function store(TipoAtendimentoRequest $request)
    {
        try {
            $this->repository->create($request->validated());
            return response()->json(['message' => 'Cadastrado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao cadastrar.'], 500);
        }
    }

    public function update(TipoAtendimentoRequest $request, int $id)
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
