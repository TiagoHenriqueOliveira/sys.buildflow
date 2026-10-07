<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogErro extends Model
{
    protected $table = 'logs_erros';
    protected $primaryKey = 'log_err_id';
    public $timestamps = false;

    protected $fillable = [
        'log_err_modulo',
        'log_err_nivel',
        'log_err_mensagem',
        'log_err_contexto',
        'log_err_usuario_id',
    ];

    protected $casts = [
        'log_err_contexto' => 'array',
    ];
}
