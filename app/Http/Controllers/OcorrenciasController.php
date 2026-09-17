<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersisteFiltros;
use App\Http\Requests\OcorrenciaRequest;
use App\Models\Ocorrencia;
use App\Repositories\OcorrenciaRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OcorrenciasController extends Controller
{
    use PersisteFiltros;

    public function __construct(
        private OcorrenciaRepository $repository,
    ) {}

    /**
     * Migrada pro pacote sbadmin/dashboard — mesmo padrão de
     * clientes/naturezas_atendimentos (ver CLAUDE.md, seção "Template
     * visual"): sem branch DataTables-JSON, paginação nativa consumida por
     * <x-sbadmin::table>; ordenação descartada (lista sempre por
     * descrição). A busca única (?busca=) foi substituída pelo filtro
     * individual de descrição abaixo, mesmo padrão de clientes/atendimentos.
     */
    public function index(Request $request): View
    {
        $filtros = $this->filtrosPersistentes('ocorrencias', ['f_descricao']);
        $filtroDescricao = $filtros['f_descricao'];

        $ocorrencias = Ocorrencia::query()
            ->when($filtroDescricao !== '', fn ($query) => $query->where('ocor_descricao', 'like', "%{$filtroDescricao}%"))
            ->orderBy('ocor_descricao')
            ->paginate(15)
            ->withQueryString();

        return view('ocorrencias.index', [
            'ocorrencias' => $ocorrencias,
            'filtroDescricao' => $filtroDescricao,
            'temFiltro' => $filtroDescricao !== '',
        ]);
    }

    public function store(OcorrenciaRequest $request): RedirectResponse
    {
        $ocorrencia = $this->repository->create($request->validated());

        return redirect()
            ->route('ocorrencias.index')
            ->with('success', 'Ocorrência "'.$ocorrencia->ocor_descricao.'" cadastrada com sucesso.');
    }

    public function update(OcorrenciaRequest $request, int $id): RedirectResponse
    {
        $ocorrencia = $this->repository->update($id, $request->validated());

        return redirect()
            ->route('ocorrencias.index')
            ->with('success', 'Ocorrência "'.$ocorrencia->ocor_descricao.'" atualizada com sucesso.');
    }

    public function autoComplete(Request $request)
    {
        $term = trim((string) $request->get('term', ''));

        if (mb_strlen($term) < 3) {
            return response()->json([]);
        }

        $rows = Ocorrencia::query()
            ->where('ocor_ativo', 1)
            ->where('ocor_descricao', 'like', "%{$term}%")
            ->orderBy('ocor_descricao')
            ->limit(20)
            ->get();

        $payload = $rows->map(function ($o) {
            return [
                'id'    => $o->ocor_id,
                'label' => $o->ocor_descricao,
                'value' => $o->ocor_descricao,
            ];
        });

        return response()->json($payload);
    }
}
