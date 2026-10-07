<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModeloRelatorioRequest extends FormRequest
{
    public function rules()
    {
        return [
            'mod_rel_descricao'              => ['required', 'string', 'max:50'],
            'mod_rel_tp_data'                => ['required', 'integer', 'in:0,1'],
            'mod_rel_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'mod_rel_descricao.required' => 'A descrição é obrigatória.',
            'mod_rel_descricao.max' => 'A descrição deve ter no máximo 50 caracteres.',
            'mod_rel_tp_data.required' => 'Selecione o tipo de data.',
            'mod_rel_tp_data.in' => 'Tipo de data inválido.',
        ];
    }
}
