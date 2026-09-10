<?php

namespace App\Http\Requests;

use App\Enums\SetorModelo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrmTipoOrcamentoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'crm_tp_orc_nome' => ['required', 'string', 'max:100'],
            'crm_tp_orc_config_modelo_id' => [
                'nullable',
                'integer',
                Rule::exists('config_modelos', 'cfg_mod_id')->where('cfg_mod_setor', SetorModelo::Comercial->value),
            ],
            'crm_tp_orc_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'crm_tp_orc_nome.required' => 'Informe o nome do tipo de orçamento.',
            'crm_tp_orc_config_modelo_id.exists' => 'O modelo do Configurador selecionado é inválido.',
        ];
    }
}