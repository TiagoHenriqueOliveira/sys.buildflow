<?php

namespace App\Http\Requests;

use App\Enums\StatusRoteiroViagem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RoteiroViagemRequest extends FormRequest
{
    public function rules()
    {
        return [
            'crm_rot_vendedor_id' => ['required', 'integer', Rule::exists('usuarios', 'user_id')],
            'crm_rot_periodo_inicio' => ['required', 'date'],
            'crm_rot_periodo_fim' => ['required', 'date', 'after_or_equal:crm_rot_periodo_inicio'],
            'crm_rot_link_mapa' => ['nullable', 'url', 'max:500'],
            'crm_rot_status' => ['nullable', Rule::in(array_column(StatusRoteiroViagem::cases(), 'value'))],

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

    /**
     * Perfil Vendedor (2026-11-27): o roteiro é sempre dele, e só entram
     * clientes visíveis para ele (os que já estavam no roteiro continuam).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $usuario = $this->user();
            if (! $usuario?->veSoOsProprios()) {
                return;
            }

            if ((int) $this->input('crm_rot_vendedor_id') !== (int) $usuario->user_id) {
                $validator->errors()->add('crm_rot_vendedor_id', 'Você só pode cadastrar roteiros para você mesmo.');
            }

            $id = $this->route('roteiros_viagem') ?? $this->route('roteiro');
            $jaNoRoteiro = $id
                ? \App\Models\RoteiroViagemCliente::where('crm_rot_cli_roteiro_id', $id)->pluck('crm_rot_cli_cliente_id')->map(fn ($v) => (int) $v)->all()
                : [];
            $novos = array_diff(array_map('intval', (array) $this->input('clientes', [])), $jaNoRoteiro);
            if ($novos !== [] && \App\Models\Cliente::whereIn('cli_id', $novos)->visivelPara($usuario)->count() !== count(array_unique($novos))) {
                $validator->errors()->add('clientes', 'O roteiro tem cliente de outro vendedor.');
            }
        });
    }
}