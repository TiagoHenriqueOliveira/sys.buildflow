<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ── Login web ────────────────────────────────────────────────────────────

    public function test_login_web_com_credenciais_validas(): void
    {
        $usuario = Usuario::factory()->create([
            'user_email' => 'tecnico@teste.com',
            'user_senha' => bcrypt('password'),
            'user_ativo' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'tecnico@teste.com',
            'senha' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_login_web_com_senha_errada_falha(): void
    {
        Usuario::factory()->create([
            'user_email' => 'tecnico@teste.com',
            'user_senha' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'tecnico@teste.com',
            'senha' => 'senha-errada',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_login_web_usuario_inativo_bloqueado(): void
    {
        Usuario::factory()->inativo()->create([
            'user_email' => 'inativo@teste.com',
            'user_senha' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'inativo@teste.com',
            'senha' => 'password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_logout_web(): void
    {
        $usuario = Usuario::factory()->create();
        $this->actingAs($usuario);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    // ── Login API ────────────────────────────────────────────────────────────

    public function test_login_api_retorna_token(): void
    {
        Usuario::factory()->create([
            'user_email' => 'api@teste.com',
            'user_senha' => bcrypt('password'),
            'user_ativo' => true,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'api@teste.com',
            'senha' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'usuario']);
    }

    public function test_login_api_com_credenciais_invalidas_retorna_422(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'naoexiste@teste.com',
            'senha' => 'qualquer',
        ]);

        $response->assertStatus(422);
    }

    public function test_logout_api_revoga_token(): void
    {
        $usuario = Usuario::factory()->create();
        $token   = $usuario->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/logout');

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_api_retorna_dados_do_usuario_autenticado(): void
    {
        $usuario = Usuario::factory()->create();
        $token   = $usuario->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonFragment(['email' => $usuario->user_email]);
    }

    public function test_rota_protegida_sem_token_retorna_401(): void
    {
        $response = $this->getJson('/api/v1/atendimentos');

        $response->assertUnauthorized();
    }

    // ── Autorização técnico × administrador ──────────────────────────────────

    public function test_tecnico_nao_acessa_rotas_de_admin(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $this->actingAs($tecnico);

        $response = $this->get('/clientes');

        $response->assertForbidden();
    }

    public function test_administrador_acessa_rotas_de_admin(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->actingAs($admin);

        $response = $this->getJson('/clientes');

        $response->assertSuccessful();
    }

    public function test_usuario_nao_autenticado_e_redirecionado_para_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/');
    }
}
