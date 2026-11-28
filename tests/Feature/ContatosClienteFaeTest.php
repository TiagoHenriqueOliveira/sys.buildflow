<?php

namespace Tests\Feature;

use App\Enums\NivelAcesso;
use App\Models\Cliente;
use App\Models\ClienteContato;
use App\Models\PerfilPermissao;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoria 27/11/2026, S-03 — decisão D-01 do usuário: contato Comercial só
 * aparece para quem o cadastrou, para o vendedor responsável do cliente, para
 * o perfil Comercial e para quem tem acesso total; contato Técnico, para
 * todos que abrem o cliente. Salvar o cliente não apaga o que o editor não vê.
 */
class ContatosClienteFaeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $vendedor;
    private Usuario $comercial;
    private Usuario $assistencia;
    private Cliente $cliente;
    private ClienteContato $tecnico;
    private ClienteContato $comercialSemAutor;
    private ClienteContato $comercialDoComercial;

    protected function setUp(): void
    {
        parent::setUp();

        PerfilPermissao::sincronizar(NivelAcesso::Vendedor->value, ['clientes.ver', 'clientes.editar', 'vendedor']);
        $this->vendedor = Usuario::factory()->vendedor()->create();
        $this->comercial = Usuario::factory()->comercial()->create();
        $this->assistencia = Usuario::factory()->assistencia()->create();

        $this->cliente = Cliente::factory()->create([
            'cli_cnpj' => '11222333000181', 'cli_cidade' => 'Blumenau', 'cli_uf' => 'SC',
            'cli_vendedor_id' => $this->vendedor->user_id,
        ]);
        $this->tecnico = $this->cliente->contatos()->create(['cli_cont_nome' => 'Joao Manutencao', 'cli_cont_tipo' => 0]);
        $this->comercialSemAutor = $this->cliente->contatos()->create(['cli_cont_nome' => 'Maria Compras', 'cli_cont_tipo' => 1]);
        $this->comercialDoComercial = $this->cliente->contatos()->create([
            'cli_cont_nome' => 'Pedro Diretor', 'cli_cont_tipo' => 1, 'cli_cont_usuario_id' => $this->comercial->user_id,
        ]);
    }

    private function token(Usuario $usuario): string
    {
        $this->app['auth']->forgetGuards();

        return $usuario->createToken('test')->plainTextToken;
    }

    private function nomesNaApi(Usuario $usuario): array
    {
        return collect($this->withToken($this->token($usuario))
            ->getJson("/api/fae/v1/clientes/{$this->cliente->cli_id}")
            ->assertOk()->json('data.contatos'))->pluck('nome')->sort()->values()->all();
    }

    public function test_assistencia_nao_ve_contato_comercial_no_html_nem_no_json(): void
    {
        $this->actingAs($this->assistencia)->get(route('clientes.edit', $this->cliente->cli_id))
            ->assertOk()
            ->assertSee('Joao Manutencao')
            ->assertDontSee('Maria Compras')
            ->assertDontSee('Pedro Diretor');

        $this->assertSame(['Joao Manutencao'], $this->nomesNaApi($this->assistencia));
    }

    public function test_salvar_o_cliente_nao_remove_o_contato_que_o_editor_nao_ve(): void
    {
        $this->actingAs($this->assistencia)->put(route('clientes.update', $this->cliente->cli_id), [
            'cli_nome' => $this->cliente->cli_nome, 'cli_cnpj' => '11222333000181', 'cli_cidade' => 'Blumenau', 'cli_uf' => 'SC',
            'contatos' => [['id' => $this->tecnico->cli_cont_id, 'nome' => 'Joao Manutencao Jr', 'tipo' => 0]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clientes_contatos', ['cli_cont_id' => $this->tecnico->cli_cont_id, 'cli_cont_nome' => 'Joao Manutencao Jr']);
        $this->assertDatabaseHas('clientes_contatos', ['cli_cont_id' => $this->comercialSemAutor->cli_cont_id]);
        $this->assertDatabaseHas('clientes_contatos', ['cli_cont_id' => $this->comercialDoComercial->cli_cont_id]);
        $this->assertSame(3, $this->cliente->contatos()->count());
    }

    public function test_app_antigo_sem_ids_preserva_os_contatos_ocultos(): void
    {
        $this->withToken($this->token($this->assistencia))->putJson("/api/fae/v1/clientes/{$this->cliente->cli_id}", [
            'cli_nome' => $this->cliente->cli_nome, 'cli_cnpj' => '11222333000181', 'cli_cidade' => 'Blumenau', 'cli_uf' => 'SC',
            'cli_vendedor_id' => $this->vendedor->user_id,
            'contatos' => [['nome' => 'Joao Manutencao', 'tipo' => 0]],
        ])->assertOk();

        $this->assertDatabaseHas('clientes_contatos', ['cli_cont_id' => $this->comercialSemAutor->cli_cont_id]);
        $this->assertDatabaseHas('clientes_contatos', ['cli_cont_id' => $this->comercialDoComercial->cli_cont_id]);
        $this->assertSame(3, $this->cliente->contatos()->count());
    }

    public function test_vendedor_responsavel_comercial_e_administrador_veem_os_comerciais(): void
    {
        $todos = ['Joao Manutencao', 'Maria Compras', 'Pedro Diretor'];

        $this->assertSame($todos, $this->nomesNaApi($this->vendedor));
        $this->assertSame($todos, $this->nomesNaApi($this->comercial));
        $this->assertSame($todos, $this->nomesNaApi(Usuario::factory()->administrador()->create()));
    }

    public function test_quem_cadastra_o_contato_comercial_continua_vendo_e_o_colega_nao(): void
    {
        $this->withToken($this->token($this->assistencia))->putJson("/api/fae/v1/clientes/{$this->cliente->cli_id}", [
            'cli_nome' => $this->cliente->cli_nome, 'cli_cnpj' => '11222333000181', 'cli_cidade' => 'Blumenau', 'cli_uf' => 'SC',
            'cli_vendedor_id' => $this->vendedor->user_id,
            'contatos' => [
                ['id' => $this->tecnico->cli_cont_id, 'nome' => 'Joao Manutencao', 'tipo' => 0],
                ['nome' => 'Ana Financeiro', 'tipo' => 1],
            ],
        ])->assertOk();

        $novo = ClienteContato::where('cli_cont_nome', 'Ana Financeiro')->firstOrFail();
        $this->assertSame($this->assistencia->user_id, $novo->cli_cont_usuario_id);

        $this->assertContains('Ana Financeiro', $this->nomesNaApi($this->assistencia));
        $this->assertNotContains('Ana Financeiro', $this->nomesNaApi(Usuario::factory()->assistencia()->create()));
        $this->assertContains('Ana Financeiro', $this->nomesNaApi($this->vendedor));
    }

    public function test_api_traz_id_tipo_e_criado_por_id(): void
    {
        $contatos = collect($this->withToken($this->token($this->comercial))
            ->getJson("/api/fae/v1/clientes/{$this->cliente->cli_id}")->json('data.contatos'))->keyBy('nome');

        $this->assertSame($this->comercialDoComercial->cli_cont_id, $contatos['Pedro Diretor']['id']);
        $this->assertSame(1, $contatos['Pedro Diretor']['tipo']);
        $this->assertSame($this->comercial->user_id, $contatos['Pedro Diretor']['criado_por_id']);
        $this->assertNull($contatos['Maria Compras']['criado_por_id']);
    }
}
