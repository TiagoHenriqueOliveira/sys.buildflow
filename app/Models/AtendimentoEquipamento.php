<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtendimentoEquipamento extends Model
{
    use HasFactory;

    protected $table = 'atendimentos_equipamentos';
    protected $primaryKey = 'aten_equip_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_equip_atendimento_id',
        'aten_equip_descricao',
        'aten_equip_observacoes',
    ];

    public function atendimento()
    {
        return $this->belongsTo(Atendimento::class, 'aten_equip_atendimento_id', 'aten_id');
    }
}
