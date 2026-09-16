<?php

namespace Tests\Feature;

use App\Console\Commands\VerificarRecontatoClientes;
use App\Models\Atendimento;
use App\Models\Cliente;
use App\Models\Notificacao;
use App\Models\Orcamento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pedido do cliente (2026-09-16): acesso ao cadastro de Cliente aberto a
 * todos os perfis exceto Tecnico (sem pre-cadastro/aprovacao - removido do
 * escopo), historico consolidado, integridade de CNPJ, e Sistema de
 * Notificacoes (alerta de recontato + retrofit do alerta de comentario de
 * orcamento, CRM03).
 */
class NucleoPersistenciaFaeTest extends TestCase
{
    use RefreshDatabase;

    public function test_assistencia_acessa_cadastro_de_cliente(): void
    {
        $usuario = Usuario::factory()->assistencia()->create();

        $response = $this->actingAs($usuario)->post(route('clientes.store'), [
            'cli_nome' => 'Cliente Teste',
            'cli_cnpj' => '12345678000199',
            'cli_cidade' => 'Blumenau',
            'cli_uf' => 'SC',
        ]);

        $response->assertRedirect(route('clientes.index'));
    }

    public function test_vendedor_acessa_cadastro_de_cliente(): void
    {
        $usuario = Usuario::factory()->vendedor()->create();

        $response = $this->actingAs($usuario)->get(route('clientes.index'));

        $response->assertOk();
    }

    public function test_tecnico_nao_acessa_cadastro_de_cliente(): void
    {
        $usuario = Usuario::factory()->tecnico()->create();

        $this->actingAs($usuario)->get(route('clientes.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('clientes.create'))->assertForbidden();
    }

    public function test_assistencia_e_vendedor_nao_acessam_orcamentos(): void
    {
        $assistencia = Usuario::factory()->assistencia()->create();
        $vendedor = Usuario::factory()->vendedor()->create();

        $this->actingAs($assistencia)->get(route('orcamentos.index'))->assertForbidden();
        $this->actingAs($vendedor)->get(route('orcamentos.index'))->assertForbidden();
    }

    public function test_cnpj_duplicado_e_rejeitado_a_nivel_de_banco(): void
    {
        $cliente = Cliente::factory()->create(['cli_cnpj' => '11222333000144']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Cliente::factory()->create(['cli_cnpj' => '11222333000144']);
    }

    public function test_historico_consolidado_retorna_atendimentos_e_orcamentos_em_ordem(): void
    {
        $cliente = Cliente::factory()->create();
        $atendimento = Atendimento::factory()->create([
            'aten_cliente_id' => $cliente->cli_id,
            'aten_dt_inicio' => '2026-01-10',
        ]);
        Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => Usuario::factory()->comercial()->create()->user_id,
            'orc_ativo' => 1,
            'orc_criado_em' => '2026-03-01',
        ]);

        $historico = $cliente->fresh()->historico();

        $this->assertCount(2, $historico);
        $this->assertSame('Orçamento', $historico->first()['tipo']);
        $this->assertSame('Atendimento', $historico->last()['tipo']);
    }

    public function test_comentario_de_orcamento_com_alerta_cria_notificacao(): void
    {
        $vendedor = Usuario::factory()->comercial()->create();
        $colega = Usuario::factory()->comercial()->create();
        $cliente = Cliente::factory()->create();
        $orcamento = Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_ativo' => 1,
            'orc_criado_em' => now(),
        ]);

        $this->actingAs($vendedor)->post(route('orcamentos.store-comentario', $orcamento->orc_id), [
            'orc_com_texto' => 'Precisa de revisão.',
            'orc_com_alerta_usuario_id' => $colega->user_id,
        ]);

        $this->assertDatabaseHas('notificacoes', [
            'notif_usuario_id' => $colega->user_id,
            'notif_tipo' => 'comentario_orcamento',
        ]);
    }

    public function test_usuario_marca_notificacao_como_lida(): void
    {
        $usuario = Usuario::factory()->comercial()->create();
        $notificacao = Notificacao::notificar($usuario->user_id, 'teste', 'Título', 'Mensagem');

        $response = $this->actingAs($usuario)->postJson(route('notificacoes.marcar-lida', $notificacao->notif_id));

        $response->assertOk();
        $this->assertDatabaseHas('notificacoes', [
            'notif_id' => $notificacao->notif_id,
            'notif_lida' => 1,
        ]);
    }

    public function test_usuario_nao_marca_notificacao_de_outro_usuario(): void
    {
        $dono = Usuario::factory()->comercial()->create();
        $outro = Usuario::factory()->comercial()->create();
        $notificacao = Notificacao::notificar($dono->user_id, 'teste', 'Título', 'Mensagem');

        $this->actingAs($outro)->postJson(route('notificacoes.marcar-lida', $notificacao->notif_id));

        $this->assertDatabaseHas('notificacoes', [
            'notif_id' => $notificacao->notif_id,
            'notif_lida' => 0,
        ]);
    }

    public function test_comando_de_recontato_cria_notificacao_para_cliente_atrasado(): void
    {
        $vendedor = Usuario::factory()->comercial()->create();
        $cliente = Cliente::factory()->create([
            'cli_vendedor_id' => $vendedor->user_id,
            'cli_dias_alerta_recontato' => 30,
        ]);
        Atendimento::factory()->create([
            'aten_cliente_id' => $cliente->cli_id,
            'aten_dt_inicio' => now()->subDays(60)->format('Y-m-d'),
        ]);

        $this->artisan(VerificarRecontatoClientes::class)->assertSuccessful();

        $this->assertDatabaseHas('notificacoes', [
            'notif_usuario_id' => $vendedor->user_id,
            'notif_tipo' => 'recontato_cliente',
        ]);
    }

    public function test_comando_de_recontato_nao_duplica_notificacao_nao_lida(): void
    {
        $vendedor = Usuario::factory()->comercial()->create();
        $cliente = Cliente::factory()->create([
            'cli_vendedor_id' => $vendedor->user_id,
            'cli_dias_alerta_recontato' => 30,
        ]);
        Atendimento::factory()->create([
            'aten_cliente_id' => $cliente->cli_id,
            'aten_dt_inicio' => now()->subDays(60)->format('Y-m-d'),
        ]);

        $this->artisan(VerificarRecontatoClientes::class);
        $this->artisan(VerificarRecontatoClientes::class);

        $this->assertDatabaseCount('notificacoes', 1);
    }

    public function test_comando_de_recontato_ignora_cliente_em_dia(): void
    {
        $vendedor = Usuario::factory()->comercial()->create();
        $cliente = Cliente::factory()->create([
            'cli_vendedor_id' => $vendedor->user_id,
            'cli_dias_alerta_recontato' => 30,
        ]);
        Atendimento::factory()->create([
            'aten_cliente_id' => $cliente->cli_id,
            'aten_dt_inicio' => now()->subDays(5)->format('Y-m-d'),
        ]);

        $this->artisan(VerificarRecontatoClientes::class);

        $this->assertDatabaseCount('notificacoes', 0);
    }
}