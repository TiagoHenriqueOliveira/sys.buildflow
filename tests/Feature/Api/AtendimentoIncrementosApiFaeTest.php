<?php

namespace Tests\Feature\Api;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\Cliente;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtendimentoIncrementosApiFaeTest extends TestCase
{
    use RefreshDatabase;

    private function token(Usuario $usuario): string
    {
        return $usuario->createToken('test')->plainTextToken;
    }

    private function criarRelatorio(Usuario $tecnico, Cliente $cliente): AtendimentoRelatorio
    {
        $natureza = NaturezaAtendimento::factory()->create();
        $atendimento = Atendimento::factory()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $tecnico->user_id,
            'aten_cliente_id' => $cliente->cli_id,
        ]);

        return AtendimentoRelatorio::create([
            'aten_rel_atendimento_id' => $atendimento->aten_id,
            'aten_rel_data' => now()->toDateString(),
            'aten_rel_status' => 0,
        ]);
    }

    // ── BF07 - comprovante de compartilhamento ──────────────────────────────

    public function test_gera_e_lista_comprovante_de_compartilhamento(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $cliente = Cliente::factory()->create();
        $relatorio = $this->criarRelatorio($tecnico, $cliente);

        $gerar = $this->withToken($this->token($tecnico))->postJson(
            "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/compartilhamentos",
            ['canal' => 'whatsapp']
        );
        $gerar->assertCreated();
        $hash = $gerar->json('data.hash');
        $this->assertNotEmpty($hash);
        $this->assertSame(64, strlen($hash));

        $listar = $this->withToken($this->token($tecnico))->getJson(
            "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/compartilhamentos"
        );
        $listar->assertOk()->assertJsonCount(1, 'data');
        $this->assertDatabaseHas('atendimentos_relatorios_compartilhamentos', [
            'aten_rel_comp_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_comp_hash' => $hash,
        ]);
    }

    // ── BF08 - mapa de demandas ──────────────────────────────────────────────

    public function test_mapa_de_demandas_so_lista_atendimentos_com_cliente_georreferenciado(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $comGeo = Cliente::factory()->create(['cli_latitude' => -23.5, 'cli_longitude' => -46.6]);
        $semGeo = Cliente::factory()->create(['cli_latitude' => null, 'cli_longitude' => null]);
        $natureza = NaturezaAtendimento::factory()->create();
        Atendimento::factory()->create(['aten_natureza_id' => $natureza->nat_aten_id, 'aten_usuario_id' => $tecnico->user_id, 'aten_cliente_id' => $comGeo->cli_id]);
        Atendimento::factory()->create(['aten_natureza_id' => $natureza->nat_aten_id, 'aten_usuario_id' => $tecnico->user_id, 'aten_cliente_id' => $semGeo->cli_id]);

        $response = $this->withToken($this->token($tecnico))->getJson('/api/fae/v1/mapa-demandas');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($comGeo->cli_id, $response->json('data.0.cliente.id'));
    }

    public function test_tecnico_so_ve_seus_proprios_atendimentos_no_mapa_de_demandas(): void
    {
        $tecnico1 = Usuario::factory()->tecnico()->create();
        $tecnico2 = Usuario::factory()->tecnico()->create();
        $cliente = Cliente::factory()->create(['cli_latitude' => -23.5, 'cli_longitude' => -46.6]);
        $natureza = NaturezaAtendimento::factory()->create();
        Atendimento::factory()->create(['aten_natureza_id' => $natureza->nat_aten_id, 'aten_usuario_id' => $tecnico2->user_id, 'aten_cliente_id' => $cliente->cli_id]);

        $response = $this->withToken($this->token($tecnico1))->getJson('/api/fae/v1/mapa-demandas');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    // ── BF09 - checklist de pecas (so "trocada") ─────────────────────────────

    public function test_adiciona_peca_marcada_como_trocada(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $cliente = Cliente::factory()->create();
        $relatorio = $this->criarRelatorio($tecnico, $cliente);

        $response = $this->withToken($this->token($tecnico))->postJson(
            "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/pecas",
            ['descricao' => 'Bomba dosadora', 'trocada' => true]
        );

        $response->assertCreated()->assertJsonPath('data.trocada', true);
        $this->assertDatabaseHas('atendimentos_relatorios_pecas', [
            'aten_rel_peca_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_peca_descricao' => 'Bomba dosadora',
            'aten_rel_peca_trocada' => 1,
        ]);
    }

    // ── BF11 - observacao interna ─────────────────────────────────────────────

    public function test_salva_observacao_interna_e_ela_nao_aparece_no_comprovante(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $cliente = Cliente::factory()->create();
        $relatorio = $this->criarRelatorio($tecnico, $cliente);

        $salvar = $this->withToken($this->token($tecnico))->putJson(
            "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/observacao-interna",
            ['valor' => 'Cliente reclamou do prazo, atenção na próxima visita.']
        );
        $salvar->assertOk();
        $this->assertDatabaseHas('atendimentos_relatorios', [
            'aten_rel_id' => $relatorio->aten_rel_id,
            'aten_rel_observacao_interna' => 'Cliente reclamou do prazo, atenção na próxima visita.',
        ]);

        $comprovante = $this->withToken($this->token($tecnico))->postJson(
            "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/compartilhamentos"
        );
        $comprovante->assertCreated();
        $comprovante->assertJsonMissingPath('data.observacao_interna');
        $comprovante->assertJsonMissing(['Cliente reclamou do prazo, atenção na próxima visita.']);
    }

    public function test_tecnico_de_outro_atendimento_nao_acessa_observacao_interna(): void
    {
        $dono = Usuario::factory()->tecnico()->create();
        $outro = Usuario::factory()->tecnico()->create();
        $cliente = Cliente::factory()->create();
        $relatorio = $this->criarRelatorio($dono, $cliente);

        $response = $this->withToken($this->token($outro))->putJson(
            "/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/observacao-interna",
            ['valor' => 'Não deveria conseguir salvar isso.']
        );

        $response->assertForbidden();
    }
}