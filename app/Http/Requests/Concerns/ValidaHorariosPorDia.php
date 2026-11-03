<?php

namespace App\Http\Requests\Concerns;

use App\Models\AtendimentoRelatorio;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

/**
 * Validação compartilhada dos horários por dia do relatório (web e
 * api/fae/v1). Formato novo:
 *   dias: [{ data: "Y-m-d", entrada, inicio_intervalo, fim_intervalo, saida }]
 *
 * Compatibilidade: se vier só o formato antigo (um horário único, sem
 * "dias"), ele vira um dia na data do próprio relatório — o app mobile
 * publicado e chamadas antigas continuam funcionando sem mudança.
 */
trait ValidaHorariosPorDia
{
    /** Web exige entrada/saída; a API mantém a tolerância que já tinha. */
    abstract protected function horariosObrigatorios(): bool;

    /** Mapa chave-do-dia => nome do campo no formato antigo. */
    abstract protected function camposLegados(): array;

    protected function prepareForValidation(): void
    {
        if ($this->has('dias')) {
            return;
        }

        $legado = [];
        foreach ($this->camposLegados() as $chave => $campo) {
            if ($this->has($campo)) {
                $legado[$chave] = $this->input($campo);
            }
        }

        if ($legado === []) {
            return;
        }

        $legado['data'] = AtendimentoRelatorio::find($this->route('id'))?->aten_rel_data?->toDateString();
        $this->merge(['dias' => [$legado]]);
    }

    public function rules(): array
    {
        $hora = $this->horariosObrigatorios() ? 'required' : 'nullable';

        return [
            'dias'                    => ['required', 'array', 'min:1'],
            'dias.*.data'             => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'distinct'],
            'dias.*.entrada'          => [$hora, 'date_format:H:i'],
            'dias.*.inicio_intervalo' => ['nullable', 'date_format:H:i'],
            'dias.*.fim_intervalo'    => ['nullable', 'date_format:H:i'],
            'dias.*.saida'            => [$hora, 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ((array) $this->input('dias', []) as $i => $dia) {
                $this->validarDia($validator, (int) $i, (array) $dia);
            }
        });
    }

    private function validarDia(Validator $validator, int $i, array $dia): void
    {
        $entrada = $dia['entrada'] ?? null;
        $saida   = $dia['saida'] ?? null;
        $iniInt  = $dia['inicio_intervalo'] ?? null;
        $fimInt  = $dia['fim_intervalo'] ?? null;
        $quando  = isset($dia['data']) ? ' (' . Carbon::parse($dia['data'])->format('d/m/Y') . ')' : '';

        if ($iniInt && ! $fimInt) {
            $validator->errors()->add("dias.$i.fim_intervalo", "Informe o fim do intervalo$quando.");
            return;
        }

        if ($fimInt && ! $iniInt) {
            $validator->errors()->add("dias.$i.inicio_intervalo", "Informe o início do intervalo$quando.");
            return;
        }

        if (! $entrada || ! $saida) {
            return;
        }

        $tEntrada = Carbon::createFromFormat('H:i', $entrada);
        $tSaida   = Carbon::createFromFormat('H:i', $saida);

        if ($tEntrada->gt($tSaida)) {
            $validator->errors()->add("dias.$i.saida", "A saída deve ser maior ou igual à entrada$quando.");
            return;
        }

        if ($iniInt && $fimInt) {
            $tIniInt = Carbon::createFromFormat('H:i', $iniInt);
            $tFimInt = Carbon::createFromFormat('H:i', $fimInt);

            if ($tIniInt->gt($tFimInt)) {
                $validator->errors()->add("dias.$i.fim_intervalo", "O fim do intervalo deve ser maior ou igual ao início$quando.");
            } elseif ($tIniInt->lt($tEntrada)) {
                $validator->errors()->add("dias.$i.inicio_intervalo", "O início do intervalo não pode ser antes da entrada$quando.");
            } elseif ($tFimInt->gt($tSaida)) {
                $validator->errors()->add("dias.$i.fim_intervalo", "O fim do intervalo não pode ser após a saída$quando.");
            }
        }
    }

    public function messages(): array
    {
        return [
            'dias.required'                       => 'Informe ao menos um dia de horário.',
            'dias.min'                            => 'Informe ao menos um dia de horário.',
            'dias.*.data.required'                => 'Informe a data de cada dia.',
            'dias.*.data.date_format'             => 'Data inválida.',
            'dias.*.data.before_or_equal'         => 'A data do horário não pode ser futura.',
            'dias.*.data.distinct'                => 'Há dias repetidos nos horários.',
            'dias.*.entrada.required'             => 'Informe a entrada.',
            'dias.*.saida.required'               => 'Informe a saída.',
            'dias.*.entrada.date_format'          => 'Formato inválido para entrada (HH:MM).',
            'dias.*.saida.date_format'            => 'Formato inválido para saída (HH:MM).',
            'dias.*.inicio_intervalo.date_format' => 'Formato inválido para início do intervalo (HH:MM).',
            'dias.*.fim_intervalo.date_format'    => 'Formato inválido para fim do intervalo (HH:MM).',
        ];
    }
}
