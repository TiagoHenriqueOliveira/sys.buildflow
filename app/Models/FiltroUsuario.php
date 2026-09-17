<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiltroUsuario extends Model
{
    protected $table = 'filtros_usuarios';
    protected $primaryKey = 'filt_id';
    public $timestamps = false;

    protected $fillable = [
        'filt_usuario_id',
        'filt_tela',
        'filt_valores',
    ];

    protected $casts = [
        'filt_valores' => 'array',
    ];
}