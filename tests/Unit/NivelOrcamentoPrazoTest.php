<?php

namespace Tests\Unit;

use App\Enums\NivelOrcamento;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * CRM02 (pedido do cliente, 2026-10-20) - prazo em dias uteis (seg-sex,
 * feriados nao descontados) a partir da data de inclusao. A mesma regra
 * existe em JS em orcamentos/form.blade.php - se um caso aqui mudar, a tela
 * tem que mudar junto.
 */
class NivelOrcamentoPrazoTest extends TestCase
{
    public static function casos(): array
    {
        return [
            'simples, sexta -> segunda' => [NivelOrcamento::Simples, '2026-10-16', '2026-10-19'],
            'simples, sabado -> segunda' => [NivelOrcamento::Simples, '2026-10-17', '2026-10-19'],
            'simples, domingo -> segunda' => [NivelOrcamento::Simples, '2026-10-18', '2026-10-19'],
            'simples, segunda -> terca' => [NivelOrcamento::Simples, '2026-10-19', '2026-10-20'],
            'medio, quarta -> quarta seguinte' => [NivelOrcamento::Medio, '2026-10-14', '2026-10-21'],
            'medio, sabado -> sexta seguinte' => [NivelOrcamento::Medio, '2026-10-17', '2026-10-23'],
            'complexo, sexta -> sexta +2 semanas' => [NivelOrcamento::Complexo, '2026-10-16', '2026-10-30'],
        ];
    }

    #[DataProvider('casos')]
    public function test_calcula_prazo_em_dias_uteis(NivelOrcamento $nivel, string $inclusao, string $esperado): void
    {
        $this->assertSame($esperado, $nivel->calcularPrazo(Carbon::parse($inclusao.' 14:30'))->toDateString());
    }

    public function test_hora_da_inclusao_nao_altera_o_dia_do_prazo(): void
    {
        $this->assertSame(
            NivelOrcamento::Medio->calcularPrazo(Carbon::parse('2026-10-14 00:01'))->toDateString(),
            NivelOrcamento::Medio->calcularPrazo(Carbon::parse('2026-10-14 23:59'))->toDateString()
        );
    }
}