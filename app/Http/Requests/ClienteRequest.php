<?php

namespace App\Http\Requests;

use App\Enums\TipoContatoCliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $cnpj = $this->input('cli_cnpj');
        $tel  = $this->input('cli_telefone');
        $uf   = $this->input('cli_uf');

        $contatos = collect($this->input('contatos', []))
            ->map(fn ($c) => [
                ...$c,
                'telefone' => isset($c['telefone']) ? preg_replace('/\D+/', '', (string) $c['telefone']) : null,
            ])
            ->all();

        $this->merge([
            'cli_cnpj' => $cnpj !== null ? preg_replace('/\D+/', '', $cnpj) : $cnpj,
            'cli_telefone' => $tel !== null ? preg_replace('/\D+/', '', $tel) : $tel,
            'cli_uf' => $uf !== null ? strtoupper(trim($uf)) : $uf,
            'contatos' => $contatos,
        ]);
    }

    public function rules()
    {
        $id = $this->route('cliente');

        return [
            'cli_nome' => ['required', 'string', 'max:100'],
            'cli_contato_principal' => ['nullable', 'string', 'max:100'],
            'cli_vendedor_id' => ['nullable', 'integer', Rule::exists('usuarios', 'user_id')],

            'cli_cnpj' => [
                'required',
                'digits:14',
                Rule::unique('clientes', 'cli_cnpj')->ignore($id, 'cli_id'),
            ],
            'cli_inscricao_estadual' => ['nullable', 'string', 'max:20'],

            'cli_cidade' => ['required', 'string', 'max:100'],
            'cli_uf' => ['required', 'string', 'size:2'],
            'cli_segmento' => ['nullable', 'string', 'max:255'],
            'cli_equipamento_vendido' => ['nullable', 'string', 'max:255'],
            'cli_caso_sucesso' => ['nullable', 'boolean'],
            'cli_caso_sucesso_descricao' => ['nullable', 'string'],
            'cli_classificacao_id' => ['nullable', 'integer', Rule::exists('classificacoes_cliente', 'cla_cli_id')],
            'cli_dias_alerta_recontato' => ['nullable', 'integer', 'min:1'],

            'cli_telefone' => ['nullable', 'digits_between:10,11'],
            'cli_email' => ['nullable', 'email', 'max:100'],
            'cli_ativo' => ['nullable', 'boolean'],

            'cli_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'cli_longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'contatos' => ['nullable', 'array'],
            'contatos.*.nome' => ['required_with:contatos.*', 'string', 'max:100'],
            'contatos.*.cargo' => ['nullable', 'string', 'max:100'],
            'contatos.*.telefone' => ['nullable', 'digits_between:10,11'],
            'contatos.*.email' => ['nullable', 'email', 'max:100'],
            'contatos.*.tipo' => ['nullable', Rule::in(array_column(TipoContatoCliente::cases(), 'value'))],

            'equipamentos' => ['nullable', 'array'],
            'equipamentos.*.descricao' => ['required_with:equipamentos.*', 'string', 'max:255'],

            // Linha incompleta (so descricao, ou so coordenadas escolhidas no
            // mapa) e ignorada silenciosamente pelo Repository em vez de
            // bloquear o salvamento do cadastro inteiro — o usuario pode
            // preencher a lista aos poucos, em qualquer ordem.
            'localizacoes' => ['nullable', 'array'],
            'localizacoes.*.descricao' => ['nullable', 'string', 'max:100'],
            'localizacoes.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'localizacoes.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages()
    {
        return [
            'cli_nome.required' => 'O nome do cliente é obrigatório.',
            'cli_vendedor_id.exists' => 'Vendedor responsável inválido.',
            'cli_cnpj.required' => 'O CNPJ é obrigatório.',
            'cli_cnpj.digits' => 'O CNPJ deve conter 14 números.',
            'cli_cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'cli_cidade.required' => 'A cidade é obrigatória.',
            'cli_uf.required' => 'A UF é obrigatória.',
            'cli_uf.size' => 'A UF deve conter 2 caracteres.',
            'cli_classificacao_id.exists' => 'Classificação inválida.',
            'cli_telefone.digits_between' => 'Informe um telefone com DDD e 10 ou 11 números.',
            'cli_email.email' => 'Informe um e-mail válido.',
            'contatos.*.nome.required_with' => 'Informe o nome do contato.',
            'contatos.*.telefone.digits_between' => 'Informe um telefone de contato com DDD e 10 ou 11 números.',
            'contatos.*.email.email' => 'Informe um e-mail de contato válido.',
            'equipamentos.*.descricao.required_with' => 'Informe a descrição do equipamento.',
        ];
    }
}
