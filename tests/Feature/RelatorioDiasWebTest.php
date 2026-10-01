<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioCondicaoClimatica;
use App\Models\AtendimentoRelatorioHorario;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// RF012 — aba web "Horários e Clima": rotas GET/POST/DELETE
// /atendimentos-relatorios/{id}/dias[/{data}], com checagem de posse e as
// mesmas regras de validação da API do app.
class RelatorioDiasWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-30 12:00:00', 'America/Sao_Paulo'));
    }

    private function relatorioDe(Usuario $tecnico, array $atributos = []): AtendimentoRelatorio
    {
        return AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create($atributos);
    }

    private function corpo(array $sobrescrever = []): array
    {
        return array_replace_recursive([
            'entrada' => '07:42',
            'inicio_intervalo' => '12:00',
            'fim_intervalo' => '13:30',
            'saida' => '17:30',
            'clima' => ['manha' => 'ensolarado', 'tarde' => null, 'noite' => null],
        ], $sobrescrever);
    }

    public function test_pagina_do_relatorio_tem_a_aba_unica_horarios_e_clima(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->relatorioDe($tecnico);

        $this->actingAs($tecnico)
            ->get(route('atendimentos-relatorios.show', $relatorio->aten_rel_id))
            ->assertOk()
            ->assertSee('href="#tab-dias"', false)
            ->assertSee('Horários e Clima')
            ->assertSee('id="modal_dia"', false)
            ->assertSee('id="modal_confirmar_acao"', false)
            ->assertDontSee('#tab-horarios', false)
            ->assertDontSee('#tab-clima', false);
    }

    public function test_lista_vazia(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->relatorioDe($tecnico);

        $this->actingAs($tecnico)
            ->getJson(route('atendimentos-relatorios.get-dias', $relatorio->aten_rel_id))
            ->assertOk()
            ->assertExactJson(['data' => [], 'horarios_legado' => false, 'legado' => null]);
    }

    public function test_lista_no_formato_antigo_devolve_o_legado_com_a_data_do_relatorio(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->relatorioDe($tecnico, ['aten_rel_data' => '2026-09-15']);
        AtendimentoRelatorioHorario::create([
            'aten_rel_hora_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_hora_entrada' => '08:00:00',
            'aten_rel_hora_saida' => '17:00:00',
        ]);
        AtendimentoRelatorioCondicaoClimatica::create([
            'aten_rel_clima_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_clima_periodo' => 3,
            'aten_rel_clima_condicao' => 3,
        ]);

        $this->actingAs($tecnico)
            ->getJson(route('atendimentos-relatorios.get-dias', $relatorio->aten_rel_id))
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('horarios_legado', true)
            ->assertJsonPath('legado', [
                'id' => null,
                'data' => '2026-09-15',
                'entrada' => '08:00',
                'inicio_intervalo' => null,
                'fim_intervalo' => null,
                'saida' => '17:00',
                'clima' => ['manha' => null, 'tarde' => null, 'noite' => 'chuvoso'],
            ]);

        // somente leitura: gravar responde 409 e não cria nada
        $this->actingAs($tecnico)
            ->postJson(route('atendimentos-relatorios.upsert-dia', [$relatorio->aten_rel_id, '2026-09-29']), $this->corpo())
            ->assertStatus(409);
        $this->assertSame(0, $relatorio->dias()->count());
    }

    public function test_inclui_edita_e_exclui_dias(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->relatorioDe($tecnico);
        $rota = fn (string $data) => route('atendimentos-relatorios.upsert-dia', [$relatorio->aten_rel_id, $data]);

        $this->actingAs($tecnico)->postJson($rota('2026-09-29'), $this->corpo())
            ->assertOk()->assertJsonPath('data.entrada', '07:42')->assertJsonStructure(['message']);
        $this->actingAs($tecnico)->postJson($rota('2026-09-28'), $this->corpo(['saida' => null]))->assertOk();
        $this->actingAs($tecnico)->postJson($rota('2026-09-29'), $this->corpo(['saida' => '18:15']))
            ->assertOk()->assertJsonPath('data.saida', '18:15');

        $this->actingAs($tecnico)
            ->deleteJson(route('atendimentos-relatorios.destroy-dia', [$relatorio->aten_rel_id, '2026-09-28']))
            ->assertOk();
        $this->actingAs($tecnico)
            ->deleteJson(route('atendimentos-relatorios.destroy-dia', [$relatorio->aten_rel_id, '2026-09-28']))
            ->assertOk();

        $this->actingAs($tecnico)
            ->getJson(route('atendimentos-relatorios.get-dias', $relatorio->aten_rel_id))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data', '2026-09-29')
            ->assertJsonPath('data.0.saida', '18:15')
            ->assertJsonPath('horarios_legado', false);
    }

    public function test_aplica_as_mesmas_regras_de_validacao_da_api(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->relatorioDe($tecnico);
        $rota = fn (string $data) => route('atendimentos-relatorios.upsert-dia', [$relatorio->aten_rel_id, $data]);

        $this->actingAs($tecnico)->postJson($rota('2026-10-01'), $this->corpo())
            ->assertStatus(422)->assertJsonValidationErrors('data');
        $this->actingAs($tecnico)->postJson($rota('2026-09-29'), $this->corpo(['fim_intervalo' => null]))
            ->assertStatus(422)->assertJsonValidationErrors('fim_intervalo');
        $this->actingAs($tecnico)->postJson($rota('2026-09-29'), $this->corpo(['entrada' => '12:30']))
            ->assertStatus(422)->assertJsonValidationErrors('inicio_intervalo');
        $this->actingAs($tecnico)->postJson($rota('2026-09-29'), $this->corpo(['clima' => ['manha' => 'neve']]))
            ->assertStatus(422)->assertJsonValidationErrors('clima.manha');
    }

    public function test_tecnico_nao_acessa_dias_de_relatorio_de_outro_tecnico(): void
    {
        $dono = Usuario::factory()->tecnico()->create();
        $intruso = Usuario::factory()->tecnico()->create();
        $relatorio = $this->relatorioDe($dono);

        $this->actingAs($intruso)
            ->getJson(route('atendimentos-relatorios.get-dias', $relatorio->aten_rel_id))
            ->assertForbidden();
        $this->actingAs($intruso)
            ->deleteJson(route('atendimentos-relatorios.destroy-dia', [$relatorio->aten_rel_id, '2026-09-29']))
            ->assertForbidden();
    }

    public function test_administrador_acessa_dias_de_qualquer_tecnico(): void
    {
        $dono = Usuario::factory()->tecnico()->create();
        $admin = Usuario::factory()->administrador()->create();
        $relatorio = $this->relatorioDe($dono);

        $this->actingAs($admin)
            ->postJson(route('atendimentos-relatorios.upsert-dia', [$relatorio->aten_rel_id, '2026-09-29']), $this->corpo())
            ->assertOk();
    }
}
