<?php

namespace App\Models;

use App\Enums\StatusRoteiroViagem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoteiroViagem extends Model
{
    use HasFactory;
    use \App\Models\Concerns\TemVersao;

    /** Contrato fae/v1 §6 (S-14): sobe a cada gravação. */
    public const COLUNA_VERSAO = 'crm_rot_versao';

    protected $table = 'crm_roteiros_viagem';
    protected $primaryKey = 'crm_rot_id';
    public $timestamps = false;

    protected $fillable = [
        'crm_rot_vendedor_id',
        'crm_rot_periodo_inicio',
        'crm_rot_periodo_fim',
        'crm_rot_link_mapa',
        'crm_rot_status',
        'crm_rot_criado_em',
    ];

    protected $casts = [
        'crm_rot_periodo_inicio' => 'date:Y-m-d',
        'crm_rot_periodo_fim' => 'date:Y-m-d',
        'crm_rot_status' => StatusRoteiroViagem::class,
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

    /**
     * Pedido do cliente (2026-11-27): roteiro concluído não aceita mais
     * edição (nem da situação) — só Administrador/Comercial reabrem
     * (Usuario::podeReabrirRoteiro, rota roteiros-viagem.reabrir).
     */
    public function estaConcluido(): bool
    {
        return $this->crm_rot_status === StatusRoteiroViagem::Concluida;
    }

    /** Perfil Vendedor (2026-11-27): só os próprios roteiros. */
    public function scopeVisivelPara(Builder $query, Usuario $usuario): Builder
    {
        return $usuario->veSoOsProprios()
            ? $query->where('crm_rot_vendedor_id', $usuario->user_id)
            : $query;
    }

    public function visivelPara(Usuario $usuario): bool
    {
        return ! $usuario->veSoOsProprios()
            || (int) $this->crm_rot_vendedor_id === (int) $usuario->user_id;
    }
}