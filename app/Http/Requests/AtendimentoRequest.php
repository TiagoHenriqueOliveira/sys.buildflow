<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'aten_natureza_id' => ['required', 'integer'],
            'aten_cliente_id' => ['required', 'integer'],
            'aten_usuario_id' => ['required', 'integer'],
            'aten_status' => ['required', 'integer', 'in:0,1,2,3'],
            'aten_nr_proposta' => ['nullable', 'string', 'max:20'],
            'aten_descricao' => ['required', 'string', 'max:50'],
            'aten_responsavel' => ['nullable', 'string', 'max:50'],
            'aten_endereco' => ['nullable', 'string', 'max:100'],
            'aten_dt_inicio' => ['required', 'date'],
            'aten_dt_fim' => ['required', 'date', 'after_or_equal:aten_dt_inicio'],
        ];
    }

    public function messages()
    {
        return [
            'aten_natureza_id.required' => 'Selecione a natureza.',
            'aten_cliente_id.required' => 'Selecione o cliente.',
            'aten_usuario_id.required' => 'Selecione o usuário.',
            'aten_status.required' => 'Selecione o status.',
            'aten_descricao.required' => 'Informe a descrição da obra.',
            'aten_dt_inicio.required' => 'Informe a data de início.',
            'aten_dt_fim.required' => 'Informe a data de término.',
        ];
    }
}
