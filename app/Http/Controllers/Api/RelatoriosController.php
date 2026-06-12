<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioAtividade;
use App\Models\AtendimentoRelatorioComentario;
use App\Models\AtendimentoRelatorioCondicaoClimatica;
use App\Models\AtendimentoRelatorioHorario;
use App\Models\AtendimentoRelatorioAnexo;
use App\Models\AtendimentoRelatorioFoto;
use App\Models\AtendimentoRelatorioVideo;
use App\Models\AtendimentoRelatorioAssinatura;
use App\Models\Equipamento;
use App\Models\Ocorrencia;
use App\Models\Ocupacao;
use App\Repositories\AtendimentoRelatorioRepository;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RelatoriosController extends Controller
{
    public function __construct(
        private readonly AtendimentoRelatorioRepository $repo
    ) {}

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Verifica se o técnico autenticado tem acesso ao relatório.
     * Admin (nivel_acesso != 1) acessa qualquer um.
     */
    private function checkAcesso(Request $request, AtendimentoRelatorio $relatorio): bool
    {
        $usuario = $request->user();
        if ($usuario->user_nivel_acesso !== 1) {
            return true;
        }
        return $relatorio->atendimento?->aten_usuario_id === $usuario->user_id;
    }

    private function condLabel(int $cond): string
    {
        return match ($cond) {
            1 => 'ensolarado',
            2 => 'nublado',
            3 => 'chuvoso',
            default => 'desconhecido',
        };
    }

    private function condValue(string $cond): int
    {
        return match ($cond) {
            'ensolarado' => 1,
            'nublado'    => 2,
            'chuvoso'    => 3,
            default      => 1,
        };
    }

    private function formatRelatorio(AtendimentoRelatorio $r): array
    {
        return [
            'id'             => $r->aten_rel_id,
            'data'           => $r->aten_rel_data?->format('Y-m-d'),
            'status'         => $r->aten_rel_status,
            'status_label'   => match ($r->aten_rel_status) {
                0 => 'Preenchendo',
                1 => 'Revisar',
                2 => 'Aprovado',
                default => '-',
            },
            'atendimento_id' => $r->aten_rel_atendimento_id,
            'obra'           => $r->atendimento?->aten_descricao,
            'natureza'       => $r->atendimento?->natureza?->nat_aten_descricao,
            'setor'          => $r->atendimento?->natureza?->tipoAtendimento?->tp_aten_descricao,
            'cliente'        => $r->atendimento?->cliente?->cli_nome,
        ];
    }

    // ─── Endpoints ───────────────────────────────────────────────────────────

    /**
     * Lista relatórios do técnico autenticado.
     *
     * GET /api/v1/relatorios
     * Query params opcionais:
     *   atendimento_id = int
     *   status         = 0|1|2
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $filters = $usuario->user_nivel_acesso === 1
            ? ['usuario_id' => $usuario->user_id]
            : [];

        if ($request->filled('atendimento_id')) {
            $filters['aten_rel_atendimento_id'] = (int) $request->atendimento_id;
        }

        $rows = $this->repo->all($filters);

        if ($request->filled('status')) {
            $rows = $rows->where('aten_rel_status', (int) $request->status)->values();
        }

        return response()->json(['data' => $rows->map(fn($r) => $this->formatRelatorio($r))]);
    }

    /**
     * Cria um novo relatório para um atendimento.
     *
     * POST /api/v1/relatorios
     * Body: { "aten_id": int, "aten_rel_data": "YYYY-MM-DD" (opcional) }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'aten_id'       => 'required|exists:atendimentos,aten_id',
            'aten_rel_data' => 'nullable|date|before_or_equal:today',
        ]);

        $usuario     = $request->user();
        $atendimento = Atendimento::with('natureza.modeloRelatorio')
            ->where('aten_id', $request->aten_id)
            ->firstOrFail();

        // Técnico só cria relatório nos próprios atendimentos
        if ($usuario->user_nivel_acesso === 1 && $atendimento->aten_usuario_id !== $usuario->user_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if (! $atendimento->natureza?->modeloRelatorio) {
            return response()->json([
                'message' => 'A natureza do atendimento não possui modelo de relatório vinculado.',
            ], 422);
        }

        $rel = $this->repo->create([
            'aten_rel_atendimento_id'      => $atendimento->aten_id,
            'aten_rel_modelo_relatorio_id' => $atendimento->natureza->modeloRelatorio->mod_rel_id,
            'aten_rel_data'                => $request->aten_rel_data ?? now()->toDateString(),
            'aten_rel_status'              => 0,
        ]);

        return response()->json([
            'message' => 'Relatório criado com sucesso.',
            'data'    => ['id' => $rel->aten_rel_id],
        ], 201);
    }

    /**
     * Retorna todos os dados de um relatório.
     *
     * GET /api/v1/relatorios/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::with([
            'atendimento.cliente',
            'atendimento.natureza.tipoAtendimento',
            'horarios',
            'climas',
            'ocupacoes.tipoOcupacao',
            'equipamentos',
            'atividades',
            'ocorrencias',
            'comentarios',
            'assinaturas',
        ])->findOrFail($id);

        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $inicio = Carbon::parse($relatorio->atendimento->aten_dt_inicio);
        $fim    = Carbon::parse($relatorio->atendimento->aten_dt_fim);
        $base   = Carbon::parse($relatorio->aten_rel_data);

        $prazoTotal     = $inicio->diffInDays($fim);
        $prazoDecorrido = min($inicio->diffInDays($base), $prazoTotal);

        $condReverse = [1 => 'ensolarado', 2 => 'nublado', 3 => 'chuvoso'];
        $clima = ['manha' => null, 'tarde' => null, 'noite' => null];
        foreach ($relatorio->climas as $c) {
            if ($c->aten_rel_clima_periodo === 1) $clima['manha'] = $condReverse[$c->aten_rel_clima_condicao] ?? null;
            if ($c->aten_rel_clima_periodo === 2) $clima['tarde'] = $condReverse[$c->aten_rel_clima_condicao] ?? null;
            if ($c->aten_rel_clima_periodo === 3) $clima['noite'] = $condReverse[$c->aten_rel_clima_condicao] ?? null;
        }

        $assinaturas = [
            'responsavel' => optional($relatorio->assinaturaResponsavel())->aten_rel_ass_path
                ? asset('storage/' . optional($relatorio->assinaturaResponsavel())->aten_rel_ass_path)
                : null,
            'cliente' => optional($relatorio->assinaturaCliente())->aten_rel_ass_path
                ? asset('storage/' . optional($relatorio->assinaturaCliente())->aten_rel_ass_path)
                : null,
        ];

        return response()->json([
            'data' => [
                'id'     => $relatorio->aten_rel_id,
                'status' => $relatorio->aten_rel_status,

                'dados' => [
                    'aten_rel_data'   => $relatorio->aten_rel_data?->format('Y-m-d'),
                    'prazo_total'     => $prazoTotal,
                    'prazo_decorrido' => $prazoDecorrido,
                    'prazo_vencer'    => max($prazoTotal - $prazoDecorrido, 0),
                ],

                'atendimento' => [
                    'id'          => $relatorio->atendimento->aten_id,
                    'descricao'   => $relatorio->atendimento->aten_descricao,
                    'responsavel' => $relatorio->atendimento->aten_responsavel,
                    'endereco'    => $relatorio->atendimento->aten_endereco,
                    'dt_inicio'   => $relatorio->atendimento->aten_dt_inicio?->format('Y-m-d'),
                    'dt_fim'      => $relatorio->atendimento->aten_dt_fim?->format('Y-m-d'),
                    'cliente'     => $relatorio->atendimento->cliente?->cli_nome,
                    'natureza'    => $relatorio->atendimento->natureza?->nat_aten_descricao,
                    'setor'       => $relatorio->atendimento->natureza?->tipoAtendimento?->tp_aten_descricao,
                ],

                'horarios' => [
                    'entrada'          => optional($relatorio->horarios)->aten_rel_hora_entrada,
                    'inicio_intervalo' => optional($relatorio->horarios)->aten_rel_hora_inicio_intervalo,
                    'fim_intervalo'    => optional($relatorio->horarios)->aten_rel_hora_fim_intervalo,
                    'saida'            => optional($relatorio->horarios)->aten_rel_hora_saida,
                ],

                'clima'        => $clima,
                'mao_obra'     => $relatorio->ocupacoes->map(fn($o) => [
                    'ocup_id'  => $o->ocup_id,
                    'descricao'=> $o->ocup_descricao,
                    'tipo'     => optional($o->tipoOcupacao)->tp_ocup_descricao,
                    'qtd'      => (int) $o->pivot->aten_rel_ocup_quantidade,
                ])->values(),

                'equipamentos' => $relatorio->equipamentos->map(fn($e) => [
                    'equip_id' => $e->equip_id,
                    'descricao'=> $e->equip_descricao,
                    'qtd'      => (int) $e->pivot->aten_rel_equip_quantidade,
                ])->values(),

                'atividades' => $relatorio->atividades->sortBy('aten_rel_ativ_id')->map(fn($a) => [
                    'id'        => (int) $a->aten_rel_ativ_id,
                    'descricao' => $a->aten_rel_ativ_descricao,
                    'status'    => (int) $a->aten_rel_ativ_status,
                ])->values(),

                'ocorrencias' => $relatorio->ocorrencias->map(fn($o) => [
                    'ocorrencia_id' => (int) $o->ocor_id,
                    'descricao'     => $o->ocor_descricao,
                    'observacao'    => $o->pivot->aten_rel_ocor_observacao ?? '',
                ])->values(),

                'comentarios' => $relatorio->comentarios->sortBy('aten_rel_com_id')->map(fn($c) => [
                    'id'        => (int) $c->aten_rel_com_id,
                    'descricao' => $c->aten_rel_com_descricao,
                ])->values(),

                'assinaturas' => $assinaturas,
            ],
        ]);
    }

    /**
     * Atualiza horários do relatório.
     *
     * POST /api/v1/relatorios/{id}/horarios
     * Body: { entrada, inicio_intervalo, fim_intervalo, saida } — formato "HH:MM"
     */
    public function updateHorarios(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'entrada'          => 'nullable|date_format:H:i',
            'inicio_intervalo' => 'nullable|date_format:H:i',
            'fim_intervalo'    => 'nullable|date_format:H:i',
            'saida'            => 'nullable|date_format:H:i',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        AtendimentoRelatorioHorario::updateOrCreate(
            ['aten_rel_hora_relatorio_id' => $relatorio->aten_rel_id],
            [
                'aten_rel_hora_entrada'          => $request->entrada,
                'aten_rel_hora_inicio_intervalo' => $request->inicio_intervalo,
                'aten_rel_hora_fim_intervalo'    => $request->fim_intervalo,
                'aten_rel_hora_saida'            => $request->saida,
            ]
        );

        return response()->json(['message' => 'Horários atualizados com sucesso.']);
    }

    /**
     * Atualiza condições climáticas.
     *
     * POST /api/v1/relatorios/{id}/clima
     * Body: { manha: "ensolarado|nublado|chuvoso", tarde: ..., noite: ... }
     */
    public function updateClima(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'manha' => 'nullable|in:ensolarado,nublado,chuvoso',
            'tarde' => 'nullable|in:ensolarado,nublado,chuvoso',
            'noite' => 'nullable|in:ensolarado,nublado,chuvoso',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $periodos = [1 => $request->manha, 2 => $request->tarde, 3 => $request->noite];
        foreach ($periodos as $periodo => $condStr) {
            if ($condStr !== null) {
                AtendimentoRelatorioCondicaoClimatica::updateOrCreate(
                    ['aten_rel_clima_relatorio_id' => $relatorio->aten_rel_id, 'aten_rel_clima_periodo' => $periodo],
                    ['aten_rel_clima_condicao' => $this->condValue($condStr)]
                );
            }
        }

        return response()->json(['message' => 'Clima atualizado com sucesso.']);
    }

    /**
     * Adiciona mão de obra ao relatório.
     *
     * POST /api/v1/relatorios/{id}/mao-obra
     * Body: { ocup_id: int, qtd: int }
     */
    public function storeMaoObra(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'ocup_id' => 'required|exists:ocupacoes,ocup_id',
            'qtd'     => 'required|integer|min:1',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($relatorio->ocupacoes()->where('ocupacoes.ocup_id', $request->ocup_id)->exists()) {
            return response()->json(['message' => 'Mão de obra já adicionada.'], 422);
        }

        $ocup = Ocupacao::with('tipoOcupacao')->findOrFail($request->ocup_id);
        $relatorio->ocupacoes()->attach($request->ocup_id, ['aten_rel_ocup_quantidade' => $request->qtd]);

        return response()->json([
            'message' => 'Mão de obra adicionada!',
            'data'    => [
                'ocup_id'  => $ocup->ocup_id,
                'descricao'=> $ocup->ocup_descricao,
                'tipo'     => optional($ocup->tipoOcupacao)->tp_ocup_descricao,
                'qtd'      => (int) $request->qtd,
            ],
        ]);
    }

    /**
     * Remove mão de obra do relatório.
     *
     * DELETE /api/v1/relatorios/{id}/mao-obra/{ocup_id}
     */
    public function destroyMaoObra(Request $request, int $id, int $ocupId): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }
        $relatorio->ocupacoes()->detach($ocupId);
        return response()->json(['message' => 'Mão de obra removida!']);
    }

    /**
     * Adiciona equipamento ao relatório.
     *
     * POST /api/v1/relatorios/{id}/equipamentos
     * Body: { equip_id: int, qtd: int }
     */
    public function storeEquipamento(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'equip_id' => 'required|exists:equipamentos,equip_id',
            'qtd'      => 'required|integer|min:1',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($relatorio->equipamentos()->where('equipamentos.equip_id', $request->equip_id)->exists()) {
            return response()->json(['message' => 'Equipamento já adicionado.'], 422);
        }

        $equip = Equipamento::findOrFail($request->equip_id);
        $relatorio->equipamentos()->attach($request->equip_id, ['aten_rel_equip_quantidade' => $request->qtd]);

        return response()->json([
            'message' => 'Equipamento adicionado!',
            'data'    => ['equip_id' => $equip->equip_id, 'descricao' => $equip->equip_descricao, 'qtd' => (int) $request->qtd],
        ]);
    }

    /**
     * Remove equipamento do relatório.
     *
     * DELETE /api/v1/relatorios/{id}/equipamentos/{equip_id}
     */
    public function destroyEquipamento(Request $request, int $id, int $equipId): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }
        $relatorio->equipamentos()->detach($equipId);
        return response()->json(['message' => 'Equipamento removido!']);
    }

    /**
     * Adiciona atividade ao relatório.
     *
     * POST /api/v1/relatorios/{id}/atividades
     * Body: { descricao: string, status: 0|1 }
     */
    public function storeAtividade(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'descricao' => 'required|string|max:500',
            'status'    => 'required|integer|in:0,1,2,3,4,5',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $row = AtendimentoRelatorioAtividade::create([
            'aten_rel_ativ_relatorio_id' => $id,
            'aten_rel_ativ_descricao'    => $request->descricao,
            'aten_rel_ativ_status'       => $request->status,
        ]);

        return response()->json([
            'message' => 'Atividade adicionada!',
            'data'    => ['id' => $row->aten_rel_ativ_id, 'descricao' => $row->aten_rel_ativ_descricao, 'status' => $row->aten_rel_ativ_status],
        ]);
    }

    /**
     * Atualiza uma atividade.
     *
     * PUT /api/v1/relatorios/{id}/atividades/{ativ_id}
     * Body: { descricao: string, status: 0|1 }
     */
    public function updateAtividade(Request $request, int $id, int $ativId): JsonResponse
    {
        $request->validate([
            'descricao' => 'required|string|max:500',
            'status'    => 'required|integer|in:0,1,2,3,4,5',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $row = AtendimentoRelatorioAtividade::where('aten_rel_ativ_id', $ativId)
            ->where('aten_rel_ativ_relatorio_id', $id)
            ->firstOrFail();

        $row->update(['aten_rel_ativ_descricao' => $request->descricao, 'aten_rel_ativ_status' => $request->status]);

        return response()->json([
            'message' => 'Atividade atualizada!',
            'data'    => ['id' => $row->aten_rel_ativ_id, 'descricao' => $row->aten_rel_ativ_descricao, 'status' => $row->aten_rel_ativ_status],
        ]);
    }

    /**
     * Remove uma atividade.
     *
     * DELETE /api/v1/relatorios/{id}/atividades/{ativ_id}
     */
    public function destroyAtividade(Request $request, int $id, int $ativId): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        AtendimentoRelatorioAtividade::where('aten_rel_ativ_id', $ativId)
            ->where('aten_rel_ativ_relatorio_id', $id)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Atividade removida!']);
    }

    /**
     * Adiciona comentário ao relatório.
     *
     * POST /api/v1/relatorios/{id}/comentarios
     * Body: { descricao: string }
     */
    public function storeComentario(Request $request, int $id): JsonResponse
    {
        $request->validate(['descricao' => 'required|string']);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $row = AtendimentoRelatorioComentario::create([
            'aten_rel_com_relatorio_id' => $id,
            'aten_rel_com_descricao'    => $request->descricao,
        ]);

        return response()->json([
            'message' => 'Comentário adicionado!',
            'data'    => ['id' => $row->aten_rel_com_id, 'descricao' => $row->aten_rel_com_descricao],
        ]);
    }

    /**
     * Remove um comentário.
     *
     * DELETE /api/v1/relatorios/{id}/comentarios/{com_id}
     */
    public function destroyComentario(Request $request, int $id, int $comId): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        AtendimentoRelatorioComentario::where('aten_rel_com_id', $comId)
            ->where('aten_rel_com_relatorio_id', $id)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Comentário removido!']);
    }

    /**
     * Adiciona ocorrência ao relatório.
     *
     * POST /api/v1/relatorios/{id}/ocorrencias
     * Body: { ocorrencia_id: int, observacao: string (opcional) }
     */
    public function storeOcorrencia(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'ocorrencia_id' => 'required|exists:ocorrencias,ocor_id',
            'observacao'    => 'nullable|string',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($relatorio->ocorrencias()->where('ocorrencias.ocor_id', $request->ocorrencia_id)->exists()) {
            return response()->json(['message' => 'Ocorrência já adicionada neste relatório.'], 422);
        }

        $ocorrencia = Ocorrencia::findOrFail($request->ocorrencia_id);
        $relatorio->ocorrencias()->attach($request->ocorrencia_id, [
            'aten_rel_ocor_observacao' => $request->observacao ?? '',
        ]);

        return response()->json([
            'message' => 'Ocorrência adicionada!',
            'data'    => [
                'ocorrencia_id' => $ocorrencia->ocor_id,
                'descricao'     => $ocorrencia->ocor_descricao,
                'observacao'    => $request->observacao ?? '',
            ],
        ]);
    }

    /**
     * Remove ocorrência do relatório.
     *
     * DELETE /api/v1/relatorios/{id}/ocorrencias/{ocorrencia_id}
     */
    public function destroyOcorrencia(Request $request, int $id, int $ocorrenciaId): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }
        $relatorio->ocorrencias()->detach($ocorrenciaId);
        return response()->json(['message' => 'Ocorrência removida!']);
    }

    /**
     * Salva assinaturas e atualiza status do relatório.
     *
     * POST /api/v1/relatorios/{id}/assinaturas
     * Body: {
     *   status: 0|1|2,
     *   assinatura_responsavel: "data:image/png;base64,...",  (opcional)
     *   assinatura_cliente:     "data:image/png;base64,...",  (opcional)
     * }
     */
    public function updateAssinaturas(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status'                 => 'required|integer|in:0,1,2',
            'assinatura_responsavel' => 'nullable|string',
            'assinatura_cliente'     => 'nullable|string',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $relatorio->update(['aten_rel_status' => $request->status]);
        $urls = [];

        foreach (['responsavel', 'cliente'] as $tipo) {
            $campo = "assinatura_{$tipo}";
            if ($request->filled($campo)) {
                $urls[$tipo] = $this->saveSignature($relatorio, $request->input($campo), $tipo);
            }
        }

        return response()->json(['message' => 'Assinaturas salvas com sucesso.', 'data' => ['assinaturas' => $urls]]);
    }

    /**
     * Upload de fotos, vídeos e arquivos anexos.
     *
     * POST /api/v1/relatorios/{id}/anexos
     * Multipart form-data:
     *   fotos[]    — jpg, jpeg, png, webp, gif  (max 10 MB cada)
     *   videos[]   — mp4, mov, avi, mkv, webm   (max 100 MB cada)
     *   arquivos[] — pdf, doc, docx, xls, xlsx, txt, csv (max 20 MB cada)
     */
    public function uploadAnexos(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'fotos'      => 'nullable|array',
            'fotos.*'    => 'file|max:10240|mimes:jpg,jpeg,png,webp,gif',
            'videos'     => 'nullable|array',
            'videos.*'   => 'file|max:102400|mimes:mp4,mov,avi,mkv,webm',
            'arquivos'   => 'nullable|array',
            'arquivos.*' => 'file|max:20480|mimes:pdf,doc,docx,xls,xlsx,txt,csv',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $saved = ['fotos' => [], 'videos' => [], 'arquivos' => []];

        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $file) {
                if (! $file->isValid()) continue;
                $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $path     = $file->storeAs("atendimentos_relatorios/{$id}/fotos", $safeName, 'public');
                $foto     = AtendimentoRelatorioFoto::create(['aten_rel_foto_relatorio_id' => $id, 'aten_rel_foto_path' => $path]);
                $saved['fotos'][] = ['id' => $foto->aten_rel_foto_id, 'url' => asset('storage/' . $path)];
            }
        }

        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $file) {
                if (! $file->isValid()) continue;
                $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $path     = $file->storeAs("atendimentos_relatorios/{$id}/videos", $safeName, 'public');
                $video    = AtendimentoRelatorioVideo::create(['aten_rel_vid_relatorio_id' => $id, 'aten_rel_vid_path' => $path]);
                $saved['videos'][] = ['id' => $video->aten_rel_vid_id, 'url' => asset('storage/' . $path)];
            }
        }

        if ($request->hasFile('arquivos')) {
            foreach ($request->file('arquivos') as $file) {
                if (! $file->isValid()) continue;
                $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $path     = $file->storeAs("atendimentos_relatorios/{$id}/arquivos", $safeName, 'public');
                $anexo    = AtendimentoRelatorioAnexo::create(['aten_rel_anexo_relatorio_id' => $id, 'aten_rel_anexo_path' => $path]);
                $saved['arquivos'][] = ['id' => $anexo->aten_rel_anexo_id, 'url' => asset('storage/' . $path)];
            }
        }

        return response()->json(['message' => 'Uploads processados com sucesso.', 'data' => $saved]);
    }

    /**
     * Remove um anexo (foto, video ou arquivo).
     *
     * DELETE /api/v1/relatorios/{id}/anexos/{tipo}/{item_id}
     * tipo = foto | video | arquivo
     */
    public function destroyAnexo(Request $request, int $id, string $tipo, int $itemId): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        switch ($tipo) {
            case 'foto':
                AtendimentoRelatorioFoto::where('aten_rel_foto_id', $itemId)->where('aten_rel_foto_relatorio_id', $id)->firstOrFail()->delete();
                break;
            case 'video':
                AtendimentoRelatorioVideo::where('aten_rel_vid_id', $itemId)->where('aten_rel_vid_relatorio_id', $id)->firstOrFail()->delete();
                break;
            case 'arquivo':
                AtendimentoRelatorioAnexo::where('aten_rel_anexo_id', $itemId)->where('aten_rel_anexo_relatorio_id', $id)->firstOrFail()->delete();
                break;
            default:
                return response()->json(['message' => 'Tipo inválido. Use: foto, video ou arquivo.'], 422);
        }

        return response()->json(['message' => 'Anexo removido!']);
    }

    // ─── Signature helper ────────────────────────────────────────────────────

    private function saveSignature(AtendimentoRelatorio $relatorio, string $base64, string $tipo): string
    {
        if (! preg_match('#^data:image\/(png|jpeg|jpg);base64,(.*)$#', $base64, $m)) {
            throw new \RuntimeException('Formato de assinatura inválido.');
        }

        $data  = base64_decode($m[2]);
        $path  = "atendimentos_relatorios/{$relatorio->aten_rel_id}/assinaturas/{$tipo}.png";
        $dir   = dirname(storage_path('app/public/' . $path));

        if (! is_dir($dir)) mkdir($dir, 0755, true);

        $image = @imagecreatefromstring($data);
        if ($image === false) throw new \RuntimeException('Imagem inválida.');

        $bg    = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($bg, 255, 255, 255);
        imagefilledrectangle($bg, 0, 0, imagesx($image), imagesy($image), $white);
        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagepng($bg, storage_path('app/public/' . $path));
        imagedestroy($image);
        imagedestroy($bg);

        $existing = AtendimentoRelatorioAssinatura::where('aten_rel_ass_relatorio_id', $relatorio->aten_rel_id)
            ->where('aten_rel_ass_path', 'like', "%/{$tipo}.%")
            ->first();

        if ($existing) {
            $existing->update(['aten_rel_ass_path' => $path]);
        } else {
            AtendimentoRelatorioAssinatura::create(['aten_rel_ass_relatorio_id' => $relatorio->aten_rel_id, 'aten_rel_ass_path' => $path]);
        }

        return asset('storage/' . $path);
    }
}
