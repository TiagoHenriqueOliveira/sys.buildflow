<?php

namespace App\Http\Controllers;

use App\Enums\AssinaturaTipo;
use App\Enums\AtendimentoRelatorioStatus;
use App\Enums\AtendimentoStatus;
use App\Enums\CondicaoClimatica;
use App\Services\AuditService;
use App\Http\Requests\AtendimentoRelatorioAssinaturasRequest;
use App\Http\Requests\AtendimentoRelatorioStoreRequest;
use App\Http\Requests\AtendimentoRelatorioCondicaoClimaticaRequest;
use App\Http\Requests\AtendimentoRelatorioDadosRequest;
use App\Http\Requests\AtendimentoRelatorioHorariosRequest;
use App\Http\Requests\AtendimentoRelatorioOcorrenciaRequest;
use App\Http\Requests\AtendimentoRelatorioRequest;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AtendimentoRelatorioCondicaoClimatica;
use App\Models\AtendimentoRelatorioHorario;
use App\Models\Ocorrencia;
use App\Models\AtendimentoRelatorioFoto;
use App\Models\AtendimentoRelatorioVideo;
use App\Models\AtendimentoRelatorioAnexo;
use App\Models\AtendimentoRelatorioAssinatura;
use App\Models\AtendimentoRelatorioServico;
use App\Models\AtendimentoRelatorioPeca;
use App\Jobs\ProcessarMidiaJob;
use App\Repositories\AtendimentoRelatorioRepository;
use App\Services\DataTableService;
use App\Services\MediaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AtendimentosRelatoriosController extends Controller
{
    public function __construct(
        private readonly AtendimentoRelatorioRepository $repo,
        private readonly MediaService $media,
        private readonly DataTableService $dataTable,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $usuario = Auth::user();
            $filters = $usuario->user_nivel_acesso === 1
                ? ['usuario_id' => $usuario->user_id]
                : [];

            return response()->json(
                $this->dataTable->process(
                    $request,
                    $this->repo->query($filters),
                    searchable: [
                        'clientes.cli_nome',
                        'naturezas_atendimentos.nat_aten_descricao',
                        'usuarios.user_nome',
                        'atendimentos.aten_nr_proposta',
                        'atendimentos_relatorios.aten_rel_data',
                    ],
                    searchableRaw: [
                        "CASE atendimentos_relatorios.aten_rel_status WHEN 0 THEN 'Preenchendo' WHEN 1 THEN 'Revisar' WHEN 2 THEN 'Aprovado' END",
                    ],
                    orderable:  [
                        'acoes'       => null,
                        'data'        => 'atendimentos_relatorios.aten_rel_data',
                        'cliente'     => 'clientes.cli_nome',
                        'nr_proposta' => 'atendimentos.aten_nr_proposta',
                        'natureza'    => 'naturezas_atendimentos.nat_aten_descricao',
                        'tecnico'     => 'usuarios.user_nome',
                        'status'      => 'atendimentos_relatorios.aten_rel_status',
                    ],
                    mapper: fn($r) => [
                        'acoes'       => view('atendimentos-relatorios.partials.acoes', ['relatorio' => $r])->render(),
                        'data'        => optional($r->aten_rel_data)->format('d/m/Y'),
                        'cliente'     => e($r->atendimento?->cliente?->cli_nome ?? '-'),
                        'nr_proposta' => e($r->atendimento?->aten_nr_proposta ?? ''),
                        'natureza'    => $r->atendimento?->natureza?->nat_aten_descricao ?? '-',
                        'tecnico'     => e($r->atendimento?->usuario?->user_nome ?? '-'),
                        'status'  => ($s = AtendimentoRelatorioStatus::tryFrom($r->aten_rel_status))
                            ? '<span class="badge ' . $s->badgeClass() . '">' . $s->label() . '</span>'
                            : '-',
                    ],
                )
            );
        }

        return view('atendimentos-relatorios.index');
    }

    public function store(AtendimentoRelatorioStoreRequest $request)
    {
        try {

            $atendimento = Atendimento::query()
                ->with('natureza.modeloRelatorio')
                ->where('aten_id', $request->aten_id)
                ->firstOrFail();

            if (
                !$atendimento->natureza ||
                !$atendimento->natureza->modeloRelatorio
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'A natureza do atendimento não possui modelo de relatório vinculado.',
                ], 422);
            }

            $modelo = $atendimento->natureza->modeloRelatorio;

            // REL-02: Bloquear se atendimento está Paralisado ou Concluído
            if (in_array($atendimento->aten_status, [
                AtendimentoStatus::Paralisada->value,
                AtendimentoStatus::Concluida->value,
            ])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível criar relatório para atendimentos Paralisados ou Concluídos.',
                ], 422);
            }

            // REL-03: Bloquear > 1 relatório de período por atendimento
            if ((int) $modelo->mod_rel_tp_data === 1) {
                $existe = AtendimentoRelatorio::where('aten_rel_atendimento_id', $atendimento->aten_id)
                    ->whereHas('modeloRelatorio', fn($q) => $q->where('mod_rel_tp_data', 1))
                    ->exists();

                if ($existe) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Não é possível criar outro relatório, seu atendimento só permite um!',
                    ], 422);
                }
            }

            $rel = $this->repo->create([
                'aten_rel_atendimento_id'      => $atendimento->aten_id,
                'aten_rel_modelo_relatorio_id' => $modelo->mod_rel_id,
                'aten_rel_data'                => $request->aten_rel_data ?? now()->toDateString(),
                'aten_rel_status'              => 0,
            ]);

            AuditService::log('Relatorios', 'CRIAR', $rel->aten_rel_id);

            // Avança o atendimento para "Em andamento" somente se ainda não chegou lá
            if (!in_array($atendimento->aten_status, [
                AtendimentoStatus::EmAndamento->value,
                AtendimentoStatus::Concluida->value,
            ])) {
                $atendimento->update(['aten_status' => AtendimentoStatus::EmAndamento->value]);
            }

            return response()->json([
                'success'      => true,
                'message'      => 'Relatório criado com sucesso.',
                'redirect_url' => route('atendimentos-relatorios.show', $rel->aten_rel_id),
            ], 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar o relatório.',
            ], 500);
        }
    }

    public function show(int $id)
    {
        $atendimentoRelatorio = AtendimentoRelatorio::findOrFail($id);

        $atendimentoRelatorio->load([
            'modeloRelatorio',
            'atendimento',
            'atendimento.cliente',
            'atendimento.natureza',
            'atendimento.equipamentos',
            'atendimento.anexos',
            'horarios',
            'fotos',
            'videos',
            'anexos',
            'assinaturas',
        ]);

        if (!$atendimentoRelatorio->modeloRelatorio) {
            abort(500, 'Modelo de relatório não encontrado.');
        }

        $prazo = $atendimentoRelatorio->calcularPrazo();

        return view('atendimentos-relatorios.show', [
            'atendimentoRelatorio' => $atendimentoRelatorio,
            'prazoTotal'           => $prazo['prazo_total'],
            'prazoDecorrido'       => $prazo['prazo_decorrido'],
            'prazoAVencer'         => $prazo['prazo_a_vencer'],
            'ocorrencias'          => \App\Models\Ocorrencia::where('ocor_ativo', true)->orderBy('ocor_descricao')->get(),
        ]);
    }

    public function update(AtendimentoRelatorioRequest $request, int $atendimentos_relatorio)
    {
        $rel = $this->repo->update($atendimentos_relatorio, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Relatório atualizado com sucesso.',
            'data'    => $rel,
        ]);
    }

    public function updateDados(AtendimentoRelatorioDadosRequest $request, int $id)
    {
        try {
            $relatorio = AtendimentoRelatorio::findOrFail($id);

            $relatorio->update([
                'aten_rel_data' => $request->aten_rel_data,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dados atualizados com sucesso.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar os dados do relatório.',
            ], 500);
        }
    }

    public function updateHorarios(AtendimentoRelatorioHorariosRequest $request, int $id)
    {
        try {
            $relatorio = AtendimentoRelatorio::findOrFail($id);

            AtendimentoRelatorioHorario::updateOrCreate(
                ['aten_rel_hora_relatorio_id' => $relatorio->aten_rel_id],
                [
                    'aten_rel_hora_entrada'          => $request->aten_rel_hora_entrada,
                    'aten_rel_hora_inicio_intervalo' => $request->aten_rel_hora_inicio_intervalo,
                    'aten_rel_hora_fim_intervalo'    => $request->aten_rel_hora_fim_intervalo,
                    'aten_rel_hora_saida'            => $request->aten_rel_hora_saida,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Horários atualizados com sucesso.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar os horários do relatório.',
            ], 500);
        }
    }

    public function updateClima(AtendimentoRelatorioCondicaoClimaticaRequest $request, int $id)
    {
        try {
            $relatorio = AtendimentoRelatorio::findOrFail($id);

            $periodos = [
                1 => $request->clima_manha,
                2 => $request->clima_tarde,
                3 => $request->clima_noite,
            ];

            foreach ($periodos as $periodo => $condStr) {
                AtendimentoRelatorioCondicaoClimatica::updateOrCreate(
                    [
                        'aten_rel_clima_relatorio_id' => $relatorio->aten_rel_id,
                        'aten_rel_clima_periodo'      => $periodo,
                    ],
                    [
                        'aten_rel_clima_condicao' => CondicaoClimatica::fromLabel($condStr)->value,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Clima atualizado com sucesso.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar o clima do relatório.',
            ], 500);
        }
    }

    public function updateAssinaturas(AtendimentoRelatorioAssinaturasRequest $request, int $id)
    {
        try {
            $relatorio = AtendimentoRelatorio::with(['modeloRelatorio', 'assinaturas'])->findOrFail($id);
            $novoStatus = (int) $request->aten_rel_status;

            // REL-05: Técnicos precisam de assinaturas para aprovar; administradores podem dispensar
            if ($novoStatus === AtendimentoRelatorioStatus::Aprovado->value && auth()->user()->user_nivel_acesso !== 0) {
                $temResponsavel = $relatorio->assinaturaResponsavel() || $request->filled('assinatura_responsavel');
                $temCliente     = $relatorio->assinaturaCliente()     || $request->filled('assinatura_cliente');

                if (!$temResponsavel || !$temCliente) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Assinaturas do técnico e do cliente são obrigatórias para concluir o relatório.',
                    ], 422);
                }
            }

            $statusAnterior = $relatorio->aten_rel_status;
            $relatorio->update(['aten_rel_status' => $novoStatus]);

            if (
                $novoStatus === AtendimentoRelatorioStatus::Aprovado->value &&
                $statusAnterior !== AtendimentoRelatorioStatus::Aprovado->value
            ) {
                $relatorio->update(['aten_rel_dt_fim' => now()->toDateString()]);
            }

            AuditService::log('Relatorios', 'APROVAR', $id, ['status' => $statusAnterior], ['status' => $novoStatus]);

            $assinaturas = [];

            if ($request->filled('assinatura_responsavel')) {
                $assinaturas['responsavel'] = $this->media->saveSignatureImage(
                    $relatorio,
                    $request->assinatura_responsavel,
                    'responsavel'
                );
            }

            if ($request->filled('assinatura_cliente')) {
                $assinaturas['cliente'] = $this->media->saveSignatureImage(
                    $relatorio,
                    $request->assinatura_cliente,
                    'cliente'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Status e assinaturas atualizados com sucesso.',
                'data' => [
                    'status'      => $relatorio->aten_rel_status,
                    'assinaturas' => $assinaturas,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar assinaturas.',
            ], 500);
        }
    }

    public function updateTexto(Request $request, int $id, string $campo): \Illuminate\Http\JsonResponse
    {
        $campos = ['aten_rel_descricao', 'aten_rel_informacoes_adicionais'];
        if (!in_array($campo, $campos)) {
            return response()->json(['success' => false, 'message' => 'Campo inválido.'], 422);
        }
        try {
            $relatorio = AtendimentoRelatorio::findOrFail($id);
            $relatorio->update([$campo => $request->input('valor')]);
            return response()->json(['success' => true, 'message' => 'Salvo com sucesso.']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Erro ao salvar.'], 500);
        }
    }

    public function storeOcorrencia(AtendimentoRelatorioOcorrenciaRequest $request, int $id)
    {
        try {
            $ocorrenciaId = (int) $request->ocorrencia_id;
            $observacao   = $request->observacao ?? '';

            $relatorio = AtendimentoRelatorio::findOrFail($id);

            $exists = $relatorio->ocorrencias()
                ->where('ocorrencias.ocor_id', $ocorrenciaId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Essa ocorrência já foi adicionada neste relatório.'
                ], 422);
            }

            $ocorrencia = Ocorrencia::findOrFail($ocorrenciaId);

            $relatorio->ocorrencias()->attach($ocorrenciaId, [
                'aten_rel_ocor_observacao' => $observacao,
            ]);

            return response()->json([
                'message' => 'Ocorrência adicionada!',
                'data' => [
                    'ocorrencia_id' => $ocorrencia->ocor_id,
                    'ocorrencia'    => $ocorrencia->ocor_descricao,
                    'observacao'    => $observacao,
                ]
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao adicionar ocorrência.'
            ], 500);
        }
    }

    public function destroyOcorrencia(int $id, int $ocorrenciaId)
    {
        try {
            $relatorio = AtendimentoRelatorio::findOrFail($id);

            $relatorio->ocorrencias()->detach($ocorrenciaId);

            return response()->json([
                'message' => 'Ocorrência removida!'
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao remover ocorrência.'
            ], 500);
        }
    }

    public function getServicos(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        return response()->json([
            'data' => $relatorio->servicos()->orderBy('aten_rel_serv_id')->get(['aten_rel_serv_id', 'aten_rel_serv_descricao']),
        ]);
    }

    public function storeServico(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $request->validate(['descricao' => 'required|string|max:255']);
        try {
            $serv = AtendimentoRelatorioServico::create([
                'aten_rel_serv_relatorio_id' => $id,
                'aten_rel_serv_descricao'    => $request->input('descricao'),
            ]);
            return response()->json(['message' => 'Serviço adicionado!', 'item' => $serv]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao adicionar serviço.'], 500);
        }
    }

    public function destroyServico(int $id, int $itemId): \Illuminate\Http\JsonResponse
    {
        try {
            AtendimentoRelatorioServico::where('aten_rel_serv_id', $itemId)
                ->where('aten_rel_serv_relatorio_id', $id)
                ->delete();
            return response()->json(['message' => 'Serviço removido!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao remover serviço.'], 500);
        }
    }

    public function getPecas(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        return response()->json([
            'data' => $relatorio->pecas()->orderBy('aten_rel_peca_id')->get(['aten_rel_peca_id', 'aten_rel_peca_descricao']),
        ]);
    }

    public function storePeca(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $request->validate(['descricao' => 'required|string|max:255']);
        try {
            $peca = AtendimentoRelatorioPeca::create([
                'aten_rel_peca_relatorio_id' => $id,
                'aten_rel_peca_descricao'    => $request->input('descricao'),
            ]);
            return response()->json(['message' => 'Peça adicionada!', 'item' => $peca]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao adicionar peça.'], 500);
        }
    }

    public function destroyPeca(int $id, int $itemId): \Illuminate\Http\JsonResponse
    {
        try {
            AtendimentoRelatorioPeca::where('aten_rel_peca_id', $itemId)
                ->where('aten_rel_peca_relatorio_id', $id)
                ->delete();
            return response()->json(['message' => 'Peça removida!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao remover peça.'], 500);
        }
    }

    public function getDados(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = AtendimentoRelatorio::with('atendimento.cliente')->findOrFail($id);
        $prazo = $relatorio->calcularPrazo();

        return response()->json([
            'aten_rel_data_iso' => $relatorio->aten_rel_data->format('Y-m-d'),
            'dia_semana'        => getFormatDiaSemana($relatorio->aten_rel_data),
            'prazo_total'       => $prazo['prazo_total'],
            'prazo_decorrido'   => $prazo['prazo_decorrido'],
            'prazo_vencer'      => $prazo['prazo_a_vencer'],
        ]);
    }

    public function getHorarios(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = AtendimentoRelatorio::with('horarios')->findOrFail($id);
        $h = $relatorio->horarios;

        return response()->json([
            'entrada'          => $h?->aten_rel_hora_entrada          ? substr($h->aten_rel_hora_entrada, 0, 5)          : '',
            'inicio_intervalo' => $h?->aten_rel_hora_inicio_intervalo ? substr($h->aten_rel_hora_inicio_intervalo, 0, 5) : '',
            'fim_intervalo'    => $h?->aten_rel_hora_fim_intervalo    ? substr($h->aten_rel_hora_fim_intervalo, 0, 5)    : '',
            'saida'            => $h?->aten_rel_hora_saida            ? substr($h->aten_rel_hora_saida, 0, 5)            : '',
        ]);
    }

    public function getClimaData(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = AtendimentoRelatorio::with('climas')->findOrFail($id);

        $clima = ['manha' => null, 'tarde' => null, 'noite' => null];
        foreach ($relatorio->climas as $c) {
            $label = CondicaoClimatica::tryFrom($c->aten_rel_clima_condicao)?->label();
            if ($c->aten_rel_clima_periodo === 1) $clima['manha'] = $label;
            if ($c->aten_rel_clima_periodo === 2) $clima['tarde'] = $label;
            if ($c->aten_rel_clima_periodo === 3) $clima['noite'] = $label;
        }

        return response()->json($clima);
    }

    public function getOcorrenciasData(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = AtendimentoRelatorio::with('ocorrencias')->findOrFail($id);

        $ocorrencias = $relatorio->ocorrencias->map(fn($o) => [
            'ocorrencia_id' => (int) $o->ocor_id,
            'ocorrencia'    => (string) $o->ocor_descricao,
            'observacao'    => (string) ($o->pivot->aten_rel_ocor_observacao ?? ''),
        ])->values();

        return response()->json(['data' => $ocorrencias]);
    }

    public function getAssinaturasData(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = AtendimentoRelatorio::findOrFail($id);
        $resp = $relatorio->assinaturaResponsavel();
        $cli  = $relatorio->assinaturaCliente();

        return response()->json([
            'status' => $relatorio->aten_rel_status,
            'assinaturas' => [
                'responsavel' => $resp?->aten_rel_ass_path ? asset('storage/' . $resp->aten_rel_ass_path) : null,
                'cliente'     => $cli?->aten_rel_ass_path  ? asset('storage/' . $cli->aten_rel_ass_path)  : null,
            ],
        ]);
    }

    public function pdf(int $id)
    {
        $relatorio = AtendimentoRelatorio::with([
            'modeloRelatorio',
            'atendimento.cliente',
            'atendimento.natureza',
            'atendimento.usuario',
            'atendimento.equipamentos',
            'horarios',
            'climas',
            'ocorrencias',
            'servicos',
            'pecas',
            'fotos',
            'assinaturas',
        ])->findOrFail($id);

        $prazo = $relatorio->calcularPrazo();

        $pdf = Pdf::loadView('atendimentos-relatorios.pdf', [
            'relatorio'      => $relatorio,
            'prazoTotal'     => $prazo['prazo_total'],
            'prazoDecorrido' => $prazo['prazo_decorrido'],
            'prazoAVencer'   => $prazo['prazo_a_vencer'],
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'dpi'                  => 150,
        ]);

        $filename = 'relatorio_' . $relatorio->aten_rel_id . '_' . $relatorio->aten_rel_data->format('Y-m-d') . '.pdf';

        return $pdf->stream($filename);
    }

    public function uploadAnexos(Request $request, int $id)
    {
        $request->validate([
            'arquivos'   => ['nullable', 'array'],
            'arquivos.*' => ['file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv'],
            'fotos'      => ['nullable', 'array'],
            'fotos.*'    => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif'],
            'videos'     => ['nullable', 'array'],
            'videos.*'   => ['file', 'max:102400', 'mimes:mp4,mov,avi,mkv,webm'],
        ], [
            'arquivos.*.file'  => 'O arquivo enviado é inválido.',
            'arquivos.*.max'   => 'Cada arquivo não pode ultrapassar 20 MB.',
            'arquivos.*.mimes' => 'Tipo de arquivo não permitido. Formatos aceitos: PDF, DOC, DOCX, XLS, XLSX, TXT, CSV.',
            'fotos.*.file'     => 'A foto enviada é inválida.',
            'fotos.*.max'      => 'Cada foto não pode ultrapassar 10 MB.',
            'fotos.*.mimes'    => 'Tipo de imagem não permitido. Formatos aceitos: JPG, JPEG, PNG, WEBP, GIF.',
            'videos.*.file'    => 'O vídeo enviado é inválido.',
            'videos.*.max'     => 'Cada vídeo não pode ultrapassar 100 MB.',
            'videos.*.mimes'   => 'Tipo de vídeo não permitido. Formatos aceitos: MP4, MOV, AVI, MKV, WEBM.',
        ]);

        try {
            $relatorio = AtendimentoRelatorio::findOrFail($id);

            $saved = ['arquivos' => [], 'fotos' => [], 'videos' => [], 'erros' => []];

            // arquivos gerais
            if ($request->hasFile('arquivos')) {
                foreach ($request->file('arquivos') as $file) {
                    if (!$file->isValid()) continue;

                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $ext          = $file->getClientOriginalExtension();
                    $safeName     = Str::slug($originalName) . '_' . Str::random(8) . '.' . $ext;
                    $path         = $file->storeAs("atendimentos_relatorios/{$id}/arquivos", $safeName, 'public');
                    if ($path === false) {
                        $saved['erros'][] = "Falha ao gravar em disco: {$file->getClientOriginalName()}";
                        continue;
                    }

                    $anexo = AtendimentoRelatorioAnexo::create([
                        'aten_rel_anexo_relatorio_id' => $id,
                        'aten_rel_anexo_path' => $path,
                    ]);

                    $saved['arquivos'][] = [
                        'id'   => $anexo->aten_rel_anexo_id,
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'url'  => asset('storage/' . $path),
                    ];
                }
            }

            // fotos
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $file) {
                    if (!$file->isValid()) continue;

                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $ext          = $file->getClientOriginalExtension();
                    $safeName     = Str::slug($originalName) . '_' . Str::random(8) . '.' . $ext;
                    $path         = $file->storeAs("atendimentos_relatorios/{$id}/fotos", $safeName, 'public');
                    if ($path === false) {
                        $saved['erros'][] = "Falha ao gravar em disco: {$file->getClientOriginalName()}";
                        continue;
                    }

                    $full      = storage_path('app/public/' . $path);
                    $thumbDir  = "atendimentos_relatorios/{$id}/fotos/thumbs";
                    $thumbName = $safeName;
                    $thumbPath = $thumbDir . '/' . $thumbName;
                    $thumbFull = storage_path('app/public/' . $thumbPath);

                    ProcessarMidiaJob::dispatch('imagem', $full, $thumbFull, 400);

                    $foto = AtendimentoRelatorioFoto::create([
                        'aten_rel_foto_relatorio_id' => $id,
                        'aten_rel_foto_path'         => $path,
                    ]);

                    $saved['fotos'][] = [
                        'id'        => $foto->aten_rel_foto_id,
                        'name'      => $file->getClientOriginalName(),
                        'path'      => $path,
                        'url'       => asset('storage/' . $path),
                        'thumb_url' => asset('storage/' . $thumbPath),
                    ];
                }
            }

            // videos
            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $file) {
                    if (!$file->isValid()) continue;

                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $ext          = $file->getClientOriginalExtension();
                    $safeName     = Str::slug($originalName) . '_' . Str::random(8) . '.' . $ext;
                    $path         = $file->storeAs("atendimentos_relatorios/{$id}/videos", $safeName, 'public');
                    if ($path === false) {
                        $saved['erros'][] = "Falha ao gravar em disco: {$file->getClientOriginalName()}";
                        continue;
                    }

                    $full      = storage_path('app/public/' . $path);
                    $thumbDir  = "atendimentos_relatorios/{$id}/videos/thumbs";
                    $thumbName = $safeName . '.jpg';
                    $thumbPath = $thumbDir . '/' . $thumbName;
                    $thumbFull = storage_path('app/public/' . $thumbPath);

                    ProcessarMidiaJob::dispatch('video', $full, $thumbFull);

                    $video = AtendimentoRelatorioVideo::create([
                        'aten_rel_vid_relatorio_id' => $id,
                        'aten_rel_vid_path'         => $path,
                    ]);

                    $saved['videos'][] = [
                        'id'        => $video->aten_rel_vid_id,
                        'name'      => $file->getClientOriginalName(),
                        'path'      => $path,
                        'url'       => asset('storage/' . $path),
                        'thumb_url' => asset('storage/' . $thumbPath),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Uploads processados com sucesso.',
                'data'    => $saved,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Erro ao processar uploads.'], 500);
        }
    }

    public function getAnexos(int $id)
    {
        $relatorio = AtendimentoRelatorio::with(['anexos', 'fotos', 'videos'])->findOrFail($id);

        $arquivos = $relatorio->anexos->map(function ($anexo) {
            return [
                'id'   => $anexo->aten_rel_anexo_id,
                'name' => basename($anexo->aten_rel_anexo_path),
                'path' => $anexo->aten_rel_anexo_path,
                'url'  => asset('storage/' . $anexo->aten_rel_anexo_path),
            ];
        });

        $fotos = $relatorio->fotos->map(function ($foto) {
            $thumbPath = preg_replace('#/fotos/#', '/fotos/thumbs/', $foto->aten_rel_foto_path);
            $thumbUrl  = Storage::disk('public')->exists($thumbPath)
                ? asset('storage/' . $thumbPath)
                : asset('storage/' . $foto->aten_rel_foto_path);

            return [
                'id'        => $foto->aten_rel_foto_id,
                'name'      => basename($foto->aten_rel_foto_path),
                'path'      => $foto->aten_rel_foto_path,
                'url'       => asset('storage/' . $foto->aten_rel_foto_path),
                'thumb_url' => $thumbUrl,
            ];
        });

        $videos = $relatorio->videos->map(function ($video) {
            $thumbPath = preg_replace('#/videos/#', '/videos/thumbs/', $video->aten_rel_vid_path) . '.jpg';
            $thumbUrl  = Storage::disk('public')->exists($thumbPath)
                ? asset('storage/' . $thumbPath)
                : asset('img/video-placeholder.svg');

            return [
                'id'        => $video->aten_rel_vid_id,
                'name'      => basename($video->aten_rel_vid_path),
                'path'      => $video->aten_rel_vid_path,
                'url'       => asset('storage/' . $video->aten_rel_vid_path),
                'thumb_url' => $thumbUrl,
            ];
        });

        return response()->json([
            'arquivos' => $arquivos,
            'fotos'    => $fotos,
            'videos'   => $videos,
        ]);
    }

    public function destroyAnexo(int $id, string $type, int $itemId)
    {
        try {
            switch ($type) {
                case 'arquivo':
                    $item = AtendimentoRelatorioAnexo::where('aten_rel_anexo_id', $itemId)
                        ->where('aten_rel_anexo_relatorio_id', $id)
                        ->firstOrFail();
                    $path = $item->aten_rel_anexo_path;
                    break;
                case 'foto':
                    $item = AtendimentoRelatorioFoto::where('aten_rel_foto_id', $itemId)
                        ->where('aten_rel_foto_relatorio_id', $id)
                        ->firstOrFail();
                    $path      = $item->aten_rel_foto_path;
                    $thumbPath = preg_replace('#/fotos/#', '/fotos/thumbs/', $path);
                    break;
                case 'video':
                    $item = AtendimentoRelatorioVideo::where('aten_rel_vid_id', $itemId)
                        ->where('aten_rel_vid_relatorio_id', $id)
                        ->firstOrFail();
                    $path      = $item->aten_rel_vid_path;
                    $thumbPath = preg_replace('#/videos/#', '/videos/thumbs/', $path) . '.jpg';
                    break;
                default:
                    return response()->json(['success' => false, 'message' => 'Tipo de anexo inválido.'], 400);
            }

            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            if (isset($thumbPath) && Storage::disk('public')->exists($thumbPath)) {
                Storage::disk('public')->delete($thumbPath);
            }

            $item->delete();

            return response()->json(['success' => true, 'message' => 'Anexo removido com sucesso.']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Erro ao remover o anexo.'], 500);
        }
    }

    public function autoComplete(Request $request)
    {
        $term = trim((string) $request->get('term', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $rows = Atendimento::query()
            ->select([
                'atendimentos.aten_id',
                'atendimentos.aten_nr_proposta',
                'clientes.cli_nome',
            ])
            ->leftJoin('clientes', 'clientes.cli_id', '=', 'atendimentos.aten_cliente_id')
            ->where('clientes.cli_nome', 'like', "%{$term}%")
            ->whereNotIn('atendimentos.aten_status', [
                \App\Enums\AtendimentoStatus::Concluida->value,
                \App\Enums\AtendimentoStatus::Paralisada->value,
            ])
            ->orderBy('clientes.cli_nome')
            ->orderBy('atendimentos.aten_id', 'desc')
            ->limit(20)
            ->get();

        $payload = $rows->map(function ($r) {
            $nome  = $r->cli_nome ?: 'Sem cliente';
            $prop  = $r->aten_nr_proposta ? " - {$r->aten_nr_proposta}" : '';
            $text  = $nome . $prop;

            return [
                'id'    => $r->aten_id,
                'label' => $text,
                'value' => $text,
            ];
        });

        return response()->json($payload);
    }
}
