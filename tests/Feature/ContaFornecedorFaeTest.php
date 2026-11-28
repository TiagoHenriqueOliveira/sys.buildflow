<?php

namespace Tests\Feature;

use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Auditoria 27/11/2026, S-12 (decisão D-06 do usuário): a conta do
 * fornecedor (usuário protegido) continua com acesso total no web, mas fica
 * visível aos administradores da FAÉ, não entra pela API do app e tem toda
 * ação registrada na auditoria.
 */
class ContaFornecedorFaeTest extends TestCase
{
    use RefreshDatabase;

    private function fornecedor(): Usuario
    {
        return Usuario::factory()->administrador()->create([
            'user_email' => 'fornecedor@exemplo.com', 'user_senha' => Hash::make('SenhaCerta2026'), 'user_protegido' => true,
        ]);
    }

    public function test_conta_do_fornecedor_nao_entra_pela_api(): void
    {
        $fornecedor = $this->fornecedor();

        $this->postJson('/api/fae/v1/login', ['email' => 'fornecedor@exemplo.com', 'senha' => 'SenhaCerta2026'])
            ->assertForbidden()->assertJsonPath('codigo', 'sem_acesso_api')->assertJsonMissingPath('token');

        // Nem com um token criado antes desta regra.
        $token = $fornecedor->createToken('antigo', ['fae-app'])->plainTextToken;
        $this->withToken($token)->getJson('/api/fae/v1/me')->assertUnauthorized();
    }

    public function test_toda_acao_do_fornecedor_no_web_vai_para_a_auditoria(): void
    {
        $fornecedor = $this->fornecedor();

        $this->actingAs($fornecedor)->get(route('dashboard'))->assertOk();
        $this->actingAs($fornecedor)->get(route('clientes.index'))->assertOk();

        $registros = LogAuditoria::where('log_aud_modulo', 'fornecedor')->where('log_aud_usuario_id', $fornecedor->user_id)->get();
        $this->assertCount(2, $registros);
        $this->assertSame('leitura', $registros->first()->log_aud_acao);
        $this->assertSame('dashboard', $registros->first()->log_aud_dados_novos['rota']);
        $this->assertSame(200, $registros->first()->log_aud_dados_novos['status']);
    }

    public function test_acoes_de_outros_usuarios_nao_entram_nessa_trilha(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())->get(route('dashboard'))->assertOk();

        $this->assertSame(0, LogAuditoria::where('log_aud_modulo', 'fornecedor')->count());
    }

    public function test_administrador_ve_o_ultimo_acesso_do_fornecedor(): void
    {
        $fornecedor = $this->fornecedor();
        LogAuditoria::create(['log_aud_modulo' => 'autenticacao', 'log_aud_acao' => 'login', 'log_aud_registro_id' => $fornecedor->user_id, 'log_aud_usuario_id' => $fornecedor->user_id]);

        $this->actingAs(Usuario::factory()->administrador()->create())->get(route('usuarios.index'))
            ->assertOk()->assertSee('Último acesso:')->assertDontSee('Último acesso: nunca');
    }
}
