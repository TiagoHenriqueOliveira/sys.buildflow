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
            // Pedido do cliente (2026-09-14): texto so e obrigatorio quando NAO
            // e uma Sessao (o campo fica desabilitado no modal nesse caso, ver
            // configurador/perguntas/modal.blade.php).
            'cfg_perg_texto' => ['required_if:cfg_perg_e_sessao,0', 'nullable', 'string'],
            // Sessão não é uma pergunta de resposta de verdade — tipo/opções
            // não se aplicam quando cfg_perg_e_sessao=true.
            'cfg_perg_tipo' => ['required_if:cfg_perg_e_sessao,0', 'nullable', Rule::in(array_column(TipoPergunta::cases(), 'value'))],
            'cfg_perg_permite_anexo' => ['nullable', 'boolean'],
            'cfg_perg_repetivel' => ['nullable', 'boolean'],
            'cfg_perg_ativo' => ['nullable', 'boolean'],
            'cfg_perg_e_sessao' => ['nullable', 'boolean'],
            'cfg_perg_sessao_nome' => ['required_if:cfg_perg_e_sessao,1', 'nullable', 'string', 'max:100'],

            'opcoes' => ['nullable', 'array'],
            'opcoes.*.texto' => ['required_with:opcoes.*', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'cfg_perg_texto.required' => 'Informe o texto da pergunta.',
            'cfg_perg_tipo.required_if' => 'Selecione o tipo de resposta.',
            'cfg_perg_sessao_nome.required_if' => 'Informe o nome da aba desta sessão.',
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
            if ($this->boolean('cfg_perg_e_sessao')) {
                return;
            }

            $tipo = TipoPergunta::tryFrom((int) $this->input('cfg_perg_tipo'));
            $opcoes = collect($this->input('opcoes', []))->filter(fn ($o) => filled($o['texto'] ?? null));

            if ($tipo?->temOpcoes() && $opcoes->isEmpty()) {
                $validator->errors()->add('opcoes', 'Cadastre ao menos uma opção de resposta para este tipo de pergunta.');
            }
        });
    }
}