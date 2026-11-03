<?php

namespace Tests\Feature;

use App\Enums\SetorModelo;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioHorario;
use App\Models\Cliente;
use App\Models\ConfigModelo;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use App\Rules\EnderecoSemLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Pedido do cliente (2026-11-03): horários por dia no relatório (um
 * relatório pode cobrir o período todo do atendimento) e endereço do
 * atendimento sem link do Google Maps.
 */
class AtendimentoRelatorioHorariosFaeTest extends TestCase
{
    use RefreshDatabase;

    private function criarRelatorio(Usuario $tecnico, string $data = '2026-10-05'): AtendimentoRelatorio
    {
        // A tela e o PDF exigem um modelo (abort 500 sem nenhum).
        $modelo = ConfigModelo::create([
            'cfg_mod_nome' => 'Manutenção Preventiva ETE',
            'cfg_mod_setor' => SetorModelo::Assistencia->value,
            'cfg_mod_ativo' => 1,
        ]);
        $natureza = NaturezaAtendimento::factory()->create([
            'nat_aten_mod_relatorio_id' => null,
            'nat_aten_config_modelo_id' => $modelo->cfg_mod_id,
        ]);
        $atendimento = Atendimento::factory()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $tecnico->user_id,
        ]);

        return AtendimentoRelatorio::create([
            'aten_rel_atendimento_id' => $atendimento->aten_id,
            'aten_rel_modelo_relatorio_id' => null,
            'aten_rel_config_modelo_id' => $modelo->cfg_mod_id,
            'aten_rel_data' => $data,
            'aten_rel_status' => 0,
        ]);
    }

    private function dia(string $data, string $entrada = '08:00', string $saida = '17:00', ?string $ini = '12:00', ?string $fim = '13:00'): array
    {
        return ['data' => $data, 'entrada' => $entrada, 'inicio_intervalo' => $ini, 'fim_intervalo' => $fim, 'saida' => $saida];
    }

    // ─── Web ─────────────────────────────────────────────────────────────

    public function test_web_grava_varios_dias_e_substitui_os_anteriores(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico);
        $rota = route('atendimentos-relatorios.update-horarios', $relatorio->aten_rel_id);

        $this->actingAs($tecnico)->postJson($rota, ['dias' => [
            $this->dia('2026-10-05'),
            $this->dia('2026-10-06', '07:30', '16:00'),
            $this->dia('2026-10-07', '08:00', '12:00', null, null),
        ]])->assertOk();

        $this->assertSame(3, AtendimentoRelatorioHorario::where('aten_rel_hora_relatorio_id', $relatorio->aten_rel_id)->count());
        $this->assertDatabaseHas('atendimentos_relatorios_horarios', [
            'aten_rel_hora_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_hora_data' => '2026-10-06',
            'aten_rel_hora_entrada' => '07:30:00',
        ]);

        // Reenvio com um dia só remove os outros.
        $this->actingAs($tecnico)->postJson($rota, ['dias' => [$this->dia('2026-10-06')]])->assertOk();
        $this->assertSame(1, AtendimentoRelatorioHorario::where('aten_rel_hora_relatorio_id', $relatorio->aten_rel_id)->count());

        $this->actingAs($tecnico)->getJson(route('atendimentos-relatorios.get-horarios', $relatorio->aten_rel_id))
            ->assertOk()
            ->assertJsonPath('dias.0.data', '2026-10-06')
            ->assertJsonPath('dias.0.dia_semana', 'Terça-feira')
            ->assertJsonPath('dias.0.entrada', '08:00');
    }

    public function test_web_rejeita_dia_repetido(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico);

        $this->actingAs($tecnico)->postJson(route('atendimentos-relatorios.update-horarios', $relatorio->aten_rel_id), ['dias' => [
            $this->dia('2026-10-05'),
            $this->dia('2026-10-05'),
        ]])->assertStatus(422)->assertJsonValidationErrors('dias.0.data');
    }

    public function test_web_rejeita_saida_antes_da_entrada_apontando_o_dia(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico);

        $this->actingAs($tecnico)->postJson(route('atendimentos-relatorios.update-horarios', $relatorio->aten_rel_id), ['dias' => [
            $this->dia('2026-10-05'),
            $this->dia('2026-10-06', '17:00', '08:00', null, null),
        ]])->assertStatus(422)->assertJsonValidationErrors('dias.1.saida');
    }

    public function test_web_rejeita_data_futura(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico);

        $this->actingAs($tecnico)->postJson(route('atendimentos-relatorios.update-horarios', $relatorio->aten_rel_id), ['dias' => [
            $this->dia(now()->addDay()->toDateString()),
        ]])->assertStatus(422)->assertJsonValidationErrors('dias.0.data');
    }

    public function test_web_formato_antigo_vira_dia_da_data_do_relatorio(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico, '2026-10-02');

        $this->actingAs($tecnico)->postJson(route('atendimentos-relatorios.update-horarios', $relatorio->aten_rel_id), [
            'aten_rel_hora_entrada' => '08:00',
            'aten_rel_hora_saida' => '17:00',
        ])->assertOk();

        $this->assertDatabaseHas('atendimentos_relatorios_horarios', [
            'aten_rel_hora_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_hora_data' => '2026-10-02',
        ]);
    }

    public function test_pdf_lista_uma_linha_por_dia(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico);
        $relatorio->sincronizarHorarios([$this->dia('2026-10-05'), $this->dia('2026-10-06')]);

        $html = view('atendimentos-relatorios.pdf', $this->dadosPdf($relatorio))->render();

        $this->assertStringContainsString('05/10/2026', $html);
        $this->assertStringContainsString('06/10/2026', $html);
        $this->assertStringContainsString('Segunda-feira', $html);
    }

    // ─── API mobile (fae/v1) ─────────────────────────────────────────────

    public function test_api_grava_dias_e_detalhe_devolve_horarios_dias(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico);
        $token = $tecnico->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/horarios", ['dias' => [
                $this->dia('2026-10-05'),
                $this->dia('2026-10-06'),
            ]])
            ->assertOk()
            ->assertJsonCount(2, 'horarios_dias');

        $this->withToken($token)
            ->getJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.horarios_dias')
            ->assertJsonPath('data.horarios_dias.1.data', '2026-10-06')
            // objeto antigo continua vindo (1º dia) pro app já publicado
            ->assertJsonPath('data.horarios.entrada', '08:00:00');
    }

    public function test_api_formato_antigo_continua_funcionando(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico, '2026-10-03');

        $this->withToken($tecnico->createToken('test')->plainTextToken)
            ->putJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/horarios", [
                'entrada' => '08:00',
                'inicio_intervalo' => '12:00',
                'fim_intervalo' => '13:00',
                'saida' => '18:00',
            ])
            ->assertOk();

        $this->assertDatabaseHas('atendimentos_relatorios_horarios', [
            'aten_rel_hora_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_hora_data' => '2026-10-03',
            'aten_rel_hora_saida' => '18:00:00',
        ]);
    }

    // ─── Endereço sem link ───────────────────────────────────────────────

    public function test_regra_endereco_rejeita_links_e_aceita_endereco_comum(): void
    {
        $valida = fn (string $valor) => Validator::make(['e' => $valor], ['e' => [new EnderecoSemLink()]])->passes();

        $this->assertFalse($valida('https://maps.app.goo.gl/AbC123'));
        $this->assertFalse($valida('www.google.com/maps/place/Cascavel'));
        $this->assertFalse($valida('maps.google.com/?q=-24.95,-53.45'));
        $this->assertFalse($valida('Ver goo.gl/maps/xyz'));
        $this->assertTrue($valida('Rua das Flores, 123 - Centro'));
        $this->assertTrue($valida('Rod. BR-277, km 590, Zona Rural'));
    }

    public function test_cadastro_de_atendimento_rejeita_link_do_maps_no_endereco(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $tecnico = Usuario::factory()->tecnico()->create();
        $natureza = NaturezaAtendimento::factory()->create(['nat_aten_mod_relatorio_id' => null]);
        $cliente = Cliente::factory()->create();

        $this->actingAs($admin)->postJson(route('atendimentos.store'), [
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_cliente_id' => $cliente->cli_id,
            'aten_usuario_id' => $tecnico->user_id,
            'aten_status' => 0,
            'aten_endereco' => 'https://maps.app.goo.gl/AbC123',
            'aten_dt_inicio' => '2026-10-01',
            'aten_dt_fim' => '2026-10-03',
        ])->assertStatus(422)->assertJsonValidationErrors('aten_endereco');
    }

    private function dadosPdf(AtendimentoRelatorio $relatorio): array
    {
        // Mesmas relações de AtendimentosRelatoriosController::pdf().
        $relatorio->load(['modeloRelatorio', 'configModelo.perguntas.opcoes', 'respostas.fotos', 'atendimento.cliente', 'atendimento.natureza', 'atendimento.usuario', 'atendimento.equipamentos', 'horariosDias', 'climas', 'ocorrencias', 'servicos', 'pecas', 'fotos', 'assinaturas', 'itensDescricao.fotos']);
        $prazo = $relatorio->calcularPrazo();

        return [
            'relatorio' => $relatorio,
            'prazoTotal' => $prazo['prazo_total'],
            'prazoDecorrido' => $prazo['prazo_decorrido'],
            'prazoAVencer' => $prazo['prazo_a_vencer'],
        ];
    }
}
