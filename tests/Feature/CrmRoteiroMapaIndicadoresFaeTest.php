<?php

namespace Tests\Feature;

use App\Enums\NivelAcesso;
use App\Enums\ResultadoVisitaRoteiro;
use App\Enums\StatusRoteiroViagem;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\RoteiroViagem;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sessao 06b do cronograma FAE (CRM Comercial) - CRM05/06 (roteiro de
 * viagem: saida e retorno por cliente), CRM07 (mapa de relacoes de
 * clientes) e CRM08 (painel de indicadores comerciais, com dado real de resultado do orcamento a partir de 2026-09-17).
 */
class CrmRoteiroMapaIndicadoresFaeTest extends TestCase
{
    use RefreshDatabase;

    private function criarVendedor(): Usuario
    {
        return Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Comercial->value]);
    }

    public function test_comercial_cadastra_roteiro_de_viagem_com_clientes(): void
    {
        $vendedor = $this->criarVendedor();
        $cliente1 = Cliente::factory()->create();
        $cliente2 = Cliente::factory()->create();

        $response = $this->actingAs($vendedor)->post(route('roteiros-viagem.store'), [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-09-15',
            'crm_rot_periodo_fim' => '2026-09-18',
            'clientes' => [$cliente1->cli_id, $cliente2->cli_id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('crm_roteiros_viagem', [
            'crm_rot_vendedor_id' => $vendedor->user_id,
        ]);
        $roteiro = RoteiroViagem::first();
        $this->assertCount(2, $roteiro->clientes);
    }

    /**
     * Pedido do cliente (2026-09-11): link da rota pronta no Google Maps,
     * pra o app Android abrir direto (CRM09, ainda nao implementado —
     * aqui so cobre o cadastro/validacao no web).
     */
    public function test_cadastra_roteiro_com_link_do_google_maps(): void
    {
        $vendedor = $this->criarVendedor();
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($vendedor)->post(route('roteiros-viagem.store'), [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-09-15',
            'crm_rot_periodo_fim' => '2026-09-18',
            'crm_rot_link_mapa' => 'https://maps.app.goo.gl/abc123',
            'clientes' => [$cliente->cli_id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('crm_roteiros_viagem', [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_link_mapa' => 'https://maps.app.goo.gl/abc123',
        ]);
    }

    public function test_link_do_google_maps_invalido_e_rejeitado(): void
    {
        $vendedor = $this->criarVendedor();
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($vendedor)->post(route('roteiros-viagem.store'), [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-09-15',
            'crm_rot_periodo_fim' => '2026-09-18',
            'crm_rot_link_mapa' => 'nao-e-uma-url',
            'clientes' => [$cliente->cli_id],
        ]);

        $response->assertSessionHasErrors('crm_rot_link_mapa');
    }

    /**
     * Pedido do cliente (2026-09-11): o campo booleano "Ativo" nao tinha
     * uso real e foi trocado por um status de viagem de verdade.
     */
    public function test_roteiro_novo_comeca_com_status_nao_iniciada(): void
    {
        $vendedor = $this->criarVendedor();
        $cliente = Cliente::factory()->create();

        $this->actingAs($vendedor)->post(route('roteiros-viagem.store'), [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-09-15',
            'crm_rot_periodo_fim' => '2026-09-18',
            'clientes' => [$cliente->cli_id],
        ]);

        $roteiro = RoteiroViagem::first();
        $this->assertSame(StatusRoteiroViagem::NaoIniciada, $roteiro->crm_rot_status);
    }

    public function test_atualiza_status_do_roteiro(): void
    {
        $vendedor = $this->criarVendedor();
        $cliente = Cliente::factory()->create();

        $this->actingAs($vendedor)->post(route('roteiros-viagem.store'), [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-09-15',
            'crm_rot_periodo_fim' => '2026-09-18',
            'clientes' => [$cliente->cli_id],
        ]);
        $roteiro = RoteiroViagem::first();

        $response = $this->actingAs($vendedor)->put(route('roteiros-viagem.update', $roteiro->crm_rot_id), [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-09-15',
            'crm_rot_periodo_fim' => '2026-09-18',
            'crm_rot_status' => StatusRoteiroViagem::Concluida->value,
            'clientes' => [$cliente->cli_id],
        ]);

        $response->assertRedirect();
        $roteiro->refresh();
        $this->assertSame(StatusRoteiroViagem::Concluida, $roteiro->crm_rot_status);
    }

    public function test_registra_retorno_do_roteiro_por_cliente(): void
    {
        $vendedor = $this->criarVendedor();
        $cliente = Cliente::factory()->create();

        $this->actingAs($vendedor)->post(route('roteiros-viagem.store'), [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-09-15',
            'crm_rot_periodo_fim' => '2026-09-18',
            'clientes' => [$cliente->cli_id],
        ]);
        $roteiro = RoteiroViagem::first();

        $response = $this->actingAs($vendedor)->put(route('roteiros-viagem.update', $roteiro->crm_rot_id), [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-09-15',
            'crm_rot_periodo_fim' => '2026-09-18',
            'clientes' => [$cliente->cli_id],
            'resultados' => [$cliente->cli_id => ResultadoVisitaRoteiro::Visitado->value],
            'observacoes' => [$cliente->cli_id => 'Cliente interessado em orcamento novo.'],
        ]);

        $response->assertRedirect(route('roteiros-viagem.edit', $roteiro->crm_rot_id));
        $this->assertDatabaseHas('crm_roteiros_viagem_clientes', [
            'crm_rot_cli_roteiro_id' => $roteiro->crm_rot_id,
            'crm_rot_cli_cliente_id' => $cliente->cli_id,
            'crm_rot_cli_resultado' => ResultadoVisitaRoteiro::Visitado->value,
            'crm_rot_cli_observacao' => 'Cliente interessado em orcamento novo.',
        ]);
    }

    public function test_tecnico_nao_acessa_roteiros_de_viagem(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();

        $this->actingAs($tecnico)->get(route('roteiros-viagem.index'))->assertForbidden();
        $this->actingAs($tecnico)->get(route('roteiros-viagem.create'))->assertForbidden();
    }

    public function test_mapa_de_relacoes_filtra_por_segmento_e_mostra_caso_de_sucesso(): void
    {
        $vendedor = $this->criarVendedor();
        Cliente::factory()->create([
            'cli_segmento' => 'Industrial',
            'cli_latitude' => -25.4284,
            'cli_longitude' => -49.2733,
            'cli_caso_sucesso' => true,
            'cli_equipamento_vendido' => 'ETE compacta 50m3 por dia',
        ]);
        Cliente::factory()->create([
            'cli_nome' => 'Cliente Fora Do Filtro',
            'cli_segmento' => 'Residencial',
            'cli_latitude' => -23.5505,
            'cli_longitude' => -46.6333,
        ]);

        $response = $this->actingAs($vendedor)->get(route('mapa-relacoes.index', ['f_segmento' => 'Industrial']));

        $response->assertOk();
        $response->assertSee('ETE compacta 50m3 por dia');
        $response->assertDontSee('Cliente Fora Do Filtro');
    }

    public function test_indicadores_comerciais_calcula_totais_por_vendedor(): void
    {
        $vendedor = $this->criarVendedor();
        $cliente = Cliente::factory()->create();

        Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_ativo' => 1,
            'orc_criado_em' => now(),
        ]);

        $response = $this->actingAs($vendedor)->get(route('indicadores-comerciais.index'));

        $response->assertOk();
        $response->assertViewHas('totalLevantadas', 1);
        $response->assertViewHas('indicadoresPorVendedor', function ($linhas) use ($vendedor) {
            return $linhas->first()['vendedor'] === $vendedor->user_nome
                && $linhas->first()['levantadas'] === 1
                && $linhas->first()['taxaConversao'] === null;
        });
    }

    /**
     * Pedido do cliente (2026-09-17): "fechadas"/"taxa de conversão" deixam
     * de ser mockadas (70% fixo) - vêm do campo orc_resultado de verdade.
     */
    public function test_indicadores_comerciais_usa_resultado_real_do_orcamento(): void
    {
        $vendedor = $this->criarVendedor();
        $cliente = Cliente::factory()->create();

        Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_ativo' => 1,
            'orc_resultado' => \App\Enums\ResultadoOrcamento::Convertido->value,
            'orc_criado_em' => now(),
        ]);
        Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_ativo' => 1,
            'orc_resultado' => \App\Enums\ResultadoOrcamento::NaoConvertido->value,
            'orc_criado_em' => now(),
        ]);
        Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_ativo' => 1,
            'orc_resultado' => \App\Enums\ResultadoOrcamento::Adiado->value,
            'orc_criado_em' => now(),
        ]);

        $response = $this->actingAs($vendedor)->get(route('indicadores-comerciais.index'));

        $response->assertOk();
        $response->assertViewHas('totalLevantadas', 3);
        $response->assertViewHas('totalFechadas', 1);
        // Taxa = 1 convertido / (1 convertido + 1 nao convertido) = 50%.
        // O adiado nao entra no denominador (resultado ainda nao definitivo).
        $response->assertViewHas('taxaConversaoGeral', 50);
    }

    public function test_tecnico_nao_acessa_mapa_nem_indicadores(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();

        $this->actingAs($tecnico)->get(route('mapa-relacoes.index'))->assertForbidden();
        $this->actingAs($tecnico)->get(route('indicadores-comerciais.index'))->assertForbidden();
    }
}