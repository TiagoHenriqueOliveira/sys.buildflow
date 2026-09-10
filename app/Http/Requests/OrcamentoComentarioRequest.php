<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrcamentoComentarioRequest extends FormRequest
{
    public function rules()
    {
        return [
            'orc_com_texto' => ['required', 'string'],
            'orc_com_alerta_usuario_id' => ['nullable', 'integer', Rule::exists('usuarios', 'user_id')],
        ];
    }

    public function messages()
    {
        return [
            'orc_com_texto.required' => 'Escreva o comentário antes de salvar.',
        ];
    }
}