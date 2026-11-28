<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Código de uso único gerado pelo administrador (auditoria S-08) — ver
 * App\Services\CodigoAcessoService. Só o HMAC do código é guardado.
 */
class CodigoAcesso extends Model
{
    protected $table = 'codigos_acesso';

    protected $primaryKey = 'cod_id';

    protected $fillable = [
        'cod_usuario_id',
        'cod_tipo',
        'cod_hash',
        'cod_expira_em',
        'cod_usado_em',
        'cod_revogado_em',
        'cod_criado_por',
    ];

    protected $hidden = ['cod_hash'];

    protected $casts = [
        'cod_expira_em' => 'datetime',
        'cod_usado_em' => 'datetime',
        'cod_revogado_em' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'cod_usuario_id', 'user_id');
    }
}
