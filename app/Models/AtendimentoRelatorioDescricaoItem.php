<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioDescricaoItem extends Model
{
    protected $table = 'atendimentos_relatorios_descricao_itens';
    protected $primaryKey = 'aten_rel_desc_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_desc_relatorio_id',
        'aten_rel_desc_texto',
        'aten_rel_desc_foto_path',
        'aten_rel_desc_criado_em',
    ];

    // Sem este cast, o valor volta como string crua ao reler do banco (só
    // funciona sem cast na instância recém-criada em memória) — quebra o
    // optional(...)->format() usado em RelatoriosController::show().
    protected $casts = [
        'aten_rel_desc_criado_em' => 'datetime',
    ];
}
