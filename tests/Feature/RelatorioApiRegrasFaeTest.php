<?php

namespace Tests\Feature;

use App\Enums\AtendimentoStatus;
use App\Enums\SetorModelo;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioFoto;
use App\Models\ConfigModelo;
use App\Models\ModeloRelatorio;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Auditoria 27/11/2026, S-01: os dois grupos de rotas legados herdados saíram
 * da API. As regras de negócio que os testes deles cobriam e que valem na
 * FAÉ foram portadas para a /api/fae/v1, sem mudar a regra: REL-02, REL-03,
 * criação de relatório (avança o atendimento), validações, listagem por
 * técnico e exclusão de anexo com o arquivo físico. A regra de aprovação
 * legada (técnico aprova com as duas assinaturas) não foi portada: na FAÉ
 * quem aprova é a Assistência (BF10).
 */
class RelatorioApiRegrasFaeTest extends TestCase
{
    use RefreshDatabase;

    private function atendimentoComModelo(Usuario $tecnico, int $statusAtendimento = 2, int $tpData = 1): Atendimento
    {
        // Na FAÉ o relatório usa o modelo do Configurador (obrigatório); o
        // modelo legado só decide o "período único" (REL-03).
        $modelo = ModeloRelatorio::factory()->create(['mod_rel_tp_data' => $tpData]);
        $configModelo = ConfigModelo::create([
            'cfg_mod_nome' => 'Modelo de teste',
            'cfg_mod_setor' => SetorModelo::Assistencia->value,
            'cfg_mod_ativo' => 1,
        ]);
        $natureza = NaturezaAtendimento::factory()->create([
            'nat_aten_mod_relatorio_id' => $modelo->mod_rel_id,
            'nat_aten_config_modelo_id' => $configModelo->cfg_mod_id,
        ]);

        return Atendimento::factory()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_status' => $statusAtendimento,
            'aten_usuario_id' => $tecnico->user_id,
        ]);
    }

    private function relatorioDoTecnico(): array
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create();

        return [$tecnico, $relatorio, $tecnico->createToken('test')->plainTextToken];
    }

    // ── Rotas herdadas fora do ar ──────────────────────────────────────────

    public function test_apis_herdadas_nao_existem_mais(): void
    {
        // Toda rota da API é da fae/v1 — nenhum grupo legado sobrou.
        $foraDaFae = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
            ->map(fn ($rota) => $rota->uri())
            ->filter(fn (string $uri) => str_starts_with($uri, 'api/') && ! str_starts_with($uri, 'api/fae/v1/'))
            ->values()
            ->all();
        $this->assertSame([], $foraDaFae);

        $token = Usuario::factory()->administrador()->create()->createToken('test')->plainTextToken;
        $this->postJson('/api/v1/login', ['email' => 'x@x.com', 'senha' => 'x'])->assertNotFound();
        $this->withToken($token)->getJson('/api/v1/relatorios')->assertNotFound();
    }

    // ── REL-02: não cria relatório em atendimento Paralisado/Concluído ─────

    public function test_rel02_bloqueia_criacao_em_atendimento_paralisado(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = $this->atendimentoComModelo($tecnico, AtendimentoStatus::Paralisada->value);

        $this->withToken($tecnico->createToken('test')->plainTextToken)
            ->postJson("/api/fae/v1/atendimentos/{$atendimento->aten_id}/relatorios", [])
            ->assertStatus(422);
        $this->assertDatabaseMissing('atendimentos_relatorios', ['aten_rel_atendimento_id' => $atendimento->aten_id]);
    }

    public function test_rel02_bloqueia_criacao_em_atendimento_concluido(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = $this->atendimentoComModelo($tecnico, AtendimentoStatus::Concluida->value);

        $this->withToken($tecnico->createToken('test')->plainTextToken)
            ->postJson("/api/fae/v1/atendimentos/{$atendimento->aten_id}/relatorios", [])
            ->assertStatus(422);
    }

    // ── REL-03: só 1 relatório quando o modelo é de período único ──────────

    public function test_rel03_bloqueia_segundo_relatorio_de_periodo_unico(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = $this->atendimentoComModelo($tecnico, tpData: 1);
        $token = $tecnico->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson("/api/fae/v1/atendimentos/{$atendimento->aten_id}/relatorios", [])->assertCreated();
        $this->withToken($token)->postJson("/api/fae/v1/atendimentos/{$atendimento->aten_id}/relatorios", [])->assertStatus(422);
        $this->assertDatabaseCount('atendimentos_relatorios', 1);
    }

    // ── Criação: devolve o id e avança o atendimento ───────────────────────

    public function test_criar_relatorio_devolve_id_e_avanca_o_atendimento(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = $this->atendimentoComModelo($tecnico, AtendimentoStatus::NaoIniciada->value);

        $resposta = $this->withToken($tecnico->createToken('test')->plainTextToken)
            ->postJson("/api/fae/v1/atendimentos/{$atendimento->aten_id}/relatorios", ['aten_rel_data' => now()->toDateString()]);

        $resposta->assertCreated()->assertJsonStructure(['message', 'data' => ['id']]);
        $this->assertDatabaseHas('atendimentos_relatorios', [
            'aten_rel_id' => $resposta->json('data.id'),
            'aten_rel_atendimento_id' => $atendimento->aten_id,
        ]);
        $this->assertDatabaseHas('atendimentos', [
            'aten_id' => $atendimento->aten_id,
            'aten_status' => AtendimentoStatus::EmAndamento->value,
        ]);
    }

    // ── Validações ─────────────────────────────────────────────────────────

    public function test_validacoes_das_secoes_do_relatorio(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();
        $base = "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}";

        $this->withToken($token)->putJson("$base/horarios", ['entrada' => '25:99'])->assertStatus(422);
        $this->withToken($token)->putJson("$base/clima", ['manha' => 'furacao'])->assertStatus(422);
        $this->withToken($token)->postJson("$base/descricao-itens", [])->assertStatus(422);
        $this->withToken($token)->postJson("$base/anexos", [
            'fotos' => [UploadedFile::fake()->create('malware.exe', 10)],
        ])->assertStatus(422);
    }

    public function test_status_do_atendimento_rejeita_valor_fora_do_enum(): void
    {
        // Quem tem atendimentos.editar (o técnico padrão da FAÉ não tem).
        $admin = Usuario::factory()->administrador()->create();
        $atendimento = Atendimento::factory()->create();

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->putJson("/api/fae/v1/atendimentos/{$atendimento->aten_id}/status", ['status' => 99])
            ->assertStatus(422);
    }

    public function test_informacoes_adicionais_gravam(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)
            ->putJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/informacoes-adicionais", [
                'informacoes_adicionais' => 'Peça trocada conforme solicitado.',
            ])
            ->assertOk();
        $this->assertDatabaseHas('atendimentos_relatorios', [
            'aten_rel_id' => $relatorio->aten_rel_id,
            'aten_rel_informacoes_adicionais' => 'Peça trocada conforme solicitado.',
        ]);
    }

    // ── Listagem: técnico só vê o seu, administrador vê tudo ───────────────

    public function test_tecnico_lista_so_os_proprios_atendimentos(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $meu = Atendimento::factory()->create(['aten_usuario_id' => $tecnicoA->user_id]);
        $alheio = Atendimento::factory()->create(['aten_usuario_id' => $tecnicoB->user_id]);

        $ids = collect($this->withToken($tecnicoA->createToken('test')->plainTextToken)
            ->getJson('/api/fae/v1/atendimentos')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($meu->aten_id));
        $this->assertFalse($ids->contains($alheio->aten_id));
    }

    public function test_admin_lista_todos_os_atendimentos(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]);
        $admin = Usuario::factory()->administrador()->create();

        $ids = collect($this->withToken($admin->createToken('test')->plainTextToken)
            ->getJson('/api/fae/v1/atendimentos')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($atendimento->aten_id));
    }

    public function test_tecnico_nao_abre_relatorio_de_outro_tecnico(): void
    {
        [, $relatorio] = $this->relatorioDoTecnico();
        $outro = Usuario::factory()->tecnico()->create();

        $this->withToken($outro->createToken('test')->plainTextToken)
            ->getJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}")
            ->assertForbidden();
    }

    // ── Exclusão de anexo apaga também o arquivo ───────────────────────────

    public function test_excluir_anexo_apaga_o_arquivo_fisico(): void
    {
        Storage::fake('public');
        [, $relatorio, $token] = $this->relatorioDoTecnico();
        $path = UploadedFile::fake()->image('foto.jpg')->store("atendimentos_relatorios/{$relatorio->aten_rel_id}/fotos", 'public');
        $foto = AtendimentoRelatorioFoto::create([
            'aten_rel_foto_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_foto_path' => $path,
        ]);

        $this->withToken($token)
            ->deleteJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/anexos/foto/{$foto->aten_rel_foto_id}")
            ->assertOk();

        $this->assertDatabaseMissing('atendimentos_relatorios_fotos', ['aten_rel_foto_id' => $foto->aten_rel_foto_id]);
        Storage::disk('public')->assertMissing($path);
    }
}
