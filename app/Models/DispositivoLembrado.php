<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Navegador marcado com "Lembrar dados" no login (2026-11-27): o próximo
 * acesso pula e-mail/senha e vai direto ao código do autenticador (o MFA
 * continua obrigatório). Só o SHA-256 do token fica no banco. Ver
 * App\Services\DispositivoLembradoService.
 */
class DispositivoLembrado extends Model
{
    protected $table = 'usuarios_dispositivos_lembrados';
    protected $primaryKey = 'disp_id';
    public $timestamps = false;

    protected $fillable = [
        'disp_usuario_id',
        'disp_token_hash',
        'disp_user_agent',
        'disp_ip',
        'disp_criado_em',
        'disp_ultimo_uso_em',
        'disp_expira_em',
    ];

    protected $casts = [
        'disp_criado_em' => 'datetime',
        'disp_ultimo_uso_em' => 'datetime',
        'disp_expira_em' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'disp_usuario_id', 'user_id');
    }
}
