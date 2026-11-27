<?php

namespace Tests\Feature;

use App\Enums\NivelAcesso;
use App\Enums\NivelOrcamento;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\PerfilPermissao;
use App\Models\RoteiroViagem;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pedido do cliente (2026-11-27): o PERFIL Vendedor só vê os próprios
 * clientes (e os sem vendedor), orçamentos e roteiros — os demais perfis
 * continuam vendo tudo.
 */
class VisibilidadeVendedorFaeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $ana;
    private Usuario $bruno;

    protected function setUp(): void
    {
        parent::setUp();

        PerfilPermissao::sincronizar(NivelAcesso::Vendedor->value, [
            'clientes.ver', 'clientes.editar', 'orcamentos.ver', 'orcamentos.editar',
            'roteiros.ver', 'roteiros.editar', 'mapa_relacoes.ver', 'vendedor',
        ]);
        $this->ana = Usuario::factory()->vendedor()->create(['user_nome' => 'Ana Vendedora']);
        $this->bruno = Usuario::factory()->vendedor()->create(['user_nome' => 'Bruno Vendedor']);
    }

    private function orcamento(Usuario $vendedor, Cliente $cliente): Orcamento
    {
        return Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_nivel' => NivelOrcamento::Medio->value,
        ]);
    }

    private function roteiro(Usuario $vendedor): RoteiroViagem
    {
        return RoteiroViagem::create([
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-12-01',
            'crm_rot_periodo_fim' => '2026-12-03',
        ]);
    }

    public function test_vendedor_ve_so_os_proprios_clientes_e_os_sem_vendedor(): void
    {
        Cliente::factory()->create(['cli_nome' => 'Cliente da Ana', 'cli_vendedor_id' => $this->ana->user_id]);
        Cliente::factory()->create(['cli_nome' => 'Cliente do Bruno', 'cli_vendedor_id' => $this->bruno->user_id]);
        Cliente::factory()->create(['cli_nome' => 'Prospect sem vendedor', 'cli_vendedor_id' => null]);

        $this->actingAs($this->ana)->get(route('clientes.index'))
            ->assertOk()
            ->assertSee('Cliente da Ana')
            ->assertSee('Prospect sem vendedor')
            ->assertDontSee('Cliente do Bruno');
    }

    public function test_vendedor_nao_abre_cliente_de_outro_vendedor(): void
    {
        $doBruno = Cliente::factory()->create(['cli_vendedor_id' => $this->bruno->user_id]);

        $this->actingAs($this->ana)->get(route('clientes.edit', $doBruno->cli_id))->assertForbidden();
        $this->actingAs($this->ana)->getJson('/api/fae/v1/clientes/' . $doBruno->cli_id, [
            'Authorization' => 'Bearer ' . $this->ana->createToken('t')->plainTextToken,
        ])->assertForbidden();
    }

    public function test_autocomplete_do_vendedor_esconde_cliente_de_outro(): void
    {
        Cliente::factory()->create(['cli_nome' => 'Alfa da Ana', 'cli_vendedor_id' => $this->ana->user_id]);
        Cliente::factory()->create(['cli_nome' => 'Alfa do Bruno', 'cli_vendedor_id' => $this->bruno->user_id]);

        $nomes = collect($this->actingAs($this->ana)->getJson(route('clientes.autocomplete', ['term' => 'Alfa']))->json())->pluck('label');

        $this->assertContains('Alfa da Ana', $nomes);
        $this->assertNotContains('Alfa do Bruno', $nomes);
    }

    public function test_comercial_continua_vendo_todos_os_clientes(): void
    {
        $comercial = Usuario::factory()->comercial()->create();
        Cliente::factory()->create(['cli_nome' => 'Cliente da Ana', 'cli_vendedor_id' => $this->ana->user_id]);
        Cliente::factory()->create(['cli_nome' => 'Cliente do Bruno', 'cli_vendedor_id' => $this->bruno->user_id]);

        $this->actingAs($comercial)->get(route('clientes.index'))
            ->assertSee('Cliente da Ana')
            ->assertSee('Cliente do Bruno');
    }

    public function test_vendedor_nao_atribui_cliente_a_outro_vendedor(): void
    {
        $this->actingAs($this->ana)->post(route('clientes.store'), [
            'cli_nome' => 'Novo', 'cli_cnpj' => '11222333000144', 'cli_cidade' => 'Blumenau', 'cli_uf' => 'SC',
            'cli_vendedor_id' => $this->bruno->user_id,
        ])->assertSessionHasErrors('cli_vendedor_id');
    }

    public function test_vendedor_ve_so_os_proprios_orcamentos_inclusive_como_adicional(): void
    {
        $cliente = Cliente::factory()->create();
        $daAna = $this->orcamento($this->ana, $cliente);
        $doBruno = $this->orcamento($this->bruno, $cliente);
        $conjunto = $this->orcamento($this->bruno, $cliente);
        $conjunto->vendedoresAdicionais()->attach($this->ana->user_id);

        $ids = $this->actingAs($this->ana)->get(route('orcamentos.index'))->viewData('orcamentos')->pluck('orc_id')->all();
        sort($ids);

        $this->assertSame([$daAna->orc_id, $conjunto->orc_id], $ids);
        $this->actingAs($this->ana)->get(route('orcamentos.edit', $doBruno->orc_id))->assertForbidden();
        $this->actingAs($this->ana)->get(route('orcamentos.edit', $conjunto->orc_id))->assertOk();
    }

    public function test_api_de_orcamentos_respeita_o_vendedor(): void
    {
        $cliente = Cliente::factory()->create();
        $this->orcamento($this->ana, $cliente);
        $doBruno = $this->orcamento($this->bruno, $cliente);
        $token = $this->ana->createToken('t')->plainTextToken;

        $this->withToken($token)->getJson('/api/fae/v1/orcamentos')->assertOk()->assertJsonCount(1, 'data');
        $this->withToken($token)->getJson('/api/fae/v1/orcamentos/' . $doBruno->orc_id)->assertForbidden();
    }

    public function test_vendedor_ve_so_os_proprios_roteiros(): void
    {
        $daAna = $this->roteiro($this->ana);
        $doBruno = $this->roteiro($this->bruno);

        $ids = $this->actingAs($this->ana)->get(route('roteiros-viagem.index'))->viewData('roteiros')->pluck('crm_rot_id')->all();

        $this->assertSame([$daAna->crm_rot_id], $ids);
        $this->actingAs($this->ana)->get(route('roteiros-viagem.edit', $doBruno->crm_rot_id))->assertForbidden();

        $token = $this->ana->createToken('t')->plainTextToken;
        $this->withToken($token)->getJson('/api/fae/v1/roteiros-viagem')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_vendedor_nao_cria_roteiro_para_outro_vendedor(): void
    {
        $cliente = Cliente::factory()->create(['cli_vendedor_id' => $this->ana->user_id]);

        $this->actingAs($this->ana)->post(route('roteiros-viagem.store'), [
            'crm_rot_vendedor_id' => $this->bruno->user_id,
            'crm_rot_periodo_inicio' => '2026-12-01',
            'crm_rot_periodo_fim' => '2026-12-02',
            'clientes' => [$cliente->cli_id],
        ])->assertSessionHasErrors('crm_rot_vendedor_id');
    }

    public function test_mapa_de_relacoes_do_vendedor_mostra_so_os_proprios_e_os_sem_vendedor(): void
    {
        $coordenadas = ['cli_latitude' => -26.9, 'cli_longitude' => -49.06];
        $daAna = Cliente::factory()->create(['cli_nome' => 'Cliente da Ana', 'cli_vendedor_id' => $this->ana->user_id] + $coordenadas);
        Cliente::factory()->create(['cli_nome' => 'Cliente do Bruno', 'cli_vendedor_id' => $this->bruno->user_id] + $coordenadas);
        $semVendedor = Cliente::factory()->create(['cli_nome' => 'Prospect sem vendedor', 'cli_vendedor_id' => null] + $coordenadas);

        $resposta = $this->actingAs($this->ana)->get(route('mapa-relacoes.index'))->assertOk();
        $ids = $resposta->viewData('clientes')->pluck('cli_id')->sort()->values()->all();
        $this->assertSame([$daAna->cli_id, $semVendedor->cli_id], $ids);
        $this->assertSame([$this->ana->user_id], $resposta->viewData('vendedores')->pluck('user_id')->all());

        // Filtro forçado com o id do outro vendedor não revela nada.
        $this->assertCount(0, $this->actingAs($this->ana)
            ->get(route('mapa-relacoes.index', ['f_vendedor' => $this->bruno->user_id]))
            ->viewData('clientes'));

        $token = $this->ana->createToken('t')->plainTextToken;
        $this->withToken($token)->getJson('/api/fae/v1/mapa-relacoes')->assertOk()->assertJsonCount(2, 'data');

        $comercial = Usuario::factory()->comercial()->create();
        $this->assertCount(3, $this->actingAs($comercial)->get(route('mapa-relacoes.index'))->viewData('clientes'));
    }

    public function test_orcamento_novo_do_vendedor_vem_com_ele_e_o_campo_travado(): void
    {
        $resposta = $this->actingAs($this->ana)->get(route('orcamentos.create'))->assertOk();

        $this->assertSame($this->ana->user_id, $resposta->viewData('orcamento')->orc_vendedor_id);
        $resposta->assertSee('name="orc_vendedor_id" value="' . $this->ana->user_id . '"', false);
        $resposta->assertSee('Ana Vendedora');
        $resposta->assertDontSee('id="orc_vendedor_id"', false);
    }

    public function test_vendedor_nao_troca_o_vendedor_responsavel_nem_forcando_o_envio(): void
    {
        $cliente = Cliente::factory()->create(['cli_vendedor_id' => $this->ana->user_id]);

        $this->actingAs($this->ana)->post(route('orcamentos.store'), [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $this->bruno->user_id,
            'orc_nivel' => NivelOrcamento::Medio->value,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($this->ana->user_id, Orcamento::latest('orc_id')->first()->orc_vendedor_id);

        // Vendedor adicional editando o orçamento do Bruno: não se coloca como responsável.
        $conjunto = $this->orcamento($this->bruno, $cliente);
        $conjunto->vendedoresAdicionais()->attach($this->ana->user_id);
        $this->actingAs($this->ana)->put(route('orcamentos.update', $conjunto->orc_id), [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $this->ana->user_id,
            'orc_nivel' => NivelOrcamento::Medio->value,
        ])->assertRedirect();
        $this->assertSame($this->bruno->user_id, $conjunto->fresh()->orc_vendedor_id);
    }

    public function test_comercial_escolhe_o_vendedor_responsavel(): void
    {
        $comercial = Usuario::factory()->comercial()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($comercial)->get(route('orcamentos.create'))
            ->assertOk()
            ->assertSee('id="orc_vendedor_id"', false)
            ->assertDontSee('id="orc_vendedor_nome"', false);

        $this->actingAs($comercial)->post(route('orcamentos.store'), [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $this->bruno->user_id,
            'orc_nivel' => NivelOrcamento::Medio->value,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($this->bruno->user_id, Orcamento::latest('orc_id')->first()->orc_vendedor_id);
    }

    public function test_comercial_ve_orcamentos_e_roteiros_de_todos(): void
    {
        $comercial = Usuario::factory()->comercial()->create();
        $cliente = Cliente::factory()->create();
        $this->orcamento($this->ana, $cliente);
        $this->orcamento($this->bruno, $cliente);
        $this->roteiro($this->ana);
        $this->roteiro($this->bruno);

        $this->assertCount(2, $this->actingAs($comercial)->get(route('orcamentos.index'))->viewData('orcamentos'));
        $this->assertCount(2, $this->actingAs($comercial)->get(route('roteiros-viagem.index'))->viewData('roteiros'));
    }
}
