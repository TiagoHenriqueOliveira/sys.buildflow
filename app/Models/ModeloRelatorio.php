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
        'mod_rel_tp_data',
        'mod_rel_descricao',
        'mod_rel_ativo',
        'mod_rel_entrega_tecnica',
        'mod_rel_descricao_secao',
        'mod_rel_servicos_prestados',
        'mod_rel_pecas_substituidas',
        'mod_rel_informacoes_adicionais',
        'mod_rel_horarios',
        'mod_rel_cond_clima',
        'mod_rel_ocorrencia',
    ];

    protected $casts = [
        'mod_rel_tp_data'                => 'integer',
        'mod_rel_ativo'                  => 'boolean',
        'mod_rel_entrega_tecnica'        => 'boolean',
        'mod_rel_descricao_secao'        => 'boolean',
        'mod_rel_servicos_prestados'     => 'boolean',
        'mod_rel_pecas_substituidas'     => 'boolean',
        'mod_rel_informacoes_adicionais' => 'boolean',
        'mod_rel_horarios'               => 'boolean',
        'mod_rel_cond_clima'             => 'boolean',
        'mod_rel_ocorrencia'             => 'boolean',
    ];
}
