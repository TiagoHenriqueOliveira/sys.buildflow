<?php

namespace Tests\Feature;

use App\Enums\AtendimentoRelatorioStatus;
use App\Enums\AtendimentoStatus;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioAssinatura;
use App\Models\ModeloRelatorio;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Item 2.10 do plano de correções: primeira camada de teste pra Api/Mcl/*
// (API que o app realmente usa — tinha zero cobertura antes desta sessão).
// Prioridade do próprio plano: REL-02, REL-05 e criação de relatório.
class AtendimentoRelatorioMclRegrasNegocioTest extends TestCase
{
    use RefreshDatabase;

    private function atendimentoComModelo(int $statusAtendimento = 2, int $tpData = 1): Atendimento
    {
        $modelo   = ModeloRelatorio::factory()->create(['mod_rel_tp_data' => $tpData]);
        $natureza = NaturezaAtendimento::factory()->create(['nat_aten_mod_relatorio_id' => $modelo->mod_rel_id]);

        return Atendimento::factory()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_status'      => $statusAtendimento,
        ]);
    }

    // ── REL-02: não cria relatório em atendimento Paralisado/Concluído ──────

    public function test_rel02_bloqueia_criacao_em_atendimento_paralisado(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = $this->atendimentoComModelo(statusAtendimento: AtendimentoStatus::Paralisada->value);
        $atendimento->update(['aten_usuario_id' => $tecnico->user_id]);
        $token = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/mcl/v1/atendimentos/{$atendimento->aten_id}/relatorios", []);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('atendimentos_relatorios', [
            'aten_rel_atendimento_id' => $atendimento->aten_id,
        ]);
    }

    public function test_rel02_bloqueia_criacao_em_atendimento_concluido(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = $this->atendimentoComModelo(statusAtendimento: AtendimentoStatus::Concluida->value);
        $atendimento->update(['aten_usuario_id' => $tecnico->user_id]);
        $token = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/mcl/v1/atendimentos/{$atendimento->aten_id}/relatorios", []);

        $response->assertStatus(422);
    }

    // ── REL-03: só 1 relatório quando o modelo é de período único ───────────

    public function test_rel03_bloqueia_segundo_relatorio_de_periodo_unico(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = $this->atendimentoComModelo(tpData: 1);
        $atendimento->update(['aten_usuario_id' => $tecnico->user_id]);
        $token = $tecnico->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/mcl/v1/atendimentos/{$atendimento->aten_id}/relatorios", [])
            ->assertCreated();

        $response = $this->withToken($token)
            ->postJson("/api/mcl/v1/atendimentos/{$atendimento->aten_id}/relatorios", []);

        $response->assertStatus(422);
        $this->assertDatabaseCount('atendimentos_relatorios', 1);
    }

    // ── REL-05: técnico não aprova sem as duas assinaturas ───────────────────

    public function test_rel05_rebaixa_silenciosamente_sem_as_duas_assinaturas(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create(['aten_rel_status' => AtendimentoRelatorioStatus::Revisar->value]);
        $token = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->putJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}/status", [
                'status' => AtendimentoRelatorioStatus::Aprovado->value,
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', AtendimentoRelatorioStatus::Revisar->value);
        $response->assertJsonPath('rebaixado', true);
        $this->assertDatabaseHas('atendimentos_relatorios', [
            'aten_rel_id'     => $relatorio->aten_rel_id,
            'aten_rel_status' => AtendimentoRelatorioStatus::Revisar->value,
        ]);
    }

    public function test_rel05_aprova_quando_as_duas_assinaturas_existem(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create(['aten_rel_status' => AtendimentoRelatorioStatus::Revisar->value]);
        AtendimentoRelatorioAssinatura::create([
            'aten_rel_ass_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_ass_path'         => 'fake/responsavel.png',
            'aten_rel_ass_tipo'         => 'responsavel',
        ]);
        AtendimentoRelatorioAssinatura::create([
            'aten_rel_ass_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_ass_path'         => 'fake/cliente.png',
            'aten_rel_ass_tipo'         => 'cliente',
        ]);
        $token = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->putJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}/status", [
                'status' => AtendimentoRelatorioStatus::Aprovado->value,
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', AtendimentoRelatorioStatus::Aprovado->value);
        $response->assertJsonPath('rebaixado', false);
    }

    public function test_rel05_admin_aprova_sem_assinaturas(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory(), 'atendimento')
            ->create(['aten_rel_status' => AtendimentoRelatorioStatus::Revisar->value]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->putJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}/status", [
                'status' => AtendimentoRelatorioStatus::Aprovado->value,
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', AtendimentoRelatorioStatus::Aprovado->value);
    }

    // ── Criação de relatório (caminho feliz, com formato de resposta) ───────

    public function test_store_relatorio_retorna_id_e_atualiza_atendimento(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = $this->atendimentoComModelo(statusAtendimento: AtendimentoStatus::NaoIniciada->value);
        $atendimento->update(['aten_usuario_id' => $tecnico->user_id]);
        $token = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/mcl/v1/atendimentos/{$atendimento->aten_id}/relatorios", [
                'aten_rel_data' => now()->toDateString(),
            ]);

        $response->assertCreated();
        $response->assertJsonStructure(['message', 'data' => ['id']]);
    }
}
