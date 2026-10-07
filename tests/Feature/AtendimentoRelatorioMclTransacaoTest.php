<?php

namespace Tests\Feature;

use App\Enums\AtendimentoStatus;
use App\Models\Atendimento;
use App\Models\ModeloRelatorio;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Item 2.3 do plano de correções: cobre que envolver a criação de relatório
// (Api\Mcl\RelatoriosController::store) em DB::transaction() não quebrou o
// caminho feliz — cria o relatório E avança o atendimento pra "em
// andamento" numa única chamada, como antes.
class AtendimentoRelatorioMclTransacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_criar_relatorio_via_api_mcl_avanca_status_do_atendimento(): void
    {
        $tecnico  = Usuario::factory()->tecnico()->create();
        $modelo   = ModeloRelatorio::factory()->create();
        $natureza = NaturezaAtendimento::factory()->create(['nat_aten_mod_relatorio_id' => $modelo->mod_rel_id]);
        $atendimento = Atendimento::factory()->naoIniciada()->create([
            'aten_usuario_id'  => $tecnico->user_id,
            'aten_natureza_id' => $natureza->nat_aten_id,
        ]);
        $token = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/mcl/v1/atendimentos/{$atendimento->aten_id}/relatorios", []);

        $response->assertCreated();
        $relatorioId = $response->json('data.id');

        $this->assertDatabaseHas('atendimentos_relatorios', [
            'aten_rel_id'              => $relatorioId,
            'aten_rel_atendimento_id'  => $atendimento->aten_id,
        ]);
        $this->assertDatabaseHas('atendimentos', [
            'aten_id'     => $atendimento->aten_id,
            'aten_status' => AtendimentoStatus::EmAndamento->value,
        ]);
    }
}
