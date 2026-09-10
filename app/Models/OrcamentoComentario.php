<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrcamentoComentario extends Model
{
    use HasFactory;

    protected $table = 'orcamentos_comentarios';
    protected $primaryKey = 'orc_com_id';
    public $timestamps = false;

    protected $fillable = [
        'orc_com_orcamento_id',
        'orc_com_autor_id',
        'orc_com_texto',
        'orc_com_alerta_usuario_id',
        'orc_com_criado_em',
    ];

    protected $casts = [
        'orc_com_criado_em' => 'datetime',
    ];

    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class, 'orc_com_orcamento_id', 'orc_id');
    }

    public function autor()
    {
        return $this->belongsTo(Usuario::class, 'orc_com_autor_id', 'user_id');
    }

    public function usuarioAlertado()
    {
        return $this->belongsTo(Usuario::class, 'orc_com_alerta_usuario_id', 'user_id');
    }
}