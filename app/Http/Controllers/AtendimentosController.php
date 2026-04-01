<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtendimentoRequest;
use App\Models\Cliente;
use App\Models\NaturezaAtendimento;
use App\Models\TipoAtendimento;
use App\Models\Usuario;
use App\Repositories\AtendimentoRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AtendimentosController extends Controller
{
    public function __construct(
        private AtendimentoRepository $repository
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->repository->all()->map(function ($a) {
                return [
                    'acoes'    => view('atendimentos.partials.acoes', compact('a'))->render(),
                    'tipo'     => e(optional($a->natureza?->tipoAtendimento)->tp_aten_descricao),
                    'natureza' => e(optional($a->natureza)->nat_aten_descricao),
                    'usuario'  => e(optional($a->usuario)->user_nome),
                    'cliente'  => e(optional($a->cliente)->cli_nome),
                    'obra'     => e($a->aten_descricao),
                    'periodo'  => $a->aten_dt_inicio->format('d/m/Y') . ' - ' . $a->aten_dt_fim->format('d/m/Y'),
                    'status'   => match ($a->aten_status) {
                        0 => 'Não iniciada',
                        1 => 'Paralisada',
                        2 => 'Em andamento',
                        3 => 'Concluída',
                    },
                ];
            });

            return response()->json(['data' => $data]);
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

    public function autoComplete(Request $request): JsonResponse
    {
        $term = $request->get('term', '');

        $clientes = Cliente::where('cli_ativo', 1)
            ->where('cli_nome', 'like', '%' . $term . '%')
            ->orderBy('cli_nome')
            ->limit(20)
            ->get();

        $result = $clientes->map(function ($c) {
            return [
                'id'    => $c->cli_id,
                'label' => $c->cli_nome,
                'value' => $c->cli_nome,
            ];
        })->values()->all();

        return response()->json($result);
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
}
