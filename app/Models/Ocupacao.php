<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ocupacao extends Model
{
    use HasFactory;

    protected $table = 'ocupacoes';
    protected $primaryKey = 'ocup_id';
    public $timestamps = false;

    protected $fillable = [
        'ocup_tp_ocupacao_id',
        'ocup_descricao',
        'ocup_ativo',
    ];

    protected $casts = [
        'ocup_ativo' => 'boolean',
    ];

    public function tipoOcupacao()
    {
        return $this->belongsTo(TipoOcupacao::class, 'ocup_tp_ocupacao_id', 'tp_ocup_id');
    }

    public function relatorios()
    {
        return $this->belongsToMany(
            AtendimentoRelatorio::class,
            'atendimentos_relatorios_maos_obras',
            'aten_rel_mo_ocupacao_id',
            'aten_rel_mo_relatorio_id'
        )->withPivot(['aten_rel_mo_qtd']);
    }
}
