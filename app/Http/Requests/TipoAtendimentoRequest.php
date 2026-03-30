<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TipoAtendimentoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'tp_aten_descricao' => ['required', 'string', 'max:30'],
            'tp_aten_ativo'     => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'tp_aten_descricao.required' => 'A descrição é obrigatória.',
            'tp_aten_descricao.max'      => 'A descrição deve ter no máximo 30 caracteres.',
        ];
    }
}
