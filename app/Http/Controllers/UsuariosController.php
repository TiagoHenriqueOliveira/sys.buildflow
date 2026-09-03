<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;
use App\Services\DataTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsuariosController extends Controller
{
    public function __construct(
        private UsuarioRepository $repository,
        private DataTableService $dataTable,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $loggedUser = Auth::user();

            return response()->json(
                $this->dataTable->process(
                    $request,
                    Usuario::query(),
                    searchable: ['user_nome', 'user_email'],
                    orderable:  [
                        'acoes'      => null,
                        'user_nome'  => 'user_nome',
                        'user_email' => 'user_email',
                        'nivel'      => null,
                        'status'     => 'user_ativo',
                    ],
                    // Filtro de usuários protegidos feito em PHP (não depende da coluna existir no banco)
                    mapper: function ($u) use ($loggedUser) {
                        if ($u->isProtegido() && $loggedUser->user_id !== $u->user_id) {
                            return null; // será filtrado abaixo
                        }
                        return [
                            'acoes'      => view('usuarios.partials.acoes', compact('u'))->render(),
                            'user_nome'  => e($u->user_nome),
                            'user_email' => e($u->user_email),
                            'nivel'      => ((int) $u->user_nivel_acesso === 0) ? 'Administrador' : 'Técnico',
                            'user_ativo' => (int) $u->user_ativo,
                            'status'     => $u->user_ativo ? 'Ativo' : 'Desativado',
                        ];
                    },
                )
            );
        }

        return view('usuarios.index');
    }

    public function store(UsuarioRequest $request)
    {
        try {
            $this->repository->create($request->validated());
            return response()->json(['message' => 'Cadastrado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao cadastrar.'], 500);
        }
    }

    public function update(UsuarioRequest $request, int $id)
    {
        try {
            $target = $this->repository->findOrFail($id);

            // Somente o próprio usuário protegido pode editar a si mesmo
            if ($target->isProtegido() && Auth::user()->user_id !== $target->user_id) {
                return response()->json(['message' => 'Não é permitido alterar o usuário administrador master.'], 403);
            }

            $this->repository->update($id, $request->validated());
            return response()->json(['message' => 'Atualizado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao atualizar.'], 500);
        }
    }
}
