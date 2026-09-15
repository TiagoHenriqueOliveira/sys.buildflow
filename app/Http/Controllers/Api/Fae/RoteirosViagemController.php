<?php

namespace App\Http\Controllers\Api\Fae;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoteiroViagemRequest;
use App\Models\RoteiroViagem;
use App\Repositories\RoteiroViagemRepository;
use Illuminate\Http\JsonResponse;

/**
 * CRM05/06/09 (mobile) - Roteiro de Viagem. Reaproveita
 * RoteiroViagemRequest/RoteiroViagemRepository (mesma validacao/regra do
 * web). Link do mapa colado a mao, sem picker/mapa embutido - mesmo padrao
 * de Cliente (BF_v1.8.2).
 */
class RoteirosViagemController extends Controller
{
    public function __construct(private RoteiroViagemRepository $repository)
    {
    }

    public function index(): JsonResponse
    {
        $roteiros = RoteiroViagem::with('vendedor')->orderByDesc('crm_rot_id')->paginate(20);

        return response()->json([
            'data' => collect($roteiros->items())->map(fn ($r) => $this->formatResumo($r))->values(),
            'current_page' => $roteiros->currentPage(),
            'last_page' => $roteiros->lastPage(),
            'total' => $roteiros->total(),
        ]);
    }

    public function show(int $roteiro): JsonResponse
    {
        $registro = RoteiroViagem::with(['vendedor', 'clientes.cliente'])->findOrFail($roteiro);

        return response()->json(['data' => $this->formatDetalhe($registro)]);
    }

    public function store(RoteiroViagemRequest $request): JsonResponse
    {
        $roteiro = $this->repository->create($request->validated());
        $roteiro->load(['vendedor', 'clientes.cliente']);

        return response()->json(['data' => $this->formatDetalhe($roteiro)], 201);
    }

    public function update(RoteiroViagemRequest $request, int $roteiro): JsonResponse
    {
        $registro = $this->repository->update($roteiro, $request->validated());
        $registro->load(['vendedor', 'clientes.cliente']);

        return response()->json(['data' => $this->formatDetalhe($registro)]);
    }

    private function formatResumo(RoteiroViagem $r): array
    {
        return [
            'id' => $r->crm_rot_id,
            'vendedor' => ['id' => optional($r->vendedor)->user_id, 'nome' => optional($r->vendedor)->user_nome],
            'periodo_inicio' => $r->crm_rot_periodo_inicio,
            'periodo_fim' => $r->crm_rot_periodo_fim,
            'status' => $r->crm_rot_status?->value,
            'status_label' => $r->crm_rot_status?->label(),
        ];
    }

    private function formatDetalhe(RoteiroViagem $r): array
    {
        return [
            ...$this->formatResumo($r),
            'vendedor_id' => $r->crm_rot_vendedor_id,
            'link_mapa' => $r->crm_rot_link_mapa,
            'clientes' => $r->clientes->map(fn ($c) => [
                'id' => $c->crm_rot_cli_id,
                'cliente_id' => $c->crm_rot_cli_cliente_id,
                'cliente_nome' => optional($c->cliente)->cli_nome,
                'ordem' => $c->crm_rot_cli_ordem,
                'resultado' => $c->crm_rot_cli_resultado?->value,
                'resultado_label' => $c->crm_rot_cli_resultado?->label(),
                'observacao' => $c->crm_rot_cli_observacao,
            ])->values(),
        ];
    }
}