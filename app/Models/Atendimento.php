<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\NaturezaAtendimento;
use App\Models\AtendimentoAnexo;
use App\Models\AtendimentoEquipamento;
use App\Models\Cliente;
use App\Models\Usuario;

class Atendimento extends Model
{
    use HasFactory;

    protected $table = 'atendimentos';
    protected $primaryKey = 'aten_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_natureza_id',
        'aten_cliente_id',
        'aten_usuario_id',
        'aten_status',
        'aten_nr_proposta',
        'aten_contato',
        'aten_responsavel',
        'aten_telefone',
        'aten_entrega_tecnica',
        'aten_endereco',
        'aten_dt_inicio',
        'aten_dt_fim',
        'aten_obs_tecnica',
        'aten_obs_cliente',
        'aten_obs_manutencao',
    ];

    protected $casts = [
        'aten_status'          => 'integer',
        'aten_entrega_tecnica' => 'boolean',
        'aten_dt_inicio' => 'date',
        'aten_dt_fim'    => 'date',
    ];

    public function natureza()
    {
        return $this->belongsTo(NaturezaAtendimento::class, 'aten_natureza_id', 'nat_aten_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'aten_cliente_id', 'cli_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'aten_usuario_id', 'user_id');
    }

    public function equipamentos()
    {
        return $this->hasMany(AtendimentoEquipamento::class, 'aten_equip_atendimento_id', 'aten_id');
    }

    public function anexos()
    {
        return $this->hasMany(AtendimentoAnexo::class, 'aten_anexo_atendimento_id', 'aten_id');
    }

    /**
     * Item 2.5 do plano de correções: a comparação "técnico só vê o seu,
     * admin vê tudo" estava reimplementada com pequenas variações em ~6
     * lugares (listagens do painel web, dashboard e 3 APIs). Mesma regra de
     * App\Policies\AtendimentoPolicy::acessar(), mas para FILTRAR uma query
     * em vez de autorizar um registro já carregado (por isso não dá pra
     * reusar a Policy diretamente aqui).
     */
    public static function idVisivelPara(Usuario $usuario): ?int
    {
        return $usuario->user_nivel_acesso === 0 ? null : $usuario->user_id;
    }

    public function scopeVisivelPara(Builder $query, Usuario $usuario): Builder
    {
        $id = static::idVisivelPara($usuario);

        return $id === null ? $query : $query->where('aten_usuario_id', $id);
    }
}
