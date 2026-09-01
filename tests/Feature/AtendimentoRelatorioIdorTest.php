<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioServico;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Item 2.2 do plano de correções: nenhum método destes dois controllers
// verificava posse — um técnico autenticado lia/escrevia relatórios e
// atendimentos de OUTRO técnico só trocando o ID na URL. Testa uma amostra
// dos endpoints listados no plano (mais store()/update(), achados durante a
// investigação e não listados explicitamente) nos dois controllers alvo, e
// confirma que administrador continua com acesso total.
class AtendimentoRelatorioIdorTest extends TestCase
{
    use RefreshDatabase;

    private function criarRelatorioDoTecnico(Usuario $tecnico): AtendimentoRelatorio
    {
        return AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create();
    }

    public function test_tecnico_nao_visualiza_relatorio_de_outro_tecnico(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorioDoTecnico($tecnicoA);

        $this->actingAs($tecnicoB)
            ->get(route('atendimentos-relatorios.show', $relatorio->aten_rel_id))
            ->assertForbidden();
    }

    public function test_tecnico_nao_atualiza_horarios_de_relatorio_de_outro_tecnico(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorioDoTecnico($tecnicoA);

        $this->actingAs($tecnicoB)
            ->postJson(route('atendimentos-relatorios.update-horarios', $relatorio->aten_rel_id), [
                'aten_rel_hora_entrada'          => '08:00',
                'aten_rel_hora_inicio_intervalo' => '12:00',
                'aten_rel_hora_fim_intervalo'    => '13:00',
                'aten_rel_hora_saida'            => '18:00',
            ])
            ->assertForbidden();
    }

    public function test_tecnico_nao_remove_servico_de_relatorio_de_outro_tecnico(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorioDoTecnico($tecnicoA);
        $servico = AtendimentoRelatorioServico::create([
            'aten_rel_serv_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_serv_descricao'    => 'Troca de peça',
        ]);

        $this->actingAs($tecnicoB)
            ->deleteJson(route('atendimentos-relatorios.destroy-servico', [$relatorio->aten_rel_id, $servico->aten_rel_serv_id]))
            ->assertForbidden();

        $this->assertDatabaseHas('atendimentos_relatorios_servicos', [
            'aten_rel_serv_id' => $servico->aten_rel_serv_id,
        ]);
    }

    public function test_tecnico_nao_ve_assinaturas_de_relatorio_de_outro_tecnico(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorioDoTecnico($tecnicoA);

        $this->actingAs($tecnicoB)
            ->getJson(route('atendimentos-relatorios.get-assinaturas', $relatorio->aten_rel_id))
            ->assertForbidden();
    }

    public function test_tecnico_nao_cria_relatorio_para_atendimento_de_outro_tecnico(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $atendimento = Atendimento::factory()->emAndamento()->create(['aten_usuario_id' => $tecnicoA->user_id]);

        $this->actingAs($tecnicoB)
            ->postJson(route('atendimentos-relatorios.store'), ['aten_id' => $atendimento->aten_id])
            ->assertForbidden();

        $this->assertDatabaseMissing('atendimentos_relatorios', [
            'aten_rel_atendimento_id' => $atendimento->aten_id,
        ]);
    }

    public function test_tecnico_nao_ve_observacoes_de_atendimento_de_outro_tecnico(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $atendimento = Atendimento::factory()->create(['aten_usuario_id' => $tecnicoA->user_id]);

        $this->actingAs($tecnicoB)
            ->getJson(route('atendimentos.get-observacoes', $atendimento->aten_id))
            ->assertForbidden();
    }

    public function test_tecnico_nao_remove_anexo_de_atendimento_de_outro_tecnico(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $atendimento = Atendimento::factory()->create(['aten_usuario_id' => $tecnicoA->user_id]);

        $this->actingAs($tecnicoB)
            ->deleteJson(route('atendimentos.destroy-anexo', [$atendimento->aten_id, 1]))
            ->assertForbidden();
    }

    public function test_administrador_continua_com_acesso_total(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $admin = Usuario::factory()->administrador()->create();
        $relatorio = $this->criarRelatorioDoTecnico($tecnicoA);

        $this->actingAs($admin)
            ->get(route('atendimentos-relatorios.show', $relatorio->aten_rel_id))
            ->assertOk();

        $this->actingAs($admin)
            ->getJson(route('atendimentos-relatorios.get-assinaturas', $relatorio->aten_rel_id))
            ->assertOk();

        $this->actingAs($admin)
            ->getJson(route('atendimentos.get-observacoes', $relatorio->atendimento->aten_id))
            ->assertOk();
    }

    public function test_tecnico_dono_continua_acessando_o_proprio_relatorio(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorioDoTecnico($tecnicoA);

        $this->actingAs($tecnicoA)
            ->get(route('atendimentos-relatorios.show', $relatorio->aten_rel_id))
            ->assertOk();
    }
}
