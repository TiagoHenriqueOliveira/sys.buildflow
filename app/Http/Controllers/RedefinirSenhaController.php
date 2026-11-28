<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\AuditService;
use App\Services\CodigoAcessoService;
use App\Services\MfaService;
use App\Services\SessaoService;
use App\Support\SenhaFicticia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * "Esqueci minha senha" (auditoria S-08, decisão do usuário de 28/11/2026):
 * o administrador gera um código de redefinição (uso único, 24 h) e o
 * usuário, na tela de login, informa o e-mail, esse código, o código do
 * autenticador e a senha nova. Não depende de e-mail e o administrador não
 * fica sabendo a senha. A troca encerra todos os acessos do usuário.
 */
class RedefinirSenhaController extends Controller
{
    private const ERRO = 'Não foi possível redefinir a senha. Confira o e-mail e os códigos.';

    public function show(): View
    {
        return view('login.redefinir-senha');
    }

    public function store(Request $request, CodigoAcessoService $codigos, MfaService $mfa, SessaoService $sessoes): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:100'],
            'codigo_redefinicao' => ['required', 'string', 'max:20'],
            'codigo' => ['required', 'string', 'max:10'],
            'user_senha' => ['required', 'string', Password::defaults(), 'max:50', 'confirmed'],
        ], [
            'codigo_redefinicao.required' => 'Digite o código de redefinição que o administrador passou.',
            'codigo.required' => 'Digite o código do aplicativo autenticador.',
            'user_senha.required' => 'Digite a senha nova.',
            'user_senha.min' => 'A senha deve ter no mínimo :min caracteres.',
            'user_senha.password.letters' => 'A senha deve ter ao menos uma letra.',
            'user_senha.password.numbers' => 'A senha deve ter ao menos um número.',
            'user_senha.password.uncompromised' => 'Esta senha aparece em vazamentos de dados conhecidos. Escolha outra.',
            'user_senha.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $usuario = Usuario::where('user_email', mb_strtolower(trim((string) $request->input('email'))))->first();

        if (! $usuario || ! $usuario->user_ativo) {
            SenhaFicticia::conferir((string) $request->input('codigo_redefinicao'));

            return $this->falha();
        }

        foreach ([[$codigos->bloqueado($usuario, CodigoAcessoService::REDEFINIR_SENHA), fn () => $codigos->minutosParaLiberar($usuario, CodigoAcessoService::REDEFINIR_SENHA)],
                  [$mfa->bloqueado($usuario), fn () => $mfa->minutosParaLiberar($usuario)]] as [$bloqueado, $minutos]) {
            if ($bloqueado) {
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Muitas tentativas com código errado. Tente de novo em ' . $minutos() . ' minutos.']);
            }
        }

        $registro = $codigos->conferir($usuario, CodigoAcessoService::REDEFINIR_SENHA, (string) $request->input('codigo_redefinicao'));

        if (! $registro || ! $mfa->verificar($usuario, (string) $request->input('codigo'))) {
            AuditService::log('autenticacao', 'redefinicao_falha', $usuario->user_id, [], ['ip' => $request->ip()]);

            return $this->falha();
        }

        DB::transaction(function () use ($usuario, $registro, $codigos, $request) {
            $usuario->forceFill(['user_senha' => Hash::make((string) $request->input('user_senha'))])->save();
            $codigos->consumir($registro);
        });
        $sessoes->encerrarOutrosAcessos($usuario);
        RateLimiter::clear('login.senha.' . sha1((string) $usuario->user_email));
        AuditService::log('autenticacao', 'senha_redefinida', $usuario->user_id, [], ['ip' => $request->ip()]);

        return redirect()->route('login')->with('success', 'Senha alterada. Entre com a senha nova.');
    }

    private function falha(): RedirectResponse
    {
        return back()->withInput(request()->only('email'))->withErrors(['email' => self::ERRO]);
    }
}
