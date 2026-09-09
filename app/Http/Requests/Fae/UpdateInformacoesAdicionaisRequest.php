<?php

namespace App\Http\Requests\Fae;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInformacoesAdicionaisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'informacoes_adicionais' => ['nullable', 'string'],
        ];
    }
}
