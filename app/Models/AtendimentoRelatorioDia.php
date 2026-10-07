<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioDia extends Model
{
    protected $table = 'atendimentos_relatorios_dias';
    protected $primaryKey = 'aten_rel_dia_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_dia_relatorio_id',
        'aten_rel_dia_data',
        'aten_rel_dia_hora_entrada',
        'aten_rel_dia_hora_inicio_intervalo',
        'aten_rel_dia_hora_fim_intervalo',
        'aten_rel_dia_hora_saida',
        'aten_rel_dia_clima_manha',
        'aten_rel_dia_clima_tarde',
        'aten_rel_dia_clima_noite',
        'aten_rel_dia_criado_em',
    ];

    protected $casts = [
        'aten_rel_dia_data' => 'date',
        'aten_rel_dia_clima_manha' => 'integer',
        'aten_rel_dia_clima_tarde' => 'integer',
        'aten_rel_dia_clima_noite' => 'integer',
        'aten_rel_dia_criado_em' => 'datetime',
    ];

    public function relatorio()
    {
        return $this->belongsTo(AtendimentoRelatorio::class, 'aten_rel_dia_relatorio_id', 'aten_rel_id');
    }
}
