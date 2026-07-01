<?php

namespace App\Http\Controllers\Api\Mcl;

use App\Enums\AssinaturaTipo;
use App\Enums\AtendimentoRelatorioStatus;
use App\Enums\CondicaoClimatica;
use App\Http\Controllers\Controller;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioAssinatura;
use App\Models\AtendimentoRelatorioAnexo;
use App\Models\AtendimentoRelatorioCondicaoClimatica;
use App\Models\AtendimentoRelatorioFoto;
use App\Models\AtendimentoRelatorioHorario;
use App\Models\AtendimentoRelatorioPeca;
use App\Models\AtendimentoRelatorioServico;
use App\Models\AtendimentoRelatorioVideo;
use App\Models\Ocorrencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RelatoriosController extends Controller
{
    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function checkAcesso(Request $request, AtendimentoRelatorio $relatorio): bool
    {
        $usuario = $request->user();
        if ($usuario->user_nivel_acesso === 0) return true;
        return $relatorio->atendimento?->aten_usuario_id === $usuario->user_id;
    }

    private function saveSignature(AtendimentoRelatorio $relatorio, string $base64, string $tipo): string
    {
        if (! preg_match('#^data:image\/(png|jpeg|jpg);base64,(.*)$#', $base64, $m)) {
            throw new \RuntimeException('Formato de assinatura inválido.');
        }

        $data = base64_decode($m[2]);
        $path = "atendimentos_relatorios/{$relatorio->aten_rel_id}/assinaturas/{$tipo}.png";
        $dir  = dirname(storage_path('app/public/' . $path));

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
            ->where('aten_rel_ass_tipo', $tipo)
            ->first();

        if ($existing) {
            $existing->update(['aten_rel_ass_path' => $path]);
        } else {
            AtendimentoRelatorioAssinatura::create([
                'aten_rel_ass_relatorio_id' => $relatorio->aten_rel_id,
                'aten_rel_ass_path'         => $path,
                'aten_rel_ass_tipo'         => $tipo,
            ]);
        }

        return asset('storage/' . $path);
    }

    private function safeFilename(string $originalName, string $dir): string
    {
        $ext  = pathinfo($originalName, PATHINFO_EXTENSION);
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        // Mantém nome original mas remove caracteres perigosos
        $safe = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $base);
        $safe = trim($safe, '_') ?: 'arquivo';
        $name = $safe . '.' . $ext;
        // Evita conflitos adicionando sufixo numérico
        $counter = 0;
        while (\Illuminate\Support\Facades\Storage::disk('public')->exists("$dir/$name")) {
            $counter++;
            $name = "{$safe}_{$counter}.{$ext}";
        }
        return $name;
    }

    // ─── Relatório CRUD ───────────────────────────────────────────────────────

    /**
     * Lista relatórios de um atendimento.
     *
     * GET /api/mcl/v1/atendimentos/{aten_id}/relatorios
     */
    public function index(Request $request, int $atenId): JsonResponse
    {
        $usuario     = $request->user();
        $atendimento = Atendimento::findOrFail($atenId);

        if ($usuario->user_nivel_acesso !== 0 && $atendimento->aten_usuario_id !== $usuario->user_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $relatorios = AtendimentoRelatorio::where('aten_rel_atendimento_id', $atenId)
            ->orderByDesc('aten_rel_id')
            ->get()
            ->map(fn($r) => [
                'id'           => $r->aten_rel_id,
                'data'         => $r->aten_rel_data?->format('Y-m-d'),
                'status'       => $r->aten_rel_status,
                'status_label' => AtendimentoRelatorioStatus::tryFrom($r->aten_rel_status)?->label() ?? '-',
            ]);

        return response()->json(['data' => $relatorios]);
    }

    /**
     * Cria novo relatório para um atendimento.
     *
     * POST /api/mcl/v1/atendimentos/{aten_id}/relatorios
     * Body: { aten_rel_data?: "YYYY-MM-DD" }
     */
    public function store(Request $request, int $atenId): JsonResponse
    {
        $request->validate([
            'aten_rel_data' => 'nullable|date|before_or_equal:today',
        ]);

        $usuario     = $request->user();
        $atendimento = Atendimento::with('natureza.modeloRelatorio')->findOrFail($atenId);

        if ($usuario->user_nivel_acesso !== 0 && $atendimento->aten_usuario_id !== $usuario->user_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if (! $atendimento->natureza?->modeloRelatorio) {
            return response()->json(['message' => 'Natureza do atendimento sem modelo de relatório.'], 422);
        }

        $rel = AtendimentoRelatorio::create([
            'aten_rel_atendimento_id'      => $atendimento->aten_id,
            'aten_rel_modelo_relatorio_id' => $atendimento->natureza->modeloRelatorio->mod_rel_id,
            'aten_rel_data'                => $request->aten_rel_data ?? now()->toDateString(),
            'aten_rel_status'              => 0,
        ]);

        return response()->json([
            'message' => 'Relatório criado.',
            'data'    => ['id' => $rel->aten_rel_id],
        ], 201);
    }

    /**
     * Detalhe completo de um relatório.
     *
     * GET /api/mcl/v1/relatorios/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::with([
            'atendimento.cliente',
            'atendimento.natureza',
            'atendimento.equipamentos',
            'horarios',
            'climas',
            'servicos',
            'pecas',
            'ocorrencias',
            'assinaturas',
        ])->findOrFail($id);

        if (! $this->checkAcesso($request, $relatorio)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $prazo = $relatorio->calcularPrazo();

        $clima = [];
        foreach ($relatorio->climas as $c) {
            $periodos = [1 => 'manha', 2 => 'tarde', 3 => 'noite'];
            $key = $periodos[$c->aten_rel_clima_periodo] ?? null;
            if ($key) $clima[$key] = CondicaoClimatica::tryFrom($c->aten_rel_clima_condicao)?->label();
        }

        $assResp = $relatorio->assinaturas->first(fn($a) => $a->aten_rel_ass_tipo->value === 'responsavel');
        $assCli  = $relatorio->assinaturas->first(fn($a) => $a->aten_rel_ass_tipo->value === 'cliente');

        return response()->json(['data' => [
            'id'                       => $relatorio->aten_rel_id,
            'data'                     => $relatorio->aten_rel_data?->format('Y-m-d'),
            'status'                   => $relatorio->aten_rel_status,
            'status_label'             => AtendimentoRelatorioStatus::tryFrom($relatorio->aten_rel_status)?->label() ?? '-',
            'descricao'                => $relatorio->aten_rel_descricao,
            'informacoes_adicionais'   => $relatorio->aten_rel_informacoes_adicionais,
            'prazo'                    => $prazo,
            'atendimento' => [
                'id'              => $relatorio->atendimento->aten_id,
                'responsavel'     => $relatorio->atendimento->aten_responsavel,
                'telefone'        => $relatorio->atendimento->aten_telefone,
                'endereco'        => $relatorio->atendimento->aten_endereco,
                'nr_proposta'     => $relatorio->atendimento->aten_nr_proposta,
                'entrega_tecnica' => (bool) $relatorio->atendimento->aten_entrega_tecnica,
                'obs_cliente'     => $relatorio->atendimento->aten_obs_cliente,
                'obs_tecnica'     => $relatorio->atendimento->aten_obs_tecnica,
                'obs_manutencao'  => $relatorio->atendimento->aten_obs_manutencao,
                'dt_inicio'       => $relatorio->atendimento->aten_dt_inicio?->format('Y-m-d'),
                'dt_fim'          => $relatorio->atendimento->aten_dt_fim?->format('Y-m-d'),
                'cliente'         => optional($relatorio->atendimento->cliente)->cli_nome,
                'natureza'        => optional($relatorio->atendimento->natureza)->nat_aten_descricao,
                'equipamentos'    => $relatorio->atendimento->equipamentos->map(fn($e) => [
                    'id'        => $e->aten_equip_id,
                    'descricao' => $e->aten_equip_descricao,
                ])->values(),
            ],
            'horarios' => [
                'entrada'          => optional($relatorio->horarios)->aten_rel_hora_entrada,
                'inicio_intervalo' => optional($relatorio->horarios)->aten_rel_hora_inicio_intervalo,
                'fim_intervalo'    => optional($relatorio->horarios)->aten_rel_hora_fim_intervalo,
                'saida'            => optional($relatorio->horarios)->aten_rel_hora_saida,
            ],
            'clima'      => $clima,
            'servicos'   => $relatorio->servicos->map(fn($s) => [
                'id'        => $s->aten_rel_serv_id,
                'descricao' => $s->aten_rel_serv_descricao,
            ])->values(),
            'pecas'      => $relatorio->pecas->map(fn($p) => [
                'id'        => $p->aten_rel_peca_id,
                'descricao' => $p->aten_rel_peca_descricao,
            ])->values(),
            'ocorrencias' => $relatorio->ocorrencias->map(fn($o) => [
                'ocorrencia_id' => $o->ocor_id,
                'descricao'     => $o->ocor_descricao,
                'observacao'    => $o->pivot->aten_rel_ocor_observacao ?? '',
            ])->values(),
            'assinaturas' => [
                'tecnico'  => $assResp?->aten_rel_ass_path ? asset('storage/' . $assResp->aten_rel_ass_path) : null,
                'cliente'  => $assCli?->aten_rel_ass_path  ? asset('storage/' . $assCli->aten_rel_ass_path)  : null,
            ],
        ]]);
    }

    // ─── Seções de escrita ────────────────────────────────────────────────────

    /**
     * Atualiza a descrição do relatório.
     *
     * PUT /api/mcl/v1/relatorios/{id}/descricao
     * Body: { descricao: string }
     */
    public function updateDescricao(Request $request, int $id): JsonResponse
    {
        $request->validate(['descricao' => 'nullable|string']);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        $relatorio->update(['aten_rel_descricao' => $request->descricao]);

        return response()->json(['message' => 'Descrição atualizada.']);
    }

    /**
     * Atualiza as informações adicionais.
     *
     * PUT /api/mcl/v1/relatorios/{id}/informacoes-adicionais
     * Body: { informacoes_adicionais: string }
     */
    public function updateInformacoesAdicionais(Request $request, int $id): JsonResponse
    {
        $request->validate(['informacoes_adicionais' => 'nullable|string']);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        $relatorio->update(['aten_rel_informacoes_adicionais' => $request->informacoes_adicionais]);

        return response()->json(['message' => 'Informações adicionais atualizadas.']);
    }

    /**
     * Atualiza horários.
     *
     * PUT /api/mcl/v1/relatorios/{id}/horarios
     * Body: { entrada, inicio_intervalo, fim_intervalo, saida } — "HH:MM"
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
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        AtendimentoRelatorioHorario::updateOrCreate(
            ['aten_rel_hora_relatorio_id' => $id],
            [
                'aten_rel_hora_entrada'          => $request->entrada,
                'aten_rel_hora_inicio_intervalo' => $request->inicio_intervalo,
                'aten_rel_hora_fim_intervalo'    => $request->fim_intervalo,
                'aten_rel_hora_saida'            => $request->saida,
            ]
        );

        return response()->json(['message' => 'Horários atualizados.']);
    }

    /**
     * Atualiza condições climáticas.
     *
     * PUT /api/mcl/v1/relatorios/{id}/clima
     * Body: { manha, tarde, noite } — "ensolarado|nublado|chuvoso"
     */
    public function updateClima(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'manha' => 'nullable|in:ensolarado,nublado,chuvoso',
            'tarde' => 'nullable|in:ensolarado,nublado,chuvoso',
            'noite' => 'nullable|in:ensolarado,nublado,chuvoso',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        foreach ([1 => $request->manha, 2 => $request->tarde, 3 => $request->noite] as $periodo => $condStr) {
            if ($condStr !== null) {
                AtendimentoRelatorioCondicaoClimatica::updateOrCreate(
                    ['aten_rel_clima_relatorio_id' => $id, 'aten_rel_clima_periodo' => $periodo],
                    ['aten_rel_clima_condicao' => CondicaoClimatica::fromLabel($condStr)->value]
                );
            }
        }

        return response()->json(['message' => 'Clima atualizado.']);
    }

    /**
     * Adiciona serviço ao relatório.
     *
     * POST /api/mcl/v1/relatorios/{id}/servicos
     * Body: { descricao: string }
     */
    public function storeServico(Request $request, int $id): JsonResponse
    {
        $request->validate(['descricao' => 'required|string|max:500']);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        $row = AtendimentoRelatorioServico::create([
            'aten_rel_serv_relatorio_id' => $id,
            'aten_rel_serv_descricao'    => $request->descricao,
        ]);

        return response()->json([
            'message' => 'Serviço adicionado.',
            'data'    => ['id' => $row->aten_rel_serv_id, 'descricao' => $row->aten_rel_serv_descricao],
        ], 201);
    }

    /**
     * Remove serviço do relatório.
     *
     * DELETE /api/mcl/v1/relatorios/{id}/servicos/{serv_id}
     */
    public function destroyServico(Request $request, int $id, int $servId): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        AtendimentoRelatorioServico::where('aten_rel_serv_id', $servId)
            ->where('aten_rel_serv_relatorio_id', $id)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Serviço removido.']);
    }

    /**
     * Adiciona peça ao relatório.
     *
     * POST /api/mcl/v1/relatorios/{id}/pecas
     * Body: { descricao: string }
     */
    public function storePeca(Request $request, int $id): JsonResponse
    {
        $request->validate(['descricao' => 'required|string|max:500']);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        $row = AtendimentoRelatorioPeca::create([
            'aten_rel_peca_relatorio_id' => $id,
            'aten_rel_peca_descricao'    => $request->descricao,
        ]);

        return response()->json([
            'message' => 'Peça adicionada.',
            'data'    => ['id' => $row->aten_rel_peca_id, 'descricao' => $row->aten_rel_peca_descricao],
        ], 201);
    }

    /**
     * Remove peça do relatório.
     *
     * DELETE /api/mcl/v1/relatorios/{id}/pecas/{peca_id}
     */
    public function destroyPeca(Request $request, int $id, int $pecaId): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        AtendimentoRelatorioPeca::where('aten_rel_peca_id', $pecaId)
            ->where('aten_rel_peca_relatorio_id', $id)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Peça removida.']);
    }

    /**
     * Adiciona ocorrência ao relatório.
     *
     * POST /api/mcl/v1/relatorios/{id}/ocorrencias
     * Body: { ocorrencia_id: int, observacao?: string }
     */
    public function storeOcorrencia(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'ocorrencia_id' => 'required|exists:ocorrencias,ocor_id',
            'observacao'    => 'nullable|string',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        if ($relatorio->ocorrencias()->where('ocorrencias.ocor_id', $request->ocorrencia_id)->exists()) {
            return response()->json(['message' => 'Ocorrência já adicionada neste relatório.'], 422);
        }

        $ocorrencia = Ocorrencia::findOrFail($request->ocorrencia_id);
        $relatorio->ocorrencias()->attach($request->ocorrencia_id, [
            'aten_rel_ocor_observacao' => $request->observacao ?? '',
        ]);

        return response()->json([
            'message' => 'Ocorrência adicionada.',
            'data'    => [
                'ocorrencia_id' => $ocorrencia->ocor_id,
                'descricao'     => $ocorrencia->ocor_descricao,
                'observacao'    => $request->observacao ?? '',
            ],
        ], 201);
    }

    /**
     * Remove ocorrência do relatório.
     *
     * DELETE /api/mcl/v1/relatorios/{id}/ocorrencias/{ocorrencia_id}
     */
    public function destroyOcorrencia(Request $request, int $id, int $ocorrenciaId): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        $relatorio->ocorrencias()->detach($ocorrenciaId);

        return response()->json(['message' => 'Ocorrência removida.']);
    }

    /**
     * Altera o status do relatório.
     *
     * PUT /api/mcl/v1/relatorios/{id}/status
     * Body: { status: 0|1|2 }
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|integer|in:0,1,2']);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        $relatorio->update(['aten_rel_status' => $request->status]);

        $label = AtendimentoRelatorioStatus::tryFrom($request->status)?->label() ?? '-';

        return response()->json(['message' => "Status alterado para: {$label}."]);
    }

    /**
     * Salva assinaturas (base64 PNG/JPEG).
     *
     * POST /api/mcl/v1/relatorios/{id}/assinaturas
     * Body: {
     *   tecnico?: "data:image/png;base64,...",
     *   cliente?: "data:image/png;base64,..."
     * }
     */
    public function storeAssinaturas(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tecnico'  => 'nullable|string',
            'cliente'  => 'nullable|string',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        $urls = [];
        foreach (['tecnico' => 'responsavel', 'cliente' => 'cliente'] as $campo => $tipo) {
            if ($request->filled($campo)) {
                $urls[$campo] = $this->saveSignature($relatorio, $request->input($campo), $tipo);
            }
        }

        return response()->json(['message' => 'Assinatura(s) salva(s).', 'data' => $urls]);
    }

    /**
     * Lista fotos, vídeos e arquivos do relatório.
     *
     * GET /api/mcl/v1/relatorios/{id}/anexos
     */
    public function getAnexos(Request $request, int $id): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::with(['fotos', 'videos', 'anexos'])->findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        return response()->json(['data' => [
            'fotos'    => $relatorio->fotos->map(fn($f) => [
                'id'       => $f->aten_rel_foto_id,
                'url'      => url('storage/' . $f->aten_rel_foto_path),
                'legenda'  => $f->aten_rel_foto_legenda ?? null,
            ])->values(),
            'videos'   => $relatorio->videos->map(fn($v) => [
                'id'  => $v->aten_rel_vid_id,
                'url' => url('storage/' . $v->aten_rel_vid_path),
            ])->values(),
            'arquivos' => $relatorio->anexos->map(fn($a) => [
                'id'  => $a->aten_rel_anexo_id,
                'url' => url('storage/' . $a->aten_rel_anexo_path),
            ])->values(),
        ]]);
    }

    /**
     * Upload de fotos, vídeos e arquivos (multipart/form-data).
     *
     * POST /api/mcl/v1/relatorios/{id}/anexos
     * Fields:
     *   fotos[]    — jpg, jpeg, png, webp  (max 10 MB)
     *   videos[]   — mp4, mov, avi, mkv, webm (max 200 MB)
     *   arquivos[] — pdf, doc, docx, xls, xlsx, txt, csv (max 20 MB)
     *   legendas[] — string (legenda por índice da foto)
     */
    public function uploadAnexos(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'fotos'      => 'nullable|array',
            'fotos.*'    => 'file|max:10240|mimes:jpg,jpeg,png,webp',
            'legendas'   => 'nullable|array',
            'legendas.*' => 'nullable|string|max:255',
            'videos'     => 'nullable|array',
            'videos.*'   => 'file|max:204800|mimes:mp4,mov,avi,mkv,webm',
            'arquivos'   => 'nullable|array',
            'arquivos.*' => 'file|max:20480|mimes:pdf,doc,docx,xls,xlsx,txt,csv',
        ]);

        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        $saved    = ['fotos' => [], 'videos' => [], 'arquivos' => []];
        $legendas = $request->input('legendas', []);

        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $i => $file) {
                if (! $file->isValid()) continue;
                $originalName = $file->getClientOriginalName();
                $safeName     = $this->safeFilename($originalName, "atendimentos_relatorios/{$id}/fotos");
                $path         = $file->storeAs("atendimentos_relatorios/{$id}/fotos", $safeName, 'public');
                $foto         = AtendimentoRelatorioFoto::create([
                    'aten_rel_foto_relatorio_id' => $id,
                    'aten_rel_foto_path'         => $path,
                    'aten_rel_foto_legenda'      => $legendas[$i] ?? null,
                ]);
                $saved['fotos'][] = ['id' => $foto->aten_rel_foto_id, 'url' => url('storage/' . $path), 'nome' => $originalName, 'legenda' => $foto->aten_rel_foto_legenda];
            }
        }

        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $file) {
                if (! $file->isValid()) continue;
                $originalName = $file->getClientOriginalName();
                $safeName     = $this->safeFilename($originalName, "atendimentos_relatorios/{$id}/videos");
                $path         = $file->storeAs("atendimentos_relatorios/{$id}/videos", $safeName, 'public');
                $video        = AtendimentoRelatorioVideo::create(['aten_rel_vid_relatorio_id' => $id, 'aten_rel_vid_path' => $path]);
                $saved['videos'][] = ['id' => $video->aten_rel_vid_id, 'url' => url('storage/' . $path), 'nome' => $originalName];
            }
        }

        if ($request->hasFile('arquivos')) {
            foreach ($request->file('arquivos') as $file) {
                if (! $file->isValid()) continue;
                $originalName = $file->getClientOriginalName();
                $safeName     = $this->safeFilename($originalName, "atendimentos_relatorios/{$id}/arquivos");
                $path         = $file->storeAs("atendimentos_relatorios/{$id}/arquivos", $safeName, 'public');
                $anexo        = AtendimentoRelatorioAnexo::create(['aten_rel_anexo_relatorio_id' => $id, 'aten_rel_anexo_path' => $path]);
                $saved['arquivos'][] = ['id' => $anexo->aten_rel_anexo_id, 'url' => url('storage/' . $path), 'nome' => $originalName];
            }
        }

        return response()->json(['message' => 'Upload realizado.', 'data' => $saved]);
    }

    /**
     * Remove um anexo.
     *
     * DELETE /api/mcl/v1/relatorios/{id}/anexos/{tipo}/{item_id}
     * tipo = foto | video | arquivo
     */
    public function destroyAnexo(Request $request, int $id, string $tipo, int $itemId): JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        if (! $this->checkAcesso($request, $relatorio)) return response()->json(['message' => 'Acesso negado.'], 403);

        match ($tipo) {
            'foto'    => AtendimentoRelatorioFoto::where('aten_rel_foto_id', $itemId)->where('aten_rel_foto_relatorio_id', $id)->firstOrFail()->delete(),
            'video'   => AtendimentoRelatorioVideo::where('aten_rel_vid_id', $itemId)->where('aten_rel_vid_relatorio_id', $id)->firstOrFail()->delete(),
            'arquivo' => AtendimentoRelatorioAnexo::where('aten_rel_anexo_id', $itemId)->where('aten_rel_anexo_relatorio_id', $id)->firstOrFail()->delete(),
            default   => throw new \InvalidArgumentException('Tipo inválido.'),
        };

        return response()->json(['message' => 'Anexo removido.']);
    }
}
