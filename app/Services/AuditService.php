<?php

namespace App\Services;

use App\Models\LogAuditoria;
use App\Models\LogErro;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public static function log(string $modulo, string $acao, int $registroId, array $anterior = [], array $novo = []): void
    {
        LogAuditoria::create([
            'log_aud_modulo'           => $modulo,
            'log_aud_acao'             => $acao,
            'log_aud_registro_id'      => $registroId,
            'log_aud_usuario_id'       => Auth::id(),
            'log_aud_dados_anteriores' => empty($anterior) ? null : $anterior,
            'log_aud_dados_novos'      => empty($novo) ? null : $novo,
        ]);
    }

    public static function erro(string $mensagem, string $modulo = '', array $contexto = [], string $nivel = 'error'): void
    {
        LogErro::create([
            'log_err_modulo'     => $modulo ?: null,
            'log_err_nivel'      => $nivel,
            'log_err_mensagem'   => $mensagem,
            'log_err_contexto'   => empty($contexto) ? null : $contexto,
            'log_err_usuario_id' => Auth::id(),
        ]);
    }
}
