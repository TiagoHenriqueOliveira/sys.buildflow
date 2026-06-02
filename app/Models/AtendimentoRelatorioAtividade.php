<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioAtividade extends Model
{
    protected $table = 'atendimentos_relatorios_atividades';
    protected $primaryKey = 'aten_rel_ativ_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_ativ_relatorio_id',
        'aten_rel_ativ_descricao',
        'aten_rel_ativ_status',
    ];

    protected $casts = [
        'aten_rel_ativ_relatorio_id' => 'integer',
        'aten_rel_ativ_status'       => 'integer',
    ];

    public function relatorio()
    {
        return $this->belongsTo(
            AtendimentoRelatorio::class,
            'aten_rel_ativ_relatorio_id',
            'aten_rel_id'
        );
    }
}
