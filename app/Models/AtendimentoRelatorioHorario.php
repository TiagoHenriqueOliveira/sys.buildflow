<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioHorario extends Model
{
    protected $table = 'atendimentos_relatorios_horarios';
    protected $primaryKey = 'aten_rel_hora_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_hora_relatorio_id',
        'aten_rel_hora_entrada',
        'aten_rel_hora_inicio_intervalo',
        'aten_rel_hora_fim_intervalo',
        'aten_rel_hora_saida',
    ];

    public function relatorio()
    {
        return $this->belongsTo(AtendimentoRelatorio::class, 'aten_rel_hora_relatorio_id', 'aten_rel_id');
    }
}
