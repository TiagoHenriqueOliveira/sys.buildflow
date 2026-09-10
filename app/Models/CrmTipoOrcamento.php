<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmTipoOrcamento extends Model
{
    use HasFactory;

    protected $table = 'crm_tipos_orcamento';
    protected $primaryKey = 'crm_tp_orc_id';
    public $timestamps = false;

    protected $fillable = [
        'crm_tp_orc_nome',
        'crm_tp_orc_config_modelo_id',
        'crm_tp_orc_ativo',
    ];

    protected $casts = [
        'crm_tp_orc_ativo' => 'boolean',
    ];

    public function configModelo()
    {
        return $this->belongsTo(ConfigModelo::class, 'crm_tp_orc_config_modelo_id', 'cfg_mod_id');
    }
}