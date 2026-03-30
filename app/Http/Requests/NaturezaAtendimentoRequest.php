<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NaturezaAtendimentoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'nat_aten_descricao' => ['required', 'string', 'max:50'],

            'nat_aten_mod_relatorio_id' => [
                'required',
                'integer',
                Rule::exists('modelos_relatorios', 'mod_rel_id')
            ],

            'nat_aten_tp_atendimento_id' => [
                'required',
                'integer',
                Rule::exists('tipos_atendimentos', 'tp_aten_id')
                    ->where(fn($q) => $q->where('tp_aten_ativo', 1)),
            ],

            'nat_aten_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'nat_aten_descricao.required' => 'A descrição é obrigatória.',
            'nat_aten_descricao.max'      => 'A descrição deve ter no máximo 50 caracteres.',

            'nat_aten_mod_relatorio_id.required' => 'Selecione um modelo de relatório.',
            'nat_aten_mod_relatorio_id.exists'   => 'O modelo de relatório selecionado é inválido.',

            'nat_aten_tp_atendimento_id.required' => 'Selecione um tipo de atendimento.',
            'nat_aten_tp_atendimento_id.exists'   => 'O tipo selecionado é inválido ou está inativo.',
        ];
    }
}
