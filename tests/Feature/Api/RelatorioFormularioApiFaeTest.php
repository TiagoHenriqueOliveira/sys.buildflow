<?php

namespace Tests\Feature\Api;

use App\Enums\SetorModelo;
use App\Enums\TipoPergunta;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioResposta;
use App\Models\ConfigModelo;
use App\Models\ConfigPergunta;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RelatorioFormularioApiFaeTest extends TestCase
{
    use RefreshDatabase;

    private function token(Usuario $usuario): string
    {
        return $usuario->createToken('test')->plainTextToken;
    }

    /** Mesmo fixture de SessaoPerguntasFaeTest::criarModeloComSessao. */
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
            'cfg_perg_permite_anexo' => true,
        ]);

        $repetivel = ConfigPergunta::create([
            'cfg_perg_texto' => 'Ocorrência encontrada',
            'cfg_perg_tipo' => TipoPergunta::TextoLivre->value,
            'cfg_perg_ativo' => 1,
            'cfg_perg_repetivel' => true,
        ]);

        DB::table('config_modelos_perguntas')->insert([
            ['cfg_mod_perg_modelo_id' => $modelo->cfg_mod_id, 'cfg_mod_perg_pergunta_id' => $generica->cfg_perg_id, 'cfg_mod_perg_ordem' => 0, 'cfg_mod_perg_sessao_id' => null],
            ['cfg_mod_perg_modelo_id' => $modelo->cfg_mod_id, 'cfg_mod_perg_pergunta_id' => $repetivel->cfg_perg_id, 'cfg_mod_perg_ordem' => 1, 'cfg_mod_perg_sessao_id' => null],
            ['cfg_mod_perg_modelo_id' => $modelo->cfg_mod_id, 'cfg_mod_perg_pergunta_id' => $sessao->cfg_perg_id, 'cfg_mod_perg_ordem' => 2, 'cfg_mod_perg_sessao_id' => null],
            ['cfg_mod_perg_modelo_id' => $modelo->cfg_mod_id, 'cfg_mod_perg_pergunta_id' => $daSessao->cfg_perg_id, 'cfg_mod_perg_ordem' => 3, 'cfg_mod_perg_sessao_id' => $sessao->cfg_perg_id],
        ]);

        return compact('modelo', 'generica', 'sessao', 'daSessao', 'repetivel');
    }

    private function criarAtendimentoComRelatorio(ConfigModelo $modelo, Usuario $tecnico): AtendimentoRelatorio
    {
        $natureza = NaturezaAtendimento::factory()->create(['nat_aten_config_modelo_id' => $modelo->cfg_mod_id]);
        $atendimento = Atendimento::factory()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $tecnico->user_id,
        ]);

        return AtendimentoRelatorio::create([
            'aten_rel_atendimento_id' => $atendimento->aten_id,
            'aten_rel_config_modelo_id' => $modelo->cfg_mod_id,
            'aten_rel_data' => now()->toDateString(),
            'aten_rel_status' => 0,
        ]);
    }

    public function test_formulario_sem_token_retorna_401(): void
    {
        $response = $this->getJson('/api/fae/v1/relatorios/1/formulario');

        $response->assertUnauthorized();
    }

    public function test_formulario_agrupa_genericas_e_sessao(): void
    {
        ['modelo' => $modelo, 'generica' => $generica, 'sessao' => $sessao, 'daSessao' => $daSessao] =
            $this->criarModeloComSessao();
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarAtendimentoComRelatorio($modelo, $tecnico);

        $response = $this->withToken($this->token($tecnico))
            ->getJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/formulario");

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(2, $data['genericas']); // generica + repetivel
        $this->assertSame($generica->cfg_perg_id, $data['genericas'][0]['id']);
        $this->assertCount(1, $data['sessoes']);
        $this->assertSame($sessao->cfg_perg_id, $data['sessoes'][0]['id']);
        $this->assertSame('Peças Substituídas', $data['sessoes'][0]['nome']);
        $this->assertCount(1, $data['sessoes'][0]['perguntas']);
        $this->assertSame($daSessao->cfg_perg_id, $data['sessoes'][0]['perguntas'][0]['id']);
        $this->assertFalse($data['tem_clima_legado']);
        $this->assertFalse($data['tem_servicos_legado']);
    }

    public function test_tecnico_de_outro_atendimento_nao_acessa_formulario(): void
    {
        ['modelo' => $modelo] = $this->criarModeloComSessao();
        $dono = Usuario::factory()->tecnico()->create();
        $outro = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarAtendimentoComRelatorio($modelo, $dono);

        $response = $this->withToken($this->token($outro))
            ->getJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/formulario");

        $response->assertForbidden();
    }

    public function test_salva_resposta_simples_nao_repetivel(): void
    {
        ['modelo' => $modelo, 'generica' => $generica] = $this->criarModeloComSessao();
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarAtendimentoComRelatorio($modelo, $tecnico);

        $response = $this->withToken($this->token($tecnico))->postJson(
            "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/respostas",
            ['pergunta_id' => $generica->cfg_perg_id, 'valor' => '25 m3/dia']
        );

        $response->assertOk();
        $this->assertDatabaseHas('atendimentos_relatorios_respostas', [
            'aten_rel_resp_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_resp_pergunta_id' => $generica->cfg_perg_id,
            'aten_rel_resp_valor' => '25 m3/dia',
        ]);

        // Nao repetivel: responder de novo ATUALIZA a mesma linha, nao cria outra.
        $this->withToken($this->token($tecnico))->postJson(
            "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/respostas",
            ['pergunta_id' => $generica->cfg_perg_id, 'valor' => '30 m3/dia']
        );
        $this->assertSame(1, AtendimentoRelatorioResposta::where('aten_rel_resp_pergunta_id', $generica->cfg_perg_id)->count());
    }

    public function test_salva_varias_respostas_para_pergunta_repetivel(): void
    {
        ['modelo' => $modelo, 'repetivel' => $repetivel] = $this->criarModeloComSessao();
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarAtendimentoComRelatorio($modelo, $tecnico);

        foreach (['Vazamento no flange', 'Ruído anormal na bomba'] as $valor) {
            $response = $this->withToken($this->token($tecnico))->postJson(
                "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/respostas",
                ['pergunta_id' => $repetivel->cfg_perg_id, 'valor' => $valor]
            );
            $response->assertOk();
        }

        $this->assertSame(2, AtendimentoRelatorioResposta::where('aten_rel_resp_pergunta_id', $repetivel->cfg_perg_id)->count());
    }

    public function test_anexa_foto_a_resposta_quando_pergunta_permite_anexo(): void
    {
        ['modelo' => $modelo, 'daSessao' => $daSessao] = $this->criarModeloComSessao();
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarAtendimentoComRelatorio($modelo, $tecnico);

        Storage::fake('public');

        $response = $this->withToken($this->token($tecnico))->post(
            "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/respostas",
            [
                'pergunta_id' => $daSessao->cfg_perg_id,
                'valor' => 'Substituída a vedação',
                'foto' => UploadedFile::fake()->image('peca.jpg'),
                'foto_comentario' => 'Peça antiga desgastada',
            ]
        );

        $response->assertOk();
        $resposta = AtendimentoRelatorioResposta::where('aten_rel_resp_pergunta_id', $daSessao->cfg_perg_id)->firstOrFail();
        $this->assertDatabaseHas('atendimentos_relatorios_respostas_fotos', [
            'aten_rel_resp_foto_resposta_id' => $resposta->aten_rel_resp_id,
            'aten_rel_resp_foto_comentario' => 'Peça antiga desgastada',
        ]);
    }

    public function test_remove_resposta(): void
    {
        ['modelo' => $modelo, 'generica' => $generica] = $this->criarModeloComSessao();
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarAtendimentoComRelatorio($modelo, $tecnico);
        $resposta = AtendimentoRelatorioResposta::create([
            'aten_rel_resp_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_resp_pergunta_id' => $generica->cfg_perg_id,
            'aten_rel_resp_valor' => 'valor qualquer',
        ]);

        $response = $this->withToken($this->token($tecnico))->deleteJson(
            "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/respostas/{$resposta->aten_rel_resp_id}"
        );

        $response->assertOk();
        $this->assertDatabaseMissing('atendimentos_relatorios_respostas', ['aten_rel_resp_id' => $resposta->aten_rel_resp_id]);
    }

    public function test_remove_foto_da_resposta(): void
    {
        ['modelo' => $modelo, 'daSessao' => $daSessao] = $this->criarModeloComSessao();
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarAtendimentoComRelatorio($modelo, $tecnico);

        Storage::fake('public');
        $resposta = AtendimentoRelatorioResposta::create([
            'aten_rel_resp_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_resp_pergunta_id' => $daSessao->cfg_perg_id,
            'aten_rel_resp_valor' => 'Trocada',
        ]);
        $foto = $resposta->fotos()->create([
            'aten_rel_resp_foto_path' => 'atendimentos_relatorios/teste/peca.jpg',
            'aten_rel_resp_foto_comentario' => 'Antes da troca',
        ]);

        $response = $this->withToken($this->token($tecnico))->deleteJson(
            "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/respostas-fotos/{$foto->aten_rel_resp_foto_id}"
        );

        $response->assertOk();
        $this->assertDatabaseMissing('atendimentos_relatorios_respostas_fotos', ['aten_rel_resp_foto_id' => $foto->aten_rel_resp_foto_id]);
    }
}