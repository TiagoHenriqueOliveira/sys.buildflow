<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioPeca extends Model
{
    protected $table = 'atendimentos_relatorios_pecas';
    protected $primaryKey = 'aten_rel_peca_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_peca_relatorio_id',
        'aten_rel_peca_descricao',
        'aten_rel_peca_trocada',
    ];

    protected $casts = [
        'aten_rel_peca_trocada' => 'boolean',
    ];
}
