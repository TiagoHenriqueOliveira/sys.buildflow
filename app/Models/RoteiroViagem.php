<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoteiroViagem extends Model
{
    use HasFactory;

    protected $table = 'crm_roteiros_viagem';
    protected $primaryKey = 'crm_rot_id';
    public $timestamps = false;

    protected $fillable = [
        'crm_rot_vendedor_id',
        'crm_rot_periodo_inicio',
        'crm_rot_periodo_fim',
        'crm_rot_ativo',
        'crm_rot_criado_em',
    ];

    protected $casts = [
        'crm_rot_periodo_inicio' => 'date:Y-m-d',
        'crm_rot_periodo_fim' => 'date:Y-m-d',
        'crm_rot_ativo' => 'boolean',
        'crm_rot_criado_em' => 'datetime',
    ];

    public function vendedor()
    {
        return $this->belongsTo(Usuario::class, 'crm_rot_vendedor_id', 'user_id');
    }

    public function clientes()
    {
        return $this->hasMany(RoteiroViagemCliente::class, 'crm_rot_cli_roteiro_id', 'crm_rot_id')
            ->orderBy('crm_rot_cli_ordem');
    }
}