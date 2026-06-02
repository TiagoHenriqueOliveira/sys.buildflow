<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRelatorioAtividadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aten_rel_ativ_descricao' => ['required', 'string', 'max:255'],
            'aten_rel_ativ_status'    => ['required', 'integer', 'in:0,1,2,3,4,5'],
        ];
    }

    public function messages(): array
    {
        return [
            'aten_rel_ativ_descricao.required' => 'Informe a descrição da atividade.',
            'aten_rel_ativ_descricao.max'      => 'A descrição deve ter no máximo 255 caracteres.',
            'aten_rel_ativ_status.required'    => 'Informe o status da atividade.',
            'aten_rel_ativ_status.in'          => 'Status inválido.',
        ];
    }
}
