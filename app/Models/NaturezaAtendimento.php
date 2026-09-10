<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModeloRelatorio;

class NaturezaAtendimento extends Model
{
    use HasFactory;

    protected $table = 'naturezas_atendimentos';
    protected $primaryKey = 'nat_aten_id';
    public $timestamps = false;

    protected $fillable = [
        'nat_aten_mod_relatorio_id',
        'nat_aten_config_modelo_id',
        'nat_aten_descricao',
        'nat_aten_ativo',
    ];

    public function modeloRelatorio()
    {
        return $this->belongsTo(
            ModeloRelatorio::class,
            'nat_aten_mod_relatorio_id',
            'mod_rel_id'
        );
    }

    /**
     * BF04 — modelo do Configurador (setor Assistência) vinculado a esta
     * natureza. Conceito diferente de modeloRelatorio() acima (flags).
     */
    public function configModelo()
    {
        return $this->belongsTo(ConfigModelo::class, 'nat_aten_config_modelo_id', 'cfg_mod_id');
    }
}
