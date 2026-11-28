<?php

namespace Tests\Feature;

use App\Models\CodigoAcesso;
use App\Models\Usuario;
use App\Services\CodigoAcessoService;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Auditoria 27/11/2026, S-08 (decisões do usuário de 28/11/2026):
 * - o autenticador só é cadastrado com o código de ativação de uso único
 *   gerado pelo administrador (72 h) — a senha sozinha não basta;
 * - "Esqueci minha senha" com o código de redefinição do administrador
 *   (24 h) + o código do autenticador;
 * - trocar e-mail ou senha em "Minha conta" exige a senha atual e o
 *   código do autenticador.
 */
class CodigosAcessoFaeTest extends TestCase
{
    use RefreshDatabase;

    private const SENHA = 'SenhaCerta2026';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function servico(): CodigoAcessoService
    {
        return app(CodigoAcessoService::class);
    }

    private function otp(string $segredo = UsuarioFactory::SEGREDO_MFA): string
    {
        return (new Google2FA())->getCurrentOtp($segredo);
    }

    private function semMfa(): Usuario
    {
        return Usuario::factory()->tecnico()->create([
            'user_senha' => Hash::make(self::SENHA), 'user_mfa_secret' => null, 'user_mfa_confirmado_em' => null,
        ]);
    }

    private function comMfa(array $extra = []): Usuario
    {
        return Usuario::factory()->tecnico()->create(['user_senha' => Hash::make(self::SENHA)] + $extra);
    }

    /** Entra só com a senha (conta sem autenticador) e abre a tela de cadastro. */
    private function abrirCadastro(Usuario $usuario): string
    {
        $this->post('/login', ['email' => $usuario->user_email, 'senha' => self::SENHA])->assertRedirect(route('mfa.cadastro'));
        $this->get(route('mfa.cadastro'))->assertOk()->assertSee('Código de ativação');

        return session('mfa.segredo_em_cadastro');
    }

    // ---- Cadastro do autenticador -------------------------------------------------

    public function test_so_a_senha_nao_cadastra_o_autenticador(): void
    {
        $usuario = $this->semMfa();
        $segredo = $this->abrirCadastro($usuario);

        $this->post(route('mfa.cadastro.store'), ['codigo' => $this->otp($segredo)])->assertSessionHasErrors('ativacao');
        $this->post(route('mfa.cadastro.store'), ['ativacao' => 'ABCDE-FGHJK', 'codigo' => $this->otp($segredo)])->assertSessionHasErrors('ativacao');

        $this->assertFalse($usuario->refresh()->temMfaConfigurado());
    }

    public function test_codigo_de_ativacao_cadastra_uma_vez_so(): void
    {
        $usuario = $this->semMfa();
        $codigo = $this->servico()->gerar($usuario, CodigoAcessoService::ATIVACAO_MFA, null)['codigo'];
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{5}-[A-HJ-NP-Z2-9]{5}$/', $codigo);

        $segredo = $this->abrirCadastro($usuario);
        $this->post(route('mfa.cadastro.store'), ['ativacao' => strtolower($codigo), 'codigo' => $this->otp($segredo)])
            ->assertRedirect(route('dashboard'));
        $this->assertTrue($usuario->refresh()->temMfaConfigurado());

        // Gasto: depois de redefinido o MFA, o mesmo código não vale de novo.
        app(\App\Services\MfaService::class)->redefinir($usuario);
        $this->post(route('logout'));
        $segredo = $this->abrirCadastro($usuario);
        $this->post(route('mfa.cadastro.store'), ['ativacao' => $codigo, 'codigo' => $this->otp($segredo)])->assertSessionHasErrors('ativacao');
    }

    public function test_codigo_de_ativacao_vence_em_72_horas_e_o_novo_revoga_o_anterior(): void
    {
        $usuario = $this->semMfa();
        $antigo = $this->servico()->gerar($usuario, CodigoAcessoService::ATIVACAO_MFA, null)['codigo'];
        $novo = $this->servico()->gerar($usuario, CodigoAcessoService::ATIVACAO_MFA, null)['codigo'];

        $this->assertNull($this->servico()->conferir($usuario, CodigoAcessoService::ATIVACAO_MFA, $antigo));
        $this->assertNotNull($this->servico()->conferir($usuario, CodigoAcessoService::ATIVACAO_MFA, $novo));

        $this->travel(73)->hours();
        $this->assertNull($this->servico()->conferir($usuario, CodigoAcessoService::ATIVACAO_MFA, $novo));
    }

    public function test_cinco_codigos_de_ativacao_errados_bloqueiam(): void
    {
        $usuario = $this->semMfa();
        $codigo = $this->servico()->gerar($usuario, CodigoAcessoService::ATIVACAO_MFA, null)['codigo'];

        foreach (range(1, 5) as $i) {
            $this->assertNull($this->servico()->conferir($usuario, CodigoAcessoService::ATIVACAO_MFA, 'ERRAD-OOOO' . $i));
        }
        $this->assertTrue($this->servico()->bloqueado($usuario, CodigoAcessoService::ATIVACAO_MFA));
        $this->assertNull($this->servico()->conferir($usuario, CodigoAcessoService::ATIVACAO_MFA, $codigo));
    }

