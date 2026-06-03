<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioVideo extends Model
{
    protected $table = 'atendimentos_relatorios_videos';
    protected $primaryKey = 'aten_rel_vid_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_vid_relatorio_id',
        'aten_rel_vid_path',
    ];
}
