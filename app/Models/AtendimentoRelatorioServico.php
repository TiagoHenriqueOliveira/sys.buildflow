<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioServico extends Model
{
    protected $table = 'atendimentos_relatorios_servicos';
    protected $primaryKey = 'aten_rel_serv_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_serv_relatorio_id',
        'aten_rel_serv_descricao',
    ];
}
