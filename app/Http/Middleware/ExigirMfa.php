<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * MFA obrigatório para todos no acesso web (2026-11-27): quem ainda não
 * cadastrou o app autenticador só acessa o cadastro dele e o "Sair". Vale
 * só para a sessão web — nas rotas que também aceitam o token do app
 * (/midia, PDF), o acesso pelo app (Sanctum) segue sem MFA por enquanto
 * (o MFA no app fica para a etapa do app Flutter).
 */
class ExigirMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::guard('web')->user();

        if ($usuario && ! $usuario->temMfaConfigurado()
            && ! $request->routeIs('mfa.cadastro', 'mfa.cadastro.store', 'logout')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Cadastre a verificação em duas etapas para continuar.'], 403);
            }

            return redirect()->route('mfa.cadastro');
        }

        return $next($request);
    }
}
