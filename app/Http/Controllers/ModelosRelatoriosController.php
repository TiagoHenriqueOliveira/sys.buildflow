<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModeloRelatorioRequest;
use App\Models\ModeloRelatorio;
use App\Repositories\ModeloRelatorioRepository;
use App\Services\DataTableService;
use Illuminate\Http\Request;

class ModelosRelatoriosController extends Controller
{
    public function __construct(
        private ModeloRelatorioRepository $repository,
        private DataTableService $dataTable,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return response()->json(
                $this->dataTable->process(
                    $request,
                    ModeloRelatorio::query(),
                    searchable: ['mod_rel_descricao'],
                    orderable:  ['acoes' => null, 'mod_rel_descricao' => 'mod_rel_descricao', 'tipo_data' => 'mod_rel_tp_data', 'status' => 'mod_rel_ativo'],
                    mapper: fn($m) => [
                        'acoes'                          => view('modelos_relatorios.partials.acoes', compact('m'))->render(),
                        'mod_rel_descricao'              => e($m->mod_rel_descricao),
                        'mod_rel_tp_data'                => (int) $m->mod_rel_tp_data,
                        'tipo_data'                      => ((int) $m->mod_rel_tp_data === 0) ? 'Relatório Diário' : 'Relatório Período',
                        'mod_rel_ativo'                  => (int) $m->mod_rel_ativo,
                        'status'                         => $m->mod_rel_ativo ? 'Ativo' : 'Desativado',
                    ],
                )
            );
        }

        return view('modelos_relatorios.index');
    }

    public function store(ModeloRelatorioRequest $request)
    {
        try {
            $this->repository->create($request->validated());
            return response()->json(['message' => 'Cadastrado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao cadastrar.'], 500);
        }
    }

    public function update(ModeloRelatorioRequest $request, int $id)
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
