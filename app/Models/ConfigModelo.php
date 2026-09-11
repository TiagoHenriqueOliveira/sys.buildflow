<?php

namespace App\Models;

use App\Enums\SetorModelo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigModelo extends Model
{
    use HasFactory;

    protected $table = 'config_modelos';
    protected $primaryKey = 'cfg_mod_id';
    public $timestamps = false;

    protected $fillable = [
        'cfg_mod_nome',
        'cfg_mod_setor',
        'cfg_mod_ativo',
    ];

    protected $casts = [
        'cfg_mod_setor' => SetorModelo::class,
        'cfg_mod_ativo' => 'boolean',
    ];

    public function perguntas()
    {
        return $this->belongsToMany(
            ConfigPergunta::class,
            'config_modelos_perguntas',
            'cfg_mod_perg_modelo_id',
            'cfg_mod_perg_pergunta_id'
        )->withPivot(['cfg_mod_perg_id', 'cfg_mod_perg_ordem'])->orderBy('cfg_mod_perg_ordem');
    }

    public function naturezasAtendimentos()
    {
        return $this->hasMany(NaturezaAtendimento::class, 'nat_aten_config_modelo_id', 'cfg_mod_id');
    }
}