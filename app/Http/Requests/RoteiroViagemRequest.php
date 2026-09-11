<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoteiroViagemRequest extends FormRequest
{
    public function rules()
    {
        return [
            'crm_rot_vendedor_id' => ['required', 'integer', Rule::exists('usuarios', 'user_id')],
            'crm_rot_periodo_inicio' => ['required', 'date'],
            'crm_rot_periodo_fim' => ['required', 'date', 'after_or_equal:crm_rot_periodo_inicio'],
            'crm_rot_link_mapa' => ['nullable', 'url', 'max:500'],
            'crm_rot_ativo' => ['nullable', 'boolean'],

            // CRM05 — lista de clientes a visitar (saida).
            'clientes' => ['required', 'array', 'min:1'],
            'clientes.*' => ['integer', Rule::exists('clientes', 'cli_id')],

            // CRM06 — retorno por cliente (opcional ate a viagem acontecer).
            'resultados' => ['nullable', 'array'],
            'resultados.*' => ['nullable', 'integer', 'in:0,1,2'],
            'observacoes' => ['nullable', 'array'],
            'observacoes.*' => ['nullable', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'crm_rot_vendedor_id.required' => 'Selecione o vendedor.',
            'crm_rot_periodo_inicio.required' => 'Informe a data de início do roteiro.',
            'crm_rot_periodo_fim.required' => 'Informe a data de término do roteiro.',
            'crm_rot_periodo_fim.after_or_equal' => 'A data de término não pode ser anterior ao início.',
            'crm_rot_link_mapa.url' => 'Informe um link válido (ex.: https://maps.app.goo.gl/...).',
            'clientes.required' => 'Adicione ao menos um cliente ao roteiro.',
            'clientes.min' => 'Adicione ao menos um cliente ao roteiro.',
        ];
    }
}