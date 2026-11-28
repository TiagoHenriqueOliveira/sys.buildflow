<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\CodigoAcessoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Auditoria 27/11/2026 (S-08): o administrador gera, na tela de Usuários,
 * o código de ativação do autenticador (quem ainda não cadastrou) ou o
 * código de redefinição de senha (quem já tem autenticador). O código
 * aparece uma única vez, para ser repassado ao usuário.
 */
class CodigosAcessoController extends Controller
{
    public function store(Request $request, int $usuario, CodigoAcessoService $codigos): RedirectResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', 'in:' . CodigoAcessoService::ATIVACAO_MFA . ',' . CodigoAcessoService::REDEFINIR_SENHA],
        ]);

        $alvo = Usuario::findOrFail($usuario);
        abort_if($alvo->isProtegido() && $alvo->user_id !== $request->user()->user_id, 403, 'Este usuário só pode ser alterado por ele mesmo.');

        if ($dados['tipo'] === CodigoAcessoService::ATIVACAO_MFA && $alvo->temMfaConfigurado()) {
            return back()->withErrors(['codigo' => "\"{$alvo->user_nome}\" já tem autenticador. Para trocar de celular, redefina a verificação em duas etapas."]);
        }

        if ($dados['tipo'] === CodigoAcessoService::REDEFINIR_SENHA && ! $alvo->temMfaConfigurado()) {
            return back()->withErrors(['codigo' => "\"{$alvo->user_nome}\" ainda não tem autenticador: defina a senha pelo cadastro do usuário e gere o código de ativação."]);
        }

        return back()->with('codigo_gerado', self::paraExibir($alvo, $dados['tipo'], $codigos->gerar($alvo, $dados['tipo'], $request->user())));
    }

    /** Conteúdo do aviso que mostra o código uma única vez (usuarios/index). */
    public static function paraExibir(Usuario $alvo, string $tipo, array $gerado): array
    {
        return [
            'nome' => $alvo->user_nome,
            'tipo' => $tipo === CodigoAcessoService::ATIVACAO_MFA ? 'ativação do autenticador' : 'redefinição de senha',
            'codigo' => $gerado['codigo'],
            'expira_em' => $gerado['expira_em']->format('d/m/Y H:i'),
        ];
    }
}
