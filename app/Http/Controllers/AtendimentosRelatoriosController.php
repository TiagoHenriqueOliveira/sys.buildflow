<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtendimentoRelatorioAtividadeRequest;
use App\Http\Requests\AtendimentoRelatorioCondicaoClimaticaRequest;
use App\Http\Requests\AtendimentoRelatorioDadosRequest;
use App\Http\Requests\AtendimentoRelatorioEquipamentoRequest;
use App\Http\Requests\AtendimentoRelatorioHorariosRequest;
use App\Http\Requests\AtendimentoRelatorioMaoObraRequest;
use App\Http\Requests\AtendimentoRelatorioOcorrenciaRequest;
use App\Http\Requests\AtendimentoRelatorioRequest;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioAtividade;
use App\Models\AtendimentoRelatorioCondicaoClimatica;
use App\Models\AtendimentoRelatorioHorario;
use App\Models\Equipamento;
use App\Models\Ocorrencia;
use App\Models\Ocupacao;
use App\Repositories\AtendimentoRelatorioRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AtendimentosRelatoriosController extends Controller
{
    public function __construct(
        private readonly AtendimentoRelatorioRepository $repo
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rows = $this->repo->all();

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
                        0 => '<span class="badge badge-warning">Preenchendo</span>',
                        1 => '<span class="badge badge-info">Revisar</span>',
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
                'aten_rel_data' => 'nullable|date',
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
            'horarios'
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
            $observacao   = $request->observacao;

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
                    'ocorrencia_id' => (int) $ocorrencia->ocor_id,
                    'ocorrencia'    => (string) $ocorrencia->ocor_descricao,
                    'observacao'    => (string) ($observacao ?? ''),
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

    public function getData(int $id)
    {
        $relatorio = AtendimentoRelatorio::with([
            'atendimento',
            'horarios',
            'climas',
            'ocupacoes.tipoOcupacao',
            'equipamentos',
            'atividades',
            'ocorrencias'
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
        ]);
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
