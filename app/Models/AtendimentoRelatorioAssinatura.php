<?php

namespace App\Models;

use App\Enums\AssinaturaTipo;
use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioAssinatura extends Model
{
    protected $table = 'atendimentos_relatorios_assinaturas';
    protected $primaryKey = 'aten_rel_ass_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_ass_relatorio_id',
        'aten_rel_ass_path',
        'aten_rel_ass_tipo',
        'aten_rel_ass_nome',
        'aten_rel_ass_cpf',
        'aten_rel_ass_assinado_em',
    ];

    protected $casts = [
        'aten_rel_ass_tipo'        => AssinaturaTipo::class,
        'aten_rel_ass_assinado_em' => 'datetime',
    ];
}
