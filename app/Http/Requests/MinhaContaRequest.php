<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MinhaContaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_nome' => ['required', 'string', 'max:50'],
            'user_email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('usuarios', 'user_email')->ignore($this->user()->user_id, 'user_id'),
            ],
            'user_senha' => ['nullable', 'string', 'min:6', 'max:50', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_nome.required' => 'O nome é obrigatório.',
            'user_email.required' => 'O e-mail é obrigatório.',
            'user_email.email' => 'Informe um e-mail válido.',
            'user_email.unique' => 'Este e-mail já está cadastrado.',
            'user_senha.min' => 'A senha deve ter no mínimo :min caracteres.',
            'user_senha.confirmed' => 'A confirmação da senha não confere.',
        ];
    }
}
