<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TipoOcupacaoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'tp_ocup_descricao' => ['required', 'string', 'max:50'],
            'tp_ocup_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'tp_ocup_descricao.required' => 'A descrição é obrigatória.',
            'tp_ocup_descricao.max' => 'A descrição deve ter no máximo 50 caracteres.',
        ];
    }
}
