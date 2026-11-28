<?php

namespace Tests\Feature\Api;

use App\Enums\SetorModelo;
use App\Enums\TipoPergunta;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\ConfigModelo;
use App\Models\ConfigPergunta;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Auditoria 27/11/2026, S-21 e S-22 / contrato fae/v1 §1 e §8: limite da
 * assinatura em base64, pergunta precisa ser do modelo do relatório, foto
 * só em pergunta com "permite anexo" e codigo "validacao" nos 422.
 */
class RegrasEntradaApiFaeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private AtendimentoRelatorio $relatorio;

    private ConfigPergunta $comAnexo;

    private ConfigPergunta $semAnexo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->administrador()->create();

        $modelo = ConfigModelo::create(['cfg_mod_nome' => 'Visita', 'cfg_mod_setor' => SetorModelo::Assistencia->value]);
        $this->comAnexo = ConfigPergunta::create(['cfg_perg_texto' => 'Foto do painel', 'cfg_perg_tipo' => TipoPergunta::TextoLivre->value, 'cfg_perg_permite_anexo' => true]);
        $this->semAnexo = ConfigPergunta::create(['cfg_perg_texto' => 'Observação', 'cfg_perg_tipo' => TipoPergunta::TextoLivre->value, 'cfg_perg_permite_anexo' => false]);
        $modelo->perguntas()->attach([$this->comAnexo->cfg_perg_id => ['cfg_mod_perg_ordem' => 1], $this->semAnexo->cfg_perg_id => ['cfg_mod_perg_ordem' => 2]]);

        $atendimento = Atendimento::factory()->create();
        $this->relatorio = AtendimentoRelatorio::factory()->create([
            'aten_rel_atendimento_id' => $atendimento->aten_id,
            'aten_rel_config_modelo_id' => $modelo->cfg_mod_id,
        ]);
    }

    private function api()
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->admin->createToken('t', ['fae-app'])->plainTextToken);
    }

    private function png(int $largura, int $altura): string
    {
        $img = imagecreatetruecolor($largura, $altura);
        ob_start();
        imagepng($img);

        return 'data:image/png;base64,' . base64_encode((string) ob_get_clean());
    }

    public function test_pergunta_fora_do_modelo_e_recusada_na_api_e_no_web(): void
    {
        $outra = ConfigPergunta::create(['cfg_perg_texto' => 'De outro modelo', 'cfg_perg_tipo' => TipoPergunta::TextoLivre->value]);

        $this->api()->postJson("/api/fae/v1/relatorios/{$this->relatorio->aten_rel_id}/respostas", ['pergunta_id' => $outra->cfg_perg_id, 'valor' => 'x'])
            ->assertStatus(422)->assertJsonPath('codigo', 'pergunta_fora_do_modelo');

        $this->actingAs($this->admin)->postJson(route('atendimentos-relatorios.store-resposta', $this->relatorio->aten_rel_id), ['pergunta_id' => $outra->cfg_perg_id, 'valor' => 'x'])
            ->assertStatus(422)->assertJsonPath('codigo', 'pergunta_fora_do_modelo');

        $this->assertSame(0, $this->relatorio->respostas()->count());
    }

    public function test_foto_so_em_pergunta_que_permite_anexo(): void
    {
        $foto = fn () => UploadedFile::fake()->image('painel.jpg');

        $this->api()->post("/api/fae/v1/relatorios/{$this->relatorio->aten_rel_id}/respostas",
            ['pergunta_id' => $this->semAnexo->cfg_perg_id, 'valor' => 'x', 'foto' => $foto()], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonPath('codigo', 'anexo_nao_permitido');

        $this->api()->post("/api/fae/v1/relatorios/{$this->relatorio->aten_rel_id}/respostas",
            ['pergunta_id' => $this->comAnexo->cfg_perg_id, 'valor' => 'x', 'foto' => $foto()], ['Accept' => 'application/json'])
            ->assertOk();
    }

    public function test_assinatura_grande_ou_invalida_e_recusada(): void
    {
        $url = "/api/fae/v1/relatorios/{$this->relatorio->aten_rel_id}/assinaturas";

        $this->api()->postJson($url, ['tecnico' => 'data:image/png;base64,' . str_repeat('A', 1_100_000)])
            ->assertStatus(422)->assertJsonPath('codigo', 'validacao')->assertJsonValidationErrors('tecnico');
        $this->api()->postJson($url, ['tecnico' => 'nao-e-imagem'])->assertStatus(422)->assertJsonValidationErrors('tecnico');
        $this->api()->postJson($url, ['tecnico' => $this->png(2500, 10)])->assertStatus(422)->assertJsonValidationErrors('tecnico');

        $this->api()->postJson($url, ['tecnico' => $this->png(600, 200)])->assertOk();
    }

    public function test_validacao_da_api_traz_o_codigo(): void
    {
        $this->api()->postJson('/api/fae/v1/clientes', [])
            ->assertStatus(422)->assertJsonPath('codigo', 'validacao')->assertJsonStructure(['message', 'errors' => ['cli_nome']]);
    }
}
