<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Usuário desativado perde a sessão web na próxima requisição (2026-11-27). */
class UsuarioAtivo
{
    public function handle(Request $request, Closure $next): Response
    {
        // Só a sessão web: o token do app de usuário desativado já é revogado
        // na desativação (UsuarioRepository/SessaoService).
        $usuario = Auth::guard('web')->user();
        if ($usuario && ! $usuario->user_ativo) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Usuário desativado.']);
        }

        return $next($request);
    }
}
