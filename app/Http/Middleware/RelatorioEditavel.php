<?php

namespace App\Http\Middleware;

use App\Models\AtendimentoRelatorio;
use App\Support\RelatorioBloqueado;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarda única de "relatório editável" (auditoria 27/11/2026, S-02): nas
 * rotas de escrita de relatório (web e api/fae/v1), relatório Aprovado
 * devolve 423 "relatorio_bloqueado". As exceções da decisão D-03
 * (assinatura do cliente, compartilhamento, observação interna, status)
 * não passam por aqui — têm a regra no próprio controller.
 *
 * Quem não tem acesso ao relatório segue para o controller, que devolve o
 * 403 de sempre (o 423 não revela o status a quem não vê o relatório).
 */
class RelatorioEditavel
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->route('id') ?? $request->route('atendimentos_relatorio');

        if ($id !== null && ($relatorio = AtendimentoRelatorio::with('atendimento')->find((int) $id))) {
            $usuario = $request->user();

            if (RelatorioBloqueado::aprovado($relatorio)
                && $usuario
                && $relatorio->atendimento
                && $usuario->can('acessar', $relatorio->atendimento)) {
                return RelatorioBloqueado::resposta($relatorio);
            }
        }

        return $next($request);
    }
}
