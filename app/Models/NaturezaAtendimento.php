<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ModeloRelatorio;
use App\Models\TipoAtendimento;

class NaturezaAtendimento extends Model
{
    protected $table = 'naturezas_atendimentos';
    protected $primaryKey = 'nat_aten_id';
    public $timestamps = false;

    protected $fillable = [
        'nat_aten_mod_relatorio_id',
        'nat_aten_tp_atendimento_id',
        'nat_aten_descricao',
        'nat_aten_ativo',
    ];

    public function modeloRelatorio()
    {
        return $this->belongsTo(
            ModeloRelatorio::class,
            'nat_aten_mod_relatorio_id',
            'mod_rel_id'
        );
    }

    public function tipoAtendimento()
    {
        return $this->belongsTo(
            TipoAtendimento::class,
            'nat_aten_tp_atendimento_id',
            'tp_aten_id'
        );
    }
}
