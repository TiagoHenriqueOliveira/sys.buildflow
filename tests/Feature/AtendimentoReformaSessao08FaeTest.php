<?php

namespace Tests\Feature;

use App\Enums\AtendimentoRelatorioStatus;
use App\Enums\SetorModelo;
use App\Enums\TipoPergunta;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioPeca;
use App\Models\ConfigModelo;
use App\Models\ConfigPergunta;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Sessão 08 do cronograma FAE (Atendimento/Assistência) - reformulação:
 * Configurador substitui modelos_relatorios; Dados/Horários/Anexos/
 * Observações Gerais/Assinatura ficam sempre fixos, o resto (Clima/
 * Serviços/Peças/Ocorrências) só aparece por dado legado - relatório
 * novo usa a aba Perguntas (NC02/NC03), inclusive perguntas repetíveis
 * (cfg_perg_repetivel, pedido do cliente em 2026-09-11). Também cobre
 * BF07 (comprovante de compartilhamento), BF09 (checklist de peça
 * trocada) e BF10 (observação do supervisor na aprovação).
 */
class AtendimentoReformaSessao08FaeTest extends TestCase
{
    use RefreshDatabase;

    private function criarNaturezaComModelo(): NaturezaAtendimento
    {
        $modelo = ConfigModelo::create([
            'cfg_mod_nome' => 'Manutenção Preventiva ETE',
            'cfg_mod_setor' => SetorModelo::Assistencia->value,
            'cfg_mod_ativo' => 1,
        ]);

        return NaturezaAtendimento::factory()->create([
            'nat_aten_mod_relatorio_id' => null,
            'nat_aten_config_modelo_id' => $modelo->cfg_mod_id,
        ]);
    }

