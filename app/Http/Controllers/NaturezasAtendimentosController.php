<?php

namespace App\Http\Controllers;

use App\Enums\SetorModelo;
use App\Http\Requests\NaturezaAtendimentoRequest;
use App\Models\ConfigModelo;
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
     * ordenação descartada nesta fase (lista sempre por descrição). A busca
     * única (?busca=) foi substituída pelo filtro individual de descrição
     * abaixo, mesmo padrão de clientes/atendimentos.
     */
    public function index(Request $request): View
    {
        $filtroDescricao = trim((string) $request->get('f_descricao', ''));

        $naturezas = NaturezaAtendimento::query()
            ->with(['modeloRelatorio', 'configModelo'])
            ->when($filtroDescricao !== '', fn ($query) => $query->where('nat_aten_descricao', 'like', "%{$filtroDescricao}%"))
            ->orderBy('nat_aten_descricao')
            ->paginate(15)
            ->withQueryString();

        $modelosRelatorios = ModeloRelatorio::where('mod_rel_ativo', 1)
            ->orderBy('mod_rel_descricao')
            ->get();

        // BF04 — só modelos do Configurador de setor Assistência podem ser
        // vinculados a uma natureza de atendimento.
        $configModelos = ConfigModelo::where('cfg_mod_ativo', 1)
            ->where('cfg_mod_setor', SetorModelo::Assistencia->value)
            ->orderBy('cfg_mod_nome')
            ->get();

        return view('naturezas_atendimentos.index', [
            'naturezas' => $naturezas,
            'modelosRelatorios' => $modelosRelatorios,
            'configModelos' => $configModelos,
            'filtroDescricao' => $filtroDescricao,
            'temFiltro' => $filtroDescricao !== '',
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