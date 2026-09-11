<?php

namespace Tests\Feature;

use App\Enums\SetorModelo;
use App\Enums\TipoPergunta;
use App\Models\ConfigModelo;
use App\Models\ConfigPergunta;
use App\Models\ModeloRelatorio;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sessão 04 do cronograma FAE (Configurador) — NC02 (perguntas/modelos) e
 * BF04 (vínculo natureza de atendimento -> modelo do Configurador).
 */
class ConfiguradorFaeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cadastra_pergunta_de_texto_livre(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('configurador.perguntas.store'), [
            'cfg_perg_texto' => 'Descreva o serviço realizado',
            'cfg_perg_tipo'  => TipoPergunta::TextoLivre->value,
        ]);

        $response->assertRedirect(route('configurador.perguntas.index'));
        $this->assertDatabaseHas('config_perguntas', [
            'cfg_perg_texto' => 'Descreva o serviço realizado',
            'cfg_perg_tipo'  => TipoPergunta::TextoLivre->value,
        ]);
    }

    public function test_pergunta_de_multipla_escolha_exige_ao_menos_uma_opcao(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('configurador.perguntas.store'), [
            'cfg_perg_texto' => 'O equipamento apresentou vazamento?',
            'cfg_perg_tipo'  => TipoPergunta::MultiplaEscolha->value,
            'opcoes'         => [],
        ]);

        $response->assertSessionHasErrors('opcoes');
        $this->assertDatabaseMissing('config_perguntas', [
            'cfg_perg_texto' => 'O equipamento apresentou vazamento?',
        ]);
    }

    public function test_admin_cadastra_pergunta_com_opcoes_e_permite_anexo(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('configurador.perguntas.store'), [
            'cfg_perg_texto'         => 'Qual a condição do equipamento?',
            'cfg_perg_tipo'          => TipoPergunta::EscolhaUnica->value,
            'cfg_perg_permite_anexo' => '1',
            'opcoes'                 => [
                ['texto' => 'Bom'],
                ['texto' => 'Regular'],
                ['texto' => 'Ruim'],
            ],
        ]);

        $response->assertRedirect(route('configurador.perguntas.index'));
        $pergunta = ConfigPergunta::where('cfg_perg_texto', 'Qual a condição do equipamento?')->firstOrFail();
        $this->assertTrue($pergunta->cfg_perg_permite_anexo);
        $this->assertCount(3, $pergunta->opcoes);
    }

    public function test_bloqueia_criacao_de_modelo_sem_pergunta(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('configurador.modelos.store'), [
            'cfg_mod_nome'  => 'Manutenção preventiva',
            'cfg_mod_setor' => SetorModelo::Assistencia->value,
            'perguntas'     => [],
        ]);

        $response->assertSessionHasErrors('perguntas');
        $this->assertDatabaseMissing('config_modelos', ['cfg_mod_nome' => 'Manutenção preventiva']);
    }

    public function test_admin_cadastra_modelo_reaproveitando_pergunta_existente(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $pergunta = ConfigPergunta::create([
            'cfg_perg_texto' => 'Houve troca de peça?',
            'cfg_perg_tipo'  => TipoPergunta::EscolhaUnica->value,
        ]);

        $response = $this->actingAs($admin)->post(route('configurador.modelos.store'), [
            'cfg_mod_nome'  => 'Manutenção preventiva',
            'cfg_mod_setor' => SetorModelo::Assistencia->value,
            'perguntas'     => [$pergunta->cfg_perg_id],
        ]);

        $response->assertRedirect(route('configurador.modelos.index'));
        $modelo = ConfigModelo::where('cfg_mod_nome', 'Manutenção preventiva')->firstOrFail();
        $this->assertCount(1, $modelo->perguntas);
        $this->assertTrue($modelo->perguntas->first()->is($pergunta));
    }

    public function test_tecnico_nao_acessa_configurador(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();

        $this->actingAs($tecnico)->get(route('configurador.perguntas.index'))->assertForbidden();
        $this->actingAs($tecnico)->get(route('configurador.modelos.index'))->assertForbidden();
    }

    // ── BF04 — vínculo natureza de atendimento -> modelo do Configurador ────

    public function test_vincula_modelo_de_assistencia_a_natureza_de_atendimento(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $modeloRelatorio = ModeloRelatorio::factory()->create();
        $modeloConfigurador = ConfigModelo::create([
            'cfg_mod_nome'  => 'Checklist padrão',
            'cfg_mod_setor' => SetorModelo::Assistencia->value,
        ]);

        $response = $this->actingAs($admin)->post(route('naturezas-dos-atendimentos.store'), [
            'nat_aten_descricao'        => 'Visita Técnica',
            'nat_aten_mod_relatorio_id' => $modeloRelatorio->mod_rel_id,
            'nat_aten_config_modelo_id' => $modeloConfigurador->cfg_mod_id,
        ]);

        $response->assertRedirect(route('naturezas-dos-atendimentos.index'));
        $this->assertDatabaseHas('naturezas_atendimentos', [
            'nat_aten_descricao'        => 'Visita Técnica',
            'nat_aten_config_modelo_id' => $modeloConfigurador->cfg_mod_id,
        ]);
    }

    public function test_rejeita_vinculo_com_modelo_de_setor_comercial(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $modeloRelatorio = ModeloRelatorio::factory()->create();
        $modeloComercial = ConfigModelo::create([
            'cfg_mod_nome'  => 'Orçamento padrão',
            'cfg_mod_setor' => SetorModelo::Comercial->value,
        ]);

        $response = $this->actingAs($admin)->post(route('naturezas-dos-atendimentos.store'), [
            'nat_aten_descricao'        => 'Visita Técnica',
            'nat_aten_mod_relatorio_id' => $modeloRelatorio->mod_rel_id,
            'nat_aten_config_modelo_id' => $modeloComercial->cfg_mod_id,
        ]);

        $response->assertSessionHasErrors('nat_aten_config_modelo_id');
    }

    /**
     * Sessao 08 - Configurador substitui modelos_relatorios (ver
     * project_fae_bioenergia na memoria): o modelo legado agora e OPCIONAL,
     * o modelo do Configurador passou a ser o vinculo obrigatorio. Este
     * teste substitui test_natureza_sem_modelo_do_configurador_continua_valida
     * (sessao 04), que testava exatamente o comportamento oposto.
     */
    public function test_natureza_sem_modelo_relatorio_legado_continua_valida(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $modeloComercial = ConfigModelo::create([
            'cfg_mod_nome'  => 'Manutenção preventiva',
            'cfg_mod_setor' => SetorModelo::Assistencia->value,
        ]);

        $response = $this->actingAs($admin)->post(route('naturezas-dos-atendimentos.store'), [
            'nat_aten_descricao'        => 'Visita Técnica',
            'nat_aten_config_modelo_id' => $modeloComercial->cfg_mod_id,
        ]);

        $response->assertRedirect(route('naturezas-dos-atendimentos.index'));
        $this->assertDatabaseHas('naturezas_atendimentos', [
            'nat_aten_descricao'        => 'Visita Técnica',
            'nat_aten_mod_relatorio_id' => null,
            'nat_aten_config_modelo_id' => $modeloComercial->cfg_mod_id,
        ]);
    }

    public function test_rejeita_natureza_sem_modelo_do_configurador(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('naturezas-dos-atendimentos.store'), [
            'nat_aten_descricao' => 'Visita Técnica',
        ]);

        $response->assertSessionHasErrors('nat_aten_config_modelo_id');
    }
}