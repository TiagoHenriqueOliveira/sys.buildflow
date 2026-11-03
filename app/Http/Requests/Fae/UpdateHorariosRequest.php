<?php

namespace App\Http\Requests\Fae;

use App\Http\Requests\Concerns\ValidaHorariosPorDia;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHorariosRequest extends FormRequest
{
    use ValidaHorariosPorDia;

    public function authorize(): bool
    {
        return true;
    }

    protected function horariosObrigatorios(): bool
    {
        return false;
    }

    protected function camposLegados(): array
    {
        return [
            'entrada'          => 'entrada',
            'inicio_intervalo' => 'inicio_intervalo',
            'fim_intervalo'    => 'fim_intervalo',
            'saida'            => 'saida',
        ];
    }
}
