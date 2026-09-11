<?php

namespace App\Http\Requests;

use App\Enums\SetorModelo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfiguradorModeloRequest extends FormRequest
{
    public function rules()
    {
        return [
            'cfg_mod_nome' => ['required', 'string', 'max:100'],
            'cfg_mod_setor' => ['required', Rule::in(array_column(SetorModelo::cases(), 'value'))],
            'cfg_mod_ativo' => ['nullable', 'boolean'],

            // NC02 — bloquear criação de modelo sem nenhuma pergunta.
            'perguntas' => ['required', 'array', 'min:1'],
            'perguntas.*' => ['integer', Rule::exists('config_perguntas', 'cfg_perg_id')],
        ];
    }

    public function messages()
    {
        return [
            'cfg_mod_nome.required' => 'Informe o nome do modelo.',
            'cfg_mod_setor.required' => 'Selecione o setor do modelo.',
            'perguntas.required' => 'Selecione ao menos uma pergunta para o modelo.',
            'perguntas.min' => 'Selecione ao menos uma pergunta para o modelo.',
        ];
    }
}