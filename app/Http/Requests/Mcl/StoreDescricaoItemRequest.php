<?php

namespace App\Http\Requests\Mcl;

use Illuminate\Foundation\Http\FormRequest;

class StoreDescricaoItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'texto' => ['required', 'string'],
            'foto'  => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
        ];
    }
}
