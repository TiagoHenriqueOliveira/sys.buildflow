<?php

namespace App\Http\Controllers;

use App\Enums\SetorModelo;
use App\Http\Requests\ConfiguradorModeloRequest;
use App\Models\ConfigModelo;
use App\Models\ConfigPergunta;
use App\Repositories\ConfiguradorModeloRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguradorModelosController extends Controller
{
    public function __construct(
        private ConfiguradorModeloRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        $filtroNome = trim((string) $request->get('f_nome', ''));
        $filtroSetor = $request->get('f_setor', '');

        $modelos = ConfigModelo::query()
            ->with('perguntas')
            ->when($filtroNome !== '', fn ($q) => $q->where('cfg_mod_nome', 'like', "%{$filtroNome}%"))
            ->when($filtroSetor !== '', fn ($q) => $q->where('cfg_mod_setor', (int) $filtroSetor))
            ->orderBy('cfg_mod_nome')
            ->paginate(15)
            ->withQueryString();

        return view('configurador.modelos.index', [
            'modelos' => $modelos,
            'setores' => SetorModelo::cases(),
            'perguntasDisponiveis' => ConfigPergunta::where('cfg_perg_ativo', 1)->orderBy('cfg_perg_texto')->get(),
            'filtroNome' => $filtroNome,
            'filtroSetor' => $filtroSetor,
            'temFiltro' => $filtroNome !== '' || $filtroSetor !== '',
        ]);
    }

    public function store(ConfiguradorModeloRequest $request): RedirectResponse
    {
        $modelo = $this->repository->create($request->validated());

        return redirect()
            ->route('configurador.modelos.index')
            ->with('success', 'Modelo "'.$modelo->cfg_mod_nome.'" cadastrado com sucesso.');
    }

    public function update(ConfiguradorModeloRequest $request, int $id): RedirectResponse
    {
        $modelo = $this->repository->update($id, $request->validated());

        return redirect()
            ->route('configurador.modelos.index')
            ->with('success', 'Modelo "'.$modelo->cfg_mod_nome.'" atualizado com sucesso.');
    }
}