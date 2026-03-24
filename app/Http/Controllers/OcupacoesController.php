<?php

namespace App\Http\Controllers;

use App\Http\Requests\OcupacaoRequest;
use App\Models\TipoOcupacao;
use App\Repositories\OcupacaoRepository;
use Illuminate\Http\Request;

class OcupacoesController extends Controller
{
    public function __construct(
        private OcupacaoRepository $repository
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->repository->all()->map(function ($o) {
                return [
                    'acoes' => view('ocupacoes.partials.acoes', compact('o'))->render(),
                    'tp_ocup_descricao' => e(optional($o->tipoOcupacao)->tp_ocup_descricao),
                    'ocup_descricao' => e($o->ocup_descricao),
                    'ocup_ativo' => (int) $o->ocup_ativo,
                    'status' => $o->ocup_ativo ? 'Ativo' : 'Desativado',
                ];
            });

            return response()->json(['data' => $data]);
        }

        // Select: somente tipos ativos e ordenados por descrição
        $tiposOcupacoesAtivos = TipoOcupacao::select('tp_ocup_id', 'tp_ocup_descricao')
            ->where('tp_ocup_ativo', 1)
            ->orderBy('tp_ocup_descricao')
            ->get();

        return view('ocupacoes.index', compact('tiposOcupacoesAtivos'));
    }

    public function store(OcupacaoRequest $request)
    {
        try {
            $this->repository->create($request->validated());
            return response()->json(['message' => 'Cadastrado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao cadastrar.'], 500);
        }
    }

    public function update(OcupacaoRequest $request, int $id)
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
