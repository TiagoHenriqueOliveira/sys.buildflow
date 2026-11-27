<?php

namespace App\Services;

use App\Models\DispositivoLembrado;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * "Lembrar dados" no login (pedido do cliente, 2026-11-27): o navegador
 * marcado vai direto para a tela do código do autenticador no próximo
 * acesso — o MFA continua obrigatório, só o e-mail/senha são pulados.
 *
 * A senha NUNCA é guardada: o cookie leva só "<id do usuário>|<token
 * aleatório>" (criptografado pelo EncryptCookies e HttpOnly); o banco guarda
 * o SHA-256 do token. O token é trocado a cada uso e expira em 30 dias. Trocar
 * a senha, redefinir o MFA ou desativar o usuário revoga todos os aparelhos
 * dele; "Não é você?" no login esquece só o aparelho atual.
 */
class DispositivoLembradoService
{
    public const COOKIE = 'bf_lembrar';

    public const DIAS = 30;

    public function emitir(Usuario $usuario, Request $request): void
    {
        $token = Str::random(64);

        DispositivoLembrado::create([
            'disp_usuario_id' => $usuario->user_id,
            'disp_token_hash' => hash('sha256', $token),
            'disp_user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'disp_ip' => $request->ip(),
            'disp_criado_em' => now(),
            'disp_ultimo_uso_em' => now(),
            'disp_expira_em' => now()->addDays(self::DIAS),
        ]);

        Cookie::queue(Cookie::make(
            self::COOKIE,
            $usuario->user_id . '|' . $token,
            self::DIAS * 24 * 60,
            '/',
            null,
            config('session.secure'),
            true,
            false,
            'lax',
        ));
    }

    /** Aparelho lembrado válido deste navegador (usuário ativo, dentro da validade), ou null. */
    public function identificar(Request $request): ?DispositivoLembrado
    {
        $valor = (string) $request->cookie(self::COOKIE, '');
        if (! str_contains($valor, '|')) {
            return null;
        }

        [$usuarioId, $token] = explode('|', $valor, 2);

        $dispositivo = DispositivoLembrado::with('usuario')
            ->where('disp_usuario_id', (int) $usuarioId)
            ->where('disp_token_hash', hash('sha256', $token))
            ->where('disp_expira_em', '>', now())
            ->first();

        return $dispositivo && $dispositivo->usuario?->user_ativo ? $dispositivo : null;
    }

    /** Depois do código certo vindo de um aparelho lembrado: troca o token (uso único). */
    public function renovar(DispositivoLembrado $dispositivo, Request $request): void
    {
        $usuario = $dispositivo->usuario;
        $dispositivo->delete();
        $this->emitir($usuario, $request);
    }

    /** "Não é você?" — esquece só este navegador. */
    public function esquecer(Request $request): void
    {
        $this->identificar($request)?->delete();
        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    /** Troca de senha, MFA redefinido ou usuário desativado: nenhum aparelho continua lembrado. */
    public function revogarTodos(Usuario $usuario): void
    {
        DispositivoLembrado::where('disp_usuario_id', $usuario->user_id)->delete();
    }
}
