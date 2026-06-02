<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRelatorioOcorrenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ocorrencia_id' => ['required', 'integer', 'exists:ocorrencias,ocor_id'],
            'observacao'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'ocorrencia_id.required' => 'Selecione uma ocorrência válida.',
            'ocorrencia_id.exists'   => 'Ocorrência inválida.',
            'observacao.max'         => 'A observação deve ter no máximo 255 caracteres.',
        ];
    }
}
