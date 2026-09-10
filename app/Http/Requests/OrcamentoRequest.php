<?php

namespace App\Http\Requests;

use App\Enums\NivelOrcamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrcamentoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'orc_cliente_id' => ['required', 'integer', Rule::exists('clientes', 'cli_id')],
            'orc_vendedor_id' => ['required', 'integer', Rule::exists('usuarios', 'user_id')],
            'orc_tipo_orcamento_id' => ['nullable', 'integer', Rule::exists('crm_tipos_orcamento', 'crm_tp_orc_id')],
            'orc_nivel' => ['nullable', Rule::in(array_column(NivelOrcamento::cases(), 'value'))],
            'orc_prazo_envio' => ['nullable', 'date'],
            'orc_ativo' => ['nullable', 'boolean'],

            'respostas' => ['nullable', 'array'],

            // CRM04 — indicação conjunta, sem cálculo de comissão.
            'vendedores_adicionais' => ['nullable', 'array'],
            'vendedores_adicionais.*' => ['integer', Rule::exists('usuarios', 'user_id')],
        ];
    }

    public function messages()
    {
        return [
            'orc_cliente_id.required' => 'Selecione o cliente.',
            'orc_vendedor_id.required' => 'Selecione o vendedor responsável.',
        ];
    }

    /**
     * CRM04 — o vendedor responsável não pode aparecer também como vendedor
     * adicional (indicação conjunta pressupõe pessoas diferentes).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $vendedorId = (int) $this->input('orc_vendedor_id');
            $adicionais = array_map('intval', $this->input('vendedores_adicionais', []));

            if (in_array($vendedorId, $adicionais, true)) {
                $validator->errors()->add('vendedores_adicionais', 'O vendedor responsável não pode ser selecionado também como vendedor adicional.');
            }
        });
    }
}