<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auditoria 27/11/2026 (S-12, decisão D-06 do usuário): toda ação da conta
 * do fornecedor (usuário protegido) no sistema web fica na auditoria —
 * inclusive as leituras —, com método, rota, resultado e IP. A conta não
 * entra pela API do app.
 */
class AuditarFornecedor
{
    public function handle(Request $request, Closure $next): Response
    {
        $resposta = $next($request);

        $usuario = Auth::guard('web')->user();
        if ($usuario?->isProtegido()) {
            AuditService::log('fornecedor', $request->isMethod('GET') ? 'leitura' : 'escrita', 0, [], [
                'metodo' => $request->method(),
                'rota' => $request->route()?->getName() ?? $request->path(),
                'caminho' => '/' . ltrim($request->path(), '/'),
                'status' => $resposta->getStatusCode(),
                'ip' => $request->ip(),
            ]);
        }

        return $resposta;
    }
}
