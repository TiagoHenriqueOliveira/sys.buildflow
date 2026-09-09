<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Repositories\ClienteRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientesController extends Controller
{
    public function __construct(
        private ClienteRepository $repository,
    ) {}

    /**
     * Tela de referência da migração pro pacote sbadmin/dashboard (ver
     * CLAUDE.md, seção "Template visual"): a listagem deixou de ser um
     * shell Blade + endpoint JSON no protocolo server-side da DataTables
     * (ver git history / feature/mcl) e passou a ser uma renderização Blade
     * normal com paginação nativa do Eloquent, consumida diretamente por
     * <x-sbadmin::table :paginator="$clientes">. Busca client-side e
     * ordenação por coluna que a DataTables oferecia de graça não têm
     * equivalente direto no novo componente — a busca foi reimplementada
     * no backend via querystring (?busca=), e a ordenação foi descartada
     * nesta fase (lista sempre ordenada por nome; não havia botões de
     * exportação Excel/PDF nesta tela para reavaliar).
     */
    public function index(Request $request): View
    {
        $busca = trim((string) $request->get('busca', ''));

        $clientes = Cliente::query()
            ->when($busca !== '', function ($query) use ($busca) {
                $query->where(function ($q) use ($busca) {
                    $q->where('cli_nome', 'like', "%{$busca}%")
                        ->orWhere('cli_cnpj', 'like', "%{$busca}%")
                        ->orWhere('cli_cidade', 'like', "%{$busca}%")
                        ->orWhere('cli_email', 'like', "%{$busca}%");
                });
            })
            ->orderBy('cli_nome')
            ->paginate(15)
            ->withQueryString();

        return view('clientes.index', [
            'clientes' => $clientes,
            'busca' => $busca,
        ]);
    }

    /**
     * O modal de criação/edição deixou de submeter via AJAX (fetch/$.ajax +
     * JSON de resposta) e passou a ser um <form> comum, com redirect +
     * mensagem flash em caso de sucesso e o padrão nativo do Laravel
     * (redirect back + $errors + old()) em caso de validação — os
     * componentes <x-sbadmin::form.*> já leem old()/$errors sozinhos, sem
     * precisar de nenhuma renderização de erro feita à mão em JS.
     */
    public function store(ClienteRequest $request): RedirectResponse
    {
        $cliente = $this->repository->create($request->validated());

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente "'.$cliente->cli_nome.'" cadastrado com sucesso.');
    }

    public function update(ClienteRequest $request, int $id): RedirectResponse
    {
        $cliente = $this->repository->update($id, $request->validated());

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente "'.$cliente->cli_nome.'" atualizado com sucesso.');
    }

    public function autoComplete(Request $request): JsonResponse
    {
        $term = $request->get('term', '');

        $clientes = Cliente::where('cli_ativo', 1)
            ->where('cli_nome', 'like', '%' . $term . '%')
            ->orderBy('cli_nome')
            ->limit(20)
            ->get();

        $result = $clientes->map(function ($c) {
            return [
                'id'    => $c->cli_id,
                'label' => $c->cli_nome,
                'value' => $c->cli_nome,
            ];
        })->values()->all();

        return response()->json($result);
    }
}
