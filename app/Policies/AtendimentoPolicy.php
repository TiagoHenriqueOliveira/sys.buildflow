<?php

namespace App\Policies;

use App\Models\Atendimento;
use App\Models\Usuario;

class AtendimentoPolicy
{
    /**
     * Administrador (nível 0) acessa qualquer atendimento; técnico (nível 1+)
     * só acessa os que são seus. Regra única — antes duplicada em pelo menos
     * 4 formas diferentes entre os controllers de API (legado e Mcl), uma
     * delas invertida (`=== 1` em vez de `!== 0`), inofensiva hoje só porque
     * os únicos níveis existentes são 0 e 1.
     */
    public function acessar(Usuario $usuario, Atendimento $atendimento): bool
    {
        return $usuario->user_nivel_acesso === 0
            || $atendimento->aten_usuario_id === $usuario->user_id;
    }
}
