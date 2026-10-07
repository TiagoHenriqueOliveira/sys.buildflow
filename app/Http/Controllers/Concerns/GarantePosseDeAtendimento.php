<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Atendimento;
use Illuminate\Support\Facades\Auth;

// Item 2.2 do plano de correções (IDOR): nenhum dos endpoints de
// relatório/atendimento verificava posse — qualquer usuário autenticado
// lia/escrevia dados de OUTRO técnico só trocando o ID na URL. Mesma regra
// de App\Policies\AtendimentoPolicy usada no resto do sistema (admin vê
// tudo, técnico só o que é seu); espelha o padrão já correto que existia
// em AtendimentosRelatoriosController::pdf() (RNF004).
trait GarantePosseDeAtendimento
{
    private function garantirPosse(?Atendimento $atendimento): void
    {
        $usuario = Auth::user();

        $temAcesso = $atendimento
            ? $usuario->can('acessar', $atendimento)
            : (int) $usuario->user_nivel_acesso === 0;

        abort_unless($temAcesso, 403, 'Você não tem acesso a este atendimento.');
    }
}
