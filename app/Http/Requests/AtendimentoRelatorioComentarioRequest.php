<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRelatorioComentarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aten_rel_com_descricao' => ['required', 'string', 'max:250'],
        ];
    }

    public function messages(): array
    {
        return [
            'aten_rel_com_descricao.required' => 'Informe o comentário.',
            'aten_rel_com_descricao.max'      => 'O comentário deve ter no máximo 250 caracteres.',
        ];
    }
}
