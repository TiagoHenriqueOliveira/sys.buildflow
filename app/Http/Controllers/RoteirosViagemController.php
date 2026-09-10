<?php

namespace App\Http\Controllers;

use App\Enums\NivelAcesso;
use App\Http\Requests\RoteiroViagemRequest;
use App\Models\Cliente;
use App\Models\RoteiroViagem;
use App\Models\Usuario;
use App\Repositories\RoteiroViagemRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoteirosViagemController extends Controller
{
    public function __construct(
        private RoteiroViagemRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        $filtroVendedor = $request->get('f_vendedor', '');
        $filtroPeriodo = trim((string) $request->get('f_periodo', ''));

        $roteiros = RoteiroViagem::query()
            ->with(['vendedor', 'clientes'])
            ->when($filtroVendedor !== '', fn ($q) => $q->where('crm_rot_vendedor_id', (int) $filtroVendedor))
            ->when($filtroPeriodo !== '', fn ($q) => $q->where('crm_rot_periodo_inicio', '<=', $filtroPeriodo)->where('crm_rot_periodo_fim', '>=', $filtroPeriodo))
            ->orderByDesc('crm_rot_id')
            ->paginate(15)
            ->withQueryString();

        return view('roteiros-viagem.index', [
            'roteiros' => $roteiros,
            'vendedores' => $this->vendedoresComerciais(),
            'filtroVendedor' => $filtroVendedor,
            'filtroPeriodo' => $filtroPeriodo,
            'temFiltro' => $filtroVendedor !== '' || $filtroPeriodo !== '',
        ]);
    }

    public function create(): View
    {
        return view('roteiros-viagem.form', [
            'roteiro' => new RoteiroViagem(),
            ...$this->dadosApoioFormulario(),
        ]);
    }

    public function edit(int $id): View
    {
        $roteiro = RoteiroViagem::with(['clientes.cliente'])->findOrFail($id);

        return view('roteiros-viagem.form', [
            'roteiro' => $roteiro,
            ...$this->dadosApoioFormulario(),
        ]);
    }

    private function dadosApoioFormulario(): array
    {
        return [
            'vendedores' => $this->vendedoresComerciais(),
            'clientesDisponiveis' => Cliente::where('cli_ativo', 1)->orderBy('cli_nome')->get(),
        ];
    }

    private function vendedoresComerciais()
    {
        return Usuario::where('user_nivel_acesso', NivelAcesso::Comercial->value)
            ->where('user_ativo', 1)
            ->orderBy('user_nome')
            ->get();
    }

    public function store(RoteiroViagemRequest $request): RedirectResponse
    {
        $roteiro = $this->repository->create($request->validated());

        return redirect()
            ->route('roteiros-viagem.edit', $roteiro->crm_rot_id)
            ->with('success', 'Roteiro de viagem cadastrado com sucesso.');
    }

    public function update(RoteiroViagemRequest $request, int $id): RedirectResponse
    {
        $this->repository->update($id, $request->validated());

        return redirect()
            ->route('roteiros-viagem.edit', $id)
            ->with('success', 'Roteiro de viagem atualizado com sucesso.');
    }
}