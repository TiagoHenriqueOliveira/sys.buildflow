<?php

namespace App\Http\Controllers;

use App\Enums\TipoPergunta;
use App\Http\Requests\ConfiguradorPerguntaRequest;
use App\Models\ConfigPergunta;
use App\Repositories\ConfiguradorPerguntaRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguradorPerguntasController extends Controller
{
    public function __construct(
        private ConfiguradorPerguntaRepository $repository,
    ) {}

    /**
     * Pedido do cliente (2026-09-11): banco de perguntas vai crescer pra
     * ~500 registros — um checklist estatico (usado ate aqui no cadastro de
     * modelo) fica inutilizavel nesse volume. Autocomplete busca por texto
     * em vez de listar tudo de uma vez, mesmo padrao de
     * ClientesController::autoComplete().
     */
    public function autoComplete(Request $request): JsonResponse
    {
        $term = trim((string) $request->get('term', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $perguntas = ConfigPergunta::where('cfg_perg_ativo', 1)
            ->where('cfg_perg_texto', 'like', "%{$term}%")
            ->orderBy('cfg_perg_texto')
            ->limit(20)
            ->get();

        return response()->json($perguntas->map(fn ($p) => [
            'id' => $p->cfg_perg_id,
            'texto' => $p->cfg_perg_texto,
            'tipo' => $p->cfg_perg_tipo->label(),
            'eSessao' => $p->cfg_perg_e_sessao,
            'sessaoNome' => $p->cfg_perg_sessao_nome,
        ])->values());
    }

    public function index(Request $request): View
    {
        $filtroTexto = trim((string) $request->get('f_texto', ''));
        $filtroTipo = $request->get('f_tipo', '');

        $perguntas = ConfigPergunta::query()
            ->with('opcoes')
            ->when($filtroTexto !== '', fn ($q) => $q->where('cfg_perg_texto', 'like', "%{$filtroTexto}%"))
            ->when($filtroTipo !== '', fn ($q) => $q->where('cfg_perg_tipo', (int) $filtroTipo))
            ->orderBy('cfg_perg_id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('configurador.perguntas.index', [
            'perguntas' => $perguntas,
            'tiposPergunta' => TipoPergunta::cases(),
            'filtroTexto' => $filtroTexto,
            'filtroTipo' => $filtroTipo,
            'temFiltro' => $filtroTexto !== '' || $filtroTipo !== '',
        ]);
    }

    public function store(ConfiguradorPerguntaRequest $request): RedirectResponse
    {
        $this->repository->create($request->validated());

        return redirect()
            ->route('configurador.perguntas.index')
            ->with('success', 'Pergunta cadastrada com sucesso.');
    }

    public function update(ConfiguradorPerguntaRequest $request, int $id): RedirectResponse
    {
        $this->repository->update($id, $request->validated());

        return redirect()
            ->route('configurador.perguntas.index')
            ->with('success', 'Pergunta atualizada com sucesso.');
    }
}