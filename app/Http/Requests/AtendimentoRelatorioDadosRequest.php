<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class AtendimentoRelatorioDadosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aten_rel_data' => [
                'required',
                'date',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->aten_rel_data) {
                $data = Carbon::parse($this->aten_rel_data)->startOfDay();
                $hoje = now()->startOfDay();

                if ($data->isAfter($hoje)) {
                    $validator->errors()->add(
                        'aten_rel_data',
                        'A data do relatório não pode ser futura.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'aten_rel_data.required' => 'Informe a data do relatório.',
            'aten_rel_data.date'     => 'Data do relatório inválida.',
        ];
    }
}
