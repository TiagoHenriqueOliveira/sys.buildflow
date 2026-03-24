<?php

namespace App\Repositories;

use App\Models\Usuario;
use App\Repositories\Contracts\CrudRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class UsuarioRepository implements CrudRepositoryInterface
{
    public function all()
    {
        return Usuario::orderBy('user_nome', 'asc')->get();
    }

    public function create(array $data)
    {
        return Usuario::create([
            'user_nivel_acesso' => $data['user_nivel_acesso'],
            'user_nome' => $data['user_nome'],
            'user_email' => $data['user_email'],
            'user_senha' => Hash::make($data['user_senha']),
            'user_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $user = Usuario::findOrFail($id);

        $payload = [
            'user_nivel_acesso' => $data['user_nivel_acesso'],
            'user_nome' => $data['user_nome'],
            'user_email' => $data['user_email'],
            'user_ativo' => $data['user_ativo'] ?? $user->user_ativo,
        ];

        if (!empty($data['user_senha'])) {
            $payload['user_senha'] = Hash::make($data['user_senha']);
        }

        $user->update($payload);

        return $user;
    }
}
