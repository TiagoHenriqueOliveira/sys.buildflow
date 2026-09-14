<?php

namespace App\Http\Controllers;

use App\Enums\SetorModelo;
use App\Http\Requests\ConfiguradorModeloRequest;
use App\Models\ConfigModelo;
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

        // Pedido do cliente (2026-09-11): banco de perguntas vai crescer pra
        // ~500 - nao carregamos mais a lista inteira aqui, so as perguntas
        // JA vinculadas a cada modelo (poucas, usadas pra montar o
        // data-perguntas do botao de editar). Novas perguntas sao buscadas
        // via configurador.perguntas.autocomplete.
        $modelos = ConfigModelo::query()
            ->with('perguntas')
            ->when($filtroNome !== '', fn ($q) => $q->where('cfg_mod_nome', 'like', "%{$filtroNome}%"))
            ->when($filtroSetor !== '', fn ($q) => $q->where('cfg_mod_setor', (int) $filtroSetor))
            ->orderBy('cfg_mod_nome')
            ->paginate(15)
            ->withQueryString();

        // old('perguntas') sobrevive a um retorno de validacao (ex.: nome
        // vazio) - sem isso, o usuario perderia as perguntas ja escolhidas
        // no autocomplete ao reenviar o form.
        $perguntasAntigas = \App\Models\ConfigPergunta::whereIn('cfg_perg_id', old('perguntas', []))
            ->get()
            ->map(fn ($p) => [
                'id' => $p->cfg_perg_id,
                'texto' => $p->cfg_perg_texto,
                'tipo' => $p->cfg_perg_tipo->label(),
                'eSessao' => $p->cfg_perg_e_sessao,
                'sessaoNome' => $p->cfg_perg_sessao_nome,
            ]);

        return view('configurador.modelos.index', [
            'modelos' => $modelos,
            'setores' => SetorModelo::cases(),
            'temPerguntasCadastradas' => \App\Models\ConfigPergunta::where('cfg_perg_ativo', 1)->exists(),
            'perguntasAntigas' => $perguntasAntigas,
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