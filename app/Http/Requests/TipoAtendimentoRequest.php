<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoAtendimentoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'tp_aten_nat_atendimento_id' => [
                'required',
                'integer',
                Rule::exists('naturezas_atendimentos', 'nat_aten_id')
                    ->where(fn($q) => $q->where('nat_aten_ativo', 1)),
            ],
            'tp_aten_descricao' => ['required', 'string', 'max:30'],
            'tp_aten_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'tp_aten_nat_atendimento_id.required' => 'Selecione a natureza de atendimento.',
            'tp_aten_nat_atendimento_id.exists'   => 'A natureza selecionada é inválida ou está inativa.',
            'tp_aten_descricao.required'          => 'A descrição é obrigatória.',
            'tp_aten_descricao.max'               => 'A descrição deve ter no máximo 30 caracteres.',
        ];
    }
}
