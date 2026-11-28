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
        // Desde 2026-11-16 a API exige "clientes.ver" (Perfis de Acesso);
        // antes a leitura de clientes pela API era aberta a qualquer perfil.
        \App\Models\PerfilPermissao::sincronizar(\App\Enums\NivelAcesso::Tecnico->value, ['clientes.ver']);
        $tecnico = Usuario::factory()->tecnico()->create();
        Cliente::factory()->count(3)->create();

        $response = $this->withToken($this->token($tecnico))->getJson('/api/fae/v1/clientes');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_mostra_detalhe_cliente_com_contatos_equipamentos_localizacoes(): void
    {
        // Desde 2026-11-16 a API exige "clientes.ver" (Perfis de Acesso);
        // antes a leitura de clientes pela API era aberta a qualquer perfil.
        \App\Models\PerfilPermissao::sincronizar(\App\Enums\NivelAcesso::Tecnico->value, ['clientes.ver']);
        $tecnico = Usuario::factory()->tecnico()->create();
        $cliente = Cliente::factory()->create();
        $cliente->contatos()->create([
            'cli_cont_nome' => 'Fulano',
            // Técnico: contato Comercial de outro autor não aparece para o
            // técnico desde a auditoria S-03 (ver ContatosClienteFaeTest).
            'cli_cont_tipo' => 0,
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

    /**
     * Regressao: ClienteRepository grava null em todo campo que nao vem no
     * PUT, e o app so edita parte do cadastro - ele devolve o resto com o
     * valor recebido no GET. O GET precisa trazer todos esses campos.
     */
    public function test_editar_pelo_app_devolvendo_o_detalhe_preserva_campos_que_o_app_nao_edita(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $vendedor = Usuario::factory()->comercial()->create();
        $cliente = Cliente::factory()->create([
            'cli_vendedor_id' => $vendedor->user_id,
            'cli_dias_alerta_recontato' => 30,
            'cli_inscricao_estadual' => '123456',
            'cli_contato_principal' => 'Maria',
            'cli_caso_sucesso' => true,
            'cli_caso_sucesso_descricao' => 'Caldeira entregue',
        ]);
        $cliente->localizacoes()->create(['cli_loc_descricao' => 'Usina 2', 'cli_loc_link_mapa' => 'https://maps.google.com/?q=-21.1,-47.8']);

        $detalhe = $this->withToken($this->token($admin))->getJson("/api/fae/v1/clientes/{$cliente->cli_id}")
            ->assertOk()
            ->assertJsonPath('data.vendedor_id', $vendedor->user_id)
            ->assertJsonPath('data.dias_alerta_recontato', 30)
            ->json('data');

        // Mesmo corpo que ClienteFormScreen._corpoRequisicao monta.
        $this->withToken($this->token($admin))->putJson("/api/fae/v1/clientes/{$cliente->cli_id}", [
            'cli_nome' => 'Nome Novo',
            'cli_cnpj' => $detalhe['cnpj'],
            'cli_cidade' => $detalhe['cidade'],
            'cli_uf' => $detalhe['uf'],
            'cli_vendedor_id' => $detalhe['vendedor_id'],
            'cli_dias_alerta_recontato' => $detalhe['dias_alerta_recontato'],
            'cli_inscricao_estadual' => $detalhe['inscricao_estadual'],
            'cli_contato_principal' => $detalhe['contato_principal'],
            'cli_caso_sucesso' => $detalhe['caso_sucesso'],
            'cli_caso_sucesso_descricao' => $detalhe['caso_sucesso_descricao'],
            'localizacoes' => $detalhe['localizacoes'],
        ])->assertOk();

        $this->assertDatabaseHas('clientes', [
            'cli_id' => $cliente->cli_id,
            'cli_nome' => 'Nome Novo',
            'cli_vendedor_id' => $vendedor->user_id,
            'cli_dias_alerta_recontato' => 30,
            'cli_inscricao_estadual' => '123456',
            'cli_contato_principal' => 'Maria',
            'cli_caso_sucesso' => 1,
            'cli_caso_sucesso_descricao' => 'Caldeira entregue',
        ]);
        $this->assertDatabaseHas('clientes_localizacoes', ['cli_loc_cliente_id' => $cliente->cli_id, 'cli_loc_descricao' => 'Usina 2']);
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