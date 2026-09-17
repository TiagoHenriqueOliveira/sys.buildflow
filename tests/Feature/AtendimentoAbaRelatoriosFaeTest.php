<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pedido do cliente (2026-09-17): aba "Relatorios" no cadastro de
 * Atendimento, listando os relatorios ja preenchidos com link pra tela de
 * preenchimento e pro PDF de cada um (AtendimentosController::getRelatorios()).
 */
class AtendimentoAbaRelatoriosFaeTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_relatorios_do_proprio_atendimento(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]);
        $relatorio = AtendimentoRelatorio::factory()->for($atendimento, 'atendimento')->create();

        $response = $this->actingAs($tecnico)->getJson(route('atendimentos.get-relatorios', $atendimento->aten_id));

        $response->assertOk();
        $response->assertJsonCount(1, 'relatorios');
        $response->assertJsonFragment([
            'id' => $relatorio->aten_rel_id,
            'url_preenchimento' => route('atendimentos-relatorios.show', $relatorio->aten_rel_id),
            'url_pdf' => route('atendimentos-relatorios.pdf', $relatorio->aten_rel_id),
        ]);
    }

    public function test_lista_vazia_quando_atendimento_nao_tem_relatorio(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]);

        $response = $this->actingAs($tecnico)->getJson(route('atendimentos.get-relatorios', $atendimento->aten_id));

        $response->assertOk();
        $response->assertJsonCount(0, 'relatorios');
    }

    public function test_tecnico_nao_ve_relatorios_de_atendimento_de_outro_tecnico(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $atendimento = Atendimento::factory()->create(['aten_usuario_id' => $tecnicoA->user_id]);
        AtendimentoRelatorio::factory()->for($atendimento, 'atendimento')->create();

        $this->actingAs($tecnicoB)
            ->getJson(route('atendimentos.get-relatorios', $atendimento->aten_id))
            ->assertForbidden();
    }
}