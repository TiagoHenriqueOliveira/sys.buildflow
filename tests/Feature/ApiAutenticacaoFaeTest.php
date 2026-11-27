<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Auditoria 27/11/2026, S-05 (parte 1) / contrato fae/v1 §2.1 e §2.3:
 * bloqueio por conta no login do app, usuário desativado, expiração do
 * token (corrida e por inatividade), habilidade "fae-app" e política de senha.
 */
class ApiAutenticacaoFaeTest extends TestCase
{
    use RefreshDatabase;

    private const SENHA = 'SenhaCerta2026';

    private function usuario(array $extra = []): Usuario
    {
        return Usuario::factory()->tecnico()->create(['user_senha' => Hash::make(self::SENHA)] + $extra);
    }

    private function login(string $email, string $senha, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/fae/v1/login', ['email' => $email, 'senha' => $senha, 'device_name' => 'teste']);
    }

    private function comToken(string $token)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    public function test_sexta_senha_errada_bloqueia_a_conta_mesmo_de_outro_ip(): void
    {
        $usuario = $this->usuario();

        foreach (range(1, 5) as $i) {
            $this->login($usuario->user_email, 'errada' . $i)
                ->assertStatus(422)->assertJsonPath('codigo', 'credenciais_invalidas')
                ->assertJsonPath('errors.email.0', 'Credenciais inválidas.');
        }

        // De outro IP, e até com a senha certa: conta bloqueada por 15 min.
        $this->login($usuario->user_email, self::SENHA, '10.0.0.2')
            ->assertStatus(429)->assertJsonPath('codigo', 'conta_bloqueada')->assertHeader('Retry-After');

        $this->travel(16)->minutes();
        $this->login($usuario->user_email, self::SENHA, '10.0.0.3')->assertOk();
    }

    public function test_email_inexistente_tem_a_mesma_resposta_da_senha_errada(): void
    {
        $this->login('ninguem@exemplo.com', 'qualquer')
            ->assertStatus(422)->assertJsonPath('codigo', 'credenciais_invalidas');
    }

    public function test_usuario_desativado_com_a_senha_certa_recebe_usuario_inativo(): void
    {
        $usuario = $this->usuario(['user_ativo' => false]);

        $this->login($usuario->user_email, self::SENHA)->assertStatus(403)->assertJsonPath('codigo', 'usuario_inativo');
        $this->login($usuario->user_email, 'errada')->assertStatus(422)->assertJsonPath('codigo', 'credenciais_invalidas');
    }

    public function test_login_devolve_token_fae_app_com_expira_em(): void
    {
        $usuario = $this->usuario();

        $resposta = $this->login($usuario->user_email, self::SENHA)->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'expira_em', 'usuario' => ['id', 'permissoes']]);

        $this->assertSame(['fae-app'], $usuario->tokens()->first()->abilities);
        $this->assertEqualsWithDelta(now()->addDays(30)->getTimestamp(), strtotime($resposta->json('expira_em')), 5);

        $this->comToken($resposta->json('token'))->getJson('/api/fae/v1/me')
            ->assertOk()->assertJsonPath('id', $usuario->user_id)->assertJsonStructure(['expira_em']);
    }

    public function test_token_vence_com_7_dias_sem_uso(): void
    {
        $usuario = $this->usuario();
        $token = $this->login($usuario->user_email, self::SENHA)->json('token');

        $this->travel(6)->days();
        $this->comToken($token)->getJson('/api/fae/v1/me')->assertOk();

        $this->travel(8)->days();
        $this->comToken($token)->getJson('/api/fae/v1/me')
            ->assertStatus(401)->assertJsonPath('codigo', 'token_expirado');
    }

    public function test_token_vence_com_30_dias_mesmo_em_uso(): void
    {
        $usuario = $this->usuario();
        $token = $this->login($usuario->user_email, self::SENHA)->json('token');

        // Usado a cada 6 dias (nunca vence por inatividade) até o 29º dia...
        foreach ([6, 6, 6, 6, 5] as $dias) {
            $this->travel($dias)->days();
            $this->comToken($token)->getJson('/api/fae/v1/me')->assertOk();
        }

        $this->travel(2)->days(); // ...e no 31º dia vence pelo prazo corrido.
        $this->comToken($token)->getJson('/api/fae/v1/me')
            ->assertStatus(401)->assertJsonPath('codigo', 'token_expirado');
    }

    public function test_token_antigo_sem_validade_tambem_vence(): void
    {
        // Tokens emitidos antes desta versão (habilidade "*", sem expires_at).
        $token = $this->usuario()->createToken('antigo')->plainTextToken;

        $this->comToken($token)->getJson('/api/fae/v1/me')->assertOk();
        $this->travel(31)->days();
        $this->comToken($token)->getJson('/api/fae/v1/me')->assertStatus(401)->assertJsonPath('codigo', 'token_expirado');
    }

    public function test_sem_token_ou_token_invalido_recebe_nao_autenticado(): void
    {
        $this->getJson('/api/fae/v1/me')->assertStatus(401)->assertJsonPath('codigo', 'nao_autenticado');
        $this->comToken('1|invalido')->getJson('/api/fae/v1/me')->assertStatus(401)->assertJsonPath('codigo', 'nao_autenticado');
    }

    public function test_token_sem_a_habilidade_fae_app_e_recusado(): void
    {
        $token = $this->usuario()->createToken('outro-uso', ['relatorios'])->plainTextToken;

        $this->comToken($token)->getJson('/api/fae/v1/me')->assertForbidden();
    }

    public function test_politica_de_senha_no_cadastro_de_usuarios_e_na_minha_conta(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $dados = fn (string $senha) => [
            'user_nivel_acesso' => 1, 'user_nome' => 'Novo', 'user_email' => 'novo' . strlen($senha) . '@fae.com.br',
            'user_senha' => $senha, 'user_senha_confirmation' => $senha, 'user_ativo' => 1,
        ];

        $this->actingAs($admin)->post(route('usuarios.store'), $dados('123456'))->assertSessionHasErrors('user_senha');
        $this->actingAs($admin)->post(route('usuarios.store'), $dados('somenteletras'))->assertSessionHasErrors('user_senha');
        $this->actingAs($admin)->post(route('usuarios.store'), $dados('1234567890'))->assertSessionHasErrors('user_senha');
        $this->actingAs($admin)->post(route('usuarios.store'), $dados('Bioenergia2026'))->assertSessionHasNoErrors();

        $this->actingAs($admin)->put(route('minha-conta.update'), [
            'user_nome' => $admin->user_nome, 'user_email' => $admin->user_email,
            'user_senha' => 'abc123', 'user_senha_confirmation' => 'abc123',
        ])->assertSessionHasErrors('user_senha');
    }

    public function test_senha_que_aparece_em_vazamento_e_recusada(): void
    {
        config(['auth.senha_checar_vazamento' => true]);
        $senha = 'Bioenergia2026';
        $sha1 = strtoupper(sha1($senha));
        Http::fake(['api.pwnedpasswords.com/*' => Http::response(substr($sha1, 5) . ":42\r\n")]);
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'user_nivel_acesso' => 1, 'user_nome' => 'Novo', 'user_email' => 'vazada@fae.com.br',
            'user_senha' => $senha, 'user_senha_confirmation' => $senha, 'user_ativo' => 1,
        ])->assertSessionHasErrors(['user_senha' => 'Esta senha aparece em vazamentos de dados conhecidos. Escolha outra.']);
    }
}
