<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientesApiFaeTest extends TestCase
{
    use RefreshDatabase;

    private function token(Usuario $usuario): string
    {
        return $usuario->createToken('test')->plainTextToken;
    }

    public function test_lista_clientes_sem_token_retorna_401(): void
    {
        $response = $this->getJson('/api/fae/v1/clientes');

        $response->assertUnauthorized();
    }

    public function test_lista_clientes_autenticado_retorna_paginado(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        Cliente::factory()->count(3)->create();

        $response = $this->withToken($this->token($tecnico))->getJson('/api/fae/v1/clientes');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_mostra_detalhe_cliente_com_contatos_equipamentos_localizacoes(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $cliente = Cliente::factory()->create();
        $cliente->contatos()->create([
            'cli_cont_nome' => 'Fulano',
            'cli_cont_tipo' => 1,
        ]);
        $cliente->equipamentos()->create(['cli_equip_descricao' => 'Bomba X']);
        $cliente->localizacoes()->create(['cli_loc_descricao' => 'Galpao 2']);

        $response = $this->withToken($this->token($tecnico))->getJson("/api/fae/v1/clientes/{$cliente->cli_id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $cliente->cli_id)
            ->assertJsonCount(1, 'data.contatos')
            ->assertJsonCount(1, 'data.equipamentos')
            ->assertJsonCount(1, 'data.localizacoes');
    }

    public function test_tecnico_nao_cria_cliente(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();

        $response = $this->withToken($this->token($tecnico))->postJson('/api/fae/v1/clientes', [
            'cli_nome' => 'Cliente Novo',
            'cli_cnpj' => '12345678000199',
            'cli_cidade' => 'Sao Paulo',
            'cli_uf' => 'SP',
        ]);

        $response->assertForbidden();
    }

    public function test_assistencia_e_vendedor_criam_cliente_via_api(): void
    {
        // Pedido do cliente (2026-09-16): cadastro de cliente aberto a todos
        // os perfis exceto Tecnico - alinhado com a mesma regra no Web.
        $assistencia = Usuario::factory()->assistencia()->create();
        $vendedor = Usuario::factory()->vendedor()->create();

        foreach ([$assistencia, $vendedor] as $indice => $usuario) {
            $response = $this->withToken($this->token($usuario))->postJson('/api/fae/v1/clientes', [
                'cli_nome' => 'Cliente Novo '.$indice,
                'cli_cnpj' => str_pad((string) (12345678000100 + $indice), 14, '0', STR_PAD_LEFT),
                'cli_cidade' => 'Sao Paulo',
                'cli_uf' => 'SP',
            ]);

            $response->assertCreated();
        }
    }

    public function test_comercial_cria_cliente_com_contatos_equipamentos_localizacoes(): void
    {
        $comercial = Usuario::factory()->comercial()->create();

        $response = $this->withToken($this->token($comercial))->postJson('/api/fae/v1/clientes', [
            'cli_nome' => 'Cliente Novo',
            'cli_cnpj' => '12345678000199',
            'cli_cidade' => 'Sao Paulo',
            'cli_uf' => 'sp',
            'contatos' => [
                ['nome' => 'Fulano', 'tipo' => 0],
            ],
            'equipamentos' => [
                ['descricao' => 'Bomba X'],
            ],
            'localizacoes' => [
                ['descricao' => 'Sede', 'link_mapa' => 'https://maps.google.com/?q=1,1'],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nome', 'Cliente Novo')
            ->assertJsonPath('data.uf', 'SP')
            ->assertJsonCount(1, 'data.contatos')
            ->assertJsonCount(1, 'data.equipamentos')
            ->assertJsonCount(1, 'data.localizacoes');

        $this->assertDatabaseHas('clientes', ['cli_nome' => 'Cliente Novo', 'cli_cnpj' => '12345678000199']);
    }

    public function test_criar_cliente_com_cnpj_invalido_retorna_422(): void
    {
        $comercial = Usuario::factory()->comercial()->create();

        $response = $this->withToken($this->token($comercial))->postJson('/api/fae/v1/clientes', [
            'cli_nome' => 'Cliente Novo',
            'cli_cnpj' => '123',
            'cli_cidade' => 'Sao Paulo',
            'cli_uf' => 'SP',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('cli_cnpj');
    }

    public function test_administrador_edita_cliente(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $cliente = Cliente::factory()->create(['cli_nome' => 'Nome Antigo']);

        $response = $this->withToken($this->token($admin))->putJson("/api/fae/v1/clientes/{$cliente->cli_id}", [
            'cli_nome' => 'Nome Novo',
            'cli_cnpj' => $cliente->cli_cnpj,
            'cli_cidade' => $cliente->cli_cidade,
            'cli_uf' => $cliente->cli_uf,
        ]);

        $response->assertOk()->assertJsonPath('data.nome', 'Nome Novo');
        $this->assertDatabaseHas('clientes', ['cli_id' => $cliente->cli_id, 'cli_nome' => 'Nome Novo']);
    }

    public function test_autocomplete_retorna_clientes_ativos_filtrados_por_nome(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        Cliente::factory()->create(['cli_nome' => 'Industria Alfa']);
        Cliente::factory()->create(['cli_nome' => 'Industria Beta']);
        Cliente::factory()->inativo()->create(['cli_nome' => 'Industria Alfa Inativa']);

        $response = $this->withToken($this->token($tecnico))->getJson('/api/fae/v1/clientes/autocomplete?term=Alfa');

        $response->assertOk();
        $nomes = collect($response->json('data'))->pluck('nome')->all();
        $this->assertSame(['Industria Alfa'], $nomes);
    }
}