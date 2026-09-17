<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SegmentoRequest extends FormRequest
{
    public function rules()
    {
        $id = $this->route('segmento');

        return [
            'seg_descricao' => [
                'required',
                'string',
                'max:100',
                Rule::unique('segmentos', 'seg_descricao')->ignore($id, 'seg_id'),
            ],
            'seg_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'seg_descricao.required' => 'A descrição do segmento é obrigatória.',
            'seg_descricao.max' => 'A descrição deve ter no máximo 100 caracteres.',
            'seg_descricao.unique' => 'Já existe um segmento com essa descrição.',
        ];
    }
}