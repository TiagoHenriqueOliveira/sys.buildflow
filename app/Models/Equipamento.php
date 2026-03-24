<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipamento extends Model
{
    use HasFactory;

    protected $table = 'equipamentos';
    protected $primaryKey = 'equip_id';
    public $timestamps = false;

    protected $fillable = [
        'equip_descricao',
        'equip_ativo',
    ];

    protected $casts = [
        'equip_ativo' => 'boolean',
    ];
}
