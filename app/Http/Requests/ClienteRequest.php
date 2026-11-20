<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $cnpj    = $this->input('cli_cnpj');
        $tel     = $this->input('cli_telefone');
        $uf      = $this->input('cli_uf');
        $unidade = $this->input('cli_unidade');

        $this->merge([
            'cli_cnpj' => $cnpj !== null ? preg_replace('/\D+/', '', $cnpj) : $cnpj,
            'cli_telefone' => $tel !== null ? preg_replace('/\D+/', '', $tel) : $tel,

            'cli_uf' => $uf !== null ? strtoupper(trim($uf)) : $uf,

            // Sem unidade = '' (não null): é assim que fica no banco, e o
            // índice único (CNPJ, unidade) só barra repetição com ''.
            'cli_unidade' => is_string($unidade) ? preg_replace('/\s+/u', ' ', trim($unidade)) : '',
        ]);
    }

    public function rules()
    {
        $id = $this->route('cliente');

        return [
            'cli_nome' => ['required', 'string', 'max:100'],
            'cli_unidade' => ['nullable', 'string', 'max:100'],

            // O mesmo CNPJ pode repetir com unidade diferente (cada unidade
            // da empresa é um cliente). A comparação usa a collation do
            // banco: "Itaquiraí" e "ITAQUIRAI" contam como a mesma unidade.
            'cli_cnpj' => [
                'required',
                'digits:14',
                Rule::unique('clientes', 'cli_cnpj')
                    ->where('cli_unidade', $this->input('cli_unidade', ''))
                    ->ignore($id, 'cli_id'),
            ],

            'cli_cidade' => ['required', 'string', 'max:100'],
            'cli_uf' => ['required', 'string', 'size:2'],
            'cli_telefone' => ['nullable', 'digits_between:10,11'],
            'cli_email' => ['nullable', 'email', 'max:100'],
            'cli_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'cli_nome.required' => 'O nome do cliente é obrigatório.',
            'cli_cnpj.required' => 'O CNPJ é obrigatório.',
            'cli_cnpj.digits' => 'O CNPJ deve conter 14 números.',
            'cli_cnpj.unique' => $this->input('cli_unidade', '') === ''
                ? 'Este CNPJ já está cadastrado. Para cadastrar outra unidade da mesma empresa, preencha o campo Unidade.'
                : 'Já existe um cliente com este CNPJ e esta unidade.',
            'cli_unidade.max' => 'A unidade deve ter no máximo 100 caracteres.',
            'cli_cidade.required' => 'A cidade é obrigatória.',
            'cli_uf.required' => 'A UF é obrigatória.',
            'cli_uf.size' => 'A UF deve conter 2 caracteres.',
            'cli_telefone.digits_between' => 'Informe um telefone com DDD e 10 ou 11 números.',
            'cli_email.email' => 'Informe um e-mail válido.',
        ];
    }
}
