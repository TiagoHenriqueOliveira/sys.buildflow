<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRelatorioEquipamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equip_id' => ['required', 'integer', 'exists:equipamentos,equip_id'],
            'qtd'      => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'equip_id.required' => 'Selecione um equipamento válido.',
            'equip_id.exists'   => 'Equipamento inválido.',
            'qtd.required'      => 'Informe a quantidade.',
            'qtd.min'           => 'Quantidade deve ser no mínimo 1.',
        ];
    }
}
