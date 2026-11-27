<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\AuditService;
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

    public function store(Request $request, MfaService $mfa, DispositivoLembradoService $dispositivos): RedirectResponse
    {
        $request->validate(['codigo' => ['required', 'string', 'max:10']], ['codigo.required' => 'Digite o código mostrado no aplicativo.']);

        $usuario = $request->user();
        $segredo = $request->session()->get(self::SESSAO_SEGREDO);

        if ($usuario->temMfaConfigurado() || ! $segredo) {
            return redirect()->route('mfa.cadastro');
        }

        if (! $mfa->codigoValidoParaSegredo($segredo, (string) $request->input('codigo'))) {
            return back()->withErrors(['codigo' => 'Código inválido. Confira se a hora do celular está certa e tente de novo.']);
        }

        $mfa->confirmar($usuario, $segredo);
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
    public function redefinir(Request $request, int $usuario, MfaService $mfa, SessaoService $sessoes): RedirectResponse
    {
        $alvo = Usuario::findOrFail($usuario);
        abort_if($alvo->isProtegido() && $alvo->user_id !== $request->user()->user_id, 403, 'Este usuário só pode ser alterado por ele mesmo.');

        $mfa->redefinir($alvo);
        $sessoes->encerrarOutrosAcessos($alvo, $alvo->user_id === $request->user()->user_id ? $request->session()->getId() : null);
        AuditService::log('autenticacao', 'mfa_redefinido', $alvo->user_id);

        return back()->with('success', 'Verificação em duas etapas de "' . $alvo->user_nome . '" redefinida. No próximo acesso ele cadastra o autenticador de novo.');
    }
}
