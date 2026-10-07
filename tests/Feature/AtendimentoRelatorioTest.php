<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtendimentoRelatorioTest extends TestCase
{
    use RefreshDatabase;

    // ── Criação de relatório ─────────────────────────────────────────────────

    public function test_tecnico_cria_relatorio_para_seu_atendimento(): void
    {
        $tecnico     = Usuario::factory()->tecnico()->create();
        $atendimento = Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]);
        $token       = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/relatorios', [
            'aten_id'       => $atendimento->aten_id,
            'aten_rel_data' => now()->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('atendimentos_relatorios', [
            'aten_rel_atendimento_id' => $atendimento->aten_id,
        ]);
    }

    public function test_relatorio_falha_sem_modelo_vinculado_a_natureza(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();

        $naturezaSemModelo = NaturezaAtendimento::factory()->create([
            'nat_aten_mod_relatorio_id' => null,
        ]);

        $atendimento = Atendimento::factory()->create([
            'aten_usuario_id'  => $tecnico->user_id,
            'aten_natureza_id' => $naturezaSemModelo->nat_aten_id,
        ]);

        $token = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/relatorios', [
            'aten_id' => $atendimento->aten_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false]);
    }

    // ── Listagem ─────────────────────────────────────────────────────────────

    public function test_tecnico_ve_apenas_seus_relatorios(): void
    {
        $tecnico1 = Usuario::factory()->tecnico()->create();
        $tecnico2 = Usuario::factory()->tecnico()->create();

        $relatorioTecnico1 = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico1->user_id]), 'atendimento')
            ->create();

        AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico2->user_id]), 'atendimento')
            ->create();

        $token = $tecnico1->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/relatorios');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($relatorioTecnico1->aten_rel_id, $data[0]['id']);
    }

    public function test_administrador_ve_todos_os_relatorios(): void
    {
        $admin   = Usuario::factory()->administrador()->create();
        $tecnico = Usuario::factory()->tecnico()->create();

        AtendimentoRelatorio::factory()->count(3)
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create();

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/relatorios');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    // ── Horários ─────────────────────────────────────────────────────────────

    public function test_atualiza_horarios_do_relatorio(): void
    {
        $tecnico   = Usuario::factory()->tecnico()->create();
        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create();

        $token = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson("/api/v1/relatorios/{$relatorio->aten_rel_id}/horarios", [
            'aten_rel_hora_entrada'          => '08:00',
            'aten_rel_hora_inicio_intervalo' => '12:00',
            'aten_rel_hora_fim_intervalo'    => '13:00',
            'aten_rel_hora_saida'            => '17:00',
        ]);

        $response->assertOk()->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('atendimentos_relatorios_horarios', [
            'aten_rel_hora_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_hora_entrada'      => '08:00:00',
            'aten_rel_hora_saida'        => '17:00:00',
        ]);
    }

    // ── Clima ─────────────────────────────────────────────────────────────────

    public function test_atualiza_clima_do_relatorio(): void
    {
        $tecnico   = Usuario::factory()->tecnico()->create();
        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create();

        $token = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson("/api/v1/relatorios/{$relatorio->aten_rel_id}/clima", [
            'clima_manha' => 'ensolarado',
            'clima_tarde' => 'nublado',
            'clima_noite' => 'chuvoso',
        ]);

        $response->assertOk()->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('atendimentos_relatorios_condicoes_climaticas', [
            'aten_rel_clima_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_clima_periodo'      => 1,
            'aten_rel_clima_condicao'     => 1,
        ]);
    }

    // ── Acesso negado ─────────────────────────────────────────────────────────

    public function test_tecnico_nao_acessa_relatorio_de_outro_tecnico(): void
    {
        $tecnico1  = Usuario::factory()->tecnico()->create();
        $tecnico2  = Usuario::factory()->tecnico()->create();

        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico2->user_id]), 'atendimento')
            ->create();

        $token = $tecnico1->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson("/api/v1/relatorios/{$relatorio->aten_rel_id}");

        $response->assertForbidden();
    }

    // ── Atividades ────────────────────────────────────────────────────────────

    public function test_adiciona_atividade_ao_relatorio(): void
    {
        $tecnico   = Usuario::factory()->tecnico()->create();
        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create();

        $token = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson("/api/v1/relatorios/{$relatorio->aten_rel_id}/atividades", [
            'aten_rel_ativ_descricao' => 'Instalação de equipamento',
            'aten_rel_ativ_status'    => 1,
        ]);

        $response->assertStatus(201)->assertJsonFragment(['descricao' => 'Instalação de equipamento']);

        $this->assertDatabaseHas('atendimentos_relatorios_atividades', [
            'aten_rel_ativ_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_ativ_descricao'    => 'Instalação de equipamento',
        ]);
    }
}
