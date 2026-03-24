<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NaturezaAtendimentoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'nat_aten_descricao' => ['required', 'string', 'max:50'],
            'nat_aten_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'nat_aten_descricao.required' => 'A descrição é obrigatória.',
            'nat_aten_descricao.max' => 'A descrição deve ter no máximo 50 caracteres.',
        ];
    }
}
