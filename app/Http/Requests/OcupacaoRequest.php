<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OcupacaoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'ocup_tp_ocupacao_id' => [
                'required',
                'integer',
                Rule::exists('tipos_ocupacoes', 'tp_ocup_id')
                    ->where(fn($q) => $q->where('tp_ocup_ativo', 1)),
            ],
            'ocup_descricao' => ['required', 'string', 'max:50'],
            'ocup_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'ocup_tp_ocupacao_id.required' => 'Selecione o tipo de ocupação.',
            'ocup_tp_ocupacao_id.exists'   => 'O tipo selecionado é inválido ou está inativo.',
            'ocup_descricao.required'      => 'A descrição é obrigatória.',
            'ocup_descricao.max'           => 'A descrição deve ter no máximo 50 caracteres.',
        ];
    }
}
