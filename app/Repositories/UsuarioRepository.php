<?php

namespace App\Repositories;

use App\Models\Usuario;
use App\Repositories\Contracts\CrudRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class UsuarioRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return Usuario::orderBy('user_nome', 'asc')->get();
    }

    public function findOrFail(int $id): Usuario
    {
        return Usuario::findOrFail($id);
    }

    public function create(array $data): \Illuminate\Database\Eloquent\Model
    {
        return Usuario::create([
            'user_nivel_acesso' => $data['user_nivel_acesso'],
            'user_nome' => $data['user_nome'],
            'user_email' => $data['user_email'],
            'user_senha' => Hash::make($data['user_senha']),
            'user_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data): \Illuminate\Database\Eloquent\Model
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

        // Usuário protegido continua sempre Administrador e ativo — nem ele
        // mesmo consegue se trancar pra fora (pedido 2026-11-16).
        if ($user->isProtegido()) {
            $payload['user_nivel_acesso'] = \App\Enums\NivelAcesso::Administrador->value;
            $payload['user_ativo'] = 1;
        }

        $user->update($payload);

        // Desativar o usuário ou trocar a senha dele derruba todos os acessos
        // já abertos (sessões web, tokens do app e navegadores lembrados) —
        // 2026-11-27, como no promaq-sys. Antes só a desativação revogava os
        // tokens do app.
        if ((int) $payload['user_ativo'] === 0 || ! empty($data['user_senha'])) {
            app(\App\Services\SessaoService::class)->encerrarOutrosAcessos(
                $user,
                $user->user_id === auth()->id() ? session()->getId() : null,
            );
        }

        return $user;
    }
}
