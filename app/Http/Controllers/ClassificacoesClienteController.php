<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassificacaoClienteRequest;
use App\Models\ClassificacaoCliente;
use App\Repositories\ClassificacaoClienteRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * NC01 (pendência #3, ver CLAUDE.md) — cadastro das opções de
 * classificação de cliente. Mesmo padrão de OcorrenciasController: lista
 * simples (nome + ativo), modal de criação/edição, sem `create`/`edit`
 * como views próprias.
 */
class ClassificacoesClienteController extends Controller
{
    public function __construct(
        private ClassificacaoClienteRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        $filtroNome = trim((string) $request->get('f_nome', ''));

        $classificacoes = ClassificacaoCliente::query()
            ->when($filtroNome !== '', fn ($q) => $q->where('cla_cli_nome', 'like', "%{$filtroNome}%"))
            ->orderBy('cla_cli_nome')
            ->paginate(15)
            ->withQueryString();

        return view('classificacoes-cliente.index', [
            'classificacoes' => $classificacoes,
            'filtroNome' => $filtroNome,
            'temFiltro' => $filtroNome !== '',
        ]);
    }

    public function store(ClassificacaoClienteRequest $request): RedirectResponse
    {
        $classificacao = $this->repository->create($request->validated());

        return redirect()
            ->route('classificacoes-cliente.index')
            ->with('success', 'Classificação "'.$classificacao->cla_cli_nome.'" cadastrada com sucesso.');
    }

    public function update(ClassificacaoClienteRequest $request, int $id): RedirectResponse
    {
        $classificacao = $this->repository->update($id, $request->validated());

        return redirect()
            ->route('classificacoes-cliente.index')
            ->with('success', 'Classificação "'.$classificacao->cla_cli_nome.'" atualizada com sucesso.');
    }
}