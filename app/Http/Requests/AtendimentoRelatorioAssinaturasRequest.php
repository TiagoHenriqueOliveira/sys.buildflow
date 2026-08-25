<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRelatorioAssinaturasRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'aten_rel_status'         => ['required', 'integer', 'in:0,1,2'],
            'assinatura_responsavel'  => ['nullable', 'string'],
            'assinatura_cliente'      => ['nullable', 'string'],
            // 'nullable' é necessário além de 'required_with': sem ele, quando
            // assinatura_cliente não vem preenchido (ex: admin aprovando sem
            // assinatura), o campo chega como null e as regras 'string'/'max'
            // rejeitam esse null mesmo não sendo mais obrigatório — 'nullable'
            // faz elas serem puladas nesse caso, sem enfraquecer o required_with.
            'assinatura_cliente_nome' => ['nullable', 'required_with:assinatura_cliente', 'string', 'max:100'],
            'assinatura_cliente_cpf'  => ['nullable', 'required_with:assinatura_cliente', 'string', 'max:14'],
        ];
    }

    public function messages(): array
    {
        return [
            'aten_rel_status.required'               => 'Informe o status do relatório.',
            'aten_rel_status.in'                     => 'Status inválido.',
            'assinatura_cliente_nome.required_with'  => 'Informe o nome de quem assinou como Cliente.',
            'assinatura_cliente_cpf.required_with'   => 'Informe o CPF de quem assinou como Cliente.',
        ];
    }
}
