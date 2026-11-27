<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auditoria 27/11/2026 (S-05): política de senha única para todo
        // cadastro/troca de senha — mínimo 10, letras e números e, fora dos
        // testes, conferência em vazamentos conhecidos (só os 5 primeiros
        // caracteres do SHA-1 saem do servidor — k-anonimato do HIBP).
        Password::defaults(function () {
            $regra = Password::min(10)->letters()->numbers();

            return config('auth.senha_checar_vazamento') ? $regra->uncompromised() : $regra;
        });

        // Auditoria 27/11/2026 (S-05) / contrato fae/v1 §2.3: o token do app
        // vence em N dias corridos ou em M dias sem uso (o que vier antes).
        // Vale também para os tokens emitidos antes desta versão. O motivo
        // fica marcado na requisição para o 401 trazer "token_expirado".
        Sanctum::authenticateAccessTokensUsing(function (PersonalAccessToken $token, bool $valido) {
            if ($valido && ! self::tokenExpirado($token)) {
                return true;
            }

            if (self::tokenExpirado($token) || ($token->expires_at && $token->expires_at->isPast())) {
                request()->attributes->set('token_expirado', true);
            }

            return false;
        });
    }

    public static function tokenExpirado(PersonalAccessToken $token): bool
    {
        $criado = $token->created_at;
        if (! $criado) {
            return false;
        }

        $ultimoUso = $token->last_used_at ?? $criado;

        return $criado->lt(now()->subDays((int) config('sanctum.expiracao_dias')))
            || $ultimoUso->lt(now()->subDays((int) config('sanctum.inatividade_dias')));
    }

    /** Validade absoluta do token (a de inatividade é móvel: renova a cada uso). */
    public static function tokenExpiraEm(PersonalAccessToken $token): \Carbon\CarbonInterface
    {
        $absoluta = $token->created_at->copy()->addDays((int) config('sanctum.expiracao_dias'));

        return $token->expires_at && $token->expires_at->lt($absoluta) ? $token->expires_at : $absoluta;
    }
}
