<?php

namespace App\Http\Requests\Mcl;

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
