<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GarantePosseDeAtendimento;
use App\Http\Controllers\Concerns\PersisteFiltros;
use App\Http\Requests\AtendimentoEquipamentoRequest;
use App\Http\Requests\AtendimentoRequest;
use App\Models\Atendimento;
use App\Models\AtendimentoAnexo;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use App\Repositories\AtendimentoRepository;
use App\Repositories\AtendimentoEquipamentoRepository;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AtendimentosController extends Controller
{
    use GarantePosseDeAtendimento;
    use PersisteFiltros;

    public function __construct(
        private AtendimentoRepository $repository,
        private AtendimentoEquipamentoRepository $equipamentoRepository,
    ) {}

    // Item 2.2: busca o atendimento já garantindo que o usuário autenticado
    // tem acesso a ele (mesmo padrão de AtendimentosRelatoriosController).
    private function atendimentoComPosseGarantida(int $id): Atendimento
    {
        $atendimento = Atendimento::findOrFail($id);
        $this->garantirPosse($atendimento);

        return $atendimento;
    }

    /**
     * Migrada pro pacote sbadmin/dashboard (ver CLAUDE.md, seção "Template
     * visual"): sem branch DataTables-JSON, paginação nativa consumida por
     * <x-sbadmin::table>; a busca única (?busca=) foi substituída pelos
     * filtros individuais por coluna abaixo. Ordenação descartada (lista
     * sempre por status + período, igual ao antigo
     * AtendimentoRepository::all()). O filtro "técnico só
     * vê o seu, admin vê tudo" (Atendimento::idVisivelPara) e o destaque
     * visual de atendimento em atraso (antes um fnRowCallback client-side
     * da DataTable) foram preservados na query/view.
     *
     * store/update e os demais endpoints AJAX (observações, anexos,
     * equipamentos) continuam retornando JSON — o modal de cadastro é um
     * fluxo com abas que precisa do aten_id imediatamente após criar o
     * atendimento pra liberar as abas de Observações/Equipamentos/Anexos
     * sem recarregar a página; convertê-los pro padrão redirect+flash das
     * telas simples removeria essa funcionalidade, não é só uma questão de
     * template. Ver resources/views/atendimentos/index.blade.php.
     */
    public function index(Request $request)
    {
        $usuarioLogado = Auth::user();
        $filtroUsuarioId = Atendimento::idVisivelPara($usuarioLogado);

        // Filtros individuais por coluna (combinaveis em AND entre si): um
        // por coluna exibida em <x-sbadmin::table>, exceto "Ações". "Período"
        // é um intervalo (aten_dt_inicio/aten_dt_fim); "Natureza" é um
        // <select> pelo id (nat_aten_id), os demais texto livre (LIKE) ou o
        // select de Status.
        $filtros = $this->filtrosPersistentes('atendimentos', ['f_natureza', 'f_tecnico', 'f_cliente', 'f_nr_proposta', 'f_periodo_de', 'f_periodo_ate', 'f_status']);
        $filtroNatureza = $filtros['f_natureza'];
        $filtroTecnico = $filtros['f_tecnico'];
        $filtroCliente = $filtros['f_cliente'];
        $filtroNrProposta = $filtros['f_nr_proposta'];
        $filtroPeriodoDe = $filtros['f_periodo_de'];
        $filtroPeriodoAte = $filtros['f_periodo_ate'];
        $filtroStatus = $filtros['f_status'];

        $atendimentos = $this->repository->query($filtroUsuarioId)
            ->when($filtroNatureza !== '', fn ($q) => $q->where('atendimentos.aten_natureza_id', (int) $filtroNatureza))
            ->when($filtroTecnico !== '', fn ($q) => $q->where('usuarios.user_nome', 'like', "%{$filtroTecnico}%"))
            ->when($filtroCliente !== '', fn ($q) => $q->where('clientes.cli_nome', 'like', "%{$filtroCliente}%"))
            ->when($filtroNrProposta !== '', fn ($q) => $q->where('atendimentos.aten_nr_proposta', 'like', "%{$filtroNrProposta}%"))
            ->when($filtroPeriodoDe !== '', fn ($q) => $q->where('atendimentos.aten_dt_inicio', '>=', $filtroPeriodoDe))
            ->when($filtroPeriodoAte !== '', fn ($q) => $q->where('atendimentos.aten_dt_fim', '<=', $filtroPeriodoAte))
            ->when($filtroStatus !== '', fn ($q) => $q->where('atendimentos.aten_status', (int) $filtroStatus))
            ->orderBy('aten_status')
            ->orderBy('aten_dt_inicio')
            ->orderBy('usuarios.user_nome')
            ->paginate(15)
            ->withQueryString();

        return view('atendimentos.index', [
            'atendimentos'          => $atendimentos,
            'filtroNatureza'        => $filtroNatureza,
            'filtroTecnico'         => $filtroTecnico,
            'filtroCliente'         => $filtroCliente,
            'filtroNrProposta'      => $filtroNrProposta,
            'filtroPeriodoDe'       => $filtroPeriodoDe,
            'filtroPeriodoAte'      => $filtroPeriodoAte,
            'filtroStatus'          => $filtroStatus,
            'temFiltro'             => $filtroNatureza !== '' || $filtroTecnico !== '' || $filtroCliente !== ''
                || $filtroNrProposta !== '' || $filtroPeriodoDe !== '' || $filtroPeriodoAte !== '' || $filtroStatus !== '',
            'usuarios'              => Usuario::where('user_nivel_acesso', 1)->where('user_ativo', 1)->orderBy('user_nome')->get(),
            'naturezasAtendimentos' => NaturezaAtendimento::select('nat_aten_id', 'nat_aten_descricao')
                ->where('nat_aten_ativo', 1)
                ->orderBy('nat_aten_descricao')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('atendimentos.form', [
            'atendimento' => new Atendimento(),
            ...$this->dadosApoioFormulario(),
        ]);
    }

    public function edit(int $id): View
    {
        $atendimento = $this->atendimentoComPosseGarantida($id);
        $atendimento->load('cliente');

        return view('atendimentos.form', [
            'atendimento' => $atendimento,
            ...$this->dadosApoioFormulario(),
        ]);
    }

    private function dadosApoioFormulario(): array
    {
        return [
            'usuarios' => Usuario::where('user_nivel_acesso', 1)->where('user_ativo', 1)->orderBy('user_nome')->get(),
            'naturezasAtendimentos' => NaturezaAtendimento::select('nat_aten_id', 'nat_aten_descricao')
                ->where('nat_aten_ativo', 1)
                ->orderBy('nat_aten_descricao')
                ->get(),
        ];
    }

    public function store(AtendimentoRequest $request)
    {
        try {
            $atendimento = $this->repository->create($request->validated());
            AuditService::log('Atendimentos', 'CRIAR', $atendimento->aten_id, [], $request->validated());

            $atendimento->load('cliente', 'usuario', 'natureza');

            return response()->json([
                'message'     => 'Atendimento cadastrado com sucesso!',
                'aten_id'     => $atendimento->aten_id,
                'atendimento' => [
                    'aten_id'          => $atendimento->aten_id,
                    'aten_natureza_id' => $atendimento->aten_natureza_id,
                    'aten_cliente_id'  => $atendimento->aten_cliente_id,
                    'aten_cliente_nome'=> optional($atendimento->cliente)->cli_nome ?? '',
                    'aten_usuario_id'  => $atendimento->aten_usuario_id,
                    'aten_status'      => $atendimento->aten_status,
                    'aten_nr_proposta'     => $atendimento->aten_nr_proposta ?? '',
                    'aten_contato'         => $atendimento->aten_contato ?? '',
                    'aten_responsavel'     => $atendimento->aten_responsavel ?? '',
                    'aten_telefone'        => $atendimento->aten_telefone ?? '',
                    'aten_entrega_tecnica' => (int) ($atendimento->aten_entrega_tecnica ?? 0),
                    'aten_endereco'        => $atendimento->aten_endereco ?? '',
                    'aten_dt_inicio'       => $atendimento->aten_dt_inicio->format('Y-m-d'),
                    'aten_dt_fim'          => $atendimento->aten_dt_fim->format('Y-m-d'),
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao cadastrar atendimento.'], 500);
        }
    }

    public function update(AtendimentoRequest $request, int $id)
    {
        try {
            $anterior = Atendimento::findOrFail($id)->toArray();
            $this->repository->update($id, $request->validated());
            AuditService::log('Atendimentos', 'EDITAR', $id, $anterior, $request->validated());
            return response()->json(['message' => 'Atendimento atualizado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao atualizar atendimento.'], 500);
        }
    }

    public function getObservacoes(int $id): JsonResponse
    {
        $atendimento = $this->atendimentoComPosseGarantida($id);
        return response()->json([
            'aten_obs_tecnica'    => $atendimento->aten_obs_tecnica,
            'aten_obs_cliente'    => $atendimento->aten_obs_cliente,
            'aten_obs_manutencao' => $atendimento->aten_obs_manutencao,
        ]);
    }

    public function updateObservacoes(Request $request, int $id): JsonResponse
    {
        $atendimento = $this->atendimentoComPosseGarantida($id);

        try {
            $atendimento->update([
                'aten_obs_tecnica'    => $request->input('aten_obs_tecnica'),
                'aten_obs_cliente'    => $request->input('aten_obs_cliente'),
                'aten_obs_manutencao' => $request->input('aten_obs_manutencao'),
            ]);
            return response()->json(['message' => 'Observações salvas com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao salvar observações.'], 500);
        }
    }

    public function getAnexos(int $id): JsonResponse
    {
        $this->atendimentoComPosseGarantida($id);

        $anexos = AtendimentoAnexo::where('aten_anexo_atendimento_id', $id)->orderByDesc('aten_anexo_id')->get();
        return response()->json(['anexos' => $anexos]);
    }

    public function uploadAnexos(Request $request, int $id): JsonResponse
    {
        // Whitelist de extensão — antes ausente aqui, ao contrário de todo
        // outro endpoint de upload do sistema (relatório web/Mcl), permitindo
        // qualquer tipo de arquivo nesta rota.
        $request->validate(['arquivos.*' => [
            'required', 'file', 'max:20480',
            'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,txt,csv,mp4,mov,avi,mkv,webm',
        ]]);

        $this->atendimentoComPosseGarantida($id);

        try {
            $criados = [];
            foreach ($request->file('arquivos', []) as $file) {
                $path = $file->store("atendimentos/{$id}/anexos", 'public');
                $criados[] = AtendimentoAnexo::create([
                    'aten_anexo_atendimento_id' => $id,
                    'aten_anexo_path'           => $path,
                    'aten_anexo_nome_original'  => $file->getClientOriginalName(),
                ]);
            }
            return response()->json(['message' => count($criados) . ' arquivo(s) enviado(s) com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao enviar arquivo(s).'], 500);
        }
    }

    public function destroyAnexo(int $id, int $itemId): JsonResponse
    {
        $this->atendimentoComPosseGarantida($id);

        try {
            $anexo = AtendimentoAnexo::where('aten_anexo_id', $itemId)
                ->where('aten_anexo_atendimento_id', $id)
                ->firstOrFail();

            Storage::disk('public')->delete($anexo->aten_anexo_path);
            $anexo->delete();

            return response()->json(['message' => 'Anexo removido com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao remover anexo.'], 500);
        }
    }

    public function storeEquipamento(AtendimentoEquipamentoRequest $request, int $id)
    {
        $this->atendimentoComPosseGarantida($id);

        try {
            $this->equipamentoRepository->create([
                'aten_equip_atendimento_id' => $id,
                'aten_equip_descricao'      => $request->input('aten_equip_descricao'),
            ]);

            $equipamentos = $this->equipamentoRepository->findByAtendimento($id);

            return response()->json([
                'message'     => 'Equipamento adicionado com sucesso!',
                'equipamentos'=> $equipamentos,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao adicionar equipamento.'], 500);
        }
    }

    public function destroyEquipamento(int $id, int $equipId)
    {
        $this->atendimentoComPosseGarantida($id);

        try {
            $this->equipamentoRepository->delete($equipId);

            $equipamentos = $this->equipamentoRepository->findByAtendimento($id);

            return response()->json([
                'message'     => 'Equipamento removido com sucesso!',
                'equipamentos'=> $equipamentos,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao remover equipamento.'], 500);
        }
    }

    public function getEquipamentos(int $id): JsonResponse
    {
        $this->atendimentoComPosseGarantida($id);

        try {
            $equipamentos = $this->equipamentoRepository->findByAtendimento($id);

            return response()->json([
                'equipamentos' => $equipamentos,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao carregar equipamentos.'], 500);
        }
    }

    /**
     * Pedido do cliente (2026-09-17): aba "Relatorios" no cadastro de
     * Atendimento - lista os relatorios ja preenchidos, com link pra tela
     * de preenchimento e pro PDF de cada um.
     */
    public function getRelatorios(int $id): JsonResponse
    {
        $atendimento = $this->atendimentoComPosseGarantida($id)->load('natureza', 'usuario');

        $relatorios = $atendimento->relatorios()
            ->orderByDesc('aten_rel_data')
            ->get()
            ->map(function ($relatorio) use ($atendimento) {
                // aten_rel_status e' castado como 'integer' puro no model
                // (nao como enum) - converte aqui na leitura.
                $status = \App\Enums\AtendimentoRelatorioStatus::from($relatorio->aten_rel_status);

                return [
                    'id' => $relatorio->aten_rel_id,
                    'data' => $relatorio->aten_rel_data->format('d/m/Y'),
                    // Natureza/Nº Proposta/Técnico são do ATENDIMENTO (o
                    // mesmo pra todo relatório desta lista) - pedido do
                    // cliente (2026-09-17) pra exibir junto de cada linha.
                    'natureza' => optional($atendimento->natureza)->nat_aten_descricao,
                    'nr_proposta' => $atendimento->aten_nr_proposta,
                    'tecnico' => optional($atendimento->usuario)->user_nome,
                    'status' => $status->label(),
                    // Tipo de badge do sbadmin (sbadmin-badge-*), nao o
                    // badgeClass() legado (Bootstrap "badge-*", de outro
                    // sistema visual - ver packages/sbadmin/.../badge.blade.php).
                    'status_tipo' => match ($status) {
                        \App\Enums\AtendimentoRelatorioStatus::Aprovado => 'success',
                        \App\Enums\AtendimentoRelatorioStatus::Revisar => 'warning',
                        default => 'info',
                    },
                    'url_preenchimento' => route('atendimentos-relatorios.show', $relatorio->aten_rel_id),
                    'url_pdf' => route('atendimentos-relatorios.pdf', $relatorio->aten_rel_id),
                ];
            });

        return response()->json(['relatorios' => $relatorios]);
    }
}
