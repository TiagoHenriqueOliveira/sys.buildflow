<?php

namespace App\Services;

use App\Models\Usuario;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;

/**
 * MFA por app autenticador (TOTP, RFC 6238: 6 dígitos a cada 30 s) —
 * obrigatório para todos no acesso web desde 2026-11-27 (mesmo desenho do
 * promaq-sys). Um código aceito não pode ser reutilizado (guarda o último
 * passo de tempo aceito por usuário).
 *
 * Força bruta: 5 códigos errados bloqueiam o MFA do usuário por 15 minutos,
 * venha de qualquer IP.
 */
class MfaService
{
    /** Tolerância de ±1 passo (30 s) para relógio do celular fora de hora. */
    private const JANELA = 1;

    public const MAXIMO_ERROS = 5;

    public const BLOQUEIO_SEGUNDOS = 900;

    public function __construct(private readonly Google2FA $google2fa) {}

    public function gerarSegredo(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function uriOtpauth(Usuario $usuario, string $segredo): string
    {
        return $this->google2fa->getQRCodeUrl('Buildflow FAÉ', $usuario->user_email, $segredo);
    }

    public function qrCodeSvg(string $conteudo): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd));

        return $writer->writeString($conteudo);
    }

    /** Confere um código contra um segredo ainda não salvo (cadastro do MFA). */
    public function codigoValidoParaSegredo(string $segredo, string $codigo): bool
    {
        $codigo = $this->limpar($codigo);

        return $codigo !== null && $this->google2fa->verifyKey($segredo, $codigo, self::JANELA);
    }

    /** Confere o código do usuário e impede o reuso do mesmo código. */
    public function verificar(Usuario $usuario, string $codigo): bool
    {
        $codigo = $this->limpar($codigo);

        if ($this->bloqueado($usuario)) {
            return false;
        }

        if ($codigo === null || ! $usuario->temMfaConfigurado()) {
            RateLimiter::hit($this->chaveErros($usuario), self::BLOQUEIO_SEGUNDOS);

            return false;
        }

        $chave = "mfa.ultimo_passo.{$usuario->user_id}";

        // Nunca passar null: sem passo anterior o google2fa devolve true em vez
        // do número do passo, e o mesmo código poderia ser usado de novo.
        $ultimoPasso = (int) Cache::get($chave, 0);

        $passo = $this->google2fa->verifyKeyNewer($usuario->user_mfa_secret, $codigo, $ultimoPasso, self::JANELA);

        if (! is_int($passo)) {
            RateLimiter::hit($this->chaveErros($usuario), self::BLOQUEIO_SEGUNDOS);

            return false;
        }

        Cache::put($chave, $passo, now()->addMinutes(5));
        RateLimiter::clear($this->chaveErros($usuario));

        return true;
    }

    /** Errou o código MAXIMO_ERROS vezes: até o código certo é recusado por um tempo. */
    public function bloqueado(Usuario $usuario): bool
    {
        return RateLimiter::tooManyAttempts($this->chaveErros($usuario), self::MAXIMO_ERROS);
    }

    public function minutosParaLiberar(Usuario $usuario): int
    {
        return max(1, (int) ceil(RateLimiter::availableIn($this->chaveErros($usuario)) / 60));
    }

    public function confirmar(Usuario $usuario, string $segredo): void
    {
        $usuario->forceFill(['user_mfa_secret' => $segredo, 'user_mfa_confirmado_em' => now()])->save();
    }

    /** Administrador redefine (ex.: celular perdido): o usuário cadastra de novo no próximo acesso. */
    public function redefinir(Usuario $usuario): void
    {
        $usuario->forceFill(['user_mfa_secret' => null, 'user_mfa_confirmado_em' => null])->save();
        RateLimiter::clear($this->chaveErros($usuario));
        Cache::forget("mfa.ultimo_passo.{$usuario->user_id}");
    }

    private function chaveErros(Usuario $usuario): string
    {
        return "mfa.erros.{$usuario->user_id}";
    }

    private function limpar(string $codigo): ?string
    {
        $codigo = preg_replace('/\s+/', '', $codigo);

        return preg_match('/^\d{6}$/', (string) $codigo) ? $codigo : null;
    }
}
