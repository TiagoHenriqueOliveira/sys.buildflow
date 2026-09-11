<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassificacaoClienteRequest extends FormRequest
{
    public function rules()
    {
        $id = $this->route('classificacoes_cliente');

        return [
            'cla_cli_nome' => [
                'required',
                'string',
                'max:50',
                Rule::unique('classificacoes_cliente', 'cla_cli_nome')->ignore($id, 'cla_cli_id'),
            ],
            'cla_cli_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'cla_cli_nome.required' => 'O nome da classificação é obrigatório.',
            'cla_cli_nome.max' => 'O nome deve ter no máximo 50 caracteres.',
            'cla_cli_nome.unique' => 'Já existe uma classificação com esse nome.',
        ];
    }
}