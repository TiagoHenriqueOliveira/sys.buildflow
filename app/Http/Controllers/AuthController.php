<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SessaoComPrazo;
use App\Models\DispositivoLembrado;
use App\Models\Usuario;
use App\Services\AuditService;
use App\Services\DispositivoLembradoService;
use App\Services\MfaService;
use App\Support\SenhaFicticia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Login do sistema web — desde 2026-11-27 no mesmo desenho do promaq-sys
 * (pedido do cliente):
 *
 * 1. E-mail + senha. Erro sempre com a mesma mensagem, e e-mail inexistente
 *    ou usuário inativo conferem uma senha fictícia (mesmo tempo de
 *    resposta) — o login não revela quais e-mails têm conta. 5 senhas
 *    erradas para o mesmo e-mail bloqueiam por 15 min, de qualquer IP (além
 *    do limite por IP do throttle:login).
 * 2. Código do app autenticador (MFA obrigatório para todos). Quem ainda
 *    não cadastrou entra só para cadastrar (middleware ExigirMfa).
 * 3. "Lembrar dados": o navegador fica lembrado até completar 6h do login
 *    completo e, nesse prazo, a volta vai direto ao passo 2 (a senha nunca é
 *    guardada — ver DispositivoLembradoService).
 * 4. Prazo da sessão (27/11/2026): 6h do login completo, mesmo entrando de
 *    novo só pelo código; vencido, login completo (SessaoComPrazo).
 */
class AuthController extends Controller
{
    private const PENDENTE = 'login.mfa_usuario_id';
    private const LEMBRAR = 'login.lembrar';
    private const DISPOSITIVO = 'login.dispositivo_id';

    public const MAXIMO_ERROS_SENHA = 5;
    public const BLOQUEIO_SENHA_SEGUNDOS = 900;

