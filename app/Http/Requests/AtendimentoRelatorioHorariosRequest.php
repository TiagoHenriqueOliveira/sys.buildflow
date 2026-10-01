<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaOrdemHorarios;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AtendimentoRelatorioHorariosRequest extends FormRequest
{
    use ValidaOrdemHorarios;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aten_rel_hora_entrada' => [
                'required',
                'date_format:H:i',
            ],
            'aten_rel_hora_saida' => [
                'required',
                'date_format:H:i',
            ],
            'aten_rel_hora_inicio_intervalo' => [
                'nullable',
                'date_format:H:i',
            ],
            'aten_rel_hora_fim_intervalo' => [
                'nullable',
                'date_format:H:i',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validarOrdemHorarios(
                $validator,
                $this->input('aten_rel_hora_entrada'),
                $this->input('aten_rel_hora_inicio_intervalo'),
                $this->input('aten_rel_hora_fim_intervalo'),
                $this->input('aten_rel_hora_saida'),
                'aten_rel_hora_entrada',
                'aten_rel_hora_inicio_intervalo',
                'aten_rel_hora_fim_intervalo',
                'aten_rel_hora_saida',
            );
        });
    }

    public function messages(): array
    {
        return [
            'aten_rel_hora_entrada.required'             => 'Informe a entrada.',
            'aten_rel_hora_saida.required'               => 'Informe a saída.',
            'aten_rel_hora_entrada.date_format'          => 'Formato inválido para entrada (HH:MM).',
            'aten_rel_hora_saida.date_format'            => 'Formato inválido para saída (HH:MM).',
            'aten_rel_hora_inicio_intervalo.date_format' => 'Formato inválido para início do intervalo (HH:MM).',
            'aten_rel_hora_fim_intervalo.date_format'    => 'Formato inválido para fim do intervalo (HH:MM).',
        ];
    }
}
