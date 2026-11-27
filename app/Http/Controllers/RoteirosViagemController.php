<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersisteFiltros;
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
    use PersisteFiltros;

    public function __construct(
        private RoteiroViagemRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        $filtros = $this->filtrosPersistentes('roteiros-viagem', ['f_vendedor', 'f_periodo']);
        $filtroVendedor = $filtros['f_vendedor'];
        $filtroPeriodo = $filtros['f_periodo'];

        $roteiros = RoteiroViagem::query()
            ->with(['vendedor', 'clientes'])
            ->visivelPara($request->user())
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
        abort_unless($roteiro->visivelPara(auth()->user()), 403, 'Este roteiro pertence a outro vendedor.');

        return view('roteiros-viagem.form', [
            'roteiro' => $roteiro,
            ...$this->dadosApoioFormulario(),
        ]);
    }

    private function dadosApoioFormulario(): array
    {
        return [
            'vendedores' => $this->vendedoresComerciais(),
            'clientesDisponiveis' => Cliente::where('cli_ativo', 1)->visivelPara(auth()->user())->orderBy('cli_nome')->get(),
        ];
    }

    private function vendedoresComerciais()
    {
        // Perfis marcados "Atua como vendedor" em Perfis de Acesso (2026-11-16);
        // o perfil Vendedor só enxerga/atribui a si mesmo (2026-11-27).
        return Usuario::vendedoresAtribuiveisPor(auth()->user())
            ->orderBy('user_nome')
            ->get();
    }

    /**
     * Reabre um roteiro concluído (2026-11-27): só Administrador/Comercial,
     * e só para uma situação diferente de "Concluída" — depois disso a
     * edição volta a ser liberada.
     */
    public function reabrir(Request $request, int $id): RedirectResponse
    {
        $roteiro = RoteiroViagem::findOrFail($id);
        abort_unless($roteiro->visivelPara($request->user()), 403, 'Este roteiro pertence a outro vendedor.');
        abort_unless($request->user()->podeReabrirRoteiro(), 403, 'Só os perfis Administrador e Comercial podem reabrir um roteiro concluído.');
        abort_unless($roteiro->estaConcluido(), 403, 'O roteiro não está concluído.');

        $dados = $request->validate([
            'crm_rot_status' => ['required', \Illuminate\Validation\Rule::in(
                collect(\App\Enums\StatusRoteiroViagem::cases())
                    ->reject(fn ($s) => $s === \App\Enums\StatusRoteiroViagem::Concluida)
                    ->map->value->all()
            )],
        ], ['crm_rot_status.*' => 'Escolha a nova situação do roteiro.']);

        $roteiro->update(['crm_rot_status' => (int) $dados['crm_rot_status']]);

        return redirect()
            ->route('roteiros-viagem.edit', $id)
            ->with('success', 'Roteiro reaberto para edição.');
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
        $roteiro = RoteiroViagem::findOrFail($id);
        abort_unless($roteiro->visivelPara($request->user()), 403, 'Este roteiro pertence a outro vendedor.');

        if ($roteiro->estaConcluido()) {
            return redirect()
                ->route('roteiros-viagem.edit', $id)
                ->withErrors(['roteiro' => 'Roteiro concluído não pode ser editado.']);
        }
        $this->repository->update($id, $request->validated());

        return redirect()
            ->route('roteiros-viagem.edit', $id)
            ->with('success', 'Roteiro de viagem atualizado com sucesso.');
    }
}