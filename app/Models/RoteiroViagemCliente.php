<?php

namespace App\Models;

use App\Enums\ResultadoVisitaRoteiro;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoteiroViagemCliente extends Model
{
    use HasFactory;

    protected $table = 'crm_roteiros_viagem_clientes';
    protected $primaryKey = 'crm_rot_cli_id';
    public $timestamps = false;

    protected $fillable = [
        'crm_rot_cli_roteiro_id',
        'crm_rot_cli_cliente_id',
        'crm_rot_cli_ordem',
        'crm_rot_cli_resultado',
        'crm_rot_cli_observacao',
    ];

    protected $casts = [
        'crm_rot_cli_resultado' => ResultadoVisitaRoteiro::class,
    ];

    public function roteiro()
    {
        return $this->belongsTo(RoteiroViagem::class, 'crm_rot_cli_roteiro_id', 'crm_rot_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'crm_rot_cli_cliente_id', 'cli_id');
    }
}