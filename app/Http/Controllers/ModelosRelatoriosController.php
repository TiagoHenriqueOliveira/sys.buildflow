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
                        'acoes'                     => view('modelos_relatorios.partials.acoes', compact('m'))->render(),
                        'mod_rel_descricao'         => e($m->mod_rel_descricao),
                        'mod_rel_tp_data'           => (int) $m->mod_rel_tp_data,
                        'tipo_data'                 => ((int) $m->mod_rel_tp_data === 0) ? 'Relatório Diário' : 'Relatório Período',
                        'mod_rel_entrega_tecnica'   => (int) $m->mod_rel_entrega_tecnica,
                        'mod_rel_anexo'             => (int) $m->mod_rel_anexo,
                        'mod_rel_atividade'         => (int) $m->mod_rel_atividade,
                        'mod_rel_comentario'        => (int) $m->mod_rel_comentario,
                        'mod_rel_cond_clima'        => (int) $m->mod_rel_cond_clima,
                        'mod_rel_controle_material' => (int) $m->mod_rel_controle_material,
                        'mod_rel_equipamento'       => (int) $m->mod_rel_equipamento,
                        'mod_rel_foto'              => (int) $m->mod_rel_foto,
                        'mod_rel_horarios'          => (int) $m->mod_rel_horarios,
                        'mod_rel_ocorrencia'        => (int) $m->mod_rel_ocorrencia,
                        'mod_rel_ocupacao'          => (int) $m->mod_rel_ocupacao,
                        'mod_rel_video'             => (int) $m->mod_rel_video,
                        'mod_rel_ativo'             => (int) $m->mod_rel_ativo,
                        'status'                    => $m->mod_rel_ativo ? 'Ativo' : 'Desativado',
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
