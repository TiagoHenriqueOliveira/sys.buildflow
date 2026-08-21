<?php

namespace App\Http\Controllers\Api\Mcl;

use App\Enums\AtendimentoStatus;
use App\Http\Controllers\Controller;
use App\Models\Atendimento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AtendimentosController extends Controller
{
    /**
     * Lista atendimentos do técnico autenticado.
     * Admin (nivel_acesso === 0) vê todos.
     *
     * GET /api/mcl/v1/atendimentos
     * Query params: status (0|1|2|3), search (string)
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $query = Atendimento::query()
            ->with(['natureza.modeloRelatorio', 'cliente', 'usuario'])
            ->orderBy('aten_dt_inicio', 'desc');

        if ($usuario->user_nivel_acesso !== 0) {
            $query->where('aten_usuario_id', $usuario->user_id);
        }

        if ($request->filled('status')) {
            $query->where('aten_status', (int) $request->status);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->whereHas('cliente', fn($c) => $c->where('cli_nome', 'like', "%{$term}%"));
        }

        return response()->json(['data' => $query->get()->map(fn($a) => $this->format($a))]);
    }

    /**
     * Detalhe de um atendimento.
     *
     * GET /api/mcl/v1/atendimentos/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $usuario     = $request->user();
        $atendimento = Atendimento::with(['natureza.modeloRelatorio', 'cliente', 'usuario', 'equipamentos', 'anexos'])->findOrFail($id);

        if ($usuario->user_nivel_acesso !== 0 && $atendimento->aten_usuario_id !== $usuario->user_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json(['data' => $this->format($atendimento, true)]);
    }

    /**
     * Altera o status de um atendimento.
     *
     * PUT /api/mcl/v1/atendimentos/{id}/status
     * Body: { status: 0|1|2|3 }
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'integer', Rule::in([0, 1, 2, 3])],
        ]);

        $usuario     = $request->user();
        $atendimento = Atendimento::findOrFail($id);

        if ($usuario->user_nivel_acesso !== 0 && $atendimento->aten_usuario_id !== $usuario->user_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $atendimento->update(['aten_status' => $request->status]);

        $label = AtendimentoStatus::tryFrom($request->status)?->label() ?? '-';

        return response()->json(['message' => "Status alterado para: {$label}."]);
    }

    private function format(Atendimento $a, bool $detalhes = false): array
    {
        $naturezaDesc = optional($a->natureza)->nat_aten_descricao ?? '';
        $clienteNome  = optional($a->cliente)->cli_nome ?? '';
        $descricao    = implode(' – ', array_filter([$naturezaDesc, $clienteNome]));

        $data = [
            'id'               => $a->aten_id,
            'descricao'        => $descricao,
            'contato'          => $a->aten_contato,
            'responsavel'      => $a->aten_responsavel,
            'telefone'         => $a->aten_telefone,
            'endereco'         => $a->aten_endereco,
            'nr_proposta'      => $a->aten_nr_proposta,
            'entrega_tecnica'  => (bool) $a->aten_entrega_tecnica,
            'status'           => $a->aten_status,
            'status_label'     => AtendimentoStatus::tryFrom($a->aten_status)?->label() ?? '-',
            'dt_inicio'        => $a->aten_dt_inicio?->format('Y-m-d'),
            'dt_fim'           => $a->aten_dt_fim?->format('Y-m-d'),
            'natureza'         => [
                'id'            => optional($a->natureza)->nat_aten_id,
                'descricao'     => optional($a->natureza)->nat_aten_descricao,
                'relatorio_unico' => (int) (optional($a->natureza?->modeloRelatorio)->mod_rel_tp_data ?? 0) === 1,
            ],
            'cliente' => [
                'id'     => optional($a->cliente)->cli_id,
                'nome'   => optional($a->cliente)->cli_nome,
                'cidade' => optional($a->cliente)->cli_cidade,
                'uf'     => optional($a->cliente)->cli_uf,
            ],
            'tecnico' => [
                'id'   => optional($a->usuario)->user_id,
                'nome' => optional($a->usuario)->user_nome,
            ],
        ];

        if ($detalhes) {
            $data['obs_cliente']    = $a->aten_obs_cliente;
            $data['obs_tecnica']    = $a->aten_obs_tecnica;
            $data['obs_manutencao'] = $a->aten_obs_manutencao;
            $data['equipamentos']   = $a->relationLoaded('equipamentos')
                ? $a->equipamentos->map(fn($e) => [
                    'id'       => $e->aten_equip_id,
                    'descricao' => $e->aten_equip_descricao,
                ])->values()
                : [];
            $data['anexos'] = $a->relationLoaded('anexos')
                ? $a->anexos->map(fn($x) => [
                    'id'            => $x->aten_anexo_id,
                    'nome_original' => $x->aten_anexo_nome_original,
                    'url'           => asset('midia/' . $x->aten_anexo_path),
                ])->values()
                : [];
        }

        return $data;
    }
}
