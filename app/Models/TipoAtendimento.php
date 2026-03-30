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
        'tp_aten_descricao',
        'tp_aten_ativo',
    ];

    protected $casts = [
        'tp_aten_ativo' => 'boolean',
    ];

    public function naturezasAtendimentos()
    {
        return $this->hasMany(NaturezaAtendimento::class, 'nat_aten_tp_atendimento_id', 'tp_aten_id');
    }
}
