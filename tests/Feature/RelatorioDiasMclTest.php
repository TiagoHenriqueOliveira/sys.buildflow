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

// RF012 — horário e clima por dia na API Mcl (contrato 3.1 a 3.3 do hotfix
// MCL): PUT/DELETE /relatorios/{id}/dias/{data} e as chaves novas `dias` e
// `horarios_legado` no GET /relatorios/{id}.
class RelatorioDiasMclTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-30 12:00:00', 'America/Sao_Paulo'));
    }

    private function relatorioDoTecnico(): array
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create();
        $token = $tecnico->createToken('test')->plainTextToken;

        return [$tecnico, $relatorio, $token];
    }

    private function corpo(array $sobrescrever = []): array
    {
        return array_replace_recursive([
            'entrada' => '07:42',
            'inicio_intervalo' => '12:00',
            'fim_intervalo' => '13:30',
            'saida' => '17:30',
            'clima' => ['manha' => 'ensolarado', 'tarde' => 'nublado', 'noite' => null],
        ], $sobrescrever);
    }

    private function url(AtendimentoRelatorio $relatorio, string $data = '2026-09-29'): string
    {
        return "/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}/dias/{$data}";
    }

    // ── PUT: criação e idempotência ──────────────────────────────────────────

    public function test_put_cria_o_dia_e_devolve_no_formato_do_contrato(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)->putJson($this->url($relatorio), $this->corpo())
            ->assertOk()
            ->assertJsonPath('data.data', '2026-09-29')
            ->assertJsonPath('data.entrada', '07:42')
            ->assertJsonPath('data.inicio_intervalo', '12:00')
            ->assertJsonPath('data.fim_intervalo', '13:30')
            ->assertJsonPath('data.saida', '17:30')
            ->assertJsonPath('data.clima', ['manha' => 'ensolarado', 'tarde' => 'nublado', 'noite' => null])
            ->assertJsonStructure(['data' => ['id'], 'message']);

        $this->assertDatabaseHas('atendimentos_relatorios_dias', [
            'aten_rel_dia_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_dia_data' => '2026-09-29',
            'aten_rel_dia_hora_entrada' => '07:42:00',
            'aten_rel_dia_clima_manha' => 1,
            'aten_rel_dia_clima_tarde' => 2,
            'aten_rel_dia_clima_noite' => null,
        ]);
    }

    public function test_put_repetido_atualiza_sem_duplicar(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $id = $this->withToken($token)->putJson($this->url($relatorio), $this->corpo(['saida' => null]))
            ->assertOk()->json('data.id');

        $this->withToken($token)->putJson($this->url($relatorio), $this->corpo(['saida' => '18:00']))
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.saida', '18:00');

        $this->assertSame(1, $relatorio->dias()->count());
    }

    public function test_put_aceita_preenchimento_parcial(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)->putJson($this->url($relatorio), [
            'entrada' => '08:00',
            'inicio_intervalo' => null,
            'fim_intervalo' => null,
            'saida' => null,
            'clima' => ['manha' => null, 'tarde' => null, 'noite' => null],
        ])->assertOk()->assertJsonPath('data.saida', null);

        $this->withToken($token)->putJson($this->url($relatorio, '2026-09-28'), [
            'entrada' => null,
            'inicio_intervalo' => null,
            'fim_intervalo' => null,
            'saida' => null,
            'clima' => ['manha' => null, 'tarde' => 'chuvoso', 'noite' => null],
        ])->assertOk()->assertJsonPath('data.clima.tarde', 'chuvoso');
    }

    public function test_put_aceita_a_data_de_hoje_no_fuso_de_sao_paulo(): void
    {
        // 22:00 em São Paulo = 01:00 do dia seguinte em UTC: "hoje" tem que
        // ser o dia de São Paulo, não o de UTC.
        $this->travelTo(Carbon::parse('2026-09-30 22:00:00', 'America/Sao_Paulo'));
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)->putJson($this->url($relatorio, '2026-09-30'), $this->corpo())->assertOk();
        $this->withToken($token)->putJson($this->url($relatorio, '2026-10-01'), $this->corpo())->assertStatus(422);
    }

    // ── PUT: regras de 422 ───────────────────────────────────────────────────

    public function test_put_rejeita_hora_fora_do_formato(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        foreach (['25:99', '7:42', '07:42:00', 'abc'] as $hora) {
            $this->withToken($token)->putJson($this->url($relatorio), $this->corpo(['entrada' => $hora]))
                ->assertStatus(422)->assertJsonValidationErrors('entrada');
        }
    }

    public function test_put_rejeita_intervalo_sem_par(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)->putJson($this->url($relatorio), $this->corpo(['fim_intervalo' => null]))
            ->assertStatus(422)->assertJsonValidationErrors('fim_intervalo');

        $this->withToken($token)->putJson($this->url($relatorio), $this->corpo(['inicio_intervalo' => null]))
            ->assertStatus(422)->assertJsonValidationErrors('inicio_intervalo');
    }

    public function test_put_rejeita_ordem_violada(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $casos = [
            'saida' => ['entrada' => '18:00', 'inicio_intervalo' => null, 'fim_intervalo' => null, 'saida' => '17:00'],
            'fim_intervalo' => ['inicio_intervalo' => '13:00', 'fim_intervalo' => '12:00'],
            'inicio_intervalo' => ['entrada' => '13:00', 'inicio_intervalo' => '12:00', 'fim_intervalo' => '13:30'],
        ];
        foreach ($casos as $campo => $sobrescrever) {
            $this->withToken($token)->putJson($this->url($relatorio), $this->corpo($sobrescrever))
                ->assertStatus(422)->assertJsonValidationErrors($campo);
        }

        // fim do intervalo depois da saída
        $this->withToken($token)->putJson($this->url($relatorio), $this->corpo(['saida' => '13:00']))
            ->assertStatus(422)->assertJsonValidationErrors('fim_intervalo');

        // ordem conferida mesmo sem a entrada (só os campos presentes)
        $this->withToken($token)->putJson($this->url($relatorio), $this->corpo(['entrada' => null, 'saida' => '12:30']))
            ->assertStatus(422)->assertJsonValidationErrors('fim_intervalo');
    }

    public function test_put_rejeita_data_futura_e_data_invalida(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)->putJson($this->url($relatorio, '2026-10-01'), $this->corpo())
            ->assertStatus(422)->assertJsonValidationErrors('data');

        $this->withToken($token)->putJson($this->url($relatorio, '2026-02-30'), $this->corpo())
            ->assertStatus(422)->assertJsonValidationErrors('data');

        // formato fora do padrão nem chega no controller (restrição da rota)
        $this->withToken($token)->putJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}/dias/29-09-2026", $this->corpo())
            ->assertNotFound();
    }

    public function test_put_rejeita_dia_totalmente_vazio(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)->putJson($this->url($relatorio), [
            'entrada' => null,
            'inicio_intervalo' => null,
            'fim_intervalo' => null,
            'saida' => null,
            'clima' => ['manha' => null, 'tarde' => null, 'noite' => null],
        ])->assertStatus(422);

        $this->assertSame(0, $relatorio->dias()->count());
    }

    public function test_put_rejeita_clima_invalido(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)->putJson($this->url($relatorio), $this->corpo(['clima' => ['noite' => 'furacao']]))
            ->assertStatus(422)->assertJsonValidationErrors('clima.noite');
    }

    public function test_put_exige_corpo_completo(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $corpo = $this->corpo();
        unset($corpo['saida']);
        $this->withToken($token)->putJson($this->url($relatorio), $corpo)
            ->assertStatus(422)->assertJsonValidationErrors('saida');

        $corpo = $this->corpo();
        unset($corpo['clima']['tarde']);
        $this->withToken($token)->putJson($this->url($relatorio), $corpo)
            ->assertStatus(422)->assertJsonValidationErrors('clima.tarde');
    }

    // ── 409 / 403 ────────────────────────────────────────────────────────────

    public function test_put_responde_409_em_relatorio_no_formato_antigo(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();
        AtendimentoRelatorioHorario::create([
            'aten_rel_hora_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_hora_entrada' => '08:00',
            'aten_rel_hora_saida' => '17:00',
        ]);

        $this->withToken($token)->putJson($this->url($relatorio), $this->corpo())->assertStatus(409);
        $this->assertSame(0, $relatorio->dias()->count());
    }

    public function test_tecnico_nao_altera_dias_de_relatorio_de_outro_tecnico(): void
    {
        [, $relatorio] = $this->relatorioDoTecnico();
        $intruso = Usuario::factory()->tecnico()->create();
        $token = $intruso->createToken('test')->plainTextToken;

        $this->withToken($token)->putJson($this->url($relatorio), $this->corpo())->assertForbidden();
        $this->withToken($token)->deleteJson($this->url($relatorio))->assertForbidden();
        $this->assertSame(0, $relatorio->dias()->count());
    }

    // ── DELETE ───────────────────────────────────────────────────────────────

    public function test_delete_remove_o_dia_e_e_idempotente(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();
        $this->withToken($token)->putJson($this->url($relatorio), $this->corpo())->assertOk();
        $this->withToken($token)->putJson($this->url($relatorio, '2026-09-28'), $this->corpo())->assertOk();

        $this->withToken($token)->deleteJson($this->url($relatorio))->assertOk()->assertJsonStructure(['message']);
        $this->withToken($token)->deleteJson($this->url($relatorio))->assertOk();

        $this->assertSame(['2026-09-28'], $relatorio->dias()->pluck('aten_rel_dia_data')->map->format('Y-m-d')->all());
    }

    // ── GET /relatorios/{id}: dias e horarios_legado ─────────────────────────

    public function test_show_sem_nenhum_horario(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)->getJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}")
            ->assertOk()
            ->assertJsonPath('data.dias', [])
            ->assertJsonPath('data.horarios_legado', false);
    }

    public function test_show_so_com_formato_antigo(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();
        AtendimentoRelatorioCondicaoClimatica::create([
            'aten_rel_clima_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_clima_periodo' => 1,
            'aten_rel_clima_condicao' => 2,
        ]);

        $this->withToken($token)->getJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}")
            ->assertOk()
            ->assertJsonPath('data.dias', [])
            ->assertJsonPath('data.horarios_legado', true)
            // chave antiga continua igual
            ->assertJsonPath('data.clima.manha', 'nublado');
    }

    public function test_show_com_dias_ordenados_por_data(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();
        $this->withToken($token)->putJson($this->url($relatorio, '2026-09-29'), $this->corpo())->assertOk();
        $this->withToken($token)->putJson($this->url($relatorio, '2026-09-27'), $this->corpo(['saida' => null]))->assertOk();

        $response = $this->withToken($token)->getJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}")
            ->assertOk()
            ->assertJsonPath('data.horarios_legado', false)
            ->assertJsonCount(2, 'data.dias');

        $this->assertSame(['2026-09-27', '2026-09-29'], array_column($response->json('data.dias'), 'data'));
        $this->assertSame(
            ['id', 'data', 'entrada', 'inicio_intervalo', 'fim_intervalo', 'saida', 'clima'],
            array_keys($response->json('data.dias.0'))
        );
    }
}
