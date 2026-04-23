<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioCondicaoClimatica extends Model
{
    protected $table = 'atendimentos_relatorios_condicoes_climaticas';
    protected $primaryKey = 'aten_rel_clima_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_clima_relatorio_id',
        'aten_rel_clima_periodo',
        'aten_rel_clima_condicao',
    ];

    protected $casts = [
        'aten_rel_clima_relatorio_id' => 'integer',
        'aten_rel_clima_periodo'      => 'integer',
        'aten_rel_clima_condicao'     => 'integer',
    ];

    public function relatorio()
    {
        return $this->belongsTo(
            AtendimentoRelatorio::class,
            'aten_rel_clima_relatorio_id',
            'aten_rel_id'
        );
    }
}
