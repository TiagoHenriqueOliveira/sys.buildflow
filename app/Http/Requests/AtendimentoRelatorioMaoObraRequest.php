<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRelatorioMaoObraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ocup_id' => ['required', 'integer', 'exists:ocupacoes,ocup_id'],
            'qtd'     => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'ocup_id.required' => 'Selecione uma mão de obra válida.',
            'ocup_id.exists'   => 'Mão de obra inválida.',
            'qtd.required'     => 'Informe a quantidade.',
            'qtd.min'          => 'Quantidade deve ser no mínimo 1.',
        ];
    }
}
