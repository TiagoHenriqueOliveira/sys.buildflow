<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioAssinatura extends Model
{
    protected $table = 'atendimentos_relatorios_assinaturas';
    protected $primaryKey = 'aten_rel_ass_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_ass_relatorio_id',
        'aten_rel_ass_path',
    ];
}
