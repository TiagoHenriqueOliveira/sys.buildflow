<?php

namespace App\Http\Requests\Mcl;

use Illuminate\Foundation\Http\FormRequest;

class StoreOcorrenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ocorrencia_id' => ['required', 'exists:ocorrencias,ocor_id'],
            'observacao'    => ['nullable', 'string'],
        ];
    }
}
