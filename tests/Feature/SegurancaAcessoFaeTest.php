<?php

namespace Tests\Feature;

use App\Models\DispositivoLembrado;
use App\Models\Usuario;
use App\Services\DispositivoLembradoService;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Segurança do acesso web (2026-11-27, "igual ao promaq-sys"): MFA
 * obrigatório para todos, "Lembrar dados", bloqueio por força bruta, login
 * que não revela e-mails, encerramento de acessos e cabeçalhos de segurança.
 */
class SegurancaAcessoFaeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function codigoAtual(string $segredo = UsuarioFactory::SEGREDO_MFA): string
    {
        return (new Google2FA())->getCurrentOtp($segredo);
    }

    private function usuario(array $dados = []): Usuario
    {
        return Usuario::factory()->create(['user_email' => 'ana@teste.com', 'user_senha' => Hash::make('senha-forte'), ...$dados]);
    }

    private function senha(array $extra = [])
    {
        return $this->post('/login', ['email' => 'ana@teste.com', 'senha' => 'senha-forte', ...$extra]);
    }

    public function test_senha_certa_e_codigo_certo_entram(): void
    {
        $usuario = $this->usuario();

        $this->senha()->assertRedirect(route('login.mfa'));
        $this->assertGuest();

        $this->get(route('login.mfa'))->assertOk()->assertSee($usuario->user_nome);

        $this->post(route('login.mfa.store'), ['codigo' => $this->codigoAtual()])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_codigo_errado_nao_entra_e_codigo_nao_pode_ser_reusado(): void
    {
        $this->usuario();
        $this->senha();

        $this->post(route('login.mfa.store'), ['codigo' => '000000'])->assertSessionHasErrors('codigo');
        $this->assertGuest();

        $codigo = $this->codigoAtual();
        $this->post(route('login.mfa.store'), ['codigo' => $codigo]);
        $this->assertAuthenticated();

        $this->post('/logout');
        $this->senha();
        $this->post(route('login.mfa.store'), ['codigo' => $codigo])->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_cinco_codigos_errados_bloqueiam_o_mfa(): void
    {
        $this->usuario();
        $this->senha();

        // IPs diferentes: o bloqueio é por usuário (o throttle:login por IP
        // também existe, mas aqui o alvo é o bloqueio do MFA).
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.2.$i"])->post(route('login.mfa.store'), ['codigo' => '000000']);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.3.1'])->post(route('login.mfa.store'), ['codigo' => $this->codigoAtual()])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_cinco_senhas_erradas_bloqueiam_o_email_mesmo_com_a_senha_certa(): void
    {
        $this->usuario();

        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.$i"])
                ->post('/login', ['email' => 'ana@teste.com', 'senha' => 'errada']);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])->senha()
            ->assertSessionHasErrors(['email' => 'Muitas tentativas com senha errada. Tente de novo em 15 minutos.']);
    }

    public function test_login_nao_revela_se_o_email_existe(): void
    {
        $this->usuario();

        $inexistente = $this->post('/login', ['email' => 'ninguem@teste.com', 'senha' => 'qualquer']);
        $senhaErrada = $this->post('/login', ['email' => 'ana@teste.com', 'senha' => 'qualquer']);

        $this->assertSame(session('errors')->first('email'), 'E-mail ou senha inválidos.');
        $inexistente->assertSessionHasErrors(['email' => 'E-mail ou senha inválidos.']);
        $senhaErrada->assertSessionHasErrors(['email' => 'E-mail ou senha inválidos.']);
    }

    public function test_primeiro_acesso_sem_autenticador_so_acessa_o_cadastro(): void
    {
        $usuario = $this->usuario(['user_mfa_secret' => null, 'user_mfa_confirmado_em' => null]);

        $this->senha()->assertRedirect(route('mfa.cadastro'));
        $this->assertAuthenticatedAs($usuario);

        $this->get(route('dashboard'))->assertRedirect(route('mfa.cadastro'));
        $this->get(route('clientes.index'))->assertRedirect(route('mfa.cadastro'));

        $this->get(route('mfa.cadastro'))->assertOk()->assertSee('<svg', false);
        $segredo = session('mfa.segredo_em_cadastro');
        $ativacao = app(\App\Services\CodigoAcessoService::class)->gerar($usuario, \App\Services\CodigoAcessoService::ATIVACAO_MFA, null)['codigo'];

        $this->post(route('mfa.cadastro.store'), ['ativacao' => $ativacao, 'codigo' => $this->codigoAtual($segredo)])
            ->assertRedirect(route('dashboard'));
        $this->assertTrue($usuario->refresh()->temMfaConfigurado());
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_lembrar_dados_leva_direto_ao_codigo_no_proximo_acesso(): void
    {
        $usuario = $this->usuario();

        $this->senha(['lembrar' => '1']);
        $resposta = $this->post(route('login.mfa.store'), ['codigo' => $this->codigoAtual()]);
        $resposta->assertCookie(DispositivoLembradoService::COOKIE);
        $this->assertSame(1, DispositivoLembrado::where('disp_usuario_id', $usuario->user_id)->count());

        $cookie = $resposta->getCookie(DispositivoLembradoService::COOKIE)->getValue();
        $this->post('/logout');
        $this->flushSession();
        Cache::forget("mfa.ultimo_passo.{$usuario->user_id}");

        // Próximo acesso: sem e-mail/senha, direto para o código.
        $this->withCookie(DispositivoLembradoService::COOKIE, $cookie)
            ->get(route('login'))
            ->assertRedirect(route('login.mfa'));
        $this->get(route('login.mfa'))->assertSee('Não é você?');
    }

    public function test_sessao_cai_com_6h_do_login_completo(): void
    {
        $this->travelTo(now()->startOfMinute());
        $this->usuario();
        $this->senha();
        $this->post(route('login.mfa.store'), ['codigo' => $this->codigoAtual()]);

        $this->travel(5 * 60 + 59)->minutes();
        $this->get(route('dashboard'))->assertOk();

        $this->travel(2)->minutes();
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Sua sessão expirou (limite de 6 horas). Entre novamente.']);
        $this->assertGuest();
    }

    public function test_lembrar_dados_vale_ate_6h_do_login_completo_sem_estender_o_prazo(): void
    {
        $this->travelTo(now()->startOfMinute());
        $usuario = $this->usuario();

        $this->senha(['lembrar' => '1']);
        $cookie = $this->post(route('login.mfa.store'), ['codigo' => $this->codigoAtual()])
            ->getCookie(DispositivoLembradoService::COOKIE)->getValue();
        $this->post('/logout');
        $this->flushSession();
        Cache::forget("mfa.ultimo_passo.{$usuario->user_id}");

        // 5ª hora: volta só pelo código.
        $this->travel(5)->hours();
        $this->withCookie(DispositivoLembradoService::COOKIE, $cookie)
            ->get(route('login'))
            ->assertRedirect(route('login.mfa'));
        $resposta = $this->post(route('login.mfa.store'), ['codigo' => $this->codigoAtual()]);
        $resposta->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($usuario);
        $novoCookie = $resposta->getCookie(DispositivoLembradoService::COOKIE)->getValue();

        // Entrar pelo código não estendeu: 6h01 do login completo, a sessão cai...
        $this->travel(61)->minutes();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();

        // ...e o próximo acesso é o login completo, com a caixa desmarcada.
        $this->flushSession();
        $this->withCookie(DispositivoLembradoService::COOKIE, $novoCookie)
            ->get(route('login'))
            ->assertOk()
            ->assertSee('name="senha"', false)
            ->assertDontSee('checked', false);
    }

    public function test_tela_de_login_sem_a_frase_do_lembrar_dados(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Lembrar dados')
            ->assertDontSee('Não marque em computador compartilhado')
            ->assertDontSee('checked', false);
    }

    public function test_sem_lembrar_dados_nao_guarda_o_navegador(): void
    {
        $this->usuario();
        $this->senha();
        $this->post(route('login.mfa.store'), ['codigo' => $this->codigoAtual()])
            ->assertCookieMissing(DispositivoLembradoService::COOKIE);
        $this->assertSame(0, DispositivoLembrado::count());
    }

    public function test_trocar_a_senha_revoga_aparelhos_lembrados_e_tokens_do_app(): void
    {
        $usuario = $this->usuario();
        DispositivoLembrado::create([
            'disp_usuario_id' => $usuario->user_id, 'disp_token_hash' => hash('sha256', 'x'),
            'disp_criado_em' => now(), 'disp_expira_em' => now()->addDays(30),
        ]);
        $usuario->createToken('app');

        $this->actingAs($usuario)->put(route('minha-conta.update'), [
            'user_nome' => $usuario->user_nome, 'user_email' => $usuario->user_email,
            'user_senha' => 'NovaSenha#2026', 'user_senha_confirmation' => 'NovaSenha#2026',
            'senha_atual' => 'senha-forte', 'codigo_mfa' => $this->codigoAtual(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(0, DispositivoLembrado::count());
        $this->assertSame(0, $usuario->tokens()->count());
    }

    public function test_usuario_desativado_perde_a_sessao(): void
    {
        $usuario = $this->usuario();
        $this->actingAs($usuario)->get(route('dashboard'))->assertOk();

        $usuario->forceFill(['user_ativo' => false])->save();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_administrador_redefine_o_mfa_de_um_usuario(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $usuario = $this->usuario();

        $this->actingAs($admin)->get(route('usuarios.index'))->assertSee('Redefinir verificação em duas etapas');

        $this->actingAs($admin)->delete(route('usuarios.mfa.redefinir', $usuario->user_id))->assertRedirect();
        $this->assertFalse($usuario->refresh()->temMfaConfigurado());
    }

    public function test_nao_administrador_nao_redefine_mfa(): void
    {
        $comercial = Usuario::factory()->comercial()->create();
        $usuario = $this->usuario();

        $this->actingAs($comercial)->delete(route('usuarios.mfa.redefinir', $usuario->user_id))->assertForbidden();
        $this->assertTrue($usuario->refresh()->temMfaConfigurado());
    }

    public function test_segredo_do_mfa_fica_criptografado_no_banco(): void
    {
        $usuario = $this->usuario();

        $bruto = \Illuminate\Support\Facades\DB::table('usuarios')->where('user_id', $usuario->user_id)->value('user_mfa_secret');
        $this->assertNotSame(UsuarioFactory::SEGREDO_MFA, $bruto);
        $this->assertSame(UsuarioFactory::SEGREDO_MFA, $usuario->refresh()->user_mfa_secret);
    }

    public function test_sem_autenticador_nao_abre_midia_nem_pdf_pela_sessao_web(): void
    {
        $usuario = $this->usuario(['user_mfa_secret' => null, 'user_mfa_confirmado_em' => null]);

        $this->actingAs($usuario)->get('/midia/qualquer/arquivo.jpg')->assertRedirect(route('mfa.cadastro'));
        $this->actingAs($usuario)->get('/atendimentos-relatorios/1/pdf')->assertRedirect(route('mfa.cadastro'));
    }

    public function test_cabecalhos_de_seguranca_nas_paginas(): void
    {
        $resposta = $this->get(route('login'));

        $resposta->assertHeader('X-Content-Type-Options', 'nosniff');
        $resposta->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertStringContainsString("frame-ancestors 'self'", (string) $resposta->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("object-src 'none'", (string) $resposta->headers->get('Content-Security-Policy'));
    }

    public function test_mapas_recebem_os_blocos_do_openstreetmap(): void
    {
        // Sem Referer o OpenStreetMap devolve 403 "Access blocked" nos blocos do mapa.
        $resposta = $this->get(route('login'));
        $resposta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('https://tile.openstreetmap.org', (string) $resposta->headers->get('Content-Security-Policy'));
    }
}
