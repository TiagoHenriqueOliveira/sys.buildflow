<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioComentario extends Model
{
    protected $table = 'atendimentos_relatorios_comentarios';
    protected $primaryKey = 'aten_rel_com_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_com_relatorio_id',
        'aten_rel_com_descricao',
    ];

    protected $casts = [
        'aten_rel_com_relatorio_id' => 'integer',
    ];

    public function relatorio()
    {
        return $this->belongsTo(
            AtendimentoRelatorio::class,
            'aten_rel_com_relatorio_id',
            'aten_rel_id'
        );
    }
}
