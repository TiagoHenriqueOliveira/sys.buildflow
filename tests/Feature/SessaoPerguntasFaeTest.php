<?php

namespace Tests\Feature;

use App\Enums\SetorModelo;
use App\Enums\TipoPergunta;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\ConfigModelo;
use App\Models\ConfigPergunta;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pedido do cliente (2026-09-14): uma pergunta pode ser marcada como
 * "Sessão" — não é uma pergunta de resposta de verdade, é um marcador que
 * vira uma ABA no preenchimento do relatório, agrupando as perguntas
 * cadastradas logo depois dela no modelo (até a próxima Sessão ou o fim da
 * lista). Confirmado com o usuário (AskUserQuestion) que o comportamento é
 * de AGRUPADOR (várias perguntas por aba), não de aba individual por
 * pergunta-sessão.
 */
class SessaoPerguntasFaeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cadastra_pergunta_marcada_como_sessao(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('configurador.perguntas.store'), [
            'cfg_perg_texto' => 'Peças Substituídas',
            'cfg_perg_e_sessao' => 1,
            'cfg_perg_sessao_nome' => 'Peças Substituídas',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('config_perguntas', [
            'cfg_perg_texto' => 'Peças Substituídas',
            'cfg_perg_e_sessao' => 1,
            'cfg_perg_sessao_nome' => 'Peças Substituídas',
        ]);
    }

    public function test_sessao_sem_nome_da_aba_e_rejeitada(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('configurador.perguntas.store'), [
            'cfg_perg_texto' => 'Peças Substituídas',
            'cfg_perg_e_sessao' => 1,
        ]);

        $response->assertSessionHasErrors('cfg_perg_sessao_nome');
    }

    public function test_pergunta_normal_ainda_exige_tipo_de_resposta(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('configurador.perguntas.store'), [
            'cfg_perg_texto' => 'Qual o volume estimado?',
            'cfg_perg_e_sessao' => 0,
        ]);

        $response->assertSessionHasErrors('cfg_perg_tipo');
    }

    private function criarModeloComSessao(): array
    {
        $modelo = ConfigModelo::create([
            'cfg_mod_nome' => 'Manutenção Preventiva ETE',
            'cfg_mod_setor' => SetorModelo::Assistencia->value,
            'cfg_mod_ativo' => 1,
        ]);

        $generica = ConfigPergunta::create([
            'cfg_perg_texto' => 'Descrição geral do atendimento',
            'cfg_perg_tipo' => TipoPergunta::TextoLivre->value,
            'cfg_perg_ativo' => 1,
        ]);

        $sessao = ConfigPergunta::create([
            'cfg_perg_texto' => 'Peças Substituídas',
            'cfg_perg_tipo' => TipoPergunta::TextoLivre->value,
            'cfg_perg_ativo' => 1,
            'cfg_perg_e_sessao' => true,
            'cfg_perg_sessao_nome' => 'Peças Substituídas',
        ]);

        $daSessao = ConfigPergunta::create([
            'cfg_perg_texto' => 'Qual peça foi trocada?',
            'cfg_perg_tipo' => TipoPergunta::TextoLivre->value,
            'cfg_perg_ativo' => 1,
        ]);

        // Ordem explicita na pivot: generica (0) -> sessao (1) -> daSessao (2).
        DB::table('config_modelos_perguntas')->insert([
            ['cfg_mod_perg_modelo_id' => $modelo->cfg_mod_id, 'cfg_mod_perg_pergunta_id' => $generica->cfg_perg_id, 'cfg_mod_perg_ordem' => 0],
            ['cfg_mod_perg_modelo_id' => $modelo->cfg_mod_id, 'cfg_mod_perg_pergunta_id' => $sessao->cfg_perg_id, 'cfg_mod_perg_ordem' => 1],
            ['cfg_mod_perg_modelo_id' => $modelo->cfg_mod_id, 'cfg_mod_perg_pergunta_id' => $daSessao->cfg_perg_id, 'cfg_mod_perg_ordem' => 2],
        ]);

        return compact('modelo', 'generica', 'sessao', 'daSessao');
    }

    public function test_modelo_agrupa_perguntas_genericas_e_por_sessao_corretamente(): void
    {
        ['modelo' => $modelo, 'generica' => $generica, 'sessao' => $sessao, 'daSessao' => $daSessao] = $this->criarModeloComSessao();

        $grupos = $modelo->fresh()->perguntasAgrupadasPorSessao();

        $this->assertCount(1, $grupos['genericas']);
        $this->assertSame($generica->cfg_perg_id, $grupos['genericas']->first()->cfg_perg_id);

        $this->assertCount(1, $grupos['por_sessao']);
        $this->assertCount(1, $grupos['por_sessao'][$sessao->cfg_perg_id]);
        $this->assertSame($daSessao->cfg_perg_id, $grupos['por_sessao'][$sessao->cfg_perg_id]->first()->cfg_perg_id);
    }

    public function test_tela_do_relatorio_mostra_aba_da_sessao(): void
    {
        ['modelo' => $modelo, 'sessao' => $sessao] = $this->criarModeloComSessao();
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = NaturezaAtendimento::factory()->create(['nat_aten_config_modelo_id' => $modelo->cfg_mod_id]);
        $atendimento = Atendimento::factory()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $tecnico->user_id,
        ]);
        $relatorio = AtendimentoRelatorio::create([
            'aten_rel_atendimento_id' => $atendimento->aten_id,
            'aten_rel_config_modelo_id' => $modelo->cfg_mod_id,
            'aten_rel_data' => now()->toDateString(),
            'aten_rel_status' => 0,
        ]);

        $response = $this->actingAs($tecnico)->get(route('atendimentos-relatorios.show', $relatorio->aten_rel_id));

        $response->assertOk();
        $response->assertSee('Peças Substituídas');
        $response->assertSee('sessao-'.$sessao->cfg_perg_id, false);
    }

    public function test_endpoint_respostas_agrupa_perguntas_por_sessao(): void
    {
        ['modelo' => $modelo, 'sessao' => $sessao, 'daSessao' => $daSessao] = $this->criarModeloComSessao();
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = NaturezaAtendimento::factory()->create(['nat_aten_config_modelo_id' => $modelo->cfg_mod_id]);
        $atendimento = Atendimento::factory()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $tecnico->user_id,
        ]);
        $relatorio = AtendimentoRelatorio::create([
            'aten_rel_atendimento_id' => $atendimento->aten_id,
            'aten_rel_config_modelo_id' => $modelo->cfg_mod_id,
            'aten_rel_data' => now()->toDateString(),
            'aten_rel_status' => 0,
        ]);

        $response = $this->actingAs($tecnico)->getJson(route('atendimentos-relatorios.get-respostas', $relatorio->aten_rel_id));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data['data']);
        $this->assertArrayHasKey($sessao->cfg_perg_id, $data['por_sessao']);
        $this->assertCount(1, $data['por_sessao'][$sessao->cfg_perg_id]);
        $this->assertSame($daSessao->cfg_perg_id, $data['por_sessao'][$sessao->cfg_perg_id][0]['id']);
    }
}