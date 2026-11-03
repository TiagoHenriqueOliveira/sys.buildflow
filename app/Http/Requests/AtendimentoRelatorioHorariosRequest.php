<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaHorariosPorDia;
use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRelatorioHorariosRequest extends FormRequest
{
    use ValidaHorariosPorDia;

    public function authorize(): bool
    {
        return true;
    }

    protected function horariosObrigatorios(): bool
    {
        return true;
    }

    protected function camposLegados(): array
    {
        return [
            'entrada'          => 'aten_rel_hora_entrada',
            'inicio_intervalo' => 'aten_rel_hora_inicio_intervalo',
            'fim_intervalo'    => 'aten_rel_hora_fim_intervalo',
            'saida'            => 'aten_rel_hora_saida',
        ];
    }
}
