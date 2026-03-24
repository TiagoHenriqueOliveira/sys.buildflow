<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OcorrenciaRequest extends FormRequest
{
    public function rules()
    {
        return [
            'ocor_descricao' => ['required', 'string', 'max:50'],
            'ocor_ativo' => ['nullable', 'boolean'],
        ];
    }
}
