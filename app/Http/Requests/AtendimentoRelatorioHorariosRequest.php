<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class AtendimentoRelatorioHorariosRequest extends FormRequest
{
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

            $entrada = $this->input('aten_rel_hora_entrada');
            $saida   = $this->input('aten_rel_hora_saida');
            $iniInt  = $this->input('aten_rel_hora_inicio_intervalo');
            $fimInt  = $this->input('aten_rel_hora_fim_intervalo');

            if ($iniInt && !$fimInt) {
                $validator->errors()->add('aten_rel_hora_fim_intervalo', 'Informe o fim do intervalo.');
                return;
            }

            if ($fimInt && !$iniInt) {
                $validator->errors()->add('aten_rel_hora_inicio_intervalo', 'Informe o início do intervalo.');
                return;
            }

            if ($entrada && $saida) {
                $tEntrada = Carbon::createFromFormat('H:i', $entrada);
                $tSaida   = Carbon::createFromFormat('H:i', $saida);

                if ($tEntrada->gt($tSaida)) {
                    $validator->errors()->add('aten_rel_hora_saida', 'A saída deve ser maior ou igual à entrada.');
                    return;
                }

                if ($iniInt && $fimInt) {
                    $tIniInt = Carbon::createFromFormat('H:i', $iniInt);
                    $tFimInt = Carbon::createFromFormat('H:i', $fimInt);

                    if ($tIniInt->gt($tFimInt)) {
                        $validator->errors()->add('aten_rel_hora_fim_intervalo', 'O fim do intervalo deve ser maior ou igual ao início.');
                        return;
                    }

                    if ($tIniInt->lt($tEntrada)) {
                        $validator->errors()->add('aten_rel_hora_inicio_intervalo', 'O início do intervalo não pode ser antes da entrada.');
                        return;
                    }

                    if ($tFimInt->gt($tSaida)) {
                        $validator->errors()->add('aten_rel_hora_fim_intervalo', 'O fim do intervalo não pode ser após a saída.');
                        return;
                    }
                }
            }
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
