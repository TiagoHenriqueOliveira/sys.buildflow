<?php

namespace App\Http\Controllers\Api\Fae;

use App\Enums\AtendimentoStatus;
use App\Enums\AtendimentoRelatorioStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fae\UpdateAtendimentoStatusRequest;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AtendimentosController extends Controller
{
    /**
     * Lista atendimentos do técnico autenticado.
     * Admin (nivel_acesso === 0) vê todos.
     *
     * GET /api/fae/v1/atendimentos
     * Query params: status (0|1|2|3), search (string)
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $query = Atendimento::query()
            ->visivelPara($usuario)
            ->with(['natureza.modeloRelatorio', 'cliente', 'usuario'])
            ->orderBy('aten_dt_inicio', 'desc');

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
     * GET /api/fae/v1/atendimentos/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $usuario     = $request->user();
        $atendimento = Atendimento::with(['natureza.modeloRelatorio', 'cliente', 'usuario', 'equipamentos', 'anexos'])->findOrFail($id);

        if (! $usuario->can('acessar', $atendimento)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json(['data' => $this->format($atendimento, true)]);
    }

    /**
     * Altera o status de um atendimento.
     *
     * PUT /api/fae/v1/atendimentos/{id}/status
     * Body: { status: 0|1|2|3 }
     */
    public function updateStatus(UpdateAtendimentoStatusRequest $request, int $id): JsonResponse
    {
        $usuario     = $request->user();
        $atendimento = Atendimento::findOrFail($id);

        if (! $usuario->can('acessar', $atendimento)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        // Um atendimento "Concluído" precisa refletir um trabalho realmente
        // fechado — sem isto, o app deixava concluir com o relatório ainda
        // faltando assinatura/aprovação, e o técnico via "Concluído" na tela
        // enquanto o relatório continuava "Revisar" para sempre no sistema.
        // Validado aqui (não só no app) para valer também pra web e futuras
        // versões do app que não tenham essa checagem no cliente.
        if ((int) $request->status === AtendimentoStatus::Concluida->value) {
            $relatorios = AtendimentoRelatorio::where('aten_rel_atendimento_id', $atendimento->aten_id)
                ->get(['aten_rel_id', 'aten_rel_status']);
            $naoAprovados = $relatorios->where('aten_rel_status', '!=', AtendimentoRelatorioStatus::Aprovado->value);
            if ($relatorios->isEmpty() || $naoAprovados->isNotEmpty()) {
                return response()->json([
                    'message' => $relatorios->isEmpty()
                        ? 'Não é possível concluir: este atendimento não tem nenhum relatório criado.'
                        : $naoAprovados->count() . ' relatório(s) deste atendimento ainda não foram aprovados.',
                ], 422);
            }
        }

        $atendimento->update(['aten_status' => $request->status]);

        $label = AtendimentoStatus::tryFrom($request->status)?->label() ?? '-';

        return response()->json(['message' => "Status alterado para: {$label}."]);
    }

    /**
     * BF08 - mapa de demandas: atendimentos cujo CLIENTE tem geolocalizacao
     * (o atendimento em si nao tem lat/lng proprio, acontece no endereco do
     * cliente - mesma logica de MapaDemandasController::index(), web).
     * Mesmo nivel de acesso da listagem normal de atendimentos (nao e
     * exclusivo de Comercial/Administrador no web, entao tambem nao aqui).
     *
     * GET /api/fae/v1/mapa-demandas
     */
    public function mapaDemandas(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $atendimentos = Atendimento::query()
            ->visivelPara($usuario)
            ->with(['cliente', 'natureza', 'usuario'])
            ->whereHas('cliente', fn ($q) => $q->whereNotNull('cli_latitude')->whereNotNull('cli_longitude'))
            ->orderByDesc('aten_id')
            ->get();

        return response()->json([
            'data' => $atendimentos->map(fn ($a) => [
                'id' => $a->aten_id,
                'descricao' => $a->aten_endereco,
                'status' => $a->aten_status,
                'status_label' => AtendimentoStatus::tryFrom($a->aten_status)?->label() ?? '-',
                'natureza' => optional($a->natureza)->nat_aten_descricao,
                'tecnico' => optional($a->usuario)->user_nome,
                'cliente' => [
                    'id' => optional($a->cliente)->cli_id,
                    'nome' => optional($a->cliente)->cli_nome,
                    'cidade' => optional($a->cliente)->cli_cidade,
                    'uf' => optional($a->cliente)->cli_uf,
                    'latitude' => optional($a->cliente)->cli_latitude,
                    'longitude' => optional($a->cliente)->cli_longitude,
                    'link_mapa' => optional($a->cliente)->cli_link_mapa,
                ],
            ])->values(),
        ]);
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
