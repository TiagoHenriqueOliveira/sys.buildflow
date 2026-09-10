<?php

namespace App\Models;

use App\Enums\TipoPergunta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigPergunta extends Model
{
    use HasFactory;

    protected $table = 'config_perguntas';
    protected $primaryKey = 'cfg_perg_id';
    public $timestamps = false;

    protected $fillable = [
        'cfg_perg_texto',
        'cfg_perg_tipo',
        'cfg_perg_permite_anexo',
        'cfg_perg_ativo',
    ];

    protected $casts = [
        'cfg_perg_tipo' => TipoPergunta::class,
        'cfg_perg_permite_anexo' => 'boolean',
        'cfg_perg_ativo' => 'boolean',
    ];

    public function opcoes()
    {
        return $this->hasMany(ConfigPerguntaOpcao::class, 'cfg_perg_op_pergunta_id', 'cfg_perg_id');
    }

    public function modelos()
    {
        return $this->belongsToMany(
            ConfigModelo::class,
            'config_modelos_perguntas',
            'cfg_mod_perg_pergunta_id',
            'cfg_mod_perg_modelo_id'
        )->withPivot(['cfg_mod_perg_id', 'cfg_mod_perg_ordem']);
    }
}
