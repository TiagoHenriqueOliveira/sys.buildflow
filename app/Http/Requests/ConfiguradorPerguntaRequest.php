<?php

namespace App\Http\Requests;

use App\Enums\TipoPergunta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ConfiguradorPerguntaRequest extends FormRequest
{
    public function rules()
    {
        return [
            'cfg_perg_texto' => ['required', 'string'],
            'cfg_perg_tipo' => ['required', Rule::in(array_column(TipoPergunta::cases(), 'value'))],
            'cfg_perg_permite_anexo' => ['nullable', 'boolean'],
            'cfg_perg_ativo' => ['nullable', 'boolean'],

            'opcoes' => ['nullable', 'array'],
            'opcoes.*.texto' => ['required_with:opcoes.*', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'cfg_perg_texto.required' => 'Informe o texto da pergunta.',
            'cfg_perg_tipo.required' => 'Selecione o tipo de resposta.',
            'opcoes.*.texto.required_with' => 'Informe o texto de cada opção.',
        ];
    }

    /**
     * NC02 — perguntas de Múltipla escolha/Escolha única precisam de pelo
     * menos 1 opção cadastrada (Texto livre não usa opções).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $tipo = TipoPergunta::tryFrom((int) $this->input('cfg_perg_tipo'));
            $opcoes = collect($this->input('opcoes', []))->filter(fn ($o) => filled($o['texto'] ?? null));

            if ($tipo?->temOpcoes() && $opcoes->isEmpty()) {
                $validator->errors()->add('opcoes', 'Cadastre ao menos uma opção de resposta para este tipo de pergunta.');
            }
        });
    }
}