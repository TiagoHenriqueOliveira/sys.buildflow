<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Auditoria 27/11/2026, S-05 (parte 2) / contrato fae/v1 §2.1 e §2.2:
 * MFA no login do app para todos os perfis (decisão D-08), ligado pela
 * flag API_MFA_PERFIS. Desafio de uso único válido por 5 minutos; código
 * errado, reusado e bloqueio após 5 erros iguais ao web.
 */
class ApiMfaFaeTest extends TestCase
{
    use RefreshDatabase;

    private const SENHA = 'SenhaCerta2026';

    private int $ip = 1;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.api_mfa_perfis' => 'todos']);
    }

    private function usuario(array $extra = []): Usuario
    {
        return Usuario::factory()->tecnico()->create(['user_senha' => Hash::make(self::SENHA)] + $extra);
    }

    /** Cada chamada sai de um IP diferente, para não esbarrar no limitador por IP. */
    private function postar(string $uri, array $dados)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.' . $this->ip++])->postJson($uri, $dados);
    }

    private function login(Usuario $usuario)
    {
        return $this->postar('/api/fae/v1/login', ['email' => $usuario->user_email, 'senha' => self::SENHA, 'device_name' => 'teste']);
    }

    private function codigo(): string
    {
        return (new Google2FA())->getCurrentOtp(UsuarioFactory::SEGREDO_MFA);
    }

    private function codigoErrado(): string
    {
        return $this->codigo() === '000000' ? '111111' : '000000';
    }

    public function test_senha_certa_devolve_o_desafio_e_nao_o_token(): void
    {
        $resposta = $this->login($this->usuario())->assertOk()
            ->assertJsonPath('mfa_pendente', true)
            ->assertJsonMissingPath('token');

        $this->assertSame(64, strlen($resposta->json('desafio')));
        $this->assertNotNull($resposta->json('desafio_expira_em'));
    }

    public function test_codigo_certo_entrega_o_token_do_app(): void
    {
        $usuario = $this->usuario();
        $desafio = $this->login($usuario)->json('desafio');

        $resposta = $this->postar('/api/fae/v1/login/mfa', ['desafio' => $desafio, 'codigo' => $this->codigo()])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('usuario.id', $usuario->user_id);

        $this->assertNotNull($resposta->json('expira_em'));
        $this->assertSame(['fae-app'], $usuario->tokens()->first()->abilities);

        $this->app['auth']->forgetGuards();
        $this->withToken($resposta->json('token'))->getJson('/api/fae/v1/me')->assertOk();
    }

    public function test_desafio_e_de_uso_unico(): void
    {
        $desafio = $this->login($this->usuario())->json('desafio');
        $this->postar('/api/fae/v1/login/mfa', ['desafio' => $desafio, 'codigo' => $this->codigo()])->assertOk();

        $this->postar('/api/fae/v1/login/mfa', ['desafio' => $desafio, 'codigo' => $this->codigo()])
            ->assertStatus(422)->assertJsonPath('codigo', 'desafio_invalido');
    }

    public function test_codigo_ja_usado_e_recusado(): void
    {
        $usuario = $this->usuario();
        $codigo = $this->codigo();
        $this->postar('/api/fae/v1/login/mfa', ['desafio' => $this->login($usuario)->json('desafio'), 'codigo' => $codigo])->assertOk();

        $this->postar('/api/fae/v1/login/mfa', ['desafio' => $this->login($usuario)->json('desafio'), 'codigo' => $codigo])
            ->assertStatus(422)->assertJsonPath('codigo', 'mfa_invalido');
    }

    public function test_desafio_vence_em_5_minutos(): void
    {
        $desafio = $this->login($this->usuario())->json('desafio');
        $this->travel(6)->minutes();

        $this->postar('/api/fae/v1/login/mfa', ['desafio' => $desafio, 'codigo' => $this->codigo()])
            ->assertStatus(422)->assertJsonPath('codigo', 'desafio_invalido');
    }

    public function test_desafio_inventado_e_recusado(): void
    {
        $this->postar('/api/fae/v1/login/mfa', ['desafio' => str_repeat('a', 64), 'codigo' => '123456'])
            ->assertStatus(422)->assertJsonPath('codigo', 'desafio_invalido');
    }

    public function test_cinco_codigos_errados_bloqueiam_inclusive_o_web(): void
    {
        $usuario = $this->usuario();
        $desafio = $this->login($usuario)->json('desafio');

        foreach (range(1, 4) as $i) {
            $this->postar('/api/fae/v1/login/mfa', ['desafio' => $desafio, 'codigo' => $this->codigoErrado()])
                ->assertStatus(422)->assertJsonPath('codigo', 'mfa_invalido');
        }
        $this->postar('/api/fae/v1/login/mfa', ['desafio' => $desafio, 'codigo' => $this->codigoErrado()])
            ->assertStatus(429)->assertJsonPath('codigo', 'mfa_bloqueado')->assertHeader('Retry-After');

        // O desafio morreu e um login novo também é barrado até liberar.
        $this->postar('/api/fae/v1/login/mfa', ['desafio' => $desafio, 'codigo' => $this->codigo()])
            ->assertStatus(422)->assertJsonPath('codigo', 'desafio_invalido');
        $this->login($usuario)->assertStatus(429)->assertJsonPath('codigo', 'mfa_bloqueado');
        $this->assertTrue(app(\App\Services\MfaService::class)->bloqueado($usuario));

        $this->travel(16)->minutes();
        $this->login($usuario)->assertOk()->assertJsonPath('mfa_pendente', true);
    }

    public function test_sem_autenticador_cadastrado_nao_entra_pelo_app(): void
    {
        $this->login($this->usuario(['user_mfa_secret' => null, 'user_mfa_confirmado_em' => null]))
            ->assertStatus(403)->assertJsonPath('codigo', 'mfa_nao_cadastrado')->assertJsonMissingPath('token');
    }

    public function test_usuario_desativado_no_meio_do_login_nao_recebe_token(): void
    {
        $usuario = $this->usuario();
        $desafio = $this->login($usuario)->json('desafio');
        $usuario->forceFill(['user_ativo' => false])->save();

        $this->postar('/api/fae/v1/login/mfa', ['desafio' => $desafio, 'codigo' => $this->codigo()])
            ->assertStatus(422)->assertJsonPath('codigo', 'desafio_invalido');
        $this->assertSame(0, $usuario->tokens()->count());
    }

    public function test_vale_para_todos_os_perfis(): void
    {
        foreach (['administrador', 'assistencia', 'comercial', 'vendedor', 'tecnico'] as $perfil) {
            $usuario = Usuario::factory()->{$perfil}()->create(['user_senha' => Hash::make(self::SENHA)]);
            $this->login($usuario)->assertOk()->assertJsonPath('mfa_pendente', true)->assertJsonMissingPath('token');
        }
    }

    public function test_flag_desligada_mantem_o_login_de_um_passo(): void
    {
        config(['auth.api_mfa_perfis' => 'nenhum']);

        $this->login($this->usuario())->assertOk()->assertJsonPath('token_type', 'Bearer')->assertJsonMissingPath('mfa_pendente');
    }
}
