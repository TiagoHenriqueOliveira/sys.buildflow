<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoAtendimento extends Model
{
    use HasFactory;

    protected $table = 'tipos_atendimentos';
    protected $primaryKey = 'tp_aten_id';
    public $timestamps = false;

    protected $fillable = [
        'tp_aten_nat_atendimento_id',
        'tp_aten_descricao',
        'tp_aten_ativo',
    ];

    protected $casts = [
        'tp_aten_ativo' => 'boolean',
    ];

    public function naturezaAtendimento()
    {
        return $this->belongsTo(NaturezaAtendimento::class, 'tp_aten_nat_atendimento_id', 'nat_aten_id');
    }
}
