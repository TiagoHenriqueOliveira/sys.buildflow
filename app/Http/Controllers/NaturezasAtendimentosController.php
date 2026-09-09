<?php

namespace App\Http\Controllers;

use App\Http\Requests\NaturezaAtendimentoRequest;
use App\Models\ModeloRelatorio;
use App\Models\NaturezaAtendimento;
use App\Repositories\NaturezaAtendimentoRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NaturezasAtendimentosController extends Controller
{
    public function __construct(
        private NaturezaAtendimentoRepository $repository,
    ) {}

    /**
     * Migrada pro pacote sbadmin/dashboard (ver CLAUDE.md, seção "Template
     * visual") seguindo o mesmo padrão de clientes.index(): sem branch
     * DataTables-JSON, paginação nativa do Eloquent consumida por
     * <x-sbadmin::table>. Busca client-side e ordenação por coluna que a
     * DataTables oferecia não têm equivalente direto no novo componente —
     * busca reimplementada via querystring (?busca=) na descrição,
     * ordenação descartada nesta fase (lista sempre por descrição).
     */
    public function index(Request $request): View
    {
        $busca = trim((string) $request->get('busca', ''));

        $naturezas = NaturezaAtendimento::query()
            ->with(['modeloRelatorio'])
            ->when($busca !== '', fn ($query) => $query->where('nat_aten_descricao', 'like', "%{$busca}%"))
            ->orderBy('nat_aten_descricao')
            ->paginate(15)
            ->withQueryString();

        $modelosRelatorios = ModeloRelatorio::where('mod_rel_ativo', 1)
            ->orderBy('mod_rel_descricao')
            ->get();

        return view('naturezas_atendimentos.index', [
            'naturezas' => $naturezas,
            'modelosRelatorios' => $modelosRelatorios,
            'busca' => $busca,
        ]);
    }

    public function store(NaturezaAtendimentoRequest $request): RedirectResponse
    {
        $natureza = $this->repository->create($request->validated());

        return redirect()
            ->route('naturezas-dos-atendimentos.index')
            ->with('success', 'Natureza de atendimento "'.$natureza->nat_aten_descricao.'" cadastrada com sucesso.');
    }

    public function update(NaturezaAtendimentoRequest $request, int $id): RedirectResponse
    {
        $natureza = $this->repository->update($id, $request->validated());

        return redirect()
            ->route('naturezas-dos-atendimentos.index')
            ->with('success', 'Natureza de atendimento "'.$natureza->nat_aten_descricao.'" atualizada com sucesso.');
    }
}
