<?php

namespace App\Http\Middleware;

use App\Services\DispositivoLembradoService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prazo máximo da sessão web (pedido do cliente, 2026-11-27): 6h contadas do
 * login completo (e-mail + senha + código), além do limite de inatividade
 * (SESSION_LIFETIME). Entrar de novo só pelo código ("Lembrar dados") não
 * estende o prazo — vencido, o próximo acesso é o login completo.
 *
 * Só a sessão web: o token do app (Sanctum) não passa por aqui.
 */
class SessaoComPrazo
{
    /** Timestamp em que a sessão vence — gravado pelo AuthController no login. */
    public const CHAVE = 'login.expira_em';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || ! Auth::guard('web')->check()) {
            return $next($request);
        }

        $expiraEm = $request->session()->get(self::CHAVE);

        // Sessão autenticada sem prazo (anterior a esta regra): começa a contar agora.
        if ($expiraEm === null) {
            $request->session()->put(self::CHAVE, now()->addHours(DispositivoLembradoService::HORAS)->getTimestamp());

            return $next($request);
        }

        if (now()->getTimestamp() < (int) $expiraEm) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        // O navegador lembrado vence junto (DispositivoLembradoService), então
        // o próximo acesso é o login completo, com o "Lembrar dados" desmarcado.
        Cookie::queue(Cookie::forget(DispositivoLembradoService::COOKIE));

        $mensagem = 'Sua sessão expirou (limite de ' . DispositivoLembradoService::HORAS . ' horas). Entre novamente.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $mensagem], 401);
        }

        return redirect()->route('login')->withErrors(['email' => $mensagem]);
    }
}
