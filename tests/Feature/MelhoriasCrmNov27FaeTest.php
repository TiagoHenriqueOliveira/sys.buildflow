<?php

namespace Tests\Feature;

use App\Enums\NivelOrcamento;
use App\Enums\ResultadoOrcamento;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\RoteiroViagem;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Melhorias pedidas pelo cliente em 2026-11-27: roteiros no histórico do
 * cliente, filtros de prazo/resultado nos orçamentos, exclusão de comentário
 * só pelo autor e filtro de casos de sucesso no mapa de relações.
 */
class MelhoriasCrmNov27FaeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $comercial;

    protected function setUp(): void
    {
        parent::setUp();
        $this->comercial = Usuario::factory()->comercial()->create();
    }

    private function orcamento(array $dados = []): Orcamento
    {
        return Orcamento::create([
            'orc_cliente_id' => Cliente::factory()->create()->cli_id,
            'orc_vendedor_id' => $this->comercial->user_id,
            'orc_nivel' => NivelOrcamento::Medio->value,
            ...$dados,
        ]);
    }

    public function test_historico_do_cliente_mostra_roteiro_com_periodo_e_observacao(): void
    {
        $cliente = Cliente::factory()->create();
        $roteiro = RoteiroViagem::create([
            'crm_rot_vendedor_id' => $this->comercial->user_id,
            'crm_rot_periodo_inicio' => '2026-12-01',
            'crm_rot_periodo_fim' => '2026-12-04',
        ]);
        $roteiro->clientes()->create([
            'crm_rot_cli_cliente_id' => $cliente->cli_id,
            'crm_rot_cli_ordem' => 0,
            'crm_rot_cli_observacao' => 'Cliente pediu retorno em janeiro',
        ]);

        $this->actingAs($this->comercial)->get(route('clientes.edit', $cliente->cli_id))
            ->assertOk()
            ->assertSee('Roteiro de viagem')
            ->assertSee('01/12/2026 a 04/12/2026')
            ->assertSee('Cliente pediu retorno em janeiro');
    }

    public function test_filtro_de_prazo_de_envio(): void
    {
        $dentro = $this->orcamento(['orc_prazo_envio' => '2026-12-10']);
        $this->orcamento(['orc_prazo_envio' => '2026-12-20']);

        $ids = $this->actingAs($this->comercial)
            ->get(route('orcamentos.index', ['f_prazo_de' => '2026-12-01', 'f_prazo_ate' => '2026-12-15']))
            ->viewData('orcamentos')->pluck('orc_id')->all();

        $this->assertSame([$dentro->orc_id], $ids);
    }

    public function test_filtro_de_resultado_inclusive_em_aberto(): void
    {
        $convertido = $this->orcamento(['orc_resultado' => ResultadoOrcamento::Convertido->value]);
        $aberto = $this->orcamento();

        $this->assertSame([$convertido->orc_id], $this->actingAs($this->comercial)
            ->get(route('orcamentos.index', ['f_resultado' => ResultadoOrcamento::Convertido->value]))
            ->viewData('orcamentos')->pluck('orc_id')->all());

        $this->assertSame([$aberto->orc_id], $this->actingAs($this->comercial)
            ->get(route('orcamentos.index', ['f_resultado' => 'aberto']))
            ->viewData('orcamentos')->pluck('orc_id')->all());
    }

    public function test_so_o_autor_exclui_o_comentario(): void
    {
        $outro = Usuario::factory()->comercial()->create();
        $orcamento = $this->orcamento();
        $comentario = $orcamento->comentarios()->create([
            'orc_com_autor_id' => $outro->user_id,
            'orc_com_texto' => 'Comentário do colega',
            'orc_com_criado_em' => now(),
        ]);

        $this->actingAs($this->comercial)
            ->delete(route('orcamentos.destroy-comentario', [$orcamento->orc_id, $comentario->orc_com_id]))
            ->assertForbidden();
        $this->assertDatabaseHas('orcamentos_comentarios', ['orc_com_id' => $comentario->orc_com_id]);

        $this->actingAs($outro)
            ->delete(route('orcamentos.destroy-comentario', [$orcamento->orc_id, $comentario->orc_com_id]))
            ->assertRedirect();
        $this->assertDatabaseMissing('orcamentos_comentarios', ['orc_com_id' => $comentario->orc_com_id]);
    }

    public function test_botao_de_excluir_comentario_de_outro_fica_bloqueado_com_tooltip(): void
    {
        $outro = Usuario::factory()->comercial()->create();
        $orcamento = $this->orcamento();
        $orcamento->comentarios()->create([
            'orc_com_autor_id' => $outro->user_id,
            'orc_com_texto' => 'Comentário do colega',
            'orc_com_criado_em' => now(),
        ]);

        $this->actingAs($this->comercial)->get(route('orcamentos.edit', $orcamento->orc_id))
            ->assertOk()
            ->assertSee('data-bs-title="Só quem incluiu o comentário pode excluí-lo."', false)
            ->assertDontSee(route('orcamentos.destroy-comentario', [$orcamento->orc_id, 1]), false);
    }

    public function test_mapa_de_relacoes_filtra_somente_casos_de_sucesso(): void
    {
        Cliente::factory()->create(['cli_nome' => 'Caso de Sucesso SA', 'cli_caso_sucesso' => true, 'cli_latitude' => -27.1, 'cli_longitude' => -52.6]);
        Cliente::factory()->create(['cli_nome' => 'Cliente Comum SA', 'cli_caso_sucesso' => false, 'cli_latitude' => -27.2, 'cli_longitude' => -52.7]);

        $nomes = $this->actingAs($this->comercial)
            ->get(route('mapa-relacoes.index', ['f_caso_sucesso' => '1']))
            ->viewData('clientes')->pluck('cli_nome')->all();

        $this->assertSame(['Caso de Sucesso SA'], $nomes);
    }
}
