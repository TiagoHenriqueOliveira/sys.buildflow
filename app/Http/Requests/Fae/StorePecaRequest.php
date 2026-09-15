<?php

namespace App\Http\Requests\Fae;

use Illuminate\Foundation\Http\FormRequest;

class StorePecaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:500'],
            // BF09 - checklist de pecas. So "trocada" existe hoje (nem no
            // Web "levada" foi implementada - decisao do usuario,
            // 15/09/2026: manter so o que ja existe).
            'trocada' => ['nullable', 'boolean'],
        ];
    }
}
