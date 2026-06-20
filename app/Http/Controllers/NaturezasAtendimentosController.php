<?php

namespace App\Http\Controllers;

use App\Http\Requests\NaturezaAtendimentoRequest;
use App\Models\ModeloRelatorio;
use App\Models\NaturezaAtendimento;
use App\Models\TipoAtendimento;
use App\Repositories\NaturezaAtendimentoRepository;
use App\Services\DataTableService;
use Illuminate\Http\Request;

class NaturezasAtendimentosController extends Controller
{
    public function __construct(
        private NaturezaAtendimentoRepository $repository,
        private DataTableService $dataTable,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return response()->json(
                $this->dataTable->process(
                    $request,
                    NaturezaAtendimento::query()->with(['tipoAtendimento', 'modeloRelatorio']),
                    searchable: ['nat_aten_descricao'],
                    orderable:  [
                        'acoes'               => null,
                        'nat_aten_descricao'  => 'nat_aten_descricao',
                        'tp_aten_descricao'   => null,
                        'mod_rel_descricao'   => null,
                        'status'              => 'nat_aten_ativo',
                    ],
                    mapper: fn($n) => [
                        'acoes'                      => view('naturezas_atendimentos.partials.acoes', compact('n'))->render(),
                        'nat_aten_descricao'         => e($n->nat_aten_descricao),
                        'tp_aten_descricao'          => e(optional($n->tipoAtendimento)->tp_aten_descricao),
                        'nat_aten_tp_atendimento_id' => (int) $n->nat_aten_tp_atendimento_id,
                        'mod_rel_descricao'          => e(optional($n->modeloRelatorio)->mod_rel_descricao),
                        'nat_aten_mod_relatorio_id'  => (int) $n->nat_aten_mod_relatorio_id,
                        'nat_aten_ativo'             => (int) $n->nat_aten_ativo,
                        'status'                     => $n->nat_aten_ativo ? 'Ativo' : 'Desativado',
                    ],
                )
            );
        }

        $modelosRelatorios = ModeloRelatorio::where('mod_rel_ativo', 1)
            ->orderBy('mod_rel_descricao')
            ->get();

        $tiposAtivos = TipoAtendimento::select('tp_aten_id', 'tp_aten_descricao')
            ->where('tp_aten_ativo', 1)
            ->orderBy('tp_aten_descricao')
            ->get();

        return view('naturezas_atendimentos.index', compact('modelosRelatorios', 'tiposAtivos'));
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
