<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Atendimento;
use App\Models\ModeloRelatorio;

class AtendimentoRelatorio extends Model
{
    protected $table = 'atendimentos_relatorios';
    protected $primaryKey = 'aten_rel_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_atendimento_id',
        'aten_rel_modelo_relatorio_id',
        'aten_rel_data',
        'aten_rel_status',
    ];

    protected $casts = [
        'aten_rel_data'   => 'date:Y-m-d',
        'aten_rel_status' => 'integer',
    ];

    public function atendimento()
    {
        return $this->belongsTo(
            Atendimento::class,
            'aten_rel_atendimento_id',
            'aten_id'
        );
    }

    public function modeloRelatorio()
    {
        return $this->belongsTo(
            ModeloRelatorio::class,
            'aten_rel_modelo_relatorio_id',
            'mod_rel_id'
        );
    }

    public function horarios()
    {
        return $this->hasOne(
            AtendimentoRelatorioHorario::class,
            'aten_rel_hora_relatorio_id',
            'aten_rel_id'
        );
    }

    public function climas()
    {
        return $this->hasMany(
            AtendimentoRelatorioCondicaoClimatica::class,
            'aten_rel_clima_relatorio_id',
            'aten_rel_id'
        );
    }

    public function ocupacoes()
    {
        return $this->belongsToMany(
            Ocupacao::class,
            'atendimentos_relatorios_ocupacoes',
            'aten_rel_ocup_relatorio_id',
            'aten_rel_ocup_ocupacao_id'
        )->withPivot([
            'aten_rel_ocup_quantidade',
            'aten_rel_ocup_id',
        ]);
    }
}
