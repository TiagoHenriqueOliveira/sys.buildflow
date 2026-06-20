<?php

namespace App\Http\Controllers;

use App\Enums\AtendimentoStatus;
use App\Http\Requests\AtendimentoEquipamentoRequest;
use App\Http\Requests\AtendimentoRequest;
use App\Models\NaturezaAtendimento;
use App\Models\TipoAtendimento;
use App\Models\Usuario;
use App\Repositories\AtendimentoRepository;
use App\Repositories\AtendimentoEquipamentoRepository;
use App\Services\DataTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AtendimentosController extends Controller
{
    public function __construct(
        private AtendimentoRepository $repository,
        private AtendimentoEquipamentoRepository $equipamentoRepository,
        private DataTableService $dataTable,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $usuario = Auth::user();
            $filtroUsuarioId = $usuario->user_nivel_acesso === 1 ? $usuario->user_id : null;

            return response()->json(
                $this->dataTable->process(
                    $request,
                    $this->repository->query($filtroUsuarioId),
                    searchable: ['aten_descricao'],
                    orderable:  [
                        'acoes'    => null,
                        'tipo'     => null,
                        'natureza' => null,
                        'usuario'  => 'usuarios.user_nome',
                        'cliente'  => null,
                        'obra'     => 'aten_descricao',
                        'periodo'  => 'aten_dt_inicio',
                        'status'   => 'aten_status',
                    ],
                    mapper: fn($a) => [
                        'acoes'    => view('atendimentos.partials.acoes', compact('a'))->render(),
                        'tipo'     => e(optional($a->natureza?->tipoAtendimento)->tp_aten_descricao),
                        'natureza' => e(optional($a->natureza)->nat_aten_descricao),
                        'usuario'  => e(optional($a->usuario)->user_nome),
                        'cliente'  => e(optional($a->cliente)->cli_nome),
                        'obra'     => e($a->aten_descricao),
                        'periodo'  => $a->aten_dt_inicio->format('d/m/Y') . ' - ' . $a->aten_dt_fim->format('d/m/Y'),
                        'status'   => AtendimentoStatus::tryFrom($a->aten_status)?->label() ?? 'Desconhecido',
                    ],
                )
            );
        }

        return view('atendimentos.index', [
            'tiposAtendimentos' => TipoAtendimento::where('tp_aten_ativo', 1)->orderBy('tp_aten_descricao')->get(),
            'usuarios' => Usuario::where('user_nivel_acesso', 1)->where('user_ativo', 1)->orderBy('user_nome')->get(),
            'naturezasAtendimentos' => NaturezaAtendimento::select('nat_aten_id', 'nat_aten_descricao', 'nat_aten_tp_atendimento_id')
                ->where('nat_aten_ativo', 1)
                ->orderBy('nat_aten_descricao')
                ->get(),
        ]);
    }

    public function store(AtendimentoRequest $request)
    {
        try {
            $this->repository->create($request->validated());
            return response()->json(['message' => 'Atendimento cadastrado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao cadastrar atendimento.'], 500);
        }
    }

    public function update(AtendimentoRequest $request, int $id)
    {
        try {
            $this->repository->update($id, $request->validated());
            return response()->json(['message' => 'Atendimento atualizado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao atualizar atendimento.'], 500);
        }
    }

    public function naturezasPorTipo(Request $request): JsonResponse
    {
        $tipoId = (int) $request->get('tipo_id');

        if (!$tipoId) {
            return response()->json([]);
        }

        $naturezas = NaturezaAtendimento::where('nat_aten_tp_atendimento_id', $tipoId)
            ->where('nat_aten_ativo', 1)
            ->orderBy('nat_aten_descricao')
            ->get();

        $result = $naturezas->map(fn($n) => [
            'id'    => $n->nat_aten_id,
            'label' => $n->nat_aten_descricao,
        ])->values()->all();

        return response()->json($result);
    }

    public function storeEquipamento(AtendimentoEquipamentoRequest $request, int $id)
    {

        try {
            $this->equipamentoRepository->create([
                'aten_equip_atendimento_id' => $id,
                'aten_equip_descricao' => $request->input('aten_equip_descricao'),
                'aten_equip_observacoes' => $request->input('aten_equip_observacoes'),
            ]);

            $equipamentos = $this->equipamentoRepository->findByAtendimento($id);

            return response()->json([
                'message' => 'Equipamento adicionado com sucesso!',
                'equipamentos' => $equipamentos,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao adicionar equipamento.'], 500);
        }
    }

    public function destroyEquipamento(int $id, int $equipId)
    {
        try {
            $this->equipamentoRepository->delete($equipId);

            $equipamentos = $this->equipamentoRepository->findByAtendimento($id);

            return response()->json([
                'message' => 'Equipamento removido com sucesso!',
                'equipamentos' => $equipamentos,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao remover equipamento.'], 500);
        }
    }

    public function getEquipamentos(int $id): JsonResponse
    {
        try {
            $equipamentos = $this->equipamentoRepository->findByAtendimento($id);

            return response()->json([
                'equipamentos' => $equipamentos,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao carregar equipamentos.'], 500);
        }
    }
}
