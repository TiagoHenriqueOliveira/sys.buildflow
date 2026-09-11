<?php

namespace App\Http\Controllers;

use App\Enums\AssinaturaTipo;
use App\Enums\AtendimentoRelatorioStatus;
use App\Enums\AtendimentoStatus;
use App\Enums\CondicaoClimatica;
use App\Http\Controllers\Concerns\GarantePosseDeAtendimento;
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
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
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
use App\Models\AtendimentoRelatorioDescricaoItem;
use App\Models\AtendimentoRelatorioResposta;
use App\Models\AtendimentoRelatorioRespostaFoto;
use App\Models\AtendimentoRelatorioCompartilhamento;
use App\Jobs\ProcessarMidiaJob;
use App\Repositories\AtendimentoRelatorioRepository;
use App\Services\MediaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AtendimentosRelatoriosController extends Controller
{
    use GarantePosseDeAtendimento;

    public function __construct(
        private readonly AtendimentoRelatorioRepository $repo,
        private readonly MediaService $media,
    ) {}

    // Item 2.2: busca o relatório já garantindo que o usuário autenticado
    // tem acesso ao atendimento dono dele — substitui o antigo padrão
    // "AtendimentoRelatorio::findOrFail($id)" repetido em cada método.
    private function relatorioComPosseGarantida(int $id, array $with = []): AtendimentoRelatorio
    {
        $relatorio = AtendimentoRelatorio::with(array_unique([...$with, 'atendimento']))->findOrFail($id);
        $this->garantirPosse($relatorio->atendimento);

        return $relatorio;
    }

    /**
     * Migrada pro pacote sbadmin/dashboard (ver CLAUDE.md, seção "Template
     * visual") seguindo o mesmo padrão das demais listagens: sem branch
     * DataTables-JSON, paginação nativa consumida por <x-sbadmin::table>;
     * a busca única (?busca=) foi substituída pelos filtros individuais por
     * coluna abaixo. Ordenação descartada (lista sempre por data + id,
     * igual ao antigo AtendimentoRelatorioRepository::query()). O filtro
     * "técnico só vê o seu, admin vê tudo" (Atendimento::idVisivelPara) foi
     * preservado.
     */
    public function index(Request $request)
    {
        $usuario = Auth::user();
        $filters = ($id = Atendimento::idVisivelPara($usuario)) !== null
            ? ['usuario_id' => $id]
            : [];

        // Filtros individuais por coluna (combinaveis em AND entre si): um
        // por coluna exibida em <x-sbadmin::table>, exceto "Ações". "Data" é
        // um valor exato (coluna DATE); "Natureza" e "Técnico" são <select>
        // pelo id (aten_natureza_id/aten_usuario_id, ja disponiveis via join
        // em AtendimentoRelatorioRepository::query()), nao mais texto livre;
        // os demais texto livre (LIKE) ou o select de Status.
        $filtroData = trim((string) $request->get('f_data', ''));
        $filtroCliente = trim((string) $request->get('f_cliente', ''));
        $filtroNrProposta = trim((string) $request->get('f_nr_proposta', ''));
        $filtroNatureza = $request->get('f_natureza', '');
        $filtroTecnico = $request->get('f_tecnico', '');
        $filtroStatus = $request->get('f_status', '');

        $relatorios = $this->repo->query($filters)
            ->when($filtroData !== '', fn ($q) => $q->where('atendimentos_relatorios.aten_rel_data', $filtroData))
            ->when($filtroCliente !== '', fn ($q) => $q->where('clientes.cli_nome', 'like', "%{$filtroCliente}%"))
            ->when($filtroNrProposta !== '', fn ($q) => $q->where('atendimentos.aten_nr_proposta', 'like', "%{$filtroNrProposta}%"))
            ->when($filtroNatureza !== '', fn ($q) => $q->where('atendimentos.aten_natureza_id', (int) $filtroNatureza))
            ->when($filtroTecnico !== '', fn ($q) => $q->where('atendimentos.aten_usuario_id', (int) $filtroTecnico))
            ->when($filtroStatus !== '', fn ($q) => $q->where('atendimentos_relatorios.aten_rel_status', (int) $filtroStatus))
            ->paginate(15)
            ->withQueryString();

        return view('atendimentos-relatorios.index', [
            'relatorios' => $relatorios,
            'filtroData' => $filtroData,
            'filtroCliente' => $filtroCliente,
            'filtroNrProposta' => $filtroNrProposta,
            'filtroNatureza' => $filtroNatureza,
            'filtroTecnico' => $filtroTecnico,
            'filtroStatus' => $filtroStatus,
            'temFiltro' => $filtroData !== '' || $filtroCliente !== '' || $filtroNrProposta !== ''
                || $filtroNatureza !== '' || $filtroTecnico !== '' || $filtroStatus !== '',
            'naturezasAtendimentos' => NaturezaAtendimento::select('nat_aten_id', 'nat_aten_descricao')
                ->where('nat_aten_ativo', 1)
                ->orderBy('nat_aten_descricao')
                ->get(),
            'usuarios' => Usuario::where('user_nivel_acesso', 1)->where('user_ativo', 1)->orderBy('user_nome')->get(),
        ]);
    }

    /**
     * Modal "Novo Relatório" migrado pro padrão redirect+flash (ver
     * CLAUDE.md, seção "Template visual") igual às telas simples — nada
     * mais depende deste endpoint retornar JSON (só o modal desta própria
     * tela), então as regras de negócio (REL-02/REL-03) passaram a devolver
     * erro via $errors->back() em vez de response()->json(...,422), sem
     * mudar a regra em si.
     */
    public function store(AtendimentoRelatorioStoreRequest $request)
    {
        $atendimento = Atendimento::query()
            ->with(['natureza.modeloRelatorio', 'natureza.configModelo'])
            ->where('aten_id', $request->aten_id)
            ->firstOrFail();

        // Item 2.2: sem isto, qualquer técnico autenticado conseguia criar
        // relatório em atendimento de OUTRO técnico só informando o aten_id
        // no corpo da requisição.
        $this->garantirPosse($atendimento);

        // Sessao 08 - Configurador substitui modelos_relatorios: o vinculo
        // exigido agora e natureza.configModelo (controla as secoes/
        // perguntas do relatorio). O modelo legado (modeloRelatorio) e
        // opcional a partir daqui - so usado abaixo para a regra REL-03, que
        // e sobre o TIPO de relatorio (diario/periodo), nao sobre secoes.
        if (
            !$atendimento->natureza ||
            !$atendimento->natureza->configModelo
        ) {
            return back()->withInput()->withErrors([
                'aten_id' => 'A natureza do atendimento não possui modelo do Configurador vinculado.',
            ]);
        }

        $configModelo = $atendimento->natureza->configModelo;
        $modeloLegado = $atendimento->natureza->modeloRelatorio;

        // REL-02: Bloquear se atendimento está Paralisado ou Concluído
        if (in_array($atendimento->aten_status, [
            AtendimentoStatus::Paralisada->value,
            AtendimentoStatus::Concluida->value,
        ])) {
            return back()->withInput()->withErrors([
                'aten_id' => 'Não é possível criar relatório para atendimentos Paralisados ou Concluídos.',
            ]);
        }

        // REL-03: Bloquear > 1 relatório de período por atendimento (só se
        // a natureza ainda tiver o modelo legado vinculado - conceito sem
        // equivalente no Configurador ainda).
        if ($modeloLegado && (int) $modeloLegado->mod_rel_tp_data === 1) {
            $existe = AtendimentoRelatorio::where('aten_rel_atendimento_id', $atendimento->aten_id)
                ->whereHas('modeloRelatorio', fn($q) => $q->where('mod_rel_tp_data', 1))
                ->exists();

            if ($existe) {
                return back()->withInput()->withErrors([
                    'aten_id' => 'Não é possível criar outro relatório, seu atendimento só permite um!',
                ]);
            }
        }

        try {
            $rel = $this->repo->create([
                'aten_rel_atendimento_id'      => $atendimento->aten_id,
                'aten_rel_modelo_relatorio_id' => $modeloLegado?->mod_rel_id,
                'aten_rel_config_modelo_id'    => $configModelo->cfg_mod_id,
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

            return redirect()
                ->route('atendimentos-relatorios.show', $rel->aten_rel_id)
                ->with('success', 'Relatório criado com sucesso.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['aten_id' => 'Erro ao criar o relatório.']);
        }
    }

    public function show(int $id)
    {
        $atendimentoRelatorio = AtendimentoRelatorio::findOrFail($id);

        $atendimentoRelatorio->load([
            'modeloRelatorio',
            'configModelo.perguntas.opcoes',
            'aprovadoPor',
            'respostas.fotos',
            'atendimento',
            'atendimento.cliente',
            'atendimento.natureza',
            'atendimento.equipamentos',
            'atendimento.anexos',
            'horarios',
            'climas',
            'servicos',
            'pecas',
            'ocorrencias',
            'fotos',
            'videos',
            'anexos',
            'assinaturas',
        ]);

        $this->garantirPosse($atendimentoRelatorio->atendimento);

        if (!$atendimentoRelatorio->modeloRelatorio && !$atendimentoRelatorio->configModelo) {
            abort(500, 'Modelo de relatório não encontrado.');
        }

        $prazo = $atendimentoRelatorio->calcularPrazo();

        return view('atendimentos-relatorios.show', [
            'atendimentoRelatorio' => $atendimentoRelatorio,
            'prazoTotal'           => $prazo['prazo_total'],
            'prazoDecorrido'       => $prazo['prazo_decorrido'],
            'prazoAVencer'         => $prazo['prazo_a_vencer'],
            'somenteLeitura'       => $atendimentoRelatorio->aten_rel_status === \App\Enums\AtendimentoRelatorioStatus::Aprovado->value,
            'ocorrencias'          => \App\Models\Ocorrencia::where('ocor_ativo', true)->orderBy('ocor_descricao')->get(),
        ]);
    }

    public function update(AtendimentoRelatorioRequest $request, int $atendimentos_relatorio)
    {
        $this->relatorioComPosseGarantida($atendimentos_relatorio);

        $rel = $this->repo->update($atendimentos_relatorio, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Relatório atualizado com sucesso.',
            'data'    => $rel,
        ]);
    }

    public function updateDados(AtendimentoRelatorioDadosRequest $request, int $id)
    {
        $relatorio = $this->relatorioComPosseGarantida($id);

        try {
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
        $relatorio = $this->relatorioComPosseGarantida($id);

        try {
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
        $relatorio = $this->relatorioComPosseGarantida($id);

        try {
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
        $relatorio = $this->relatorioComPosseGarantida($id, ['modeloRelatorio', 'assinaturas']);

        try {
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
            $relatorio->update([
                'aten_rel_status' => $novoStatus,
                'aten_rel_observacao_supervisor' => $request->input('observacao_supervisor', $relatorio->aten_rel_observacao_supervisor),
            ]);

            if (
                $novoStatus === AtendimentoRelatorioStatus::Aprovado->value &&
                $statusAnterior !== AtendimentoRelatorioStatus::Aprovado->value
            ) {
                $relatorio->update([
                    'aten_rel_dt_fim' => now()->toDateString(),
                    'aten_rel_aprovado_por' => auth()->id(),
                    'aten_rel_aprovado_em' => now(),
                ]);
            }

            AuditService::log('Relatorios', 'APROVAR', $id, ['status' => $statusAnterior], ['status' => $novoStatus]);

            $assinaturas = [];

            if ($request->filled('assinatura_responsavel')) {
                $assinaturas['responsavel'] = $this->media->saveSignatureImage(
                    $relatorio,
                    $request->assinatura_responsavel,
                    'responsavel',
                    $request->input('assinatura_responsavel_nome'),
                    $request->input('assinatura_responsavel_cpf'),
                );
            }

            if ($request->filled('assinatura_cliente')) {
                $assinaturas['cliente'] = $this->media->saveSignatureImage(
                    $relatorio,
                    $request->assinatura_cliente,
                    'cliente',
                    $request->input('assinatura_cliente_nome'),
                    $request->input('assinatura_cliente_cpf'),
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
        // aten_rel_descricao removido da lista (RF001): agora só é editável
        // via storeDescricaoItem/destroyDescricaoItem, para não contornar a
        // regra de retrocompatibilidade do RF004 (legado x itens novos).
        // aten_rel_observacao_interna (BF11) reaproveita este mesmo endpoint
        // genérico - nunca aparece no PDF assinado (ver pdf.blade.php).
        $campos = ['aten_rel_informacoes_adicionais', 'aten_rel_observacao_interna'];
        if (!in_array($campo, $campos)) {
            return response()->json(['success' => false, 'message' => 'Campo inválido.'], 422);
        }
        $relatorio = $this->relatorioComPosseGarantida($id);

        try {
            $relatorio->update([$campo => $request->input('valor')]);
            return response()->json(['success' => true, 'message' => 'Salvo com sucesso.']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Erro ao salvar.'], 500);
        }
    }

    public function storeOcorrencia(AtendimentoRelatorioOcorrenciaRequest $request, int $id)
    {
        $relatorio = $this->relatorioComPosseGarantida($id);

        try {
            $ocorrenciaId = (int) $request->ocorrencia_id;
            $observacao   = $request->observacao ?? '';

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
        $relatorio = $this->relatorioComPosseGarantida($id);

        try {
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
        $relatorio = $this->relatorioComPosseGarantida($id);
        return response()->json([
            'data' => $relatorio->servicos()->orderBy('aten_rel_serv_id')->get(['aten_rel_serv_id', 'aten_rel_serv_descricao']),
        ]);
    }

    public function storeServico(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $request->validate(['descricao' => 'required|string|max:255']);
        $this->relatorioComPosseGarantida($id);

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
        $this->relatorioComPosseGarantida($id);

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
        $relatorio = $this->relatorioComPosseGarantida($id);
        return response()->json([
            'data' => $relatorio->pecas()->orderBy('aten_rel_peca_id')->get(['aten_rel_peca_id', 'aten_rel_peca_descricao', 'aten_rel_peca_trocada']),
        ]);
    }

    public function storePeca(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $request->validate(['descricao' => 'required|string|max:255', 'trocada' => 'nullable|boolean']);
        $this->relatorioComPosseGarantida($id);

        try {
            $peca = AtendimentoRelatorioPeca::create([
                'aten_rel_peca_relatorio_id' => $id,
                'aten_rel_peca_descricao'    => $request->input('descricao'),
                'aten_rel_peca_trocada'      => $request->boolean('trocada'),
            ]);
            return response()->json(['message' => 'Peça adicionada!', 'item' => $peca]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao adicionar peça.'], 500);
        }
    }

    public function destroyPeca(int $id, int $itemId): \Illuminate\Http\JsonResponse
    {
        $this->relatorioComPosseGarantida($id);

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

    // ─── Itens de descrição (texto + foto opcional) — RF001 ────────────────

    public function getDescricaoItens(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = $this->relatorioComPosseGarantida($id);
        $itens = $relatorio->itensDescricao()->with('fotos')->orderBy('aten_rel_desc_id')->get();
        $usaDescricaoNova = $itens->isNotEmpty();

        // RF001/RF004: retrocompatibilidade — um relatório usa OU o campo
        // legado de texto único, OU a lista nova de itens, nunca os dois. O
        // critério é a existência de item novo, não a data do relatório.
        return response()->json([
            'legado' => $usaDescricaoNova ? null : $relatorio->aten_rel_descricao,
            'data'   => $usaDescricaoNova ? $itens->map(fn($it) => [
                'id'       => $it->aten_rel_desc_id,
                'texto'    => $it->aten_rel_desc_texto,
                'foto_url' => optional($it->fotos->first())->aten_rel_desc_foto_path
                    ? asset('midia/' . $it->fotos->first()->aten_rel_desc_foto_path)
                    : null,
            ]) : [],
        ]);
    }

    public function storeDescricaoItem(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'texto' => ['required', 'string'],
            'foto'  => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif'],
        ], [
            'texto.required' => 'Descreva o item antes de adicionar.',
            'foto.file'      => 'A foto enviada é inválida.',
            'foto.max'       => 'A foto não pode ultrapassar 10 MB.',
            'foto.mimes'     => 'Tipo de imagem não permitido. Formatos aceitos: JPG, JPEG, PNG, WEBP, GIF.',
        ]);

        $this->relatorioComPosseGarantida($id);

        try {
            $item = AtendimentoRelatorioDescricaoItem::create([
                'aten_rel_desc_relatorio_id' => $id,
                'aten_rel_desc_texto'        => $request->input('texto'),
                'aten_rel_desc_criado_em'    => now(),
            ]);

            $fotoUrl = null;
            if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
                $file         = $request->file('foto');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext          = $file->getClientOriginalExtension();
                $safeName     = Str::slug($originalName) . '_' . Str::random(8) . '.' . $ext;
                $path         = $file->storeAs("atendimentos_relatorios/{$id}/descricao", $safeName, 'public');
                if ($path === false) {
                    return response()->json(['message' => 'Falha ao gravar a foto em disco.'], 500);
                }
                $item->fotos()->create(['aten_rel_desc_foto_path' => $path]);
                $fotoUrl = asset('midia/' . $path);
            }

            return response()->json([
                'message' => 'Item adicionado!',
                'item'    => [
                    'id'       => $item->aten_rel_desc_id,
                    'texto'    => $item->aten_rel_desc_texto,
                    'foto_url' => $fotoUrl,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao adicionar item.'], 500);
        }
    }

    public function destroyDescricaoItem(int $id, int $itemId): \Illuminate\Http\JsonResponse
    {
        $this->relatorioComPosseGarantida($id);

        try {
            $item = AtendimentoRelatorioDescricaoItem::with('fotos')
                ->where('aten_rel_desc_id', $itemId)
                ->where('aten_rel_desc_relatorio_id', $id)
                ->first();
            foreach ($item?->fotos ?? [] as $foto) {
                Storage::disk('public')->delete($foto->aten_rel_desc_foto_path);
            }
            $item?->delete();
            return response()->json(['message' => 'Item removido!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao remover item.'], 500);
        }
    }

    // ─── Perguntas do modelo do Configurador (NC02/NC03) ───────────────────

    public function getRespostas(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = $this->relatorioComPosseGarantida($id, ['configModelo.perguntas.opcoes', 'respostas.fotos']);
        $respostasPorPergunta = $relatorio->respostas->groupBy('aten_rel_resp_pergunta_id');

        $mapaResposta = fn ($resposta) => [
            'id' => $resposta->aten_rel_resp_id,
            'valor' => $resposta->aten_rel_resp_valor,
            'fotos' => $resposta->fotos->map(fn ($f) => [
                'id' => $f->aten_rel_resp_foto_id,
                'url' => asset('midia/' . $f->aten_rel_resp_foto_path),
                'comentario' => $f->aten_rel_resp_foto_comentario,
            ])->values(),
        ];

        $perguntas = ($relatorio->configModelo?->perguntas ?? collect())->map(function ($pergunta) use ($respostasPorPergunta, $mapaResposta) {
            $respostas = ($respostasPorPergunta->get($pergunta->cfg_perg_id) ?? collect())
                ->sortBy('aten_rel_resp_id')
                ->map($mapaResposta)
                ->values();

            return [
                'id' => $pergunta->cfg_perg_id,
                'texto' => $pergunta->cfg_perg_texto,
                'tipo' => $pergunta->cfg_perg_tipo->value,
                'permite_anexo' => $pergunta->cfg_perg_permite_anexo,
                'repetivel' => $pergunta->cfg_perg_repetivel,
                'opcoes' => $pergunta->opcoes->map(fn ($o) => [
                    'id' => $o->cfg_perg_op_id,
                    'texto' => $o->cfg_perg_op_texto,
                ])->values(),
                // Pergunta nao repetivel: no maximo 1 resposta - mantem os
                // campos 'valor'/'fotos' no nivel da pergunta por
                // compatibilidade com quem so olha isso. Repetivel: usa
                // sempre a lista 'respostas'.
                'valor' => $respostas->first()['valor'] ?? null,
                'fotos' => $respostas->first()['fotos'] ?? [],
                'respostas' => $respostas,
            ];
        })->values();

        return response()->json(['data' => $perguntas]);
    }

    public function storeResposta(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'pergunta_id' => ['required', 'integer', 'exists:config_perguntas,cfg_perg_id'],
            'valor' => ['nullable', 'string'],
            'foto' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif'],
            'foto_comentario' => ['nullable', 'string', 'max:500'],
        ], [
            'foto.mimes' => 'Tipo de imagem não permitido. Formatos aceitos: JPG, JPEG, PNG, WEBP, GIF.',
        ]);
        $relatorio = $this->relatorioComPosseGarantida($id, ['configModelo.perguntas']);

        try {
            $perguntaId = (int) $request->input('pergunta_id');
            $pergunta = $relatorio->configModelo?->perguntas->firstWhere('cfg_perg_id', $perguntaId);

            if ($pergunta?->cfg_perg_repetivel) {
                // Pedido do cliente (2026-09-11): pergunta repetivel sempre
                // cria uma resposta NOVA - cada "adicionar" e uma linha
                // independente (mesmo espirito da antiga aba Descricao,
                // agora por pergunta). Remover uma resposta especifica e
                // via destroyResposta().
                $resposta = AtendimentoRelatorioResposta::create([
                    'aten_rel_resp_relatorio_id' => $id,
                    'aten_rel_resp_pergunta_id' => $perguntaId,
                    'aten_rel_resp_valor' => $request->input('valor'),
                ]);
            } else {
                // Nao repetivel: no maximo 1 resposta por pergunta - sem
                // constraint unica de banco (dropada pra liberar as
                // repetiveis), a garantia agora e so na aplicacao.
                $resposta = AtendimentoRelatorioResposta::firstOrNew([
                    'aten_rel_resp_relatorio_id' => $id,
                    'aten_rel_resp_pergunta_id' => $perguntaId,
                ]);
                $resposta->aten_rel_resp_valor = $request->input('valor');
                $resposta->save();
            }

            $fotoUrl = null;
            if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
                $file = $request->file('foto');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext = $file->getClientOriginalExtension();
                $safeName = Str::slug($originalName) . '_' . Str::random(8) . '.' . $ext;
                $path = $file->storeAs("atendimentos_relatorios/{$id}/perguntas", $safeName, 'public');
                if ($path === false) {
                    return response()->json(['message' => 'Falha ao gravar a foto em disco.'], 500);
                }
                $resposta->fotos()->create([
                    'aten_rel_resp_foto_path' => $path,
                    'aten_rel_resp_foto_comentario' => $request->input('foto_comentario'),
                ]);
                $fotoUrl = asset('midia/' . $path);
            }

            return response()->json([
                'message' => 'Resposta salva com sucesso.',
                'resposta_id' => $resposta->aten_rel_resp_id,
                'foto_url' => $fotoUrl,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao salvar resposta.'], 500);
        }
    }

    /**
     * Remove uma resposta inteira (usado pelo "+ Adicionar outra resposta"
     * de uma pergunta repetivel - cada entrada e removivel individualmente,
     * junto com suas fotos em disco).
     */
    public function destroyResposta(int $id, int $respostaId): \Illuminate\Http\JsonResponse
    {
        $this->relatorioComPosseGarantida($id);

        try {
            $resposta = AtendimentoRelatorioResposta::with('fotos')
                ->where('aten_rel_resp_relatorio_id', $id)
                ->where('aten_rel_resp_id', $respostaId)
                ->firstOrFail();

            foreach ($resposta->fotos as $foto) {
                if (Storage::disk('public')->exists($foto->aten_rel_resp_foto_path)) {
                    Storage::disk('public')->delete($foto->aten_rel_resp_foto_path);
                }
            }
            $resposta->delete();

            return response()->json(['message' => 'Resposta removida!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao remover resposta.'], 500);
        }
    }

    public function destroyRespostaFoto(int $id, int $fotoId): \Illuminate\Http\JsonResponse
    {
        $this->relatorioComPosseGarantida($id);

        try {
            $foto = AtendimentoRelatorioRespostaFoto::whereHas(
                'resposta',
                fn ($q) => $q->where('aten_rel_resp_relatorio_id', $id)
            )->where('aten_rel_resp_foto_id', $fotoId)->firstOrFail();

            if (Storage::disk('public')->exists($foto->aten_rel_resp_foto_path)) {
                Storage::disk('public')->delete($foto->aten_rel_resp_foto_path);
            }
            $foto->delete();

            return response()->json(['message' => 'Foto removida!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao remover foto.'], 500);
        }
    }

    // ─── BF07 - comprovante de compartilhamento ─────────────────────────────

    public function getCompartilhamentos(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = $this->relatorioComPosseGarantida($id, ['compartilhamentos.usuario']);

        return response()->json([
            'data' => $relatorio->compartilhamentos->map(fn ($c) => [
                'id' => $c->aten_rel_comp_id,
                'canal' => $c->aten_rel_comp_canal,
                'hash' => $c->aten_rel_comp_hash,
                'usuario' => optional($c->usuario)->user_nome,
                'criado_em' => $c->aten_rel_comp_criado_em->format('d/m/Y H:i'),
            ])->values(),
        ]);
    }

    public function storeCompartilhamento(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $request->validate(['canal' => ['nullable', 'string', 'max:50']]);
        $relatorio = $this->relatorioComPosseGarantida($id);

        try {
            $hash = hash('sha256', $relatorio->aten_rel_id . '|' . now()->timestamp . '|' . Str::random(16));

            $comp = AtendimentoRelatorioCompartilhamento::create([
                'aten_rel_comp_relatorio_id' => $id,
                'aten_rel_comp_usuario_id' => auth()->id(),
                'aten_rel_comp_canal' => $request->input('canal', 'painel-web'),
                'aten_rel_comp_hash' => $hash,
                'aten_rel_comp_criado_em' => now(),
            ]);

            return response()->json([
                'message' => 'Comprovante de compartilhamento gerado.',
                'item' => [
                    'id' => $comp->aten_rel_comp_id,
                    'canal' => $comp->aten_rel_comp_canal,
                    'hash' => $comp->aten_rel_comp_hash,
                    'criado_em' => $comp->aten_rel_comp_criado_em->format('d/m/Y H:i'),
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao gerar comprovante.'], 500);
        }
    }
    public function getDados(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = $this->relatorioComPosseGarantida($id, ['atendimento.cliente']);
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
        $relatorio = $this->relatorioComPosseGarantida($id, ['horarios']);
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
        $relatorio = $this->relatorioComPosseGarantida($id, ['climas']);

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
        $relatorio = $this->relatorioComPosseGarantida($id, ['ocorrencias']);

        $ocorrencias = $relatorio->ocorrencias->map(fn($o) => [
            'ocorrencia_id' => (int) $o->ocor_id,
            'ocorrencia'    => (string) $o->ocor_descricao,
            'observacao'    => (string) ($o->pivot->aten_rel_ocor_observacao ?? ''),
        ])->values();

        return response()->json(['data' => $ocorrencias]);
    }

    public function getAssinaturasData(int $id): \Illuminate\Http\JsonResponse
    {
        $relatorio = $this->relatorioComPosseGarantida($id);
        $resp = $relatorio->assinaturaResponsavel();
        $cli  = $relatorio->assinaturaCliente();

        return response()->json([
            'status' => $relatorio->aten_rel_status,
            'assinaturas' => [
                'responsavel' => $resp?->aten_rel_ass_path ? asset('midia/' . $resp->aten_rel_ass_path) : null,
                'cliente'     => $cli?->aten_rel_ass_path  ? asset('midia/' . $cli->aten_rel_ass_path)  : null,
            ],
        ]);
    }

    public function pdf(Request $request, int $id)
    {
        $relatorio = AtendimentoRelatorio::with([
            'modeloRelatorio',
            'configModelo.perguntas.opcoes',
            'respostas.fotos',
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
            'itensDescricao.fotos',
        ])->findOrFail($id);

        // RF005/RNF004 — a rota agora aceita token do app (Sanctum), além da
        // sessão do painel; sem essa checagem, qualquer técnico autenticado
        // conseguiria baixar o PDF de um atendimento de outro técnico só
        // trocando o ID na URL. Mesma regra de App\Policies\AtendimentoPolicy
        // usada em toda a API (Mcl e legada).
        $usuario = $request->user();
        $temAcesso = $relatorio->atendimento
            ? $usuario->can('acessar', $relatorio->atendimento)
            : (int) $usuario->user_nivel_acesso === 0;
        if (! $temAcesso) {
            abort(403, 'Você não tem acesso a este relatório.');
        }

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
        // Sessao 08 - pedido do cliente (feedback pos-demo): a aba Anexos
        // passa a aceitar SOMENTE fotos daqui pra frente. Arquivos/vídeos já
        // enviados antes continuam listados normalmente (getAnexos/pdf não
        // mudaram) - só o upload de novos itens desses dois tipos foi
        // removido.
        $request->validate([
            'fotos'      => ['nullable', 'array'],
            'fotos.*'    => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif'],
        ], [
            'fotos.*.file'     => 'A foto enviada é inválida.',
            'fotos.*.max'      => 'Cada foto não pode ultrapassar 10 MB.',
            'fotos.*.mimes'    => 'Tipo de imagem não permitido. Formatos aceitos: JPG, JPEG, PNG, WEBP, GIF.',
        ]);

        $relatorio = $this->relatorioComPosseGarantida($id);

        try {
            $saved = ['fotos' => [], 'erros' => []];

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
                        'url'       => asset('midia/' . $path),
                        'thumb_url' => asset('midia/' . $thumbPath),
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
        $relatorio = $this->relatorioComPosseGarantida($id, ['anexos', 'fotos', 'videos']);

        $arquivos = $relatorio->anexos->map(function ($anexo) {
            return [
                'id'   => $anexo->aten_rel_anexo_id,
                'name' => basename($anexo->aten_rel_anexo_path),
                'path' => $anexo->aten_rel_anexo_path,
                'url'  => asset('midia/' . $anexo->aten_rel_anexo_path),
            ];
        });

        $fotos = $relatorio->fotos->map(function ($foto) {
            $thumbPath = preg_replace('#/fotos/#', '/fotos/thumbs/', $foto->aten_rel_foto_path);
            $thumbUrl  = Storage::disk('public')->exists($thumbPath)
                ? asset('midia/' . $thumbPath)
                : asset('midia/' . $foto->aten_rel_foto_path);

            return [
                'id'        => $foto->aten_rel_foto_id,
                'name'      => basename($foto->aten_rel_foto_path),
                'path'      => $foto->aten_rel_foto_path,
                'url'       => asset('midia/' . $foto->aten_rel_foto_path),
                'thumb_url' => $thumbUrl,
            ];
        });

        $videos = $relatorio->videos->map(function ($video) {
            $thumbPath = preg_replace('#/videos/#', '/videos/thumbs/', $video->aten_rel_vid_path) . '.jpg';
            $thumbUrl  = Storage::disk('public')->exists($thumbPath)
                ? asset('midia/' . $thumbPath)
                : asset('img/video-placeholder.svg');

            return [
                'id'        => $video->aten_rel_vid_id,
                'name'      => basename($video->aten_rel_vid_path),
                'path'      => $video->aten_rel_vid_path,
                'url'       => asset('midia/' . $video->aten_rel_vid_path),
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
        $this->relatorioComPosseGarantida($id);

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
