<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtendimentoRelatorioAtividadeRequest;
use App\Http\Requests\AtendimentoRelatorioComentarioRequest;
use App\Http\Requests\AtendimentoRelatorioCondicaoClimaticaRequest;
use App\Http\Requests\AtendimentoRelatorioDadosRequest;
use App\Http\Requests\AtendimentoRelatorioEquipamentoRequest;
use App\Http\Requests\AtendimentoRelatorioHorariosRequest;
use App\Http\Requests\AtendimentoRelatorioMaoObraRequest;
use App\Http\Requests\AtendimentoRelatorioOcorrenciaRequest;
use App\Http\Requests\AtendimentoRelatorioRequest;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AtendimentoRelatorioAtividade;
use App\Models\AtendimentoRelatorioComentario;
use App\Models\AtendimentoRelatorioCondicaoClimatica;
use App\Models\AtendimentoRelatorioHorario;
use App\Models\Equipamento;
use App\Models\Ocorrencia;
use App\Models\Ocupacao;
use App\Models\AtendimentoRelatorioFoto;
use App\Models\AtendimentoRelatorioVideo;
use App\Models\AtendimentoRelatorioAnexo;
use App\Models\AtendimentoRelatorioAssinatura;
use App\Repositories\AtendimentoRelatorioRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AtendimentosRelatoriosController extends Controller
{
    public function __construct(
        private readonly AtendimentoRelatorioRepository $repo
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $usuario = Auth::user();
            $filters = $usuario->user_nivel_acesso === 1
                ? ['usuario_id' => $usuario->user_id]
                : [];

            $rows = $this->repo->all($filters);

            $data = $rows->map(function ($r) {
                return [
                    'acoes' => view(
                        'atendimentos-relatorios.partials.acoes',
                        ['relatorio' => $r]
                    )->render(),

                    'data' => optional($r->aten_rel_data)->format('d/m/Y'),

                    'obra' => $r->atendimento?->aten_descricao ?? '-',

                    'natureza' => $r->atendimento?->natureza?->nat_aten_descricao ?? '-',

                    'setor' => $r->atendimento?->natureza?->tipoAtendimento?->tp_aten_descricao ?? '-',

                    'status' => match ($r->aten_rel_status) {
                        0 => '<span class="badge badge-info">Preenchendo</span>',
                        1 => '<span class="badge badge-warning">Revisar</span>',
                        2 => '<span class="badge badge-success">Aprovado</span>',
                        default => '-',
                    },
                ];
            });

            return response()->json(['data' => $data]);
        }

        return view('atendimentos-relatorios.index');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'aten_id'       => 'required|exists:atendimentos,aten_id',
                'aten_rel_data' => 'nullable|date|before_or_equal:today',
            ]);

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

            $rel = $this->repo->create([
                'aten_rel_atendimento_id'      => $atendimento->aten_id,
                'aten_rel_modelo_relatorio_id' => $atendimento->natureza->modeloRelatorio->mod_rel_id,
                'aten_rel_data'                => $request->aten_rel_data ?? now()->toDateString(),
                'aten_rel_status'              => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Relatório criado com sucesso.',
                'data'    => $rel,
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
            'atendimento.natureza.tipoAtendimento',
            'horarios',
            'fotos',
            'videos',
            'anexos',
            'assinaturas'
        ]);

        if (!$atendimentoRelatorio->modeloRelatorio) {
            abort(500, 'Modelo de relatório não encontrado.');
        }

        $inicio = Carbon::parse($atendimentoRelatorio->atendimento->aten_dt_inicio);
        $fim    = Carbon::parse($atendimentoRelatorio->atendimento->aten_dt_fim);
        $hoje   = Carbon::parse($atendimentoRelatorio->aten_rel_data);

        $prazoTotal = $inicio->diffInDays($fim);

        $prazoDecorrido = min(
            $inicio->diffInDays($hoje),
            $prazoTotal
        );

        $prazoAVencer = max(
            $prazoTotal - $prazoDecorrido,
            0
        );

        return view('atendimentos-relatorios.show', [
            'atendimentoRelatorio' => $atendimentoRelatorio,
            'prazoTotal'           => $prazoTotal,
            'prazoDecorrido'       => $prazoDecorrido,
            'prazoAVencer'         => $prazoAVencer,
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

            $condMap = [
                'ensolarado' => 1,
                'nublado'    => 2,
                'chuvoso'    => 3,
            ];

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
                        'aten_rel_clima_condicao' => $condMap[$condStr],
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

    public function updateAssinaturas(Request $request, int $id)
    {
        try {
            $request->validate([
                'aten_rel_status'        => 'required|integer|in:0,1,2',
                'assinatura_responsavel' => 'nullable|string',
                'assinatura_cliente'     => 'nullable|string',
            ]);

            $relatorio = AtendimentoRelatorio::findOrFail($id);
            $relatorio->update(['aten_rel_status' => $request->aten_rel_status]);

            $assinaturas = [];

            if ($request->filled('assinatura_responsavel')) {
                $assinaturas['responsavel'] = $this->saveSignatureImage(
                    $relatorio,
                    $request->assinatura_responsavel,
                    'responsavel'
                );
            }

            if ($request->filled('assinatura_cliente')) {
                $assinaturas['cliente'] = $this->saveSignatureImage(
                    $relatorio,
                    $request->assinatura_cliente,
                    'cliente'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Status e assinaturas atualizados com sucesso.',
                'data' => [
                    'status' => $relatorio->aten_rel_status,
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

    private function saveSignatureImage(AtendimentoRelatorio $relatorio, string $base64, string $tipo)
    {
        if (!preg_match('#^data:image\/(png|jpeg|jpg);base64,(.*)$#', $base64, $matches)) {
            throw new \RuntimeException('Formato de assinatura inválido.');
        }

        $mime = $matches[1];
        $data = base64_decode($matches[2]);
        $path = "atendimentos_relatorios/{$relatorio->aten_rel_id}/assinaturas/{$tipo}.png";

        $image = @imagecreatefromstring($data);
        if ($image === false) {
            throw new \RuntimeException('Não foi possível processar a imagem da assinatura.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // create a white background canvas
        $bg = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($bg, 255, 255, 255);
        imagefilledrectangle($bg, 0, 0, $width, $height, $white);

        // Ensure we compose correctly when source has alpha (preserve strokes over white)
        imagealphablending($image, true);
        imagesavealpha($image, true);

        imagealphablending($bg, true);
        imagesavealpha($bg, false); // do not keep alpha in final image

        // copy source onto white background
        imagecopy($bg, $image, 0, 0, 0, 0, $width, $height);

        $dir = dirname(storage_path('app/public/' . $path));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // save PNG without alpha channel so background stays white
        imagepng($bg, storage_path('app/public/' . $path));

        imagedestroy($image);
        imagedestroy($bg);

        // Busca pelo relatório + padrão do tipo (ex: "/responsavel." ou "/cliente.")
        // para evitar duplicatas sem precisar de coluna extra
        $existing = AtendimentoRelatorioAssinatura::where('aten_rel_ass_relatorio_id', $relatorio->aten_rel_id)
            ->where('aten_rel_ass_path', 'like', "%/{$tipo}.%")
            ->first();

        if ($existing) {
            $existing->update(['aten_rel_ass_path' => $path]);
        } else {
            AtendimentoRelatorioAssinatura::create([
                'aten_rel_ass_relatorio_id' => $relatorio->aten_rel_id,
                'aten_rel_ass_path'         => $path,
            ]);
        }

        return asset('storage/' . $path);
    }

    public function storeMaoObra(AtendimentoRelatorioMaoObraRequest $request, int $id)
    {
        try {
            $ocupId = (int) $request->ocup_id;
            $qtd    = (int) $request->qtd;

            $relatorio = AtendimentoRelatorio::findOrFail($id);

            $exists = $relatorio->ocupacoes()
                ->where('ocupacoes.ocup_id', $ocupId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Essa mão de obra já foi adicionada neste relatório.'
                ], 422);
            }

            $ocup = Ocupacao::with('tipoOcupacao')->findOrFail($ocupId);

            $relatorio->ocupacoes()->attach($ocupId, [
                'aten_rel_ocup_quantidade' => $qtd,
            ]);

            return response()->json([
                'message' => 'Mão de obra adicionada!',
                'data' => [
                    'ocup_id'  => $ocup->ocup_id,
                    'ocup'     => $ocup->ocup_descricao,
                    'tp_id'    => $ocup->ocup_tp_ocupacao_id,
                    'tp_label' => optional($ocup->tipoOcupacao)->tp_ocup_descricao,
                    'qtd'      => $qtd,
                ]
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao adicionar mão de obra.'
            ], 500);
        }
    }

    public function destroyMaoObra(int $id, int $ocupId)
    {
        try {
            $relatorio = AtendimentoRelatorio::findOrFail($id);

            $relatorio->ocupacoes()->detach($ocupId);

            return response()->json([
                'message' => 'Mão de obra removida!'
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao remover mão de obra.'
            ], 500);
        }
    }

    public function storeEquipamento(AtendimentoRelatorioEquipamentoRequest $request, int $id)
    {
        try {
            $equipId = (int) $request->equip_id;
            $qtd     = (int) $request->qtd;

            $relatorio = AtendimentoRelatorio::findOrFail($id);

            $exists = $relatorio->equipamentos()
                ->where('equipamentos.equip_id', $equipId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Esse equipamento já foi adicionado neste relatório.'
                ], 422);
            }

            $equip = Equipamento::findOrFail($equipId);

            $relatorio->equipamentos()->attach($equipId, [
                'aten_rel_equip_quantidade' => $qtd,
            ]);

            return response()->json([
                'message' => 'Equipamento adicionado!',
                'data' => [
                    'equip_id' => (int) $equip->equip_id,
                    'equip'    => (string) $equip->equip_descricao,
                    'qtd'      => $qtd,
                ]
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao adicionar equipamento.'
            ], 500);
        }
    }

    public function destroyEquipamento(int $id, int $equipId)
    {
        try {
            $relatorio = AtendimentoRelatorio::findOrFail($id);
            $relatorio->equipamentos()->detach($equipId);

            return response()->json([
                'message' => 'Equipamento removido!'
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao remover equipamento.'
            ], 500);
        }
    }

    public function storeAtividade(AtendimentoRelatorioAtividadeRequest $request, int $id)
    {
        try {
            $relatorio = AtendimentoRelatorio::findOrFail($id);

            $row = AtendimentoRelatorioAtividade::create([
                'aten_rel_ativ_relatorio_id' => $relatorio->aten_rel_id,
                'aten_rel_ativ_descricao'    => $request->aten_rel_ativ_descricao,
                'aten_rel_ativ_status'       => (int) $request->aten_rel_ativ_status,
            ]);

            return response()->json([
                'message' => 'Atividade adicionada!',
                'data'    => [
                    'id'        => (int) $row->aten_rel_ativ_id,
                    'descricao' => (string) $row->aten_rel_ativ_descricao,
                    'status'    => (int) $row->aten_rel_ativ_status,
                ]
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao adicionar atividade.'], 500);
        }
    }

    public function updateAtividade(AtendimentoRelatorioAtividadeRequest $request, int $id, int $ativId)
    {
        try {
            AtendimentoRelatorio::findOrFail($id);

            $row = AtendimentoRelatorioAtividade::where('aten_rel_ativ_id', $ativId)
                ->where('aten_rel_ativ_relatorio_id', $id)
                ->firstOrFail();

            $row->update([
                'aten_rel_ativ_descricao' => $request->aten_rel_ativ_descricao,
                'aten_rel_ativ_status'    => (int) $request->aten_rel_ativ_status,
            ]);

            return response()->json([
                'message' => 'Atividade atualizada!',
                'data'    => [
                    'id'        => (int) $row->aten_rel_ativ_id,
                    'descricao' => (string) $row->aten_rel_ativ_descricao,
                    'status'    => (int) $row->aten_rel_ativ_status,
                ]
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao atualizar atividade.'], 500);
        }
    }

    public function destroyAtividade(int $id, int $ativId)
    {
        try {
            AtendimentoRelatorio::findOrFail($id);

            $row = AtendimentoRelatorioAtividade::where('aten_rel_ativ_id', $ativId)
                ->where('aten_rel_ativ_relatorio_id', $id)
                ->firstOrFail();

            $row->delete();

            return response()->json(['message' => 'Atividade removida!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao remover atividade.'], 500);
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

    public function storeComentario(AtendimentoRelatorioComentarioRequest $request, int $id)
    {
        try {
            $relatorio = AtendimentoRelatorio::findOrFail($id);

            $row = AtendimentoRelatorioComentario::create([
                'aten_rel_com_relatorio_id' => $relatorio->aten_rel_id,
                'aten_rel_com_descricao'    => $request->aten_rel_com_descricao,
            ]);

            return response()->json([
                'message' => 'Comentário adicionado!',
                'data'    => [
                    'id'        => (int) $row->aten_rel_com_id,
                    'descricao' => (string) $row->aten_rel_com_descricao,
                ]
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao adicionar comentário.'
            ], 500);
        }
    }

    public function updateComentario(AtendimentoRelatorioComentarioRequest $request, int $id, int $comentarioId)
    {
        try {
            AtendimentoRelatorio::findOrFail($id);

            $row = AtendimentoRelatorioComentario::where('aten_rel_com_id', $comentarioId)
                ->where('aten_rel_com_relatorio_id', $id)
                ->firstOrFail();

            $row->update([
                'aten_rel_com_descricao' => $request->aten_rel_com_descricao,
            ]);

            return response()->json([
                'message' => 'Comentário atualizado!',
                'data'    => [
                    'id'        => (int) $row->aten_rel_com_id,
                    'descricao' => (string) $row->aten_rel_com_descricao,
                ]
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao atualizar comentário.'
            ], 500);
        }
    }

    public function destroyComentario(int $id, int $comentarioId)
    {
        try {
            AtendimentoRelatorio::findOrFail($id);

            $row = AtendimentoRelatorioComentario::where('aten_rel_com_id', $comentarioId)
                ->where('aten_rel_com_relatorio_id', $id)
                ->firstOrFail();

            $row->delete();

            return response()->json([
                'message' => 'Comentário removido!'
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao remover comentário.'
            ], 500);
        }
    }

    public function getData(int $id)
    {
        $relatorio = AtendimentoRelatorio::with([
            'atendimento',
            'horarios',
            'climas',
            'ocupacoes.tipoOcupacao',
            'equipamentos',
            'atividades',
            'ocorrencias',
            'comentarios',
        ])->findOrFail($id);

        $inicio = Carbon::parse($relatorio->atendimento->aten_dt_inicio);
        $fim    = Carbon::parse($relatorio->atendimento->aten_dt_fim);
        $base   = Carbon::parse($relatorio->aten_rel_data);

        $prazoTotal = $inicio->diffInDays($fim);
        $prazoDecorrido = min(
            $inicio->diffInDays($base),
            $prazoTotal
        );

        $condReverse = [
            1 => 'ensolarado',
            2 => 'nublado',
            3 => 'chuvoso',
        ];

        $climaPorPeriodo = [
            'manha' => null,
            'tarde' => null,
            'noite' => null,
        ];

        foreach ($relatorio->climas as $c) {
            if ($c->aten_rel_clima_periodo === 1) $climaPorPeriodo['manha'] = $condReverse[$c->aten_rel_clima_condicao] ?? null;
            if ($c->aten_rel_clima_periodo === 2) $climaPorPeriodo['tarde'] = $condReverse[$c->aten_rel_clima_condicao] ?? null;
            if ($c->aten_rel_clima_periodo === 3) $climaPorPeriodo['noite'] = $condReverse[$c->aten_rel_clima_condicao] ?? null;
        }

        $maoObra = $relatorio->ocupacoes->map(function ($o) {
            return [
                'ocup_id'  => $o->ocup_id,
                'ocup'     => $o->ocup_descricao,
                'tp_id'    => $o->ocup_tp_ocupacao_id,
                'tp_label' => optional($o->tipoOcupacao)->tp_ocup_descricao,
                'qtd'      => (int) $o->pivot->aten_rel_ocup_quantidade,
            ];
        })->values();

        $equipamentos = $relatorio->equipamentos->map(function ($e) {
            return [
                'equip_id' => $e->equip_id,
                'equip'    => $e->equip_descricao,
                'qtd'      => (int) $e->pivot->aten_rel_equip_quantidade,
            ];
        })->values();

        $atividades = $relatorio->atividades
            ->sortBy('aten_rel_ativ_id')
            ->map(function ($a) {
                return [
                    'id'        => (int) $a->aten_rel_ativ_id,
                    'descricao' => (string) $a->aten_rel_ativ_descricao,
                    'status'    => (int) $a->aten_rel_ativ_status,
                ];
            })->values();

        $ocorrencias = $relatorio->ocorrencias->map(function ($o) {
            return [
                'ocorrencia_id' => (int) $o->ocor_id,
                'ocorrencia'    => (string) $o->ocor_descricao,
                'observacao'    => (string) ($o->pivot->aten_rel_ocor_observacao ?? ''),
            ];
        })->values();

        $comentarios = $relatorio->comentarios
            ->sortBy('aten_rel_com_id')
            ->map(function ($c) {
                return [
                    'id'        => (int) $c->aten_rel_com_id,
                    'descricao' => (string) $c->aten_rel_com_descricao,
                ];
            })->values();

        $assinaturas = [
            'responsavel' => optional($relatorio->assinaturaResponsavel())->aten_rel_ass_path
                ? asset('storage/' . optional($relatorio->assinaturaResponsavel())->aten_rel_ass_path)
                : null,
            'cliente' => optional($relatorio->assinaturaCliente())->aten_rel_ass_path
                ? asset('storage/' . optional($relatorio->assinaturaCliente())->aten_rel_ass_path)
                : null,
        ];

        return response()->json([
            'dados' => [
                'aten_rel_data_iso' => $relatorio->aten_rel_data->format('Y-m-d'),
                'aten_rel_data_fmt' => $relatorio->aten_rel_data->format('d/m/Y'),
                'dia_semana'        => getFormatDiaSemana($relatorio->aten_rel_data),
                'prazo_total'       => $prazoTotal,
                'prazo_decorrido'   => $prazoDecorrido,
                'prazo_vencer'      => max($prazoTotal - $prazoDecorrido, 0),
            ],

            'horarios' => [
                'entrada'          => optional($relatorio->horarios)->aten_rel_hora_entrada,
                'inicio_intervalo' => optional($relatorio->horarios)->aten_rel_hora_inicio_intervalo,
                'fim_intervalo'    => optional($relatorio->horarios)->aten_rel_hora_fim_intervalo,
                'saida'            => optional($relatorio->horarios)->aten_rel_hora_saida,
            ],

            'clima'         => $climaPorPeriodo,
            'mao_obra'      => $maoObra,
            'equipamentos'  => $equipamentos,
            'atividades'    => $atividades,
            'ocorrencias' => $ocorrencias,
            'comentarios' => $comentarios,
            'status' => $relatorio->aten_rel_status,
            'assinaturas' => $assinaturas,
        ]);
    }

    public function pdf(int $id)
    {
        $relatorio = AtendimentoRelatorio::with([
            'modeloRelatorio',
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

        $inicio = Carbon::parse($relatorio->atendimento->aten_dt_inicio);
        $fim    = Carbon::parse($relatorio->atendimento->aten_dt_fim);
        $hoje   = Carbon::parse($relatorio->aten_rel_data);

        $prazoTotal     = $inicio->diffInDays($fim);
        $prazoDecorrido = min($inicio->diffInDays($hoje), $prazoTotal);
        $prazoAVencer   = max($prazoTotal - $prazoDecorrido, 0);

        $pdf = Pdf::loadView('atendimentos-relatorios.pdf', [
            'relatorio'      => $relatorio,
            'prazoTotal'     => $prazoTotal,
            'prazoDecorrido' => $prazoDecorrido,
            'prazoAVencer'   => $prazoAVencer,
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'       => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'   => true,
            'dpi'               => 150,
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
        ]);

        try {
            $relatorio = AtendimentoRelatorio::findOrFail($id);

            $saved = ['arquivos' => [], 'fotos' => [], 'videos' => []];

            // arquivos gerais
            if ($request->hasFile('arquivos')) {
                foreach ($request->file('arquivos') as $file) {
                    if (!$file->isValid()) continue;

                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $ext          = $file->getClientOriginalExtension();
                    $safeName     = Str::slug($originalName) . '_' . Str::random(8) . '.' . $ext;
                    $path         = $file->storeAs("atendimentos_relatorios/{$id}/arquivos", $safeName, 'public');

                    $anexo = AtendimentoRelatorioAnexo::create([
                        'aten_rel_anexo_relatorio_id' => $id,
                        'aten_rel_anexo_path' => $path,
                    ]);

                    $saved['arquivos'][] = [
                        'id' => $anexo->aten_rel_anexo_id,
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'url' => asset('storage/' . $path),
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

                    $full      = storage_path('app/public/' . $path);
                    $thumbDir  = "atendimentos_relatorios/{$id}/fotos/thumbs";
                    $thumbName = $safeName;
                    $thumbPath = $thumbDir . '/' . $thumbName;
                    $thumbFull = storage_path('app/public/' . $thumbPath);
                    $thumbCreated = $this->createImageThumbnail($full, $thumbFull, 400);

                    $foto = AtendimentoRelatorioFoto::create([
                        'aten_rel_foto_relatorio_id' => $id,
                        'aten_rel_foto_path' => $path,
                    ]);

                    $saved['fotos'][] = [
                        'id' => $foto->aten_rel_foto_id,
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'url' => asset('storage/' . $path),
                        'thumb_url' => $thumbCreated ? asset('storage/' . $thumbPath) : asset('storage/' . $path),
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

                    $full      = storage_path('app/public/' . $path);
                    $thumbDir  = "atendimentos_relatorios/{$id}/videos/thumbs";
                    $thumbName = $safeName . '.jpg';
                    $thumbPath = $thumbDir . '/' . $thumbName;
                    $thumbFull = storage_path('app/public/' . $thumbPath);
                    $thumbCreated = $this->createVideoThumbnail($full, $thumbFull);

                    $video = AtendimentoRelatorioVideo::create([
                        'aten_rel_vid_relatorio_id' => $id,
                        'aten_rel_vid_path' => $path,
                    ]);

                    $saved['videos'][] = [
                        'id' => $video->aten_rel_vid_id,
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'url' => asset('storage/' . $path),
                        'thumb_url' => $thumbCreated ? asset('storage/' . $thumbPath) : asset('img/video-placeholder.svg'),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Uploads processados com sucesso.',
                'data' => $saved,
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
                'id' => $anexo->aten_rel_anexo_id,
                'name' => basename($anexo->aten_rel_anexo_path),
                'path' => $anexo->aten_rel_anexo_path,
                'url' => asset('storage/' . $anexo->aten_rel_anexo_path),
            ];
        });

        $fotos = $relatorio->fotos->map(function ($foto) {
            $thumbPath = preg_replace('#/fotos/#', '/fotos/thumbs/', $foto->aten_rel_foto_path);
            $thumbUrl = file_exists(public_path('storage/' . $thumbPath))
                ? asset('storage/' . $thumbPath)
                : asset('storage/' . $foto->aten_rel_foto_path);

            return [
                'id' => $foto->aten_rel_foto_id,
                'name' => basename($foto->aten_rel_foto_path),
                'path' => $foto->aten_rel_foto_path,
                'url' => asset('storage/' . $foto->aten_rel_foto_path),
                'thumb_url' => $thumbUrl,
            ];
        });

        $videos = $relatorio->videos->map(function ($video) {
            $thumbPath = preg_replace('#/videos/#', '/videos/thumbs/', $video->aten_rel_vid_path) . '.jpg';
            $thumbUrl = file_exists(public_path('storage/' . $thumbPath))
                ? asset('storage/' . $thumbPath)
                : asset('img/video-placeholder.svg');

            return [
                'id' => $video->aten_rel_vid_id,
                'name' => basename($video->aten_rel_vid_path),
                'path' => $video->aten_rel_vid_path,
                'url' => asset('storage/' . $video->aten_rel_vid_path),
                'thumb_url' => $thumbUrl,
            ];
        });

        return response()->json([
            'arquivos' => $arquivos,
            'fotos' => $fotos,
            'videos' => $videos,
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
                    $path = $item->aten_rel_foto_path;
                    $thumbPath = preg_replace('#/fotos/#', '/fotos/thumbs/', $path);
                    break;
                case 'video':
                    $item = AtendimentoRelatorioVideo::where('aten_rel_vid_id', $itemId)
                        ->where('aten_rel_vid_relatorio_id', $id)
                        ->firstOrFail();
                    $path = $item->aten_rel_vid_path;
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

    private function createImageThumbnail(string $src, string $dest, int $maxWidth = 300)
    {
        try {
            if (!file_exists($src)) return false;
            $info = getimagesize($src);
            if (!$info) return false;

            [$width, $height] = [$info[0], $info[1]];
            $ratio = $height ? ($width / $height) : 1;
            $newWidth = $maxWidth;
            $newHeight = (int) ($newWidth / $ratio);

            $mime = $info['mime'];
            switch ($mime) {
                case 'image/jpeg':
                    $img = imagecreatefromjpeg($src);
                    break;
                case 'image/png':
                    $img = imagecreatefrompng($src);
                    break;
                case 'image/gif':
                    $img = imagecreatefromgif($src);
                    break;
                default:
                    return false;
            }

            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($thumb, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // ensure destination dir
            $dir = dirname($dest);
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            switch ($mime) {
                case 'image/jpeg':
                    imagejpeg($thumb, $dest, 85);
                    break;
                case 'image/png':
                    imagepng($thumb, $dest);
                    break;
                case 'image/gif':
                    imagegif($thumb, $dest);
                    break;
            }

            imagedestroy($img);
            imagedestroy($thumb);
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    private function createVideoThumbnail(string $videoPath, string $dest)
    {
        try {
            // try ffmpeg if available
            if (!file_exists($videoPath)) return false;

            $ffmpegCheck = null;
            if (function_exists('shell_exec')) {
                $ffmpegCheck = trim(@shell_exec('ffmpeg -version 2>&1'));
            }

            if ($ffmpegCheck) {
                $dir = dirname($dest);
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $cmd = sprintf('ffmpeg -y -i %s -ss 00:00:01 -vframes 1 %s 2>&1', escapeshellarg($videoPath), escapeshellarg($dest));
                @shell_exec($cmd);
                return file_exists($dest);
            }

            return false;
        } catch (\Throwable $e) {
            report($e);
            return false;
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
                'atendimentos.aten_descricao',
                'clientes.cli_nome',
            ])
            ->leftJoin('clientes', 'clientes.cli_id', '=', 'atendimentos.aten_cliente_id')
            ->where(function ($q) use ($term) {
                $q->where('clientes.cli_nome', 'like', "%{$term}%")
                    ->orWhere('atendimentos.aten_descricao', 'like', "%{$term}%");
            })
            ->orderBy('clientes.cli_nome')
            ->orderBy('atendimentos.aten_id', 'desc')
            ->limit(20)
            ->get();

        $payload = $rows->map(function ($r) {
            $cliente = $r->cli_nome ?: 'Sem cliente';
            $obra    = $r->aten_descricao ?: 'Sem descrição';

            $text = "{$cliente} ({$obra})";

            return [
                'id'    => $r->aten_id,
                'label' => $text,
                'value' => $text,
            ];
        });

        return response()->json($payload);
    }
}
