<?php

namespace App\Enums;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

enum NivelOrcamento: int
{
    case Simples = 0;
    case Medio = 1;
    case Complexo = 2;

    public function label(): string
    {
        return match ($this) {
            self::Simples => 'Simples',
            self::Medio => 'Médio',
            self::Complexo => 'Complexo',
        };
    }

    /**
     * CRM02 - prazo de envio em dias uteis (pedido do cliente, 2026-10-20).
     */
    public function diasUteisPrazo(): int
    {
        return match ($this) {
            self::Simples => 1,
            self::Medio => 5,
            self::Complexo => 10,
        };
    }

    /**
     * Soma diasUteisPrazo() a data de inclusao do orcamento contando so
     * segunda a sexta - feriados NAO sao descontados (decisao do cliente).
     * A tela replica esta mesma regra em JS (orcamentos/form.blade.php,
     * recalcularPrazoOrcamento) pra mostrar o prazo antes de salvar: manter
     * as duas iguais.
     */
    public function calcularPrazo(CarbonInterface $dataInclusao): Carbon
    {
        $data = Carbon::parse($dataInclusao->toDateString());
        $restantes = $this->diasUteisPrazo();

        while ($restantes > 0) {
            $data->addDay();
            if (! $data->isWeekend()) {
                $restantes--;
            }
        }

        return $data;
    }
}