<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipamentoRequest;
use App\Models\Equipamento;
use App\Repositories\EquipamentoRepository;
use Illuminate\Http\Request;

class EquipamentosController extends Controller
{
    public function __construct(
        private EquipamentoRepository $repository
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->repository->all()->map(function ($e) {
                return [
                    'acoes' => view('equipamentos.partials.acoes', compact('e'))->render(),
                    'equip_descricao' => e($e->equip_descricao),
                    'equip_ativo' => (int) $e->equip_ativo,
                    'status' => $e->equip_ativo ? 'Ativo' : 'Desativado',
                ];
            });

            return response()->json(['data' => $data]);
        }

        return view('equipamentos.index');
    }

    public function store(EquipamentoRequest $request)
    {
        try {
            $this->repository->create($request->validated());
            return response()->json(['message' => 'Cadastrado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao cadastrar.'], 500);
        }
    }

    public function update(EquipamentoRequest $request, int $id)
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

        $rows = Equipamento::query()
            ->where('equip_ativo', 1)
            ->where('equip_descricao', 'like', "%{$term}%")
            ->orderBy('equip_descricao')
            ->limit(20)
            ->get();

        $payload = $rows->map(function ($e) {
            return [
                'id'    => $e->equip_id,
                'label' => $e->equip_descricao,
                'value' => $e->equip_descricao,
            ];
        });

        return response()->json($payload);
    }
}