    private function criarAtendimentoComRelatorio(NaturezaAtendimento $natureza, Usuario $tecnico): AtendimentoRelatorio
    {
        $atendimento = Atendimento::factory()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $tecnico->user_id,
        ]);

        return AtendimentoRelatorio::create([
            'aten_rel_atendimento_id' => $atendimento->aten_id,
            'aten_rel_modelo_relatorio_id' => null,
            'aten_rel_config_modelo_id' => $natureza->nat_aten_config_modelo_id,
            'aten_rel_data' => now()->toDateString(),
            'aten_rel_status' => 0,
        ]);
    }

    public function test_tecnico_cria_relatorio_usando_modelo_do_configurador(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $atendimento = Atendimento::factory()->naoIniciada()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $tecnico->user_id,
        ]);

        $response = $this->actingAs($tecnico)->post(route('atendimentos-relatorios.store'), [
            'aten_id' => $atendimento->aten_id,
            'aten_rel_data' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('atendimentos_relatorios', [
            'aten_rel_atendimento_id' => $atendimento->aten_id,
            'aten_rel_config_modelo_id' => $natureza->nat_aten_config_modelo_id,
            'aten_rel_modelo_relatorio_id' => null,
        ]);
    }

    public function test_falha_ao_criar_relatorio_para_natureza_sem_modelo_do_configurador(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = NaturezaAtendimento::factory()->create(['nat_aten_config_modelo_id' => null]);
        $atendimento = Atendimento::factory()->naoIniciada()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $tecnico->user_id,
        ]);

        $response = $this->actingAs($tecnico)->post(route('atendimentos-relatorios.store'), [
            'aten_id' => $atendimento->aten_id,
        ]);

        $response->assertSessionHasErrors('aten_id');
    }

    public function test_responde_pergunta_de_texto_livre_do_modelo(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $pergunta = ConfigPergunta::create([
            'cfg_perg_texto' => 'Qual o volume estimado de efluente (m3/dia)?',
            'cfg_perg_tipo' => TipoPergunta::TextoLivre->value,
        ]);
        ConfigModelo::find($natureza->nat_aten_config_modelo_id)->perguntas()->sync([$pergunta->cfg_perg_id]);
        $relatorio = $this->criarAtendimentoComRelatorio($natureza, $tecnico);

        $response = $this->actingAs($tecnico)->postJson(
            route('atendimentos-relatorios.store-resposta', $relatorio->aten_rel_id),
            ['pergunta_id' => $pergunta->cfg_perg_id, 'valor' => '25 m3/dia']
        );

        $response->assertOk();
        $this->assertDatabaseHas('atendimentos_relatorios_respostas', [
            'aten_rel_resp_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_resp_pergunta_id' => $pergunta->cfg_perg_id,
            'aten_rel_resp_valor' => '25 m3/dia',
        ]);
    }

    public function test_anexa_foto_a_resposta_quando_pergunta_permite_anexo(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $pergunta = ConfigPergunta::create([
            'cfg_perg_texto' => 'Registre uma foto do equipamento',
            'cfg_perg_tipo' => TipoPergunta::TextoLivre->value,
            'cfg_perg_permite_anexo' => true,
        ]);
        ConfigModelo::find($natureza->nat_aten_config_modelo_id)->perguntas()->sync([$pergunta->cfg_perg_id]);
        $relatorio = $this->criarAtendimentoComRelatorio($natureza, $tecnico);

        \Illuminate\Support\Facades\Storage::fake('public');

        $response = $this->actingAs($tecnico)->post(
            route('atendimentos-relatorios.store-resposta', $relatorio->aten_rel_id),
            [
                'pergunta_id' => $pergunta->cfg_perg_id,
                'valor' => 'Sem vazamentos aparentes',
                'foto' => UploadedFile::fake()->image('equipamento.jpg'),
                'foto_comentario' => 'Foto lateral do equipamento',
            ]
        );

        $response->assertOk();
        $resposta = \App\Models\AtendimentoRelatorioResposta::where('aten_rel_resp_pergunta_id', $pergunta->cfg_perg_id)->firstOrFail();
        $this->assertDatabaseHas('atendimentos_relatorios_respostas_fotos', [
            'aten_rel_resp_foto_resposta_id' => $resposta->aten_rel_resp_id,
            'aten_rel_resp_foto_comentario' => 'Foto lateral do equipamento',
        ]);
    }

    public function test_aba_pecas_so_aparece_com_dado_legado(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $relatorio = $this->criarAtendimentoComRelatorio($natureza, $tecnico);

        $semDado = $this->actingAs($tecnico)->get(route('atendimentos-relatorios.show', $relatorio->aten_rel_id));
        $semDado->assertOk();
        $semDado->assertDontSee('tab-pecas', false);

        AtendimentoRelatorioPeca::create([
            'aten_rel_peca_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_peca_descricao' => 'Bomba dosadora (registro antigo)',
            'aten_rel_peca_trocada' => false,
        ]);

        $comDado = $this->actingAs($tecnico)->get(route('atendimentos-relatorios.show', $relatorio->aten_rel_id));
        $comDado->assertOk();
        $comDado->assertSee('tab-pecas', false);
    }

    public function test_pergunta_repetivel_permite_varias_respostas_e_remocao_individual(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $pergunta = ConfigPergunta::create([
            'cfg_perg_texto' => 'Descrição do serviço realizado',
            'cfg_perg_tipo' => TipoPergunta::TextoLivre->value,
            'cfg_perg_repetivel' => true,
        ]);
        ConfigModelo::find($natureza->nat_aten_config_modelo_id)->perguntas()->sync([$pergunta->cfg_perg_id]);
        $relatorio = $this->criarAtendimentoComRelatorio($natureza, $tecnico);

        $primeira = $this->actingAs($tecnico)->postJson(
            route('atendimentos-relatorios.store-resposta', $relatorio->aten_rel_id),
            ['pergunta_id' => $pergunta->cfg_perg_id, 'valor' => 'Troca do anel de vedação']
        )->assertOk()->json();

        $segunda = $this->actingAs($tecnico)->postJson(
            route('atendimentos-relatorios.store-resposta', $relatorio->aten_rel_id),
            ['pergunta_id' => $pergunta->cfg_perg_id, 'valor' => 'Limpeza do filtro de entrada']
        )->assertOk()->json();

        $this->assertNotSame($primeira['resposta_id'], $segunda['resposta_id']);
        $this->assertDatabaseCount('atendimentos_relatorios_respostas', 2);

        $listagem = $this->actingAs($tecnico)->getJson(
            route('atendimentos-relatorios.get-respostas', $relatorio->aten_rel_id)
        )->assertOk()->json('data');
        $this->assertCount(2, $listagem[0]['respostas']);

        $this->actingAs($tecnico)->deleteJson(
            route('atendimentos-relatorios.destroy-resposta', ['id' => $relatorio->aten_rel_id, 'respostaId' => $primeira['resposta_id']])
        )->assertOk();

        $this->assertDatabaseCount('atendimentos_relatorios_respostas', 1);
        $this->assertDatabaseHas('atendimentos_relatorios_respostas', [
            'aten_rel_resp_id' => $segunda['resposta_id'],
            'aten_rel_resp_valor' => 'Limpeza do filtro de entrada',
        ]);
    }

    public function test_pergunta_nao_repetivel_mantem_no_maximo_uma_resposta(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $pergunta = ConfigPergunta::create([
            'cfg_perg_texto' => 'O equipamento apresentou vazamento?',
            'cfg_perg_tipo' => TipoPergunta::TextoLivre->value,
            'cfg_perg_repetivel' => false,
        ]);
        ConfigModelo::find($natureza->nat_aten_config_modelo_id)->perguntas()->sync([$pergunta->cfg_perg_id]);
        $relatorio = $this->criarAtendimentoComRelatorio($natureza, $tecnico);

        $this->actingAs($tecnico)->postJson(
            route('atendimentos-relatorios.store-resposta', $relatorio->aten_rel_id),
            ['pergunta_id' => $pergunta->cfg_perg_id, 'valor' => 'Não']
        )->assertOk();

        $this->actingAs($tecnico)->postJson(
            route('atendimentos-relatorios.store-resposta', $relatorio->aten_rel_id),
            ['pergunta_id' => $pergunta->cfg_perg_id, 'valor' => 'Sim']
        )->assertOk();

        $this->assertDatabaseCount('atendimentos_relatorios_respostas', 1);
        $this->assertDatabaseHas('atendimentos_relatorios_respostas', [
            'aten_rel_resp_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_resp_pergunta_id' => $pergunta->cfg_perg_id,
            'aten_rel_resp_valor' => 'Sim',
        ]);
    }

    public function test_registra_peca_marcada_como_trocada(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $relatorio = $this->criarAtendimentoComRelatorio($natureza, $tecnico);

        $response = $this->actingAs($tecnico)->postJson(
            route('atendimentos-relatorios.store-peca', $relatorio->aten_rel_id),
            ['descricao' => 'Bomba dosadora', 'trocada' => '1']
        );

        $response->assertOk();
        $this->assertDatabaseHas('atendimentos_relatorios_pecas', [
            'aten_rel_peca_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_peca_descricao' => 'Bomba dosadora',
            'aten_rel_peca_trocada' => 1,
        ]);
    }

    public function test_aprovacao_registra_aprovador_data_e_observacao_do_supervisor(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $relatorio = $this->criarAtendimentoComRelatorio($natureza, $tecnico);

        $response = $this->actingAs($admin)->postJson(
            route('atendimentos-relatorios.update-assinaturas', $relatorio->aten_rel_id),
            [
                'aten_rel_status' => AtendimentoRelatorioStatus::Aprovado->value,
                'observacao_supervisor' => 'Conferido em campo, aprovado sem ressalvas.',
            ]
        );

        $response->assertOk();
        $relatorio->refresh();
        $this->assertSame(AtendimentoRelatorioStatus::Aprovado->value, $relatorio->aten_rel_status);
        $this->assertSame($admin->user_id, $relatorio->aten_rel_aprovado_por);
        $this->assertNotNull($relatorio->aten_rel_aprovado_em);
        $this->assertSame('Conferido em campo, aprovado sem ressalvas.', $relatorio->aten_rel_observacao_supervisor);
    }

    public function test_relatorio_aprovado_aparece_em_modo_somente_leitura(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $relatorio = $this->criarAtendimentoComRelatorio($natureza, $tecnico);
        $relatorio->update(['aten_rel_status' => AtendimentoRelatorioStatus::Aprovado->value]);

        $response = $this->actingAs($tecnico)->get(route('atendimentos-relatorios.show', $relatorio->aten_rel_id));

        $response->assertOk();
        $response->assertSee('somente para leitura');
        $response->assertDontSee('id="btnAtualizarRelatorio"', false);
    }

    public function test_salva_observacao_interna_e_nunca_aparece_no_pdf(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $relatorio = $this->criarAtendimentoComRelatorio($natureza, $tecnico);

        $response = $this->actingAs($tecnico)->postJson(
            route('atendimentos-relatorios.update-texto', ['id' => $relatorio->aten_rel_id, 'campo' => 'aten_rel_observacao_interna']),
            ['valor' => 'Cliente reclamou do prazo, atenção na próxima visita.']
        );

        $response->assertOk();
        $this->assertDatabaseHas('atendimentos_relatorios', [
            'aten_rel_id' => $relatorio->aten_rel_id,
            'aten_rel_observacao_interna' => 'Cliente reclamou do prazo, atenção na próxima visita.',
        ]);

        $this->assertStringNotContainsString(
            'observacao_interna',
            file_get_contents(resource_path('views/atendimentos-relatorios/pdf.blade.php'))
        );
    }

    public function test_registra_comprovante_de_compartilhamento(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $relatorio = $this->criarAtendimentoComRelatorio($natureza, $tecnico);

        $response = $this->actingAs($tecnico)->postJson(
            route('atendimentos-relatorios.store-compartilhamento', $relatorio->aten_rel_id),
            ['canal' => 'painel-web']
        );

        $response->assertOk();
        $this->assertDatabaseHas('atendimentos_relatorios_compartilhamentos', [
            'aten_rel_comp_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_comp_usuario_id' => $tecnico->user_id,
            'aten_rel_comp_canal' => 'painel-web',
        ]);

        $listagem = $this->actingAs($tecnico)->getJson(
            route('atendimentos-relatorios.get-compartilhamentos', $relatorio->aten_rel_id)
        );
        $listagem->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_mapa_de_demandas_mostra_atendimento_do_cliente_geolocalizado(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $cliente = \App\Models\Cliente::factory()->create([
            'cli_latitude' => -25.4284,
            'cli_longitude' => -49.2733,
        ]);
        $atendimento = Atendimento::factory()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $tecnico->user_id,
            'aten_cliente_id' => $cliente->cli_id,
            'aten_nr_proposta' => 'PROP-0099',
        ]);

        $response = $this->actingAs($tecnico)->get(route('mapa-demandas.index'));

        $response->assertOk();
        $response->assertSee('PROP-0099');
    }

    public function test_tecnico_de_outro_atendimento_nao_acessa_respostas_nem_compartilhamento(): void
    {
        $dono = Usuario::factory()->tecnico()->create();
        $outroTecnico = Usuario::factory()->tecnico()->create();
        $natureza = $this->criarNaturezaComModelo();
        $relatorio = $this->criarAtendimentoComRelatorio($natureza, $dono);

        $this->actingAs($outroTecnico)
            ->getJson(route('atendimentos-relatorios.get-respostas', $relatorio->aten_rel_id))
            ->assertForbidden();

        $this->actingAs($outroTecnico)
            ->postJson(route('atendimentos-relatorios.store-compartilhamento', $relatorio->aten_rel_id), [])
            ->assertForbidden();
    }
}