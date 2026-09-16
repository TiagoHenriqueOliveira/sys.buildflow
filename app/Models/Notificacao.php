<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacao extends Model
{
    use HasFactory;

    protected $table = 'notificacoes';
    protected $primaryKey = 'notif_id';
    public $timestamps = false;

    protected $fillable = [
        'notif_usuario_id',
        'notif_tipo',
        'notif_titulo',
        'notif_mensagem',
        'notif_link',
        'notif_lida',
        'notif_criado_em',
    ];

    protected $casts = [
        'notif_lida' => 'boolean',
        'notif_criado_em' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'notif_usuario_id', 'user_id');
    }

    /**
     * Cria uma notificacao pro usuario, com os campos default de sempre
     * (lida=false, criado_em=now) - fonte unica pros dois gatilhos hoje
     * (alerta de recontato de cliente, alerta de comentario de orcamento).
     */
    public static function notificar(int $usuarioId, string $tipo, string $titulo, string $mensagem, ?string $link = null): self
    {
        return self::create([
            'notif_usuario_id' => $usuarioId,
            'notif_tipo' => $tipo,
            'notif_titulo' => $titulo,
            'notif_mensagem' => $mensagem,
            'notif_link' => $link,
            'notif_lida' => false,
            'notif_criado_em' => now(),
        ]);
    }
}