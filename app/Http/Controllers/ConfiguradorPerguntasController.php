<?php

namespace App\Http\Controllers;

use App\Enums\TipoPergunta;
use App\Http\Requests\ConfiguradorPerguntaRequest;
use App\Models\ConfigPergunta;
use App\Repositories\ConfiguradorPerguntaRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguradorPerguntasController extends Controller
{
    public function __construct(
        private ConfiguradorPerguntaRepository $repository,
    ) {}

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