<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';
    protected $primaryKey = 'cli_id';
    public $timestamps = false;

    protected $fillable = [
        'cli_nome',
        'cli_contato_principal',
        'cli_vendedor_id',
        'cli_cnpj',
        'cli_inscricao_estadual',
        'cli_cidade',
        'cli_uf',
        'cli_segmento',
        'cli_equipamento_vendido',
        'cli_caso_sucesso',
        'cli_caso_sucesso_descricao',
        'cli_classificacao_id',
        'cli_dias_alerta_recontato',
        'cli_telefone',
        'cli_email',
        'cli_ativo',
        'cli_latitude',
        'cli_longitude',
        'cli_link_mapa',
    ];

    protected $casts = [
        'cli_ativo' => 'boolean',
        'cli_latitude' => 'float',
        'cli_longitude' => 'float',
        'cli_caso_sucesso' => 'boolean',
    ];

    public function vendedor()
    {
        return $this->belongsTo(Usuario::class, 'cli_vendedor_id', 'user_id');
    }

    public function classificacao()
    {
        return $this->belongsTo(ClassificacaoCliente::class, 'cli_classificacao_id', 'cla_cli_id');
    }

    public function contatos()
    {
        return $this->hasMany(ClienteContato::class, 'cli_cont_cliente_id', 'cli_id');
    }

    public function equipamentos()
    {
        return $this->hasMany(ClienteEquipamento::class, 'cli_equip_cliente_id', 'cli_id');
    }

    public function localizacoes()
    {
        return $this->hasMany(ClienteLocalizacao::class, 'cli_loc_cliente_id', 'cli_id');
    }

    public function temGeolocalizacao(): bool
    {
        return $this->cli_latitude !== null && $this->cli_longitude !== null;
    }
}