    public function test_codigo_fica_guardado_so_como_hmac(): void
    {
        $usuario = $this->semMfa();
        $codigo = $this->servico()->gerar($usuario, CodigoAcessoService::ATIVACAO_MFA, null)['codigo'];
        $hash = CodigoAcesso::first()->cod_hash;

        $this->assertSame(64, strlen($hash));
        $this->assertStringNotContainsString(str_replace('-', '', $codigo), $hash);
    }

    public function test_administrador_gera_o_codigo_ao_criar_o_usuario_e_ao_redefinir_o_mfa(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'user_nivel_acesso' => 1, 'user_nome' => 'Novo', 'user_email' => 'novo@fae.com.br',
            'user_senha' => 'Bioenergia2026', 'user_senha_confirmation' => 'Bioenergia2026', 'user_ativo' => 1,
        ])->assertSessionHas('codigo_gerado');
        $novo = Usuario::where('user_email', 'novo@fae.com.br')->first();
        $this->assertNotNull($this->servico()->pendente($novo, CodigoAcessoService::ATIVACAO_MFA));

        $pagina = $this->get(route('usuarios.index'))->assertSee('Repasse ao usuário por um canal seguro')->getContent();
        $this->assertMatchesRegularExpression('/[A-HJ-NP-Z2-9]{5}-[A-HJ-NP-Z2-9]{5}/', $pagina);
        // Só aparece uma vez.
        $this->get(route('usuarios.index'))->assertDontSee('Repasse ao usuário por um canal seguro');

        $comMfa = $this->comMfa();
        $this->actingAs($admin)->delete(route('usuarios.mfa.redefinir', $comMfa->user_id))->assertSessionHas('codigo_gerado');
        $this->assertNotNull($this->servico()->pendente($comMfa, CodigoAcessoService::ATIVACAO_MFA));
    }

    public function test_botao_da_tela_de_usuarios_gera_o_codigo_certo_para_cada_situacao(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $semMfa = $this->semMfa();
        $comMfa = $this->comMfa();

        $this->actingAs($admin)->post(route('usuarios.codigo-acesso', $semMfa->user_id), ['tipo' => CodigoAcessoService::ATIVACAO_MFA])
            ->assertSessionHas('codigo_gerado');
        $this->actingAs($admin)->post(route('usuarios.codigo-acesso', $comMfa->user_id), ['tipo' => CodigoAcessoService::REDEFINIR_SENHA])
            ->assertSessionHas('codigo_gerado');

        // Combinações sem sentido são recusadas.
        $this->actingAs($admin)->post(route('usuarios.codigo-acesso', $comMfa->user_id), ['tipo' => CodigoAcessoService::ATIVACAO_MFA])
            ->assertSessionHasErrors('codigo');
        $this->actingAs($admin)->post(route('usuarios.codigo-acesso', $semMfa->user_id), ['tipo' => CodigoAcessoService::REDEFINIR_SENHA])
            ->assertSessionHasErrors('codigo');
    }

    public function test_so_acesso_total_gera_codigo(): void
    {
        $assistencia = Usuario::factory()->assistencia()->create();

        $this->actingAs($assistencia)->post(route('usuarios.codigo-acesso', $this->semMfa()->user_id), ['tipo' => CodigoAcessoService::ATIVACAO_MFA])
            ->assertForbidden();
        $this->assertSame(0, CodigoAcesso::count());
    }

    // ---- Esqueci minha senha ------------------------------------------------------

    private function redefinir(Usuario $usuario, string $codigo, ?string $otp = null, string $senha = 'NovaSenha2026')
    {
        return $this->post(route('login.redefinir.store'), [
            'email' => $usuario->user_email, 'codigo_redefinicao' => $codigo, 'codigo' => $otp ?? $this->otp(),
            'user_senha' => $senha, 'user_senha_confirmation' => $senha,
        ]);
    }

    public function test_redefine_a_senha_com_o_codigo_do_admin_e_o_autenticador(): void
    {
        $usuario = $this->comMfa();
        $usuario->createToken('app');
        $codigo = $this->servico()->gerar($usuario, CodigoAcessoService::REDEFINIR_SENHA, null)['codigo'];

        $this->get(route('login'))->assertSee('Esqueci minha senha');
        $this->get(route('login.redefinir'))->assertOk();

        $this->redefinir($usuario, $codigo)->assertRedirect(route('login'))->assertSessionHas('success');
        $this->assertTrue(Hash::check('NovaSenha2026', $usuario->refresh()->user_senha));
        $this->assertSame(0, $usuario->tokens()->count(), 'a redefinição encerra os acessos do app');

        // Uso único.
        $this->travel(1)->minutes();
        $this->redefinir($usuario, $codigo, null, 'OutraSenha2026')->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('NovaSenha2026', $usuario->refresh()->user_senha));
    }

    public function test_sem_o_autenticador_ou_com_codigo_errado_nao_redefine(): void
    {
        $usuario = $this->comMfa();
        $codigo = $this->servico()->gerar($usuario, CodigoAcessoService::REDEFINIR_SENHA, null)['codigo'];
        $otpErrado = $this->otp() === '000000' ? '111111' : '000000';

        $this->redefinir($usuario, $codigo, $otpErrado)->assertSessionHasErrors('email');
        $this->redefinir($usuario, 'ABCDE-FGHJK')->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check(self::SENHA, $usuario->refresh()->user_senha));

        // O código não foi gasto pelas tentativas erradas.
        $this->assertNotNull($this->servico()->pendente($usuario, CodigoAcessoService::REDEFINIR_SENHA));
    }

    public function test_redefinicao_vence_em_24_horas_e_nao_revela_o_email(): void
    {
        $usuario = $this->comMfa();
        $codigo = $this->servico()->gerar($usuario, CodigoAcessoService::REDEFINIR_SENHA, null)['codigo'];

        $this->post(route('login.redefinir.store'), [
            'email' => 'ninguem@exemplo.com', 'codigo_redefinicao' => $codigo, 'codigo' => $this->otp(),
            'user_senha' => 'NovaSenha2026', 'user_senha_confirmation' => 'NovaSenha2026',
        ])->assertSessionHasErrors(['email' => 'Não foi possível redefinir a senha. Confira o e-mail e os códigos.']);

        $this->travel(25)->hours();
        $this->redefinir($usuario, $codigo)->assertSessionHasErrors(['email' => 'Não foi possível redefinir a senha. Confira o e-mail e os códigos.']);
    }

    public function test_redefinicao_segue_a_politica_de_senha(): void
    {
        $usuario = $this->comMfa();
        $codigo = $this->servico()->gerar($usuario, CodigoAcessoService::REDEFINIR_SENHA, null)['codigo'];

        $this->redefinir($usuario, $codigo, null, 'curta1')->assertSessionHasErrors('user_senha');
        $this->assertNotNull($this->servico()->pendente($usuario, CodigoAcessoService::REDEFINIR_SENHA));
    }

    // ---- Minha conta --------------------------------------------------------------

    public function test_trocar_email_ou_senha_exige_senha_atual_e_codigo_do_autenticador(): void
    {
        $usuario = $this->comMfa();
        $this->actingAs($usuario);
        $base = ['user_nome' => $usuario->user_nome, 'user_email' => 'outro@fae.com.br'];

        $this->put(route('minha-conta.update'), $base)->assertSessionHasErrors('senha_atual');
        $this->put(route('minha-conta.update'), $base + ['senha_atual' => 'errada'])->assertSessionHasErrors('senha_atual');
        $this->put(route('minha-conta.update'), $base + ['senha_atual' => self::SENHA, 'codigo_mfa' => '12'])->assertSessionHasErrors('codigo_mfa');
        $this->assertNotSame('outro@fae.com.br', $usuario->refresh()->user_email);

        $this->put(route('minha-conta.update'), $base + ['senha_atual' => self::SENHA, 'codigo_mfa' => $this->otp()])
            ->assertSessionHasNoErrors();
        $this->assertSame('outro@fae.com.br', $usuario->refresh()->user_email);

        // Senha nova também pede os dois.
        $this->put(route('minha-conta.update'), ['user_nome' => $usuario->user_nome, 'user_email' => 'outro@fae.com.br',
            'user_senha' => 'NovaSenha2026', 'user_senha_confirmation' => 'NovaSenha2026'])->assertSessionHasErrors('senha_atual');
    }

    public function test_trocar_so_o_nome_nao_pede_senha(): void
    {
        $usuario = $this->comMfa();

        $this->actingAs($usuario)->put(route('minha-conta.update'), ['user_nome' => 'Nome Novo', 'user_email' => $usuario->user_email])
            ->assertSessionHasNoErrors();
        $this->assertSame('Nome Novo', $usuario->refresh()->user_nome);
    }

    public function test_senha_digitada_nao_volta_preenchida_depois_de_erro(): void
    {
        $usuario = $this->comMfa();

        $this->actingAs($usuario)->put(route('minha-conta.update'), [
            'user_nome' => $usuario->user_nome, 'user_email' => 'outro@fae.com.br', 'senha_atual' => 'SegredoDigitado1',
            'user_senha' => 'SenhaNovaDigitada1', 'user_senha_confirmation' => 'SenhaNovaDigitada1',
        ])->assertSessionHasErrors();

        $this->assertNull(session()->getOldInput('senha_atual'));
        $this->assertNull(session()->getOldInput('user_senha'));
        $this->get(route('minha-conta.edit'))->assertDontSee('SegredoDigitado1')->assertDontSee('SenhaNovaDigitada1');
    }
}
