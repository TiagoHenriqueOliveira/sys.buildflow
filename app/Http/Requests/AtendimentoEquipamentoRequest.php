<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoEquipamentoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'aten_equip_descricao'   => ['required', 'string', 'max:255'],
            'aten_equip_observacoes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'aten_equip_descricao.required' => 'Informe a descrição do equipamento.',
            'aten_equip_descricao.max'      => 'A descrição não pode ultrapassar 255 caracteres.',
        ];
    }
}
