<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\AuditService;
use App\Services\CodigoAcessoService;
use App\Services\DispositivoLembradoService;
use App\Services\MfaService;
use App\Services\SessaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cadastro do app autenticador (MFA) pelo próprio usuário — obrigatório para
 * todos desde 2026-11-27: quem ainda não cadastrou só acessa esta tela e o
 * "Sair" (middleware ExigirMfa). O administrador pode redefinir o MFA de um
 * usuário (ex.: celular perdido); ele cadastra de novo no próximo acesso.
 *
 * Auditoria S-08 (28/11/2026): o cadastro exige o código de ativação de uso
 * único gerado pelo administrador (criação do usuário, redefinição do MFA
 * ou o botão na tela de Usuários) — ter só a senha não basta mais.
 */
class MfaController extends Controller
{
    private const SESSAO_SEGREDO = 'mfa.segredo_em_cadastro';

    public function show(Request $request, MfaService $mfa): View|RedirectResponse
    {
        $usuario = $request->user();

        if ($usuario->temMfaConfigurado()) {
            return redirect()->route('minha-conta.edit');
        }

        $segredo = $request->session()->get(self::SESSAO_SEGREDO);

        if (! $segredo) {
            $segredo = $mfa->gerarSegredo();
            $request->session()->put(self::SESSAO_SEGREDO, $segredo);
        }

        return view('login.mfa-cadastro', [
            'usuario' => $usuario,
            'segredo' => $segredo,
            'qrCode' => $mfa->qrCodeSvg($mfa->uriOtpauth($usuario, $segredo)),
        ]);
    }

    public function store(Request $request, MfaService $mfa, DispositivoLembradoService $dispositivos, CodigoAcessoService $codigos): RedirectResponse
    {
        $request->validate([
            'ativacao' => ['required', 'string', 'max:20'],
            'codigo' => ['required', 'string', 'max:10'],
        ], [
            'ativacao.required' => 'Digite o código de ativação que o administrador passou.',
            'codigo.required' => 'Digite o código mostrado no aplicativo.',
        ]);

        $usuario = $request->user();
        $segredo = $request->session()->get(self::SESSAO_SEGREDO);

        if ($usuario->temMfaConfigurado() || ! $segredo) {
            return redirect()->route('mfa.cadastro');
        }

        if ($codigos->bloqueado($usuario, CodigoAcessoService::ATIVACAO_MFA)) {
            return back()->withErrors(['ativacao' => 'Muitas tentativas com código de ativação errado. Tente de novo em '
                . $codigos->minutosParaLiberar($usuario, CodigoAcessoService::ATIVACAO_MFA) . ' minutos.']);
        }

        $ativacao = $codigos->conferir($usuario, CodigoAcessoService::ATIVACAO_MFA, (string) $request->input('ativacao'));
        if (! $ativacao) {
            AuditService::log('autenticacao', 'ativacao_invalida', $usuario->user_id, [], ['ip' => $request->ip()]);

            return back()->withErrors(['ativacao' => 'Código de ativação inválido ou vencido. Peça um novo ao administrador.']);
        }

        if (! $mfa->codigoValidoParaSegredo($segredo, (string) $request->input('codigo'))) {
            return back()->withErrors(['codigo' => 'Código inválido. Confira se a hora do celular está certa e tente de novo.']);
        }

        $mfa->confirmar($usuario, $segredo);
        $codigos->consumir($ativacao);
        $request->session()->forget(self::SESSAO_SEGREDO);
        $request->session()->regenerate();
        AuditService::log('autenticacao', 'mfa_configurado', $usuario->user_id);

        // "Lembrar dados" marcado no login que levou até aqui.
        if ($request->session()->pull('login.lembrar', false)) {
            $dispositivos->emitir($usuario, $request);
        }

        return redirect()->route('dashboard')->with('success', 'Verificação em duas etapas ativada.');
    }

    /**
     * Administrador redefine o MFA de outro usuário (celular perdido/trocado):
     * derruba os acessos dele e, no próximo login, ele cadastra de novo.
     */
    public function redefinir(Request $request, int $usuario, MfaService $mfa, SessaoService $sessoes, CodigoAcessoService $codigos): RedirectResponse
    {
        $alvo = Usuario::findOrFail($usuario);
        abort_if($alvo->isProtegido() && $alvo->user_id !== $request->user()->user_id, 403, 'Este usuário só pode ser alterado por ele mesmo.');

        $mfa->redefinir($alvo);
        $sessoes->encerrarOutrosAcessos($alvo, $alvo->user_id === $request->user()->user_id ? $request->session()->getId() : null);
        AuditService::log('autenticacao', 'mfa_redefinido', $alvo->user_id);
        // S-08: o código de redefinição de senha pendente não serve mais (pede o autenticador).
        $codigos->revogar($alvo, CodigoAcessoService::REDEFINIR_SENHA);
        $gerado = $codigos->gerar($alvo, CodigoAcessoService::ATIVACAO_MFA, $request->user());

        return back()
            ->with('success', 'Verificação em duas etapas de "' . $alvo->user_nome . '" redefinida. No próximo acesso ele cadastra o autenticador de novo, com o código de ativação abaixo.')
            ->with('codigo_gerado', CodigosAcessoController::paraExibir($alvo, CodigoAcessoService::ATIVACAO_MFA, $gerado));
    }
}
