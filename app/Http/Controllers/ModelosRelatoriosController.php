<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModeloRelatorioRequest;
use App\Models\ModeloRelatorio;
use App\Repositories\ModeloRelatorioRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModelosRelatoriosController extends Controller
{
    public function __construct(
        private ModeloRelatorioRepository $repository,
    ) {}

    /**
     * Migrada pro pacote sbadmin/dashboard — mesmo padrão das demais telas
     * de cadastro simples (ver CLAUDE.md, seção "Template visual"): sem
     * branch DataTables-JSON, paginação nativa consumida por
     * <x-sbadmin::table>; ordenação descartada (lista sempre por
     * descrição). A busca única (?busca=) foi substituída pelo filtro
     * individual de descrição abaixo, mesmo padrão de clientes/atendimentos.
     */
    public function index(Request $request): View
    {
        $filtroDescricao = trim((string) $request->get('f_descricao', ''));

        $modelos = ModeloRelatorio::query()
            ->when($filtroDescricao !== '', fn ($query) => $query->where('mod_rel_descricao', 'like', "%{$filtroDescricao}%"))
            ->orderBy('mod_rel_descricao')
            ->paginate(15)
            ->withQueryString();

        return view('modelos_relatorios.index', [
            'modelos' => $modelos,
            'filtroDescricao' => $filtroDescricao,
            'temFiltro' => $filtroDescricao !== '',
        ]);
    }

    public function store(ModeloRelatorioRequest $request): RedirectResponse
    {
        $modelo = $this->repository->create($request->validated());

        return redirect()
            ->route('modelos-de-relatorios.index')
            ->with('success', 'Modelo de relatório "'.$modelo->mod_rel_descricao.'" cadastrado com sucesso.');
    }

    public function update(ModeloRelatorioRequest $request, int $id): RedirectResponse
    {
        $modelo = $this->repository->update($id, $request->validated());

        return redirect()
            ->route('modelos-de-relatorios.index')
            ->with('success', 'Modelo de relatório "'.$modelo->mod_rel_descricao.'" atualizado com sucesso.');
    }
}
