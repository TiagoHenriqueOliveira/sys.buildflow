<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UsuariosController extends Controller
{
    public function __construct(
        private UsuarioRepository $repository,
    ) {}

    /**
     * Migrada pro pacote sbadmin/dashboard — mesmo padrão das demais telas
     * de cadastro simples (ver CLAUDE.md, seção "Template visual"): sem
     * branch DataTables-JSON, paginação nativa consumida por
     * <x-sbadmin::table>; busca via ?busca= (nome/e-mail), ordenação
     * descartada (lista sempre por nome). O filtro de usuário "protegido"
     * (admin master, só visível/editável por si mesmo — ver
     * Usuario::isProtegido()) que antes era feito no mapper de linha da
     * DataTables virou uma cláusula WHERE equivalente na query.
     */
    public function index(Request $request): View
    {
        $busca = trim((string) $request->get('busca', ''));
        $loggedUserId = Auth::user()->user_id;

        $usuarios = Usuario::query()
            ->where(function ($query) use ($loggedUserId) {
                $query->where('user_protegido', 0)
                    ->orWhere('user_id', $loggedUserId);
            })
            ->when($busca !== '', function ($query) use ($busca) {
                $query->where(function ($q) use ($busca) {
                    $q->where('user_nome', 'like', "%{$busca}%")
                        ->orWhere('user_email', 'like', "%{$busca}%");
                });
            })
            ->orderBy('user_nome')
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'busca' => $busca,
        ]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $usuario = $this->repository->create($request->validated());

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuário "'.$usuario->user_nome.'" cadastrado com sucesso.');
    }

    public function update(UsuarioRequest $request, int $id): RedirectResponse
    {
        $target = $this->repository->findOrFail($id);

        // Somente o próprio usuário protegido pode editar a si mesmo
        if ($target->isProtegido() && Auth::user()->user_id !== $target->user_id) {
            abort(403, 'Não é permitido alterar o usuário administrador master.');
        }

        $usuario = $this->repository->update($id, $request->validated());

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuário "'.$usuario->user_nome.'" atualizado com sucesso.');
    }
}
