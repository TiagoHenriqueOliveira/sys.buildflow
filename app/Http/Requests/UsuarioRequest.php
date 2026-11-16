<?php

namespace App\Http\Requests;

use App\Enums\NivelAcesso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsuarioRequest extends FormRequest
{
    public function rules()
    {
        $isCreate = $this->isMethod('post');
        $id = $this->route('usuario') ?? $this->route('usuarios') ?? null;

        return [
            // Só quem tem acesso total (Administrador/usuário protegido)
            // concede o perfil Administrador — quem só tem "usuarios.editar"
            // não pode promover ninguém (nem a si mesmo) a acesso total.
            'user_nivel_acesso' => ['required', 'integer', Rule::in(self::niveisPermitidos())],
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

    /** Níveis que o usuário logado pode atribuir. */
    public static function niveisPermitidos(): array
    {
        $niveis = array_column(NivelAcesso::cases(), 'value');

        if (auth()->user()?->temAcessoTotal()) {
            return $niveis;
        }

        return array_values(array_diff($niveis, [NivelAcesso::Administrador->value]));
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
