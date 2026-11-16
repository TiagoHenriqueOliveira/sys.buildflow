<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login — retorna token Sanctum.
     *
     * POST /api/v1/login
     * Body: { "email": "...", "senha": "..." }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'senha' => ['required', 'string'],
        ]);

        $usuario = Usuario::where('user_email', $request->email)
            ->where('user_ativo', true)
            ->first();

        if (! $usuario || ! Hash::check($request->senha, $usuario->user_senha)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        // Revogar tokens antigos do mesmo device_name (evita acúmulo)
        $deviceName = $request->input('device_name', 'flutter_app');
        $usuario->tokens()->where('name', $deviceName)->delete();

        $token = $usuario->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'usuario'    => self::dadosUsuario($usuario),
        ]);
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
     * POST /api/v1/logout
     * Header: Authorization: Bearer {token}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    /**
     * Retorna dados do usuário autenticado.
     *
     * GET /api/v1/me
     * Header: Authorization: Bearer {token}
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(self::dadosUsuario($request->user()));
    }
}
