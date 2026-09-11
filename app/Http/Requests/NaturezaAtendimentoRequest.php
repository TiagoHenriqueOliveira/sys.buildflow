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
            // Sessao 08 - Configurador substitui modelos_relatorios (ver
            // project_fae_bioenergia na memoria): o modelo do Configurador
            // passa a ser obrigatorio (e o que de fato controla quais secoes
            // o relatorio exibe, ver ConfigModelo::secoesAtivas()); o campo
            // legado vira opcional, mantido soh para nao quebrar leitura de
            // relatorios ja criados antes desta sessao.
            'nat_aten_mod_relatorio_id' => ['nullable', 'integer', Rule::exists('modelos_relatorios', 'mod_rel_id')],
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
            'nat_aten_mod_relatorio_id.exists'   => 'O modelo de relatório selecionado é inválido.',
            'nat_aten_config_modelo_id.required' => 'Selecione um modelo do Configurador (setor Assistência).',
            'nat_aten_config_modelo_id.exists'   => 'O modelo do Configurador selecionado é inválido.',
        ];
    }
}