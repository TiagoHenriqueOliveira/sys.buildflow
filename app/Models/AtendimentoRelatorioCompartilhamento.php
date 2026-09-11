<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioCompartilhamento extends Model
{
    protected $table = 'atendimentos_relatorios_compartilhamentos';
    protected $primaryKey = 'aten_rel_comp_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_comp_relatorio_id',
        'aten_rel_comp_usuario_id',
        'aten_rel_comp_canal',
        'aten_rel_comp_hash',
        'aten_rel_comp_criado_em',
    ];

    protected $casts = [
        'aten_rel_comp_criado_em' => 'datetime',
    ];

    public function relatorio()
    {
        return $this->belongsTo(AtendimentoRelatorio::class, 'aten_rel_comp_relatorio_id', 'aten_rel_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'aten_rel_comp_usuario_id', 'user_id');
    }
}