<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioDescricaoItem;
use App\Models\AtendimentoRelatorioDescricaoItemFoto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// RF013 — editar item da Descrição (texto; incluir, substituir ou remover a
// foto) na API do app (contrato 3.4) e na web, e exclusão de item apagando
// a foto do disco (a API do app não apagava).
class DescricaoItemEdicaoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $tecnico;
    private AtendimentoRelatorio $relatorio;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->tecnico = Usuario::factory()->tecnico()->create();
        $this->relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $this->tecnico->user_id]), 'atendimento')
            ->create();
        $this->token = $this->tecnico->createToken('test')->plainTextToken;
    }

    private function item(?string $fotoPath = null, ?AtendimentoRelatorio $relatorio = null): AtendimentoRelatorioDescricaoItem
    {
        $relatorio ??= $this->relatorio;
        $item = AtendimentoRelatorioDescricaoItem::create([
            'aten_rel_desc_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_desc_texto' => 'Texto original',
            'aten_rel_desc_criado_em' => now(),
        ]);
        if ($fotoPath) {
            Storage::disk('public')->put($fotoPath, 'conteudo antigo');
            $item->fotos()->create(['aten_rel_desc_foto_path' => $fotoPath]);
        }

        return $item;
    }

    private function urlMcl(AtendimentoRelatorioDescricaoItem $item, ?int $relatorioId = null): string
    {
        return '/api/mcl/v1/relatorios/' . ($relatorioId ?? $this->relatorio->aten_rel_id) . "/descricao-itens/{$item->aten_rel_desc_id}";
    }

    private function editarMcl(AtendimentoRelatorioDescricaoItem $item, array $campos)
    {
        return $this->withToken($this->token)->post($this->urlMcl($item), $campos, ['Accept' => 'application/json']);
    }

    // ── API do app ───────────────────────────────────────────────────────────

    public function test_edita_so_o_texto_e_mantem_a_foto(): void
    {
        $antiga = "atendimentos_relatorios/{$this->relatorio->aten_rel_id}/descricao/antiga.jpg";
        $item = $this->item($antiga);

        $this->editarMcl($item, ['texto' => 'Texto novo'])
            ->assertOk()
            ->assertJsonPath('data.id', $item->aten_rel_desc_id)
            ->assertJsonPath('data.texto', 'Texto novo')
            ->assertJsonPath('data.foto_url', url('midia/' . $antiga))
            ->assertJsonStructure(['data' => ['criado_em'], 'message']);

        Storage::disk('public')->assertExists($antiga);
        $this->assertSame(1, $item->fotos()->count());
    }

    public function test_substitui_a_foto_e_apaga_o_arquivo_antigo(): void
    {
        $antiga = "atendimentos_relatorios/{$this->relatorio->aten_rel_id}/descricao/antiga.jpg";
        $item = $this->item($antiga);

        $response = $this->editarMcl($item, ['texto' => 'Com foto nova', 'foto' => UploadedFile::fake()->image('nova foto.jpg')])
            ->assertOk();

        $nova = $item->fotos()->value('aten_rel_desc_foto_path');
        $this->assertSame(1, $item->fotos()->count());
        $this->assertNotSame($antiga, $nova);
        $this->assertStringStartsWith("atendimentos_relatorios/{$this->relatorio->aten_rel_id}/descricao/nova_foto", $nova);
        $response->assertJsonPath('data.foto_url', url('midia/' . $nova));
        Storage::disk('public')->assertMissing($antiga);
        Storage::disk('public')->assertExists($nova);
    }

    public function test_inclui_foto_em_item_sem_foto(): void
    {
        $item = $this->item();

        $this->editarMcl($item, ['texto' => 'Agora com foto', 'foto' => UploadedFile::fake()->image('a.png')])
            ->assertOk();

        $this->assertSame(1, $item->fotos()->count());
        Storage::disk('public')->assertExists($item->fotos()->value('aten_rel_desc_foto_path'));
    }

    public function test_remove_a_foto_sem_substituir(): void
    {
        foreach (['1', 'true'] as $valor) {
            $antiga = "atendimentos_relatorios/{$this->relatorio->aten_rel_id}/descricao/antiga_{$valor}.jpg";
            $item = $this->item($antiga);

            $this->editarMcl($item, ['texto' => 'Sem foto', 'remover_foto' => $valor])
                ->assertOk()
                ->assertJsonPath('data.foto_url', null);

            $this->assertSame(0, $item->fotos()->count());
            Storage::disk('public')->assertMissing($antiga);
        }
    }

    public function test_rejeita_foto_junto_com_remover_foto(): void
    {
        $antiga = "atendimentos_relatorios/{$this->relatorio->aten_rel_id}/descricao/antiga.jpg";
        $item = $this->item($antiga);

        $this->editarMcl($item, ['texto' => 'x', 'foto' => UploadedFile::fake()->image('b.jpg'), 'remover_foto' => '1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('remover_foto');

        $this->editarMcl($item, ['texto' => ''])->assertStatus(422)->assertJsonValidationErrors('texto');
        $this->editarMcl($item, ['texto' => 'x', 'foto' => UploadedFile::fake()->create('a.gif', 10, 'image/gif')])
            ->assertStatus(422)->assertJsonValidationErrors('foto');

        Storage::disk('public')->assertExists($antiga);
        $this->assertSame('Texto original', $item->fresh()->aten_rel_desc_texto);
    }

    public function test_responde_404_para_item_de_outro_relatorio(): void
    {
        $outroRelatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $this->tecnico->user_id]), 'atendimento')
            ->create();
        $itemDoOutro = $this->item(null, $outroRelatorio);

        // URL com o relatório "errado" para o item
        $this->withToken($this->token)
            ->post($this->urlMcl($itemDoOutro), ['texto' => 'invasão'], ['Accept' => 'application/json'])
            ->assertNotFound();
        $this->assertSame('Texto original', $itemDoOutro->fresh()->aten_rel_desc_texto);
    }

    public function test_tecnico_de_fora_recebe_403(): void
    {
        $item = $this->item();
        $intruso = Usuario::factory()->tecnico()->create();

        $this->withToken($intruso->createToken('t')->plainTextToken)
            ->post($this->urlMcl($item), ['texto' => 'invasão'], ['Accept' => 'application/json'])
            ->assertForbidden();
        $this->assertSame('Texto original', $item->fresh()->aten_rel_desc_texto);
    }

    public function test_falha_no_banco_mantem_a_foto_antiga_e_descarta_a_nova(): void
    {
        $antiga = "atendimentos_relatorios/{$this->relatorio->aten_rel_id}/descricao/antiga.jpg";
        $item = $this->item($antiga);
        AtendimentoRelatorioDescricaoItemFoto::creating(fn () => throw new \RuntimeException('falha simulada no banco'));

        $this->withoutExceptionHandling();
        try {
            $this->editarMcl($item, ['texto' => 'Não deve gravar', 'foto' => UploadedFile::fake()->image('nova.jpg')]);
            $this->fail('A exceção simulada deveria ter subido.');
        } catch (\RuntimeException $e) {
            $this->assertSame('falha simulada no banco', $e->getMessage());
        }

        Storage::disk('public')->assertExists($antiga);
        $this->assertSame([$antiga], Storage::disk('public')->allFiles("atendimentos_relatorios/{$this->relatorio->aten_rel_id}/descricao"));
        $this->assertSame('Texto original', $item->fresh()->aten_rel_desc_texto);
        $this->assertSame([$antiga], $item->fotos()->pluck('aten_rel_desc_foto_path')->all());
    }

    public function test_excluir_item_pela_api_apaga_o_arquivo_da_foto(): void
    {
        $antiga = "atendimentos_relatorios/{$this->relatorio->aten_rel_id}/descricao/antiga.jpg";
        $item = $this->item($antiga);

        $this->withToken($this->token)->deleteJson($this->urlMcl($item))->assertOk();

        $this->assertDatabaseMissing('atendimentos_relatorios_descricao_itens', ['aten_rel_desc_id' => $item->aten_rel_desc_id]);
        $this->assertDatabaseMissing('atendimentos_relatorios_descricao_itens_fotos', ['aten_rel_desc_foto_item_id' => $item->aten_rel_desc_id]);
        Storage::disk('public')->assertMissing($antiga);
    }

    // ── Web ──────────────────────────────────────────────────────────────────

    public function test_web_edita_texto_e_substitui_foto(): void
    {
        $antiga = "atendimentos_relatorios/{$this->relatorio->aten_rel_id}/descricao/antiga.jpg";
        $item = $this->item($antiga);

        $this->actingAs($this->tecnico)
            ->post(
                route('atendimentos-relatorios.update-descricao-item', [$this->relatorio->aten_rel_id, $item->aten_rel_desc_id]),
                ['texto' => 'Editado na web', 'foto' => UploadedFile::fake()->image('web.jpg')],
                ['Accept' => 'application/json']
            )
            ->assertOk()
            ->assertJsonPath('data.texto', 'Editado na web');

        Storage::disk('public')->assertMissing($antiga);
        Storage::disk('public')->assertExists($item->fotos()->value('aten_rel_desc_foto_path'));
    }

    public function test_web_checa_posse_e_relatorio_do_item(): void
    {
        $item = $this->item();
        $intruso = Usuario::factory()->tecnico()->create();
        $rota = route('atendimentos-relatorios.update-descricao-item', [$this->relatorio->aten_rel_id, $item->aten_rel_desc_id]);

        $this->actingAs($intruso)->post($rota, ['texto' => 'invasão'], ['Accept' => 'application/json'])->assertForbidden();

        $outroRelatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $this->tecnico->user_id]), 'atendimento')
            ->create();
        $this->actingAs($this->tecnico)
            ->post(
                route('atendimentos-relatorios.update-descricao-item', [$outroRelatorio->aten_rel_id, $item->aten_rel_desc_id]),
                ['texto' => 'relatório errado'],
                ['Accept' => 'application/json']
            )
            ->assertNotFound();

        $this->assertSame('Texto original', $item->fresh()->aten_rel_desc_texto);
    }

    public function test_web_excluir_item_apaga_o_arquivo_da_foto(): void
    {
        $antiga = "atendimentos_relatorios/{$this->relatorio->aten_rel_id}/descricao/antiga.jpg";
        $item = $this->item($antiga);

        $this->actingAs($this->tecnico)
            ->deleteJson(route('atendimentos-relatorios.destroy-descricao-item', [$this->relatorio->aten_rel_id, $item->aten_rel_desc_id]))
            ->assertOk();

        $this->assertDatabaseMissing('atendimentos_relatorios_descricao_itens', ['aten_rel_desc_id' => $item->aten_rel_desc_id]);
        Storage::disk('public')->assertMissing($antiga);
    }
}
