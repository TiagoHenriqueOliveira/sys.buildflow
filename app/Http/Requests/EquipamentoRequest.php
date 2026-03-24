<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EquipamentoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'equip_descricao' => ['required', 'string', 'max:50'],
            'equip_ativo' => ['nullable', 'boolean'],
        ];
    }
}
