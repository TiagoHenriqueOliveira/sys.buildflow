<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipoOcupacaoRequest;
use App\Repositories\TipoOcupacaoRepository;
use Illuminate\Http\Request;

class TiposOcupacoesController extends Controller
{
    public function __construct(
        private TipoOcupacaoRepository $repository
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->repository->all()->map(function ($t) {
                return [
                    'acoes' => view('tipos_ocupacoes.partials.acoes', compact('t'))->render(),
                    'tp_ocup_descricao' => e($t->tp_ocup_descricao),
                    'tp_ocup_ativo' => (int) $t->tp_ocup_ativo,
                    'status' => $t->tp_ocup_ativo ? 'Ativo' : 'Desativado',
                ];
            });

            return response()->json(['data' => $data]);
        }

        return view('tipos_ocupacoes.index');
    }

    public function store(TipoOcupacaoRequest $request)
    {
        try {
            $this->repository->create($request->validated());
            return response()->json(['message' => 'Cadastrado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao cadastrar.'], 500);
        }
    }

    public function update(TipoOcupacaoRequest $request, int $id)
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
