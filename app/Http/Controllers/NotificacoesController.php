<?php

namespace App\Http\Controllers;

use App\Models\Notificacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificacoesController extends Controller
{
    /**
     * Sino do topbar (sbadmin) - as 10 mais recentes do usuario logado +
     * contagem de nao lidas, consumido via fetch() no Alpine (sbAdmin()).
     */
    public function index(): JsonResponse
    {
        $usuarioId = Auth::id();

        $notificacoes = Notificacao::where('notif_usuario_id', $usuarioId)
            ->orderByDesc('notif_criado_em')
            ->limit(10)
            ->get();

        $naoLidas = Notificacao::where('notif_usuario_id', $usuarioId)
            ->where('notif_lida', false)
            ->count();

        return response()->json([
            'notificacoes' => $notificacoes->map(fn ($n) => [
                'id' => $n->notif_id,
                'titulo' => $n->notif_titulo,
                'mensagem' => $n->notif_mensagem,
                'link' => $n->notif_link,
                'lida' => $n->notif_lida,
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
}