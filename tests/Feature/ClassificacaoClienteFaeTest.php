<?php

namespace Tests\Feature;

use App\Models\ClassificacaoCliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NC01 (pendencia #3, ver CLAUDE.md) — admin cadastra as opcoes de
 * classificacao de cliente exibidas na aba Dados Gerais do cadastro de
 * cliente (`ClassificacaoCliente`, ja existia so a tabela, sem tela).
 */
class ClassificacaoClienteFaeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cadastra_classificacao(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('classificacoes-cliente.store'), [
            'cla_cli_nome' => 'A',
        ]);

        $response->assertRedirect(route('classificacoes-cliente.index'));
        $this->assertDatabaseHas('classificacoes_cliente', [
            'cla_cli_nome' => 'A',
            'cla_cli_ativo' => 1,
        ]);
    }

    public function test_nao_permite_nomes_duplicados(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        ClassificacaoCliente::factory()->create(['cla_cli_nome' => 'Ativo']);

        $response = $this->actingAs($admin)->post(route('classificacoes-cliente.store'), [
            'cla_cli_nome' => 'Ativo',
        ]);

        $response->assertSessionHasErrors('cla_cli_nome');
    }

    public function test_admin_atualiza_e_inativa_classificacao(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $classificacao = ClassificacaoCliente::factory()->create(['cla_cli_nome' => 'B']);

        $response = $this->actingAs($admin)->put(route('classificacoes-cliente.update', $classificacao->cla_cli_id), [
            'cla_cli_nome' => 'B - revisado',
            'cla_cli_ativo' => false,
        ]);

        $response->assertRedirect(route('classificacoes-cliente.index'));
        $this->assertDatabaseHas('classificacoes_cliente', [
            'cla_cli_id' => $classificacao->cla_cli_id,
            'cla_cli_nome' => 'B - revisado',
            'cla_cli_ativo' => 0,
        ]);
    }

    public function test_tecnico_nao_acessa_classificacoes(): void
    {
        $tecnico = Usuario::factory()->create();

        $this->actingAs($tecnico)->get(route('classificacoes-cliente.index'))->assertForbidden();
    }
}