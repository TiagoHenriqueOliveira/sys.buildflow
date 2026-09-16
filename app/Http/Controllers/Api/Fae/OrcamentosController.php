<?php

namespace App\Http\Controllers\Api\Fae;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrcamentoComentarioRequest;
use App\Http\Requests\OrcamentoRequest;
use App\Models\Orcamento;
use App\Repositories\OrcamentoRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CRM09 (mobile) - Orcamentos. Reaproveita OrcamentoRequest/
 * OrcamentoRepository (mesma validacao/regra do web).
 *
 * Diferenca do formulario dinamico de Atendimento (sessao 13): aqui as
 * respostas as perguntas do modelo (setor Comercial) sao um mapa simples
 * {pergunta_id: valor}, enviado JUNTO no mesmo POST/PUT do orcamento (ver
 * OrcamentoRepository::sincronizarRespostas) - nao existe conceito de
 * resposta repetivel nem foto por resposta pra Orcamento (OrcamentoResposta
 * nao tem relacao de fotos). Por isso nao ha endpoint /formulario nem
 * /respostas separados aqui, ao contrario do que o cronograma da sessao 15
 * cogitava antes de olhar o modelo real - as perguntas do tipo de orcamento
 * escolhido vem de GET /catalogos/tipos-orcamento (CatalogoController).
 */
class OrcamentosController extends Controller
{
    public function __construct(private OrcamentoRepository $repository)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Orcamento::query()->with(['cliente', 'vendedor', 'tipoOrcamento'])->orderByDesc('orc_id');

        if ($request->filled('cliente')) {
            $termo = $request->cliente;
            $query->whereHas('cliente', fn ($c) => $c->where('cli_nome', 'like', "%{$termo}%"));
        }

        $orcamentos = $query->paginate(20);

        return response()->json([
            'data' => collect($orcamentos->items())->map(fn ($o) => $this->formatResumo($o))->values(),
            'current_page' => $orcamentos->currentPage(),
            'last_page' => $orcamentos->lastPage(),
            'total' => $orcamentos->total(),
        ]);
    }

    public function show(int $orcamento): JsonResponse
    {
        $registro = Orcamento::with(['cliente', 'vendedor', 'tipoOrcamento', 'respostas', 'comentarios.autor', 'comentarios.usuarioAlertado', 'vendedoresAdicionais'])
            ->findOrFail($orcamento);

        return response()->json(['data' => $this->formatDetalhe($registro)]);
    }

    public function store(OrcamentoRequest $request): JsonResponse
    {
        $orcamento = $this->repository->create($request->validated());
        $orcamento->load(['cliente', 'vendedor', 'tipoOrcamento', 'respostas', 'comentarios.autor', 'vendedoresAdicionais']);

        return response()->json(['data' => $this->formatDetalhe($orcamento)], 201);
    }

    public function update(OrcamentoRequest $request, int $orcamento): JsonResponse
    {
        $registro = $this->repository->update($orcamento, $request->validated());
        $registro->load(['cliente', 'vendedor', 'tipoOrcamento', 'respostas', 'comentarios.autor', 'vendedoresAdicionais']);

        return response()->json(['data' => $this->formatDetalhe($registro)]);
    }

    public function storeComentario(OrcamentoComentarioRequest $request, int $id): JsonResponse
    {
        $orcamento = Orcamento::findOrFail($id);

        $comentario = $orcamento->comentarios()->create([
            'orc_com_autor_id' => Auth::id(),
            'orc_com_texto' => $request->input('orc_com_texto'),
            'orc_com_alerta_usuario_id' => $request->input('orc_com_alerta_usuario_id'),
            'orc_com_criado_em' => now(),
        ]);

        return response()->json(['data' => $this->formatComentario($comentario->load(['autor', 'usuarioAlertado']))], 201);
    }

    public function destroyComentario(int $id, int $comentarioId): JsonResponse
    {
        $orcamento = Orcamento::findOrFail($id);
        $orcamento->comentarios()->where('orc_com_id', $comentarioId)->firstOrFail()->delete();

        return response()->json(['message' => 'Comentário removido com sucesso.']);
    }

    private function formatResumo(Orcamento $o): array
    {
        return [
            'id' => $o->orc_id,
            'cliente' => ['id' => optional($o->cliente)->cli_id, 'nome' => optional($o->cliente)->cli_nome],
            'vendedor' => ['id' => optional($o->vendedor)->user_id, 'nome' => optional($o->vendedor)->user_nome],
            'tipo_orcamento' => optional($o->tipoOrcamento)->crm_tp_orc_nome,
            'nivel' => $o->orc_nivel?->value,
            'nivel_label' => $o->orc_nivel?->label(),
            'prazo_envio' => $o->orc_prazo_envio?->format('Y-m-d'),
            'criado_em' => $o->orc_criado_em?->format('d/m/Y H:i'),
        ];
    }

    private function formatDetalhe(Orcamento $o): array
    {
        return [
            ...$this->formatResumo($o),
            'cliente_id' => $o->orc_cliente_id,
            'vendedor_id' => $o->orc_vendedor_id,
            'tipo_orcamento_id' => $o->orc_tipo_orcamento_id,
            // Multipla escolha vem serializada em JSON (ver
            // OrcamentoRepository::sincronizarRespostas) - decodifica pro
            // cliente receber array de verdade, igual ao Web
            // (orcamentos/form.blade.php, $respostasExistentes).
            'respostas' => $o->respostas->mapWithKeys(function ($r) {
                $decodificado = json_decode((string) $r->orc_resp_valor, true);
                return [(string) $r->orc_resp_pergunta_id => is_array($decodificado) ? $decodificado : $r->orc_resp_valor];
            }),
            'vendedores_adicionais' => $o->vendedoresAdicionais->map(fn ($v) => ['id' => $v->user_id, 'nome' => $v->user_nome])->values(),
            'comentarios' => $o->comentarios->map(fn ($c) => $this->formatComentario($c))->values(),
        ];
    }

    private function formatComentario($c): array
    {
        return [
            'id' => $c->orc_com_id,
            'texto' => $c->orc_com_texto,
            'autor' => optional($c->autor)->user_nome,
            'alerta_usuario' => optional($c->usuarioAlertado)->user_nome,
            'criado_em' => $c->orc_com_criado_em?->format('d/m/Y H:i'),
        ];
    }
}