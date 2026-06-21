<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAuditoria extends Model
{
    protected $table = 'logs_auditoria';
    protected $primaryKey = 'log_aud_id';
    public $timestamps = false;

    protected $fillable = [
        'log_aud_modulo',
        'log_aud_acao',
        'log_aud_registro_id',
        'log_aud_usuario_id',
        'log_aud_dados_anteriores',
        'log_aud_dados_novos',
    ];

    protected $casts = [
        'log_aud_dados_anteriores' => 'array',
        'log_aud_dados_novos'      => 'array',
    ];
}
