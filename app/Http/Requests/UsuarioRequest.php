<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsuarioRequest extends FormRequest
{
    public function rules()
    {
        $isCreate = $this->isMethod('post');
        $id = $this->route('usuario') ?? $this->route('usuarios') ?? null;

        return [
            'user_nivel_acesso' => ['required', 'integer', Rule::in([0, 1])],
            'user_nome' => ['required', 'string', 'max:50'],
            'user_email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('usuarios', 'user_email')->ignore($id, 'user_id'),
            ],

            'user_senha' => [
                $isCreate ? 'required' : 'nullable',
                'string',
                'min:6',
                'max:50',
                'confirmed',
            ],
            'user_senha_confirmation' => [
                $isCreate ? 'required' : 'nullable',
                'string',
                'min:6',
                'max:50',
            ],

            'user_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'user_nivel_acesso.required' => 'Selecione o nível de acesso.',
            'user_nivel_acesso.in' => 'Nível de acesso inválido.',
            'user_nome.required' => 'O nome é obrigatório.',
            'user_email.required' => 'O e-mail é obrigatório.',
            'user_email.email' => 'Informe um e-mail válido.',
            'user_email.unique' => 'Este e-mail já está cadastrado.',
            'user_senha.required' => 'A senha é obrigatória no cadastro.',
            'user_senha.min' => 'A senha deve ter no mínimo :min caracteres.',
            'user_senha.confirmed' => 'A confirmação da senha não confere.',
            'user_senha_confirmation.required' => 'A confirmação da senha é obrigatória.',
            'user_senha_confirmation.min' => 'A confirmação da senha deve ter no mínimo :min caracteres.',
        ];
    }
}
