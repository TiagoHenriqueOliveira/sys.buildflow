<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersisteFiltros;
use App\Enums\SetorModelo;
use App\Http\Requests\CrmTipoOrcamentoRequest;
use App\Models\ConfigModelo;
use App\Models\CrmTipoOrcamento;
use App\Repositories\CrmTipoOrcamentoRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmTiposOrcamentoController extends Controller
{
    use PersisteFiltros;

    public function __construct(
        private CrmTipoOrcamentoRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        $filtros = $this->filtrosPersistentes('crm-tipos-orcamento', ['f_nome']);
        $filtroNome = $filtros['f_nome'];

        $tipos = CrmTipoOrcamento::query()
            ->with('configModelo')
            ->when($filtroNome !== '', fn ($q) => $q->where('crm_tp_orc_nome', 'like', "%{$filtroNome}%"))
            ->orderBy('crm_tp_orc_nome')
            ->paginate(15)
            ->withQueryString();

        return view('crm.tipos-orcamento.index', [
            'tipos' => $tipos,
            'modelosComerciais' => ConfigModelo::where('cfg_mod_ativo', 1)
                ->where('cfg_mod_setor', SetorModelo::Comercial->value)
                ->orderBy('cfg_mod_nome')
                ->get(),
            'filtroNome' => $filtroNome,
            'temFiltro' => $filtroNome !== '',
        ]);
    }

    public function store(CrmTipoOrcamentoRequest $request): RedirectResponse
    {
        $tipo = $this->repository->create($request->validated());

        return redirect()
            ->route('crm.tipos-orcamento.index')
            ->with('success', 'Tipo de orçamento "'.$tipo->crm_tp_orc_nome.'" cadastrado com sucesso.');
    }

    public function update(CrmTipoOrcamentoRequest $request, int $id): RedirectResponse
    {
        $tipo = $this->repository->update($id, $request->validated());

        return redirect()
            ->route('crm.tipos-orcamento.index')
            ->with('success', 'Tipo de orçamento "'.$tipo->crm_tp_orc_nome.'" atualizado com sucesso.');
    }
}