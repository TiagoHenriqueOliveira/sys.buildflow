<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioCondicaoClimatica;
use App\Models\AtendimentoRelatorioHorario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// HTML do PDF do cliente (view renderizada para string, sem passar pelo
// dompdf): RF012 — seção "Horários e Clima" com uma linha por dia; Bloco E —
// Observações Técnicas e de Manutenção não saem, a observação do cliente sai.
class RelatorioPdfHtmlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-30 12:00:00', 'America/Sao_Paulo'));
    }

    private function relatorio(array $atendimento = [], array $relatorio = []): AtendimentoRelatorio
    {
        return AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create($atendimento), 'atendimento')
            ->create($relatorio);
    }

    private function html(AtendimentoRelatorio $relatorio): string
    {
        $relatorio = $relatorio->fresh();
        $prazo = $relatorio->calcularPrazo();

        return view('atendimentos-relatorios.pdf', [
            'relatorio'      => $relatorio,
            'prazoTotal'     => $prazo['prazo_total'],
            'prazoDecorrido' => $prazo['prazo_decorrido'],
            'prazoAVencer'   => $prazo['prazo_a_vencer'],
        ])->render();
    }

    public function test_sai_a_observacao_do_cliente_e_nao_saem_as_observacoes_internas(): void
    {
        $relatorio = $this->relatorio([
            'aten_obs_cliente'    => 'Cliente pediu retorno na semana que vem',
            'aten_obs_tecnica'    => 'Nota interna do tecnico XYZ',
            'aten_obs_manutencao' => 'Nota interna de manutencao XYZ',
        ]);

        $html = $this->html($relatorio);

        $this->assertStringContainsString('Observações</div>', $html);
        $this->assertStringContainsString('Cliente pediu retorno na semana que vem', $html);
        $this->assertStringNotContainsString('Observações Técnicas', $html);
        $this->assertStringNotContainsString('Observações de Manutenção', $html);
        $this->assertStringNotContainsString('Nota interna do tecnico XYZ', $html);
        $this->assertStringNotContainsString('Nota interna de manutencao XYZ', $html);
    }

    public function test_horarios_e_clima_tem_uma_linha_por_dia_em_ordem_de_data(): void
    {
        $relatorio = $this->relatorio();
        $relatorio->dias()->create([
            'aten_rel_dia_data'         => '2026-09-29',
            'aten_rel_dia_hora_entrada' => '07:42:00',
            'aten_rel_dia_hora_saida'   => '17:30:00',
            'aten_rel_dia_clima_tarde'  => 3,
        ]);
        $relatorio->dias()->create([
            'aten_rel_dia_data'                  => '2026-09-28',
            'aten_rel_dia_hora_entrada'          => '08:00:00',
            'aten_rel_dia_hora_inicio_intervalo' => '12:00:00',
            'aten_rel_dia_hora_fim_intervalo'    => '13:30:00',
            'aten_rel_dia_hora_saida'            => '18:00:00',
            'aten_rel_dia_clima_manha'           => 1,
            'aten_rel_dia_clima_noite'           => 1,
        ]);

        $html = $this->html($relatorio);

        $this->assertStringContainsString('Horários e Clima', $html);
        $this->assertSame(2, substr_count($html, 'Manhã: '));
        $this->assertStringContainsString('28/09/2026 - segunda-feira', $html);
        $this->assertStringContainsString('29/09/2026 - terça-feira', $html);
        $this->assertLessThan(strpos($html, '29/09/2026'), strpos($html, '28/09/2026'));
        $this->assertStringContainsString('Manhã: Ensolarado · Tarde: - · Noite: Céu limpo', $html);
        $this->assertStringContainsString('Manhã: - · Tarde: Chuvoso · Noite: -', $html);
        foreach (['07:42', '17:30', '08:00', '12:00', '13:30', '18:00'] as $hora) {
            $this->assertStringContainsString(">{$hora}</td>", $html);
        }
    }

    public function test_relatorio_no_formato_antigo_vira_uma_linha_com_a_data_do_relatorio(): void
    {
        $relatorio = $this->relatorio([], ['aten_rel_data' => '2026-09-15']);
        AtendimentoRelatorioHorario::create([
            'aten_rel_hora_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_hora_entrada'      => '08:00:00',
            'aten_rel_hora_saida'        => '17:00:00',
        ]);
        AtendimentoRelatorioCondicaoClimatica::create([
            'aten_rel_clima_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_clima_periodo'      => 2,
            'aten_rel_clima_condicao'     => 2,
        ]);

        $html = $this->html($relatorio);

        $this->assertStringContainsString('Horários e Clima', $html);
        $this->assertSame(1, substr_count($html, 'Manhã: '));
        $this->assertStringContainsString('15/09/2026 - terça-feira', $html);
        $this->assertStringContainsString('Manhã: - · Tarde: Nublado · Noite: -', $html);
    }

    public function test_secao_some_sem_dias_nem_formato_antigo(): void
    {
        $html = $this->html($this->relatorio());

        $this->assertStringNotContainsString('Horários e Clima', $html);
    }
}
