<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioDescricaoItemFoto extends Model
{
    protected $table = 'atendimentos_relatorios_descricao_itens_fotos';
    protected $primaryKey = 'aten_rel_desc_foto_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_desc_foto_item_id',
        'aten_rel_desc_foto_path',
        'aten_rel_desc_foto_criado_em',
    ];

    protected $casts = [
        'aten_rel_desc_foto_criado_em' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(AtendimentoRelatorioDescricaoItem::class, 'aten_rel_desc_foto_item_id', 'aten_rel_desc_id');
    }
}
