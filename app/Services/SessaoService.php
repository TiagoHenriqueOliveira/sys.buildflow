<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Encerra o acesso de um usuário em outros lugares (2026-11-27, mesmo
 * comportamento do promaq-sys): troca de senha e desativação derrubam as
 * sessões web (SESSION_DRIVER=database), os tokens do app e os aparelhos
 * lembrados — quem tinha a senha antiga não continua logado.
 */
class SessaoService
{
    public function __construct(private readonly DispositivoLembradoService $dispositivos) {}

    /**
     * @param  string|null  $manterSessaoId  sessão atual, quando é o próprio
     *                                        usuário trocando a senha (ele
     *                                        continua logado só aqui)
     */
    public function encerrarOutrosAcessos(Usuario $usuario, ?string $manterSessaoId = null): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $usuario->user_id)
                ->when($manterSessaoId, fn ($q) => $q->where('id', '!=', $manterSessaoId))
                ->delete();
        }

        $usuario->tokens()->delete();
        $this->dispositivos->revogarTodos($usuario);
    }
}