    public function mostrarLogin(Request $request, DispositivoLembradoService $dispositivos): View|RedirectResponse
    {
        if ($dispositivo = $dispositivos->identificar($request)) {
            $request->session()->put(self::PENDENTE, $dispositivo->disp_usuario_id);
            $request->session()->put(self::DISPOSITIVO, $dispositivo->disp_id);
            $request->session()->put(self::LEMBRAR, true);

            return redirect()->route('login.mfa');
        }

        return view('login.index');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'senha' => ['required', 'string'],
        ], [
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email'    => 'Informe um e-mail válido.',
            'senha.required' => 'O campo senha é obrigatório.',
        ]);

        $email = mb_strtolower(trim((string) $request->input('email')));
        $chaveErros = 'login.senha.' . sha1($email);

        if (RateLimiter::tooManyAttempts($chaveErros, self::MAXIMO_ERROS_SENHA)) {
            $minutos = max(1, (int) ceil(RateLimiter::availableIn($chaveErros) / 60));

            return back()
                ->withErrors(['email' => "Muitas tentativas com senha errada. Tente de novo em {$minutos} minutos."])
                ->withInput($request->only('email'));
        }

        $usuario = Usuario::where('user_email', $email)->first();

        if (! $usuario || ! $usuario->user_ativo) {
            SenhaFicticia::conferir((string) $request->input('senha'));
        }

        if (! $usuario || ! $usuario->user_ativo || ! Hash::check((string) $request->input('senha'), $usuario->user_senha)) {
            RateLimiter::hit($chaveErros, self::BLOQUEIO_SENHA_SEGUNDOS);
            AuditService::log('autenticacao', 'login_falha', (int) $usuario?->user_id, [], ['email' => $email, 'ip' => $request->ip()]);

            return back()
                ->withErrors(['email' => 'E-mail ou senha inválidos.'])
                ->withInput($request->only('email'));
        }

        RateLimiter::clear($chaveErros);
        $request->session()->regenerate();
        $request->session()->forget(self::DISPOSITIVO);
        $request->session()->put(self::LEMBRAR, $request->boolean('lembrar'));

        if ($usuario->temMfaConfigurado()) {
            $request->session()->put(self::PENDENTE, $usuario->user_id);

            return redirect()->route('login.mfa');
        }

        // Ainda sem autenticador: entra só para cadastrá-lo (ExigirMfa).
        $request->session()->forget(self::PENDENTE);
        Auth::login($usuario);
        $request->session()->put(SessaoComPrazo::CHAVE, now()->addHours(DispositivoLembradoService::HORAS)->getTimestamp());
        AuditService::log('autenticacao', 'login', $usuario->user_id, [], ['mfa' => false, 'ip' => $request->ip()]);

        return redirect()->route('mfa.cadastro');
    }

    public function mostrarMfa(Request $request): View|RedirectResponse
    {
        $usuario = Usuario::find($request->session()->get(self::PENDENTE));

        if (! $usuario) {
            return redirect()->route('login');
        }

        return view('login.mfa', [
            'usuario' => $usuario,
            'lembrado' => $request->session()->has(self::DISPOSITIVO),
        ]);
    }

    public function confirmarMfa(Request $request, MfaService $mfa, DispositivoLembradoService $dispositivos): RedirectResponse
    {
        $request->validate(['codigo' => ['required', 'string', 'max:10']], ['codigo.required' => 'Digite o código do aplicativo autenticador.']);

        $usuario = Usuario::find($request->session()->get(self::PENDENTE));

        if (! $usuario || ! $usuario->user_ativo) {
            $this->limparPendencia($request);

            return redirect()->route('login');
        }

        if ($mfa->bloqueado($usuario)) {
            $this->limparPendencia($request);
            AuditService::log('autenticacao', 'mfa_bloqueado', $usuario->user_id, [], ['ip' => $request->ip()]);

            return redirect()->route('login')->withErrors([
                'email' => "Muitas tentativas com código errado. Tente de novo em {$mfa->minutosParaLiberar($usuario)} minutos.",
            ]);
        }

        if (! $mfa->verificar($usuario, (string) $request->input('codigo'))) {
            AuditService::log('autenticacao', 'mfa_invalido', $usuario->user_id, [], ['ip' => $request->ip()]);

            return back()->withErrors(['codigo' => 'Código inválido ou já usado.']);
        }

        $dispositivoId = $request->session()->get(self::DISPOSITIVO);
        $lembrar = (bool) $request->session()->get(self::LEMBRAR, false);
        $this->limparPendencia($request);

        $request->session()->regenerate();
        Auth::login($usuario);

        $dispositivo = $dispositivoId ? DispositivoLembrado::find($dispositivoId) : null;
        if ($dispositivo && (int) $dispositivo->disp_usuario_id === (int) $usuario->user_id) {
            // Só pelo código: a sessão vence junto com o login completo de origem.
            $expiraEm = $dispositivo->disp_expira_em;
            $dispositivos->renovar($dispositivo, $request);
        } else {
            $dispositivo = null;
            $expiraEm = now()->addHours(DispositivoLembradoService::HORAS);
            if ($lembrar) {
                $dispositivos->emitir($usuario, $request, $expiraEm);
            }
        }
        $request->session()->put(SessaoComPrazo::CHAVE, $expiraEm->getTimestamp());

        AuditService::log('autenticacao', 'login', $usuario->user_id, [], ['mfa' => true, 'lembrado' => (bool) $dispositivo, 'ip' => $request->ip()]);

        return redirect()->intended(route('dashboard'));
    }

    /** "Não é você? Entrar com outra conta": esquece este navegador e volta ao e-mail/senha. */
    public function esquecerDispositivo(Request $request, DispositivoLembradoService $dispositivos): RedirectResponse
    {
        $dispositivos->esquecer($request);
        $this->limparPendencia($request);

        return redirect()->route('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function limparPendencia(Request $request): void
    {
        $request->session()->forget([self::PENDENTE, self::DISPOSITIVO, self::LEMBRAR]);
    }
}
