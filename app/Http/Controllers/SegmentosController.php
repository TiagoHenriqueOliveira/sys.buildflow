<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersisteFiltros;
use App\Http\Requests\SegmentoRequest;
use App\Models\Segmento;
use App\Repositories\SegmentoRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pedido do cliente (2026-09-17) — cadastro das opções de Segmento,
 * listadas no select de "Segmento" do cadastro de Cliente. Mesmo padrão de
 * ClassificacoesClienteController: lista simples (descrição + ativo), modal
 * de criação/edição, sem `create`/`edit` como views próprias.
 */
class SegmentosController extends Controller
{
    use PersisteFiltros;

    public function __construct(
        private SegmentoRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        $filtros = $this->filtrosPersistentes('segmentos', ['f_descricao']);
        $filtroDescricao = $filtros['f_descricao'];

        $segmentos = Segmento::query()
            ->when($filtroDescricao !== '', fn ($q) => $q->where('seg_descricao', 'like', "%{$filtroDescricao}%"))
            ->orderBy('seg_descricao')
            ->paginate(15)
            ->withQueryString();

        return view('segmentos.index', [
            'segmentos' => $segmentos,
            'filtroDescricao' => $filtroDescricao,
            'temFiltro' => $filtroDescricao !== '',
        ]);
    }

    public function store(SegmentoRequest $request): RedirectResponse
    {
        $segmento = $this->repository->create($request->validated());

        return redirect()
            ->route('segmentos.index')
            ->with('success', 'Segmento "'.$segmento->seg_descricao.'" cadastrado com sucesso.');
    }

    public function update(SegmentoRequest $request, int $id): RedirectResponse
    {
        $segmento = $this->repository->update($id, $request->validated());

        return redirect()
            ->route('segmentos.index')
            ->with('success', 'Segmento "'.$segmento->seg_descricao.'" atualizado com sucesso.');
    }
}