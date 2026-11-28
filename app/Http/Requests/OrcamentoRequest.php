<?php

namespace App\Http\Requests;

use App\Enums\NivelOrcamento;
use App\Enums\ResultadoOrcamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrcamentoRequest extends FormRequest
{
    /**
     * Perfil Vendedor (27/11/2026): o vendedor responsável não é escolhido —
     * na inclusão é ele mesmo; na alteração fica o que já estava gravado
     * (vale também para vendedor adicional editando o orçamento de outro).
     * Os demais perfis com acesso escolhem livremente.
     */
    use Concerns\PutParcialDaApi;

    protected function prepareForValidation(): void
    {
        $id = $this->route('orcamento');
        $atual = $id ? \App\Models\Orcamento::find($id) : null;

        // Contrato fae/v1 §6 (S-14): PUT parcial na API.
        $this->completarComAtual($atual, [
            'orc_cliente_id', 'orc_vendedor_id', 'orc_tipo_orcamento_id', 'orc_nivel',
            'orc_prazo_envio', 'orc_resultado', 'orc_ativo',
        ]);
        $this->listasAusentesComoVazias(['respostas', 'vendedores_adicionais']);

        $usuario = $this->user();
        if (! $usuario?->veSoOsProprios()) {
            return;
        }

        $this->merge(['orc_vendedor_id' => $atual?->orc_vendedor_id ?? $usuario->user_id]);
    }

    public function rules()
    {
        return [
            'orc_cliente_id' => ['required', 'integer', Rule::exists('clientes', 'cli_id')],
            'orc_vendedor_id' => ['required', 'integer', Rule::exists('usuarios', 'user_id')],
            'orc_tipo_orcamento_id' => ['nullable', 'integer', Rule::exists('crm_tipos_orcamento', 'crm_tp_orc_id')],
            'orc_nivel' => ['nullable', Rule::in(array_column(NivelOrcamento::cases(), 'value'))],
            // CRM02 - sugerido pelo nivel, mas editavel (2026-11-16); vazio,
            // o OrcamentoRepository calcula.
            'orc_prazo_envio' => ['nullable', 'date_format:Y-m-d'],
            // Pedido do cliente (2026-09-17): resultado do orcamento
            // (Convertido/Nao Convertido/Adiado/Projeto Futuro) - alimenta
            // os indicadores comerciais (CRM08) de verdade.
            'orc_resultado' => ['nullable', Rule::in(array_column(ResultadoOrcamento::cases(), 'value'))],
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

            $this->validarPerfilVendedor($validator);
        });
    }

    /**
     * Perfil Vendedor (2026-11-27): só escolhe cliente visível para ele —
     * manter o cliente já gravado continua permitido. O vendedor responsável
     * é fixado em prepareForValidation.
     */
    private function validarPerfilVendedor(Validator $validator): void
    {
        $usuario = $this->user();
        if (! $usuario?->veSoOsProprios()) {
            return;
        }

        $id = $this->route('orcamento');
        $atual = $id ? \App\Models\Orcamento::find($id) : null;

        $clienteId = (int) $this->input('orc_cliente_id');
        $cliente = \App\Models\Cliente::find($clienteId);
        if ($cliente && $clienteId !== (int) $atual?->orc_cliente_id && ! $cliente->visivelPara($usuario)) {
            $validator->errors()->add('orc_cliente_id', 'Este cliente pertence a outro vendedor.');
        }
    }
}