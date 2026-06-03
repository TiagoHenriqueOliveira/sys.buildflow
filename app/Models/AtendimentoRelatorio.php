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

    public function equipamentos()
    {
        return $this->belongsToMany(
            Equipamento::class,
            'atendimentos_relatorios_equipamentos',
            'aten_rel_equip_relatorio_id',
            'aten_rel_equip_equipamento_id'
        )->withPivot(['aten_rel_equip_quantidade', 'aten_rel_equip_id']);
    }

    public function atividades()
    {
        return $this->hasMany(
            AtendimentoRelatorioAtividade::class,
            'aten_rel_ativ_relatorio_id',
            'aten_rel_id'
        );
    }

    public function fotos()
    {
        return $this->hasMany(
            AtendimentoRelatorioFoto::class,
            'aten_rel_foto_relatorio_id',
            'aten_rel_id'
        );
    }

    public function videos()
    {
        return $this->hasMany(
            AtendimentoRelatorioVideo::class,
            'aten_rel_vid_relatorio_id',
            'aten_rel_id'
        );
    }

    public function anexos()
    {
        return $this->hasMany(
            AtendimentoRelatorioAnexo::class,
            'aten_rel_anexo_relatorio_id',
            'aten_rel_id'
        );
    }

    public function ocorrencias()
    {
        return $this->belongsToMany(
            Ocorrencia::class,
            'atendimentos_relatorios_ocorrencias',
            'aten_rel_ocor_relatorio_id',
            'aten_rel_ocor_ocorrencia_id'
        )->withPivot([
            'aten_rel_ocor_id',
            'aten_rel_ocor_observacao',
        ]);
    }

    public function comentarios()
    {
        return $this->hasMany(
            AtendimentoRelatorioComentario::class,
            'aten_rel_com_relatorio_id',
            'aten_rel_id'
        );
    }
}
