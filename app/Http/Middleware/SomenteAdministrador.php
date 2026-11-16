<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SomenteAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        // Perfil Administrador ou usuário protegido. Usado só no que não é
        // configurável em "Perfis de Acesso" (a própria tela de perfis,
        // logs e manutenção) — o resto usa `can:<tela>.<acao>`.
        if (! Auth::user()->temAcessoTotal()) {
            abort(403);
        }

        return $next($request);
    }
}
