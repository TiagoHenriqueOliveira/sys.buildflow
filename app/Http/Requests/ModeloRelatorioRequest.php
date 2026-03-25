<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModeloRelatorioRequest extends FormRequest
{
    public function rules()
    {
        return [
            'mod_rel_descricao' => ['required', 'string', 'max:50'],
            'mod_rel_tp_data' => ['required', 'integer', 'in:0,1'],
            'mod_rel_anexo' => ['nullable', 'boolean'],
            'mod_rel_atividade' => ['nullable', 'boolean'],
            'mod_rel_checklist' => ['nullable', 'boolean'],
            'mod_rel_comentario' => ['nullable', 'boolean'],
            'mod_rel_cond_clima' => ['nullable', 'boolean'],
            'mod_rel_controle_material' => ['nullable', 'boolean'],
            'mod_rel_entrega_tecnica' => ['nullable', 'boolean'],
            'mod_rel_equipamento' => ['nullable', 'boolean'],
            'mod_rel_foto' => ['nullable', 'boolean'],
            'mod_rel_horarios' => ['nullable', 'boolean'],
            'mod_rel_ocorrencia' => ['nullable', 'boolean'],
            'mod_rel_ocupacao' => ['nullable', 'boolean'],
            'mod_rel_video' => ['nullable', 'boolean'],
            'mod_rel_ativo' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'mod_rel_descricao.required' => 'A descrição é obrigatória.',
            'mod_rel_descricao.max' => 'A descrição deve ter no máximo 50 caracteres.',
            'mod_rel_tp_data.required' => 'Selecione o tipo de data.',
            'mod_rel_tp_data.in' => 'Tipo de data inválido.',
        ];
    }
}
