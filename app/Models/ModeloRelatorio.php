<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModeloRelatorio extends Model
{
    use HasFactory;

    protected $table = 'modelos_relatorios';
    protected $primaryKey = 'mod_rel_id';
    public $timestamps = false;

    protected $fillable = [
        'mod_rel_anexo',
        'mod_rel_atividade',
        'mod_rel_checklist',
        'mod_rel_comentario',
        'mod_rel_cond_clima',
        'mod_rel_controle_material',
        'mod_rel_entrega_tecnica',
        'mod_rel_equipamento',
        'mod_rel_foto',
        'mod_rel_horarios',
        'mod_rel_ocorrencia',
        'mod_rel_ocupacao',
        'mod_rel_video',
        'mod_rel_tp_data',
        'mod_rel_descricao',
        'mod_rel_ativo',
    ];

    protected $casts = [
        'mod_rel_tp_data' => 'integer',
        'mod_rel_ativo' => 'boolean',
        'mod_rel_anexo' => 'boolean',
        'mod_rel_atividade' => 'boolean',
        'mod_rel_checklist' => 'boolean',
        'mod_rel_comentario' => 'boolean',
        'mod_rel_cond_clima' => 'boolean',
        'mod_rel_controle_material' => 'boolean',
        'mod_rel_entrega_tecnica' => 'boolean',
        'mod_rel_equipamento' => 'boolean',
        'mod_rel_foto' => 'boolean',
        'mod_rel_horarios' => 'boolean',
        'mod_rel_ocorrencia' => 'boolean',
        'mod_rel_ocupacao' => 'boolean',
        'mod_rel_video' => 'boolean',
    ];
}
