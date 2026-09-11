<?php

namespace App\Http\Requests;

use App\Enums\SetorModelo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NaturezaAtendimentoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'nat_aten_descricao'        => ['required', 'string', 'max:50'],
            // Configurador substitui modelos_relatorios por completo (tela
            // legada removida a pedido do usuario) - o modelo do
            // Configurador que define as perguntas do relatorio.
            // nat_aten_mod_relatorio_id continua existindo no banco
            // (relatorios antigos ainda leem dele), mas nao e mais
            // preenchivel por nenhum formulario.
            'nat_aten_config_modelo_id' => [
                'required',
                'integer',
                Rule::exists('config_modelos', 'cfg_mod_id')->where('cfg_mod_setor', SetorModelo::Assistencia->value),
            ],
            'nat_aten_ativo'            => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'nat_aten_descricao.required'        => 'A descrição é obrigatória.',
            'nat_aten_descricao.max'             => 'A descrição deve ter no máximo 50 caracteres.',
            'nat_aten_config_modelo_id.required' => 'Selecione um modelo do Configurador (setor Assistência).',
            'nat_aten_config_modelo_id.exists'   => 'O modelo do Configurador selecionado é inválido.',
        ];
    }
}