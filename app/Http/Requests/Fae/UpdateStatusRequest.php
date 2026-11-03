<?php

namespace App\Http\Requests\Fae;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 3 = Liberado para aprovação (2026-11-03)
            'status' => ['required', 'integer', 'in:0,1,2,3'],
        ];
    }
}
