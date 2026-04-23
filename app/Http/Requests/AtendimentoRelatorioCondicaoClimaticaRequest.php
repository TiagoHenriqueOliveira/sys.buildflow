<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRelatorioCondicaoClimaticaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clima_manha' => ['required', 'in:ensolarado,nublado,chuvoso'],
            'clima_tarde' => ['required', 'in:ensolarado,nublado,chuvoso'],
            'clima_noite' => ['required', 'in:ensolarado,nublado,chuvoso'],
        ];
    }

    public function messages(): array
    {
        return [
            'clima_manha.required' => 'Informe a condição climática da manhã.',
            'clima_tarde.required' => 'Informe a condição climática da tarde.',
            'clima_noite.required' => 'Informe a condição climática da noite.',

            'clima_manha.in' => 'Condição inválida para manhã.',
            'clima_tarde.in' => 'Condição inválida para tarde.',
            'clima_noite.in' => 'Condição inválida para noite.',
        ];
    }
}
