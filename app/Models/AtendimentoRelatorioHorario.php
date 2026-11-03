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
        'aten_rel_hora_data',
        'aten_rel_hora_entrada',
        'aten_rel_hora_inicio_intervalo',
        'aten_rel_hora_fim_intervalo',
        'aten_rel_hora_saida',
    ];

    protected $casts = [
        'aten_rel_hora_data' => 'date',
    ];

    /** Formato usado pela tela web e pela API (HH:MM, data Y-m-d). */
    public function paraJson(): array
    {
        $hora = fn (?string $h) => $h ? substr($h, 0, 5) : '';

        return [
            'data'             => $this->aten_rel_hora_data?->toDateString() ?? '',
            'dia_semana'       => $this->aten_rel_hora_data ? getFormatDiaSemana($this->aten_rel_hora_data) : '',
            'entrada'          => $hora($this->aten_rel_hora_entrada),
            'inicio_intervalo' => $hora($this->aten_rel_hora_inicio_intervalo),
            'fim_intervalo'    => $hora($this->aten_rel_hora_fim_intervalo),
            'saida'            => $hora($this->aten_rel_hora_saida),
        ];
    }

    public function relatorio()
    {
        return $this->belongsTo(AtendimentoRelatorio::class, 'aten_rel_hora_relatorio_id', 'aten_rel_id');
    }
}
