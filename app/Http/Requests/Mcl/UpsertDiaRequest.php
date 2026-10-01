<?php

namespace App\Http\Requests\Mcl;

use App\Http\Requests\Concerns\ValidaOrdemHorarios;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * RF012 — PUT /relatorios/{id}/dias/{data} (contrato 3.2). Corpo completo:
 * todas as chaves presentes, null = campo vazio (o upsert grava exatamente o
 * que veio, então chave ausente não pode significar "manter o atual").
 * Também usado pela rota web equivalente (mesmas regras nos dois lados).
 */
class UpsertDiaRequest extends FormRequest
{
    use ValidaOrdemHorarios;

    private const CLIMAS = 'in:ensolarado,nublado,chuvoso';

    public function authorize(): bool
    {
        return true;
    }

    /** {data} vem da rota, não do corpo — entra na validação junto. */
    public function validationData(): array
    {
        return array_merge($this->all(), ['data' => $this->route('data')]);
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'date_format:Y-m-d'],
            'entrada' => ['present', 'nullable', 'date_format:H:i'],
            'inicio_intervalo' => ['present', 'nullable', 'date_format:H:i'],
            'fim_intervalo' => ['present', 'nullable', 'date_format:H:i'],
            'saida' => ['present', 'nullable', 'date_format:H:i'],
            'clima' => ['present', 'array'],
            'clima.manha' => ['present', 'nullable', self::CLIMAS],
            'clima.tarde' => ['present', 'nullable', self::CLIMAS],
            'clima.noite' => ['present', 'nullable', self::CLIMAS],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // DateTime nativo (não Carbon::createFromFormat) porque devolve
            // false em vez de lançar exceção quando a data não parseia — a
            // regra date_format já reporta esse erro. Carbon::today() na
            // comparação para respeitar Carbon::setTestNow() nos testes.
            $data = $this->route('data');
            $dia = is_string($data)
                ? DateTime::createFromFormat('!Y-m-d', $data, new DateTimeZone('America/Sao_Paulo'))
                : false;
            if ($dia && $dia->format('Y-m-d') === $data && $dia > Carbon::today('America/Sao_Paulo')) {
                $validator->errors()->add('data', 'Não é possível lançar horário ou clima de uma data futura.');
            }

            $clima = $this->input('clima');
            $tudoVazio = blank($this->input('entrada'))
                && blank($this->input('inicio_intervalo'))
                && blank($this->input('fim_intervalo'))
                && blank($this->input('saida'))
                && (! is_array($clima) || (blank($clima['manha'] ?? null) && blank($clima['tarde'] ?? null) && blank($clima['noite'] ?? null)));
            if ($tudoVazio) {
                $validator->errors()->add('entrada', 'Informe ao menos um horário ou uma condição climática.');

                return;
            }

            $this->validarOrdemHorarios(
                $validator,
                $this->input('entrada'),
                $this->input('inicio_intervalo'),
                $this->input('fim_intervalo'),
                $this->input('saida'),
            );
        });
    }

    public function messages(): array
    {
        return [
            'data.date_format' => 'Data inválida (use AAAA-MM-DD).',
            '*.present' => 'O campo :attribute deve ser enviado (use null para vazio).',
            'clima.*.present' => 'O campo :attribute deve ser enviado (use null para vazio).',
            '*.date_format' => 'Formato inválido para :attribute (HH:MM).',
            'clima.*.in' => 'Condição climática inválida em :attribute (use ensolarado, nublado ou chuvoso).',
        ];
    }

    public function attributes(): array
    {
        return [
            'entrada' => 'entrada',
            'inicio_intervalo' => 'início do intervalo',
            'fim_intervalo' => 'fim do intervalo',
            'saida' => 'saída',
            'clima.manha' => 'clima da manhã',
            'clima.tarde' => 'clima da tarde',
            'clima.noite' => 'clima da noite',
        ];
    }
}
