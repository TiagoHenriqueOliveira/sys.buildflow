<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AuthController as AuthWebController;
use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Providers\AppServiceProvider;
use App\Services\AuditService;
use App\Services\MfaService;
use App\Support\SenhaFicticia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /** Habilidade única dos tokens do app (contrato fae/v1 §2.3). */
    public const HABILIDADE = 'fae-app';

    /** Validade do desafio do segundo passo (contrato §2.1: 5 minutos). */
    public const DESAFIO_MINUTOS = 5;

    /**
     * Login — retorna token Sanctum (ou o desafio do MFA).
     *
     * POST /api/fae/v1/login
     * Body: { "email": "...", "senha": "...", "device_name": "..." }
     *
     * Auditoria 27/11/2026 (S-05) / contrato fae/v1 §2.1:
     * - mesmo bloqueio por conta do web (5 senhas erradas → 15 min, vale para
     *   qualquer IP e soma as tentativas do web e do app): 429 "conta_bloqueada";
     * - senha certa de usuário desativado: 403 "usuario_inativo";
     * - credenciais inválidas: 422 "credenciais_invalidas" (mesma mensagem
     *   para e-mail inexistente e senha errada; mantém "errors.email");
     * - MFA exigido (Usuario::exigeMfaNoApp, decisão D-08: todos os perfis,
     *   ligado por API_MFA_PERFIS): sem autenticador → 403
     *   "mfa_nao_cadastrado"; com autenticador → 200 "mfa_pendente" com o
     *   desafio do segundo passo (POST /login/mfa), sem token;
     * - token só com a habilidade "fae-app" e com "expira_em".
     */
    public function login(Request $request, MfaService $mfa): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'senha' => ['required', 'string'],
        ]);

        $email = mb_strtolower(trim((string) $request->input('email')));
        $chaveErros = 'login.senha.' . sha1($email);

        if (RateLimiter::tooManyAttempts($chaveErros, AuthWebController::MAXIMO_ERROS_SENHA)) {
            $segundos = RateLimiter::availableIn($chaveErros);
            $minutos = max(1, (int) ceil($segundos / 60));

            return response()->json([
                'message' => "Muitas tentativas com senha errada. Tente de novo em {$minutos} minutos.",
                'codigo' => 'conta_bloqueada',
            ], 429)->header('Retry-After', (string) $segundos);
        }

        $usuario = Usuario::where('user_email', $email)->first();

        if (! $usuario) {
            SenhaFicticia::conferir((string) $request->input('senha'));
        }

        if (! $usuario || ! Hash::check((string) $request->input('senha'), $usuario->user_senha)) {
            RateLimiter::hit($chaveErros, AuthWebController::BLOQUEIO_SENHA_SEGUNDOS);
            AuditService::log('autenticacao', 'login_falha', (int) $usuario?->user_id, [], ['email' => $email, 'ip' => $request->ip(), 'canal' => 'api']);

            return response()->json([
                'message' => 'Credenciais inválidas.',
                'errors' => ['email' => ['Credenciais inválidas.']],
                'codigo' => 'credenciais_invalidas',
            ], 422);
        }

        RateLimiter::clear($chaveErros);

        if (! $usuario->user_ativo) {
            return response()->json([
                'message' => 'Usuário desativado. Procure o administrador.',
                'codigo' => 'usuario_inativo',
            ], 403);
        }

        $deviceName = (string) $request->input('device_name', 'flutter_app');

        if (! $usuario->exigeMfaNoApp()) {
            return $this->emitirToken($usuario, $deviceName, $request, false);
        }

        if (! $usuario->temMfaConfigurado()) {
            AuditService::log('autenticacao', 'login_sem_mfa', $usuario->user_id, [], ['ip' => $request->ip(), 'canal' => 'api']);

            return response()->json([
                'message' => 'Cadastre o aplicativo autenticador no sistema web antes de entrar pelo app.',
                'codigo' => 'mfa_nao_cadastrado',
            ], 403);
        }

        if ($mfa->bloqueado($usuario)) {
            return $this->mfaBloqueado($usuario, $mfa, $request);
        }

        $desafio = Str::random(64);
        $expiraEm = now()->addMinutes(self::DESAFIO_MINUTOS);
        Cache::put(self::chaveDesafio($desafio), [
            'usuario_id' => $usuario->user_id,
            'device_name' => $deviceName,
        ], $expiraEm);

        return response()->json([
            'mfa_pendente' => true,
            'desafio' => $desafio,
            'desafio_expira_em' => $expiraEm->toIso8601String(),
        ]);
    }

    /**
     * Segundo passo do login com MFA (contrato fae/v1 §2.2).
     *
     * POST /api/fae/v1/login/mfa
     * Body: { "desafio": "...", "codigo": "123456" }
     *
     * O desafio é de uso único e vale 5 minutos. O bloqueio por erros de
     * código (5 → 15 min) e o "código já usado" são os mesmos do web
     * (MfaService), por usuário, de qualquer IP.
     */
    public function loginMfa(Request $request, MfaService $mfa): JsonResponse
    {
        $request->validate([
            'desafio' => ['required', 'string', 'max:128'],
            'codigo' => ['required', 'string', 'max:10'],
        ]);

        $chave = self::chaveDesafio((string) $request->input('desafio'));
        $pendente = Cache::get($chave);
        $usuario = is_array($pendente) ? Usuario::find($pendente['usuario_id'] ?? 0) : null;

        if (! $usuario || ! $usuario->user_ativo) {
            Cache::forget($chave);

            return response()->json([
                'message' => 'O login expirou. Entre de novo com e-mail e senha.',
                'codigo' => 'desafio_invalido',
            ], 422);
        }

        if ($mfa->bloqueado($usuario)) {
            Cache::forget($chave);

            return $this->mfaBloqueado($usuario, $mfa, $request);
        }

        if (! $mfa->verificar($usuario, (string) $request->input('codigo'))) {
            AuditService::log('autenticacao', 'mfa_invalido', $usuario->user_id, [], ['ip' => $request->ip(), 'canal' => 'api']);

            if ($mfa->bloqueado($usuario)) {
                Cache::forget($chave);

                return $this->mfaBloqueado($usuario, $mfa, $request);
            }

            return response()->json([
                'message' => 'Código inválido ou já usado.',
                'codigo' => 'mfa_invalido',
            ], 422);
        }

        Cache::forget($chave);

        return $this->emitirToken($usuario, (string) ($pendente['device_name'] ?? 'flutter_app'), $request, true);
    }

    private function mfaBloqueado(Usuario $usuario, MfaService $mfa, Request $request): JsonResponse
    {
        AuditService::log('autenticacao', 'mfa_bloqueado', $usuario->user_id, [], ['ip' => $request->ip(), 'canal' => 'api']);

        return response()->json([
            'message' => "Muitas tentativas com código errado. Tente de novo em {$mfa->minutosParaLiberar($usuario)} minutos.",
            'codigo' => 'mfa_bloqueado',
        ], 429)->header('Retry-After', (string) $mfa->segundosParaLiberar($usuario));
    }

    private function emitirToken(Usuario $usuario, string $deviceName, Request $request, bool $comMfa): JsonResponse
    {
        // Revogar tokens antigos do mesmo device_name (evita acúmulo)
        $usuario->tokens()->where('name', $deviceName)->delete();

        $novo = $usuario->createToken(
            $deviceName,
            [self::HABILIDADE],
            now()->addDays((int) config('sanctum.expiracao_dias')),
        );

        AuditService::log('autenticacao', 'login', $usuario->user_id, [], ['mfa' => $comMfa, 'ip' => $request->ip(), 'canal' => 'api']);

        return response()->json([
            'token'      => $novo->plainTextToken,
            'token_type' => 'Bearer',
            'expira_em'  => AppServiceProvider::tokenExpiraEm($novo->accessToken)->toIso8601String(),
            'usuario'    => self::dadosUsuario($usuario),
        ]);
    }

    /** O desafio nunca é guardado em claro: só o SHA-256 dele vira chave do cache. */
    private static function chaveDesafio(string $desafio): string
    {
        return 'api.mfa.desafio.' . hash('sha256', $desafio);
    }

    /**
     * Dados do usuário para o app. Desde 2026-11-16 inclui as permissões
     * por tela do cadastro "Perfis de Acesso" ("permissoes", ex.:
     * ["orcamentos.ver", ...]) para o app esconder o que o usuário não
     * acessa — a API também bloqueia (403). "acesso_total" = Administrador
     * ou usuário protegido (recebe todas as chaves).
     */
    private static function dadosUsuario(\App\Models\Usuario $usuario): array
    {
        return [
            'id'                 => $usuario->user_id,
            'nome'               => $usuario->user_nome,
            'email'              => $usuario->user_email,
            'nivel_acesso'       => $usuario->user_nivel_acesso,
            'nivel_acesso_label' => \App\Enums\NivelAcesso::tryFrom((int) $usuario->user_nivel_acesso)?->label(),
            'acesso_total'       => $usuario->temAcessoTotal(),
            'vendedor'           => in_array((int) $usuario->user_nivel_acesso, \App\Models\PerfilPermissao::niveisCom(\App\Support\Permissoes::VENDEDOR), true),
            'permissoes'         => $usuario->permissoes(),
        ];
    }

    /**
     * Logout — revoga o token atual.
     *
     * POST /api/fae/v1/logout
     * Header: Authorization: Bearer {token}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    /**
     * Retorna dados do usuário autenticado (+ "expira_em" do token, contrato §2.3).
     *
     * GET /api/fae/v1/me
     * Header: Authorization: Bearer {token}
     */
    public function me(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        return response()->json([
            ...self::dadosUsuario($request->user()),
            'expira_em' => $token instanceof PersonalAccessToken
                ? AppServiceProvider::tokenExpiraEm($token)->toIso8601String()
                : null,
        ]);
    }
}
