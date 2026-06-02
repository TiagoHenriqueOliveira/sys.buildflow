<?php

namespace App\Http\Controllers;

use App\Http\Requests\OcorrenciaRequest;
use App\Models\Ocorrencia;
use App\Repositories\OcorrenciaRepository;
use Illuminate\Http\Request;

class OcorrenciasController extends Controller
{
    public function __construct(
        private OcorrenciaRepository $repository
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->repository->all()->map(function ($o) {
                return [
                    'acoes' => view('ocorrencias.partials.acoes', compact('o'))->render(),
                    'ocor_descricao' => e($o->ocor_descricao),
                    'ocor_ativo' => (int) $o->ocor_ativo,
                    'status' => $o->ocor_ativo ? 'Ativo' : 'Desativado',
                ];
            });

            return response()->json(['data' => $data]);
        }

        return view('ocorrencias.index');
    }

    public function store(OcorrenciaRequest $request)
    {
        try {
            $this->repository->create($request->validated());
            return response()->json(['message' => 'Cadastrado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao cadastrar.'], 500);
        }
    }

    public function update(OcorrenciaRequest $request, int $id)
    {
        try {
            $this->repository->update($id, $request->validated());
            return response()->json(['message' => 'Atualizado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao atualizar.'], 500);
        }
    }

    public function autoComplete(Request $request)
    {
        $term = trim((string) $request->get('term', ''));

        if (mb_strlen($term) < 3) {
            return response()->json([]);
        }

        $rows = Ocorrencia::query()
            ->where('ocor_ativo', 1)
            ->where('ocor_descricao', 'like', "%{$term}%")
            ->orderBy('ocor_descricao')
            ->limit(20)
            ->get();

        $payload = $rows->map(function ($o) {
            return [
                'id'    => $o->ocor_id,
                'label' => $o->ocor_descricao,
                'value' => $o->ocor_descricao,
            ];
        });

        return response()->json($payload);
    }
}
