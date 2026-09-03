<?php

namespace App\Http\Requests\Mcl;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHorariosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entrada'          => ['nullable', 'date_format:H:i'],
            'inicio_intervalo' => ['nullable', 'date_format:H:i'],
            'fim_intervalo'    => ['nullable', 'date_format:H:i'],
            'saida'            => ['nullable', 'date_format:H:i'],
        ];
    }
}
