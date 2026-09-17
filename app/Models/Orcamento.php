<?php

namespace App\Models;

use App\Enums\NivelOrcamento;
use App\Enums\ResultadoOrcamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orcamento extends Model
{
    use HasFactory;

    protected $table = 'orcamentos';
    protected $primaryKey = 'orc_id';
    public $timestamps = false;

    protected $fillable = [
        'orc_cliente_id',
        'orc_vendedor_id',
        'orc_tipo_orcamento_id',
        'orc_nivel',
        'orc_prazo_envio',
        'orc_resultado',
        'orc_ativo',
        'orc_criado_em',
    ];

    protected $casts = [
        'orc_nivel' => NivelOrcamento::class,
        'orc_prazo_envio' => 'date:Y-m-d',
        'orc_resultado' => ResultadoOrcamento::class,
        'orc_ativo' => 'boolean',
        'orc_criado_em' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'orc_cliente_id', 'cli_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(Usuario::class, 'orc_vendedor_id', 'user_id');
    }

    public function tipoOrcamento()
    {
        return $this->belongsTo(CrmTipoOrcamento::class, 'orc_tipo_orcamento_id', 'crm_tp_orc_id');
    }

    public function respostas()
    {
        return $this->hasMany(OrcamentoResposta::class, 'orc_resp_orcamento_id', 'orc_id');
    }

    public function comentarios()
    {
        return $this->hasMany(OrcamentoComentario::class, 'orc_com_orcamento_id', 'orc_id')
            ->orderByDesc('orc_com_id');
    }

    /**
     * CRM04 — vendedores adicionais (indicação conjunta), sem cálculo de
     * comissão.
     */
    public function vendedoresAdicionais()
    {
        return $this->belongsToMany(
            Usuario::class,
            'orcamentos_vendedores_adicionais',
            'orc_vend_ad_orcamento_id',
            'orc_vend_ad_usuario_id'
        );
    }
}