<?php

namespace App\Http\Middleware;

use App\Enums\NivelAcesso;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pedido do cliente (2026-09-16): cadastro de Cliente aberto a todos os
 * perfis, exceto Tecnico - diferente do middleware "comercial", que
 * restringe a Administrador/Comercial (usado pelo resto do CRM: Orcamentos,
 * Roteiro de Viagem, Mapa de Relacoes, Indicadores).
 */
class SomenteNaoTecnico
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::user()->user_nivel_acesso === NivelAcesso::Tecnico->value) {
            abort(403);
        }

        return $next($request);
    }
}