<?php

namespace App\Http\Requests\Mcl;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClimaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manha' => ['nullable', 'in:ensolarado,nublado,chuvoso'],
            'tarde' => ['nullable', 'in:ensolarado,nublado,chuvoso'],
            'noite' => ['nullable', 'in:ensolarado,nublado,chuvoso'],
        ];
    }
}
