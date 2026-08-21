<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRelatorioAssinaturasRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'aten_rel_status'             => ['required', 'integer', 'in:0,1,2'],
            'assinatura_responsavel'      => ['nullable', 'string'],
            'assinatura_responsavel_nome' => ['required_with:assinatura_responsavel', 'nullable', 'string', 'max:100'],
            'assinatura_responsavel_cpf'  => ['nullable', 'string', 'max:14'],
            'assinatura_cliente'          => ['nullable', 'string'],
            'assinatura_cliente_nome'     => ['required_with:assinatura_cliente', 'nullable', 'string', 'max:100'],
            'assinatura_cliente_cpf'      => ['nullable', 'string', 'max:14'],
        ];
    }

    public function messages(): array
    {
        return [
            'aten_rel_status.required'        => 'Informe o status do relatório.',
            'aten_rel_status.in'              => 'Status inválido.',
            'assinatura_responsavel_nome.required_with' => 'Informe o nome de quem assinou como Técnico.',
            'assinatura_cliente_nome.required_with'     => 'Informe o nome de quem assinou como Cliente.',
        ];
    }
}
