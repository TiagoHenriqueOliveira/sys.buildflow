<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioAnexo extends Model
{
    protected $table = 'atendimentos_relatorios_anexos';
    protected $primaryKey = 'aten_rel_anexo_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_anexo_relatorio_id',
        'aten_rel_anexo_path',
    ];
}
