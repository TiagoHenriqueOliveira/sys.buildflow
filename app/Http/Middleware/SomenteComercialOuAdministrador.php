<?php

namespace App\Http\Middleware;

use App\Enums\NivelAcesso;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SomenteComercialOuAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        $nivel = Auth::user()->user_nivel_acesso;

        if ($nivel !== NivelAcesso::Administrador->value && $nivel !== NivelAcesso::Comercial->value) {
            abort(403);
        }

        return $next($request);
    }
}
