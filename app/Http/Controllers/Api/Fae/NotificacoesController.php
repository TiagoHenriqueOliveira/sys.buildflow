<?php

namespace App\Http\Controllers\Api\Fae;

use App\Http\Controllers\Controller;
use App\Models\Notificacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Pedido do cliente (2026-09-17) - Sistema de Notificacoes tambem
 * disponivel pro app (mesma fonte de dado do sino do Web). A push nativa
 * (Android, mesmo com app fechado) e um transporte a parte, ainda nao
 * implementado - este endpoint so cobre consulta/marcacao, reaproveitavel
 * quando esse transporte existir.
 */
class NotificacoesController extends Controller
{
    public function index(): JsonResponse
    {
        $usuarioId = Auth::id();

        $notificacoes = Notificacao::where('notif_usuario_id', $usuarioId)
            ->orderByDesc('notif_criado_em')
            ->limit(20)
            ->get();

        $naoLidas = Notificacao::where('notif_usuario_id', $usuarioId)
            ->where('notif_lida', false)
            ->count();

        return response()->json([
            'data' => $notificacoes->map(fn ($n) => [
                'id' => $n->notif_id,
                'tipo' => $n->notif_tipo,
                'titulo' => $n->notif_titulo,
                'mensagem' => $n->notif_mensagem,
                'link' => $n->notif_link,
                'lida' => $n->notif_lida,
                'criado_em' => $n->notif_criado_em->toIso8601String(),
            ]),
            'nao_lidas' => $naoLidas,
        ]);
    }

    public function marcarLida(int $id): JsonResponse
    {
        Notificacao::where('notif_id', $id)
            ->where('notif_usuario_id', Auth::id())
            ->update(['notif_lida' => true]);

        return response()->json(['message' => 'Notificação marcada como lida.']);
    }

    public function marcarNaoLida(int $id): JsonResponse
    {
        Notificacao::where('notif_id', $id)
            ->where('notif_usuario_id', Auth::id())
            ->update(['notif_lida' => false]);

        return response()->json(['message' => 'Notificação marcada como não lida.']);
    }
}