<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atendimento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AtendimentosController extends Controller
{
    /**
     * Lista atendimentos do técnico autenticado.
     * Administradores (nivel_acesso != 1) veem todos.
     *
     * GET /api/v1/atendimentos
     * Query params opcionais:
     *   status  = 0|1|2|3
     *   search  = string (busca em aten_descricao e nome do cliente)
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $query = Atendimento::query()
            ->with(['natureza.tipoAtendimento', 'cliente', 'usuario'])
            ->orderBy('aten_dt_inicio', 'desc');

        // Técnico só vê os próprios; admin vê todos
        if ($usuario->user_nivel_acesso === 1) {
            $query->where('aten_usuario_id', $usuario->user_id);
        }

        if ($request->filled('status')) {
            $query->where('aten_status', (int) $request->status);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('aten_descricao', 'like', "%{$term}%")
                  ->orWhereHas('cliente', fn($c) => $c->where('cli_nome', 'like', "%{$term}%"));
            });
        }

        $atendimentos = $query->get()->map(fn($a) => $this->formatAtendimento($a));

        return response()->json(['data' => $atendimentos]);
    }

    /**
     * Detalhe de um atendimento (somente os do técnico).
     *
     * GET /api/v1/atendimentos/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $usuario     = $request->user();
        $atendimento = Atendimento::with([
            'natureza.tipoAtendimento',
            'cliente',
            'usuario',
            'equipamentos',
        ])->findOrFail($id);

        // Garante que técnico acesse só os seus
        if ($usuario->user_nivel_acesso === 1 && $atendimento->aten_usuario_id !== $usuario->user_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json(['data' => $this->formatAtendimento($atendimento, includeEquipamentos: true)]);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function formatAtendimento(Atendimento $a, bool $includeEquipamentos = false): array
    {
        $statusLabel = match ($a->aten_status) {
            0 => 'Não iniciada',
            1 => 'Paralisada',
            2 => 'Em andamento',
            3 => 'Concluída',
            default => 'Desconhecido',
        };

        $data = [
            'id'          => $a->aten_id,
            'descricao'   => $a->aten_descricao,
            'responsavel' => $a->aten_responsavel,
            'endereco'    => $a->aten_endereco,
            'nr_proposta' => $a->aten_nr_proposta,
            'status'      => $a->aten_status,
            'status_label'=> $statusLabel,
            'dt_inicio'   => $a->aten_dt_inicio?->format('Y-m-d'),
            'dt_fim'      => $a->aten_dt_fim?->format('Y-m-d'),
            'natureza'    => [
                'id'        => optional($a->natureza)->nat_aten_id,
                'descricao' => optional($a->natureza)->nat_aten_descricao,
            ],
            'setor'       => [
                'id'        => optional($a->natureza?->tipoAtendimento)->tp_aten_id,
                'descricao' => optional($a->natureza?->tipoAtendimento)->tp_aten_descricao,
            ],
            'cliente'     => [
                'id'   => optional($a->cliente)->cli_id,
                'nome' => optional($a->cliente)->cli_nome,
            ],
            'tecnico'     => [
                'id'   => optional($a->usuario)->user_id,
                'nome' => optional($a->usuario)->user_nome,
            ],
        ];

        if ($includeEquipamentos && $a->relationLoaded('equipamentos')) {
            $data['equipamentos'] = $a->equipamentos->map(fn($e) => [
                'id'          => $e->aten_equip_id,
                'descricao'   => $e->aten_equip_descricao,
                'observacoes' => $e->aten_equip_observacoes,
            ])->values();
        }

        return $data;
    }
}
