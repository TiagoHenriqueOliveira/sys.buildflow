<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRelatorioStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'aten_id'       => ['required', 'integer', 'exists:atendimentos,aten_id'],
            'aten_rel_data' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'aten_id.required' => 'Informe o atendimento.',
            'aten_id.exists'   => 'Atendimento não encontrado.',
            'aten_rel_data.before_or_equal' => 'A data não pode ser futura.',
        ];
    }
}
