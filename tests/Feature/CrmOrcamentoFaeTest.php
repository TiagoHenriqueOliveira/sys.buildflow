<?php

namespace Tests\Feature;

use App\Enums\NivelOrcamento;
use App\Enums\SetorModelo;
use App\Enums\TipoPergunta;
use App\Models\Cliente;
use App\Models\ConfigModelo;
use App\Models\ConfigPergunta;
use App\Models\CrmTipoOrcamento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sessao 06a do cronograma FAE (CRM Comercial) - CRM01 (vinculo tipo de
 * orcamento -> modelo), CRM02 (nivel/prazo mockado), CRM03 (comentarios),
 * CRM04 (vendedores adicionais, sem comissao).
 */
class CrmOrcamentoFaeTest extends TestCase
{
    use RefreshDatabase;

    private function criarVendedor(): Usuario
    {
        return Usuario::factory()->create(['user_nivel_acesso' => \App\Enums\NivelAcesso::Comercial->value]);
    }

    public function test_admin_vincula_tipo_de_orcamento_a_modelo_comercial(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $modelo = ConfigModelo::create(['cfg_mod_nome' => 'Orcamento Padrao', 'cfg_mod_setor' => SetorModelo::Comercial->value]);

        $response = $this->actingAs($admin)->post(route('crm.tipos-orcamento.store'), [
            'crm_tp_orc_nome' => 'ETE Teste',
            'crm_tp_orc_config_modelo_id' => $modelo->cfg_mod_id,
        ]);

        $response->assertRedirect(route('crm.tipos-orcamento.index'));
        $this->assertDatabaseHas('crm_tipos_orcamento', [
            'crm_tp_orc_nome' => 'ETE Teste',
            'crm_tp_orc_config_modelo_id' => $modelo->cfg_mod_id,
        ]);
    }

    public function test_comercial_cadastra_orcamento_com_resposta_de_pergunta_dinamica(): void
    {
        $vendedor = $this->criarVendedor();
        $cliente = Cliente::factory()->create();

        $pergunta = ConfigPergunta::create(['cfg_perg_texto' => 'Volume?', 'cfg_perg_tipo' => TipoPergunta::TextoLivre->value]);
        $modelo = ConfigModelo::create(['cfg_mod_nome' => 'Orcamento Padrao', 'cfg_mod_setor' => SetorModelo::Comercial->value]);
        $modelo->perguntas()->sync([$pergunta->cfg_perg_id]);
        $tipo = CrmTipoOrcamento::create(['crm_tp_orc_nome' => 'ETE Teste', 'crm_tp_orc_config_modelo_id' => $modelo->cfg_mod_id]);

        $response = $this->actingAs($vendedor)->post(route('orcamentos.store'), [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_tipo_orcamento_id' => $tipo->crm_tp_orc_id,
            'orc_nivel' => NivelOrcamento::Medio->value,
            'respostas' => [$pergunta->cfg_perg_id => 'Cinquenta m3/dia'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orcamentos', [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_nivel' => NivelOrcamento::Medio->value,
        ]);
        $this->assertDatabaseHas('orcamentos_respostas', [
            'orc_resp_pergunta_id' => $pergunta->cfg_perg_id,
            'orc_resp_valor' => 'Cinquenta m3/dia',
        ]);
    }

    public function test_prazo_de_envio_e_calculado_automaticamente_quando_nao_informado(): void
    {
        $vendedor = $this->criarVendedor();
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($vendedor)->post(route('orcamentos.store'), [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_nivel' => NivelOrcamento::Complexo->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orcamentos', [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_prazo_envio' => now()->addDays(NivelOrcamento::Complexo->prazoEmDiasMockado())->toDateString(),
        ]);
    }

    public function test_associa_vendedor_adicional_sem_duplicar_o_responsavel(): void
    {
        $vendedor = $this->criarVendedor();
        $adicional = $this->criarVendedor();
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($vendedor)->post(route('orcamentos.store'), [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'vendedores_adicionais' => [$adicional->user_id],
        ]);

        $response->assertRedirect();
        $orcamento = \App\Models\Orcamento::first();
        $this->assertCount(1, $orcamento->vendedoresAdicionais);
        $this->assertTrue($orcamento->vendedoresAdicionais->first()->is($adicional));
    }

    public function test_rejeita_vendedor_adicional_igual_ao_responsavel(): void
    {
        $vendedor = $this->criarVendedor();
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($vendedor)->post(route('orcamentos.store'), [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'vendedores_adicionais' => [$vendedor->user_id],
        ]);

        $response->assertSessionHasErrors('vendedores_adicionais');
    }

    public function test_registra_comentario_com_alerta_para_outro_usuario(): void
    {
        $vendedor = $this->criarVendedor();
        $colega = $this->criarVendedor();
        $cliente = Cliente::factory()->create();
        $orcamento = \App\Models\Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_ativo' => 1,
            'orc_criado_em' => now(),
        ]);

        $response = $this->actingAs($vendedor)->post(route('orcamentos.store-comentario', $orcamento->orc_id), [
            'orc_com_texto' => 'Cliente pediu revisao de prazo.',
            'orc_com_alerta_usuario_id' => $colega->user_id,
        ]);

        $response->assertRedirect(route('orcamentos.edit', $orcamento->orc_id));
        // Pedido do cliente (2026-09-14): apos comentar, a pagina deve
        // reabrir na aba Comentarios (nao voltar pra Dados) - via sessao
        // flash 'tab', lida no x-data de orcamentos/form.blade.php.
        $response->assertSessionHas('tab', 'comentarios');
        $this->assertDatabaseHas('orcamentos_comentarios', [
            'orc_com_orcamento_id' => $orcamento->orc_id,
            'orc_com_autor_id' => $vendedor->user_id,
            'orc_com_alerta_usuario_id' => $colega->user_id,
        ]);
    }

    public function test_tecnico_nao_acessa_orcamentos(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();

        $this->actingAs($tecnico)->get(route('orcamentos.index'))->assertForbidden();
        $this->actingAs($tecnico)->get(route('orcamentos.create'))->assertForbidden();
    }
}