<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioFoto extends Model
{
    protected $table = 'atendimentos_relatorios_fotos';
    protected $primaryKey = 'aten_rel_foto_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_foto_relatorio_id',
        'aten_rel_foto_path',
        'aten_rel_foto_legenda',
    ];
}
