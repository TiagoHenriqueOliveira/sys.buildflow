<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Repositories\UsuarioRepository;
use Illuminate\Http\Request;

class UsuariosController extends Controller
{
    public function __construct(
        private UsuarioRepository $repository
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->repository->all()->map(function ($u) {
                $nivel = ((int)$u->user_nivel_acesso === 0) ? 'Administrador' : 'Técnico';

                return [
                    'acoes' => view('usuarios.partials.acoes', compact('u'))->render(),
                    'user_nome' => e($u->user_nome),
                    'user_email' => e($u->user_email),
                    'nivel' => $nivel,
                    'user_ativo' => (int) $u->user_ativo,
                    'status' => $u->user_ativo ? 'Ativo' : 'Desativado',
                ];
            });

            return response()->json(['data' => $data]);
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
            $this->repository->update($id, $request->validated());
            return response()->json(['message' => 'Atualizado com sucesso!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao atualizar.'], 500);
        }
    }
}
