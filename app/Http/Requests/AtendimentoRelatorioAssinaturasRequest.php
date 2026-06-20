<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRelatorioAssinaturasRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'aten_rel_status'        => ['required', 'integer', 'in:0,1,2'],
            'assinatura_responsavel' => ['nullable', 'string'],
            'assinatura_cliente'     => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'aten_rel_status.required' => 'Informe o status do relatório.',
            'aten_rel_status.in'       => 'Status inválido.',
        ];
    }
}
