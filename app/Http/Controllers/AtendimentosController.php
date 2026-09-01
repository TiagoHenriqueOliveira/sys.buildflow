<?php

namespace App\Http\Controllers;

use App\Enums\AtendimentoStatus;
use App\Http\Controllers\Concerns\GarantePosseDeAtendimento;
use App\Http\Requests\AtendimentoEquipamentoRequest;
use App\Http\Requests\AtendimentoRequest;
use App\Models\Atendimento;
use App\Models\AtendimentoAnexo;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use App\Repositories\AtendimentoRepository;
use App\Repositories\AtendimentoEquipamentoRepository;
use App\Services\AuditService;
use App\Services\DataTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AtendimentosController extends Controller
{
    use GarantePosseDeAtendimento;

    public function __construct(
        private AtendimentoRepository $repository,
        private AtendimentoEquipamentoRepository $equipamentoRepository,
        private DataTableService $dataTable,
    ) {}

    // Item 2.2: busca o atendimento já garantindo que o usuário autenticado
    // tem acesso a ele (mesmo padrão de AtendimentosRelatoriosController).
    private function atendimentoComPosseGarantida(int $id): Atendimento
    {
        $atendimento = Atendimento::findOrFail($id);
        $this->garantirPosse($atendimento);

        return $atendimento;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            try {
            $usuario = Auth::user();
            $filtroUsuarioId = $usuario->user_nivel_acesso === 1 ? $usuario->user_id : null;

            return response()->json(
                $this->dataTable->process(
                    $request,
                    $this->repository->query($filtroUsuarioId),
                    searchable: [
                        'clientes.cli_nome',
                        'usuarios.user_nome',
                        'atendimentos.aten_nr_proposta',
                        'naturezas_atendimentos.nat_aten_descricao',
                    ],
                    searchableRaw: [
                        "CASE atendimentos.aten_status WHEN 0 THEN 'Não iniciada' WHEN 1 THEN 'Paralisada' WHEN 2 THEN 'Em andamento' WHEN 3 THEN 'Concluída' END",
                    ],
                    orderable:  [
                        'acoes'       => null,
                        'natureza'    => null,
                        'usuario'     => 'usuarios.user_nome',
                        'cliente'     => null,
                        'nr_proposta' => 'aten_nr_proposta',
                        'periodo'     => 'aten_dt_inicio',
                        'status'      => 'aten_status',
                    ],
                    mapper: fn($a) => [
                        'acoes'        => view('atendimentos.partials.acoes', compact('a'))->render(),
                        'natureza'     => e(optional($a->natureza)->nat_aten_descricao),
                        'usuario'      => e(optional($a->usuario)->user_nome),
                        'cliente'      => e(optional($a->cliente)->cli_nome),
                        'nr_proposta'  => e($a->aten_nr_proposta ?? ''),
                        'periodo'      => $a->aten_dt_inicio->format('d/m/Y') . ' - ' . $a->aten_dt_fim->format('d/m/Y'),
                        'status'       => ($s = AtendimentoStatus::tryFrom($a->aten_status))
                            ? '<span class="badge ' . $s->badgeClass() . '">' . $s->label() . '</span>'
                            : '-',
                        'aten_status'  => $a->aten_status,
                        'aten_dt_fim'  => $a->aten_dt_fim->format('Y-m-d'),
                    ],
                )
            );
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['message' => 'DataTable error: ' . $e->getMessage()], 500);
            }
        }

        return view('atendimentos.index', [
            'usuarios'              => Usuario::where('user_nivel_acesso', 1)->where('user_ativo', 1)->orderBy('user_nome')->get(),
            'naturezasAtendimentos' => NaturezaAtendimento::select('nat_aten_id', 'nat_aten_descricao')
                ->where('nat_aten_ativo', 1)
                ->orderBy('nat_aten_descricao')
                ->get(),
        ]);
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
}
